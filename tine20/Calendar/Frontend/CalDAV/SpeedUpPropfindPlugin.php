<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sabre speedup plugin for propfind
 *
 * This plugin checks if all properties requested by propfind can be served with one single query.
 *
 * @package     Calendar
 * @subpackage  Frontend
 */

class Calendar_Frontend_CalDAV_SpeedUpPropfindPlugin extends Sabre\DAV\ServerPlugin
{
    /**
     * Reference to server object
     *
     * @var Sabre\DAV\Server
     */
    private $server;

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'speedUpPropfindPlugin';
    }

    /**
     * Initializes the plugin
     *
     * @param Sabre\DAV\Server $server
     * @return void
     */
    public function initialize(Sabre\DAV\Server $server)
    {
        $this->server = $server;

        $self = $this;
        $server->subscribeEvent('beforeMethod', function($method, $uri) use ($self) {
            if ('PROPFIND' === $method)
                return $self->propfind($uri);
            elseif ('REPORT' === $method)
                return $self->report($uri);
            else
                return true;
        });
    }

    /**
     * This functions handles REPORT requests specific to CalDAV
     *
     * @param string $uri
     * @return bool
     */
    public function report($uri)
    {
        if ($this->server->httpRequest->getHeader('Depth') !== '1') {
            return true;
        }

        $body = $this->server->httpRequest->getBody(true);
        rewind($this->server->httpRequest->getBody());
        $dom = Sabre\DAV\XMLUtil::loadDOMDocument($body);

        $reportName = Sabre\DAV\XMLUtil::toClarkNotation($dom->firstChild);

        if(strpos($reportName, 'calendar-query') !== false) {

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " in report speedup");

            $properties = array_keys(\Sabre\DAV\XMLUtil::parseProperties($dom->firstChild));
            if (count($properties) != 2 || !in_array('{DAV:}getetag', $properties) || !in_array('{DAV:}getcontenttype',$properties)) {

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " requested properties dont match speedup conditions, continuing");

                return true;
            }

            $filter = $dom->getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav','filter');
            if ($filter->length != 1 || $filter->item(0)->childNodes->length != 1 ||
                $filter->item(0)->childNodes->item(0)->getAttribute('name') !== 'VCALENDAR' ||
                $filter->item(0)->childNodes->item(0)->childNodes->length != 1 ||
                $filter->item(0)->childNodes->item(0)->childNodes->item(0)->getAttribute('name') !== 'VTODO' ||
                $filter->item(0)->childNodes->item(0)->childNodes->item(0)->hasChildNodes()) {

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " requested properties dont match speedup conditions, continuing");

                return true;
            }


            return $this->_speedUpRequest($uri);
        }
        return true;
    }

    /**
     * This functions handles PROPFIND requests specific to CalDAV
     * 
     * @param string $uri
     * @return bool
     */
    public function propfind($uri)
    {
        if ($this->server->httpRequest->getHeader('Depth') !== '1') {
            return true;
        }

        $body = $this->server->httpRequest->getBody(true);
        if (! $body) {
            return true;
        }
        
        rewind($this->server->httpRequest->getBody());
        $dom = Sabre\DAV\XMLUtil::loadDOMDocument($body);

        $reportName = Sabre\DAV\XMLUtil::toClarkNotation($dom->firstChild);

        if($reportName === '{DAV:}propfind') {

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " in propfind speedup");

            $properties = array_keys(\Sabre\DAV\XMLUtil::parseProperties($dom->firstChild));
            if (count($properties) != 2 || !in_array('{DAV:}getetag', $properties) || !in_array('{DAV:}getcontenttype',$properties)) {

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " requested properties dont match speedup conditions, continuing");

                return true;
            }


            return $this->_speedUpRequest($uri);
        }
        return true;
    }

    /**
     * @param string $uri
     * @return bool
     */
    protected function _speedUpRequest($uri)
    {
        /**
         * @var Calendar_Frontend_WebDAV_Container
         */
        $node = $this->server->tree->getNodeForPath($uri);
        if (!($node instanceof Calendar_Frontend_WebDAV_Container) ) {
            return true;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " speedup sql start");

        $db = Tinebase_Core::getDb();

        $stmt = $db->query('SELECT ev.id, ev.seq, ev.base_event_id FROM ' . SQL_TABLE_PREFIX . 'cal_events AS ev WHERE ev.is_deleted = 0 AND ' .
            /*ev.recurid IS NULL AND*/' (ev.container_id = ' . $db->quote($node->getId()) . ' OR ev.id IN (
            SELECT cal_event_id FROM ' . SQL_TABLE_PREFIX . 'cal_attendee WHERE displaycontainer_id = ' . $db->quote($node->getId()) . '))');

        $result = $stmt->fetchAll();

        $baseEvents = [];
        array_walk($result, function($val) use(&$baseEvents) {
            if (empty($val['base_event_id'])) {
                $baseEvents[$val['id']] = $val;
            }
        });
        array_walk($result, function($val) use(&$baseEvents) {
            if (!empty($val['base_event_id']) && !isset($baseEvents[$val['base_event_id']])) {
                $baseEvents[$val['id']] = $val;
            }
        });

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " speedup sql done");

        $dom = new \DOMDocument('1.0', 'utf-8');

        //$dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:multistatus');

        // Adding in default namespaces
        foreach ($this->server->xmlNamespaces as $namespace => $prefix) {
            $multiStatus->setAttribute('xmlns:' . $prefix, $namespace);
        }

        /*$response = $dom->createElement('d:response');
        $href = $dom->createElement('d:href', $uri);
        $response->appendChild($href);
        $multiStatus->appendChild($response);*/

        foreach ($baseEvents as $row) {
            $a = array();
            $a[200] = array(
                '{DAV:}getetag' => '"' . sha1($row['id'] . $row['seq']) . '"',
                '{DAV:}getcontenttype' => 'text/calendar',
            );
            $href = $uri . '/' . $row['id'] . '.ics';
            $response = new Sabre\DAV\Property\Response($href, $a);
            $response->serialize($this->server, $multiStatus);
        }

        $dom->appendChild($multiStatus);

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($dom->saveXML());

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " speedup successfully responded to request");

        return false;
    }
}