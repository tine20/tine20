<?php


class Calendar_Frontend_CalDAV_PropertyResponse implements Sabre\DAV\Property\IHref
{
    /**
     * Url for the response
     *
     * @var string
     */
    private $href;

    /**
     * Propertylist, ordered by HTTP status code
     *
     * @var array
     */
    private $responseProperties;

    /**
     * The responseProperties argument is a list of properties
     * within an array with keys representing HTTP status codes
     *
     * @param string $href
     * @param array $responseProperties
     */
    public function __construct($href, array $responseProperties) {

        $this->href = $href;
        $this->responseProperties = $responseProperties;

    }

    /**
     * Returns the url
     *
     * @return string
     */
    public function getHref() {

        return $this->href;

    }

    /**
     * Returns the property list
     *
     * @return array
     */
    public function getResponseProperties() {

        return $this->responseProperties;

    }

    /**
     * serialize
     *
     * @param Sabre\DAV\Server $server
     * @param \DOMElement $dom
     * @return void
     */
    public function serialize(Sabre\DAV\Server $server, DOMElement $dom) {

        $document = $dom->ownerDocument;
        $properties = $this->responseProperties;

        $xresponse = $document->createElement('d:response');
        $dom->appendChild($xresponse);

        $uri = Sabre\DAV\URLUtil::encodePath($this->href);

        // Adding the baseurl to the beginning of the url
        $uri = $server->getBaseUri() . $uri;

        $xresponse->appendChild($document->createElement('d:href',$uri));

        if ( !isset($properties[200]) && isset($properties[$this->href]) ) {
            $xresponse->appendChild($document->createElement('d:status',$server->httpResponse->getStatusMessage($properties[$this->href])));
            return;
        }

        // The properties variable is an array containing properties, grouped by
        // HTTP status
        foreach($properties as $httpStatus=>$propertyGroup) {

            // The 'href' is also in this array, and it's special cased.
            // We will ignore it
            if ($httpStatus=='href') continue;

            if (!is_array($propertyGroup)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .' $propertyGroup is no array: ' . print_r($propertyGroup, true));
                continue;
            }

            // If there are no properties in this group, we can also just carry on
            if (!count($propertyGroup)) continue;

            $xpropstat = $document->createElement('d:propstat');
            $xresponse->appendChild($xpropstat);

            $xprop = $document->createElement('d:prop');
            $xpropstat->appendChild($xprop);

            $nsList = $server->xmlNamespaces;

            foreach($propertyGroup as $propertyName=>$propertyValue) {

                $propName = null;
                preg_match('/^{([^}]*)}(.*)$/',$propertyName,$propName);

                // special case for empty namespaces
                if ($propName[1]=='') {

                    $currentProperty = $document->createElement($propName[2]);
                    $xprop->appendChild($currentProperty);
                    $currentProperty->setAttribute('xmlns','');

                } else {

                    if (!isset($nsList[$propName[1]])) {
                        $nsList[$propName[1]] = 'x' . count($nsList);
                    }

                    // If the namespace was defined in the top-level xml namespaces, it means
                    // there was already a namespace declaration, and we don't have to worry about it.
                    if (isset($server->xmlNamespaces[$propName[1]])) {
                        $currentProperty = $document->createElement($nsList[$propName[1]] . ':' . $propName[2]);
                    } else {
                        $currentProperty = $document->createElementNS($propName[1],$nsList[$propName[1]].':' . $propName[2]);
                    }
                    $xprop->appendChild($currentProperty);

                }

                if (is_scalar($propertyValue)) {
                    $text = $document->createTextNode($propertyValue);
                    $currentProperty->appendChild($text);
                } elseif ($propertyValue instanceof Sabre\DAV\PropertyInterface) {
                    $propertyValue->serialize($server,$currentProperty);
                } elseif (!is_null($propertyValue)) {
                    throw new Sabre\DAV\Exception('Unknown property value type: ' . gettype($propertyValue) . ' for property: ' . $propertyName);
                }

            }

            $xpropstat->appendChild($document->createElement('d:status',$server->httpResponse->getStatusMessage($httpStatus)));

        }

    }
}
