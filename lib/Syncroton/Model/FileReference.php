<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync event
 *
 * @package     Model
 * @property    string  ContentType
 * @property    string  Data
 */

class Syncroton_Model_FileReference extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'ApplicationData';
    
    protected $_properties = array(
        'AirSyncBase' => array(
            'ContentType' => array('type' => 'string'),
        ),
        'ItemOperations' => array(
            'Data'        => array('type' => 'string', 'encoding' => 'base64'),
        )
    );
        
    /**
     * append email data to xml element
     *
     * @param DOMElement  $_domParrent   the parrent xml node
     * @param string      $_folderId  the local folder id
     */
    public function appendXML(DOMElement $_domParrent)
    {
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:AirSyncBase', 'uri:AirSyncBase');
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ItemOperations', 'uri:ItemOperations');
        
        foreach($this->_elements as $elementName => $value) {
            // skip empty values
            if($value === null || $value === '' || (is_array($value) && empty($value))) {
                continue;
            }
            
            list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);
            
            $nameSpace = 'uri:' . $nameSpace;
            
            // strip off any non printable control characters
            if (!ctype_print($value)) {
                #$value = $this->removeControlChars($value);
            }
            
            switch($elementName) {
                default:
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    if ($value instanceof DateTime) {
                        $value = $value->format("Ymd\THis\Z");
                    }
                    
                    if (isset($elementProperties['encoding']) && $elementProperties['encoding'] == 'base64') {
                        if (is_resource($value)) {
                            stream_filter_append($value, 'convert.base64-encode');
                            $value = stream_get_contents($value);
                        } else {
                            $value = base64_encode($value);
                        }
                    }
                        
                    $element->appendChild($_domParrent->ownerDocument->createTextNode($value));
                    
                    $_domParrent->appendChild($element);
            }
        }
        
    }
    
    /**
     * 
     * @param SimpleXMLElement $xmlCollection
     * @throws InvalidArgumentException
     */
    public function setFromSimpleXMLElement(SimpleXMLElement $properties)
    {
        //do nothing
                
        return;
    }
}