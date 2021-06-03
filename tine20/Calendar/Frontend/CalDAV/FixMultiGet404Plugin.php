<?php
/**
 * Tine 2.0
 *
 * @package    Sabre
 * @subpackage CalDAV
 * @copyright  Copyright (c) 2015-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Paul Mehrer <p.mehrer@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Calendar_Frontend_CalDAV_FixMultiGet404Plugin extends Sabre\CalDAV\Plugin
{
    protected $_fakeEvent = null;
    protected $_calBackend = null;

    /**
     * This function handles the calendar-multiget REPORT.
     *
     * This report is used by the client to fetch the content of a series
     * of urls. Effectively avoiding a lot of redundant requests.
     *
     * @param DOMNode $dom
     * @return void
     */
    public function calendarMultiGetReport($dom)
    {
        $properties = array_keys(Sabre\DAV\XMLUtil::parseProperties($dom->firstChild));
        $hrefElems = $dom->getElementsByTagNameNS('urn:DAV','href');

        $xpath = new \DOMXPath($dom);
        $xpath->registerNameSpace('cal',self::NS_CALDAV);
        $xpath->registerNameSpace('dav','urn:DAV');

        $expand = $xpath->query('/cal:calendar-multiget/dav:prop/cal:calendar-data/cal:expand');
        if ($expand->length>0) {
            $expandElem = $expand->item(0);
            $start = $expandElem->getAttribute('start');
            $end = $expandElem->getAttribute('end');
            if(!$start || !$end) {
                throw new Sabre\DAV\Exception\BadRequest('The "start" and "end" attributes are required for the CALDAV:expand element');
            }
            $start = Sabre\VObject\DateTimeParser::parseDateTime($start);
            $end = Sabre\VObject\DateTimeParser::parseDateTime($end);

            if ($end <= $start) {
                throw new Sabre\DAV\Exception\BadRequest('The end-date must be larger than the start-date in the expand element.');
            }
            $expand = true;
        } else {
            $expand = false;
        }

        $propertyList = [];
        foreach ($hrefElems as $elem) {
            $uri = $this->server->calculateUri($elem->nodeValue);
            try {
                list($objProps) = $this->server->getPropertiesForPath($uri, $properties);

                if ($expand && isset($objProps[200]['{' . self::NS_CALDAV . '}calendar-data'])) {
                    $vObject = Sabre\VObject\Reader::read($objProps[200]['{' . self::NS_CALDAV . '}calendar-data']);
                    $vObject->expand($start, $end);
                    $objProps[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
                }
            } catch (Sabre\DAV\Exception\NotFound $e) {

                try {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' returning fake properties for:' . $uri);

                    // return fake events properties
                    $node = $this->_getFakeEventFacade($uri);
                    $objProps = $this->_getFakeProperties($uri, $node, $properties);

                } catch (Tinebase_Exception_NotFound $tenf) {
                    $objProps = array($uri => 404);
                }
            }

            $propertyList[]=$objProps;
        }

        $prefer = $this->server->getHTTPPRefer();

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary','Brief,Prefer');
        $this->server->httpResponse->sendBody($this->generateMultiStatus($propertyList, $prefer['return-minimal']));
    }

    /**
     * @param string $path
     * @param Calendar_Frontend_WebDAV_Event $node
     * @param array $properties
     * @return array
     */
    protected function _getFakeProperties($path, $node, $properties)
    {
        $newProperties = array();
        $newProperties['href'] = trim($path,'/');

        if (count($properties) === 0) {
            // Default list of propertyNames, when all properties were requested.
            $properties = array(
                '{DAV:}getlastmodified',
                '{DAV:}getcontentlength',
                '{DAV:}resourcetype',
                '{DAV:}quota-used-bytes',
                '{DAV:}quota-available-bytes',
                '{DAV:}getetag',
                '{DAV:}getcontenttype',
            );
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' requested fake properties:' . print_r($properties, true));

        foreach ($properties as $prop) {
            switch($prop) {
                case '{DAV:}getetag'               : if ($node instanceof Sabre\DAV\IFile && $etag = $node->getETag())  $newProperties[200][$prop] = $etag; break;
                case '{DAV:}getcontenttype'        : if ($node instanceof Sabre\DAV\IFile && $ct = $node->getContentType())  $newProperties[200][$prop] = $ct; break;
                /** @noinspection PhpMissingBreakStatementInspection */
                case '{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-data':
                                                     if ($node instanceof Sabre\CalDAV\ICalendarObject) {
                                                         $val = $node->get();
                                                         if (is_resource($val))
                                                             $val = stream_get_contents($val);
                                                         $newProperties[200][$prop] = str_replace("\r","", $val);
                                                         break;
                                                     }
                                                     // don't break here!
                /** DO NOT ADD A CASE HERE, WE FALL THROUGH IN THE ABOVE CASE! */
                default:
                    $newProperties[404][$prop] = null;
                    break;
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' returning fake properties:' . print_r($newProperties, true));

        return $newProperties;
    }

    /**
     * @param string $path
     * @return Calendar_Frontend_WebDAV_Event
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getFakeEventFacade($path)
    {
        $path = rtrim($path,'/');
        $parentPath = explode('/', $path);

        $id = array_pop($parentPath);
        if (($icsPos = stripos($id, '.ics')) !== false) {
            $id = substr($id, 0, $icsPos);
        }

        $parentPath = join('/', $parentPath);
        $parentNode = $this->server->tree->getNodeForPath($parentPath);

        if (null === $this->_fakeEvent) {
            $this->_fakeEvent = new Calendar_Model_Event(
                array(
                    'originator_tz'     => 'UTC',
                    'creation_time'     => '1976-06-06 06:06:06',
                    'dtstart'           => '1977-07-07 07:07:07',
                    'dtend'             => '1977-07-07 07:14:07',
                    'summary'           => '-',
                ), true);

            $this->_calBackend = new Calendar_Backend_Sql(Tinebase_Core::getDb());
        }

        list($id, $seq) = $this->_calBackend->getIdSeq($id, $parentNode->getId());
        $this->_fakeEvent->setId($id);
        $this->_fakeEvent->seq = $seq;

        return new Calendar_Frontend_WebDAV_Event($parentNode->getContainer(), $this->_fakeEvent);
    }

    /**
     * Generates a WebDAV propfind response body based on a list of nodes.
     *
     * If 'strip404s' is set to true, all 404 responses will be removed.
     *
     * @param array $fileProperties The list with nodes
     * @param bool strip404s
     * @return string
     */
    public function generateMultiStatus(array $fileProperties, $strip404s = false) {

        $dom = new DOMDocument('1.0','utf-8');
        //$dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:multistatus');
        $dom->appendChild($multiStatus);

        // Adding in default namespaces
        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $multiStatus->setAttribute('xmlns:' . $prefix,$namespace);

        }

        foreach($fileProperties as $entry) {

            if (isset($entry[200])) {
                $href = $entry['href'];
                unset($entry['href']);

                if ($strip404s && isset($entry[404])) {
                    unset($entry[404]);
                }
            } else {
                if ($strip404s) {
                    continue;
                }
                list($href) = array_keys($entry);
            }

            $response = new Calendar_Frontend_CalDAV_PropertyResponse($href,$entry);
            $response->serialize($this->server,$multiStatus);

        }

        return $dom->saveXML();

    }

}