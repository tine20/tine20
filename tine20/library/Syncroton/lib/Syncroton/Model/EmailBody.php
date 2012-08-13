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
 * class to handle AirSyncBase:Body
 *
 * @package    Model
 * @property   int     EstimatedDataSize
 * @property   string  Data
 * @property   string  Part
 * @property   string  Preview
 * @property   bool    Truncated
 * @property   string  Type
 */

class Syncroton_Model_EmailBody extends Syncroton_Model_AEntry
{
    const TYPE_PLAINTEXT = 1;
    const TYPE_HTML      = 2;
    const TYPE_RTF       = 3;
    const TYPE_MIME      = 4;
    
    protected $_xmlBaseElement = 'Body';
    
    // @todo handle body
    protected $_properties = array(
        'AirSyncBase' => array(
            'Type'              => array('type' => 'string'),
            'EstimatedDataSize' => array('type' => 'string'),
            'Data'              => array('type' => 'string'),
            'Truncated'         => array('type' => 'number'),
            'Part'              => array('type' => 'number'),
            'Preview'           => array('type' => 'string'),
        ),
    );
    
    public function appendXML(DOMElement $_domParrent)
    {
        $this->_addXMLNamespaces($_domParrent);
        
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
                        $value = $value->format("Y-m-d\TH:i:s.000\Z");
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
        if ($properties->getName() !== $this->_xmlBaseElement) {
            throw new InvalidArgumentException('Unexpected element name: ' . $properties->getName());
        }
        
        $this->_elements = array();
        
        foreach (array_keys($this->_properties) as $namespace) {
            $functionName = '_parse' . $namespace . 'Namespace';
            $this->$functionName($properties);
        }
        
        $airSyncBaseData = $properties->children('uri:AirSyncBase');
        
        return;
    }
    
    protected function _parseAirSyncBaseNamespace(SimpleXMLElement $properties)
    {
        // fetch data from Email namespace
        $children = $properties->children('uri:AirSyncBase');
    
        foreach ($children as $elementName => $xmlElement) {
    
            switch ($elementName) {
                case 'Attachments':
                    $attachments = array();
                    
                    foreach ($xmlElement->$elementName as $attachment) {
                        $attachments[] = new Syncroton_Model_EmailAttachment($attachment);
                    }
                    
                    $this->$elementName = $attachments;
                    
                    break;
                    
                case 'Recurrence':
                    $this->$elementName = new Syncroton_Model_TaskRecurrence($xmlElement);
                    
                    break;
                    
                default:
                    list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);
                    
                    switch ($elementProperties['type']) {
                        case 'datetime':
                            $this->$elementName = new DateTime((string) $xmlElement, new DateTimeZone('UTC'));
                            
                            break;
                            
                        case 'number':
                            $this->$elementName = (int) $xmlElement;
                            
                            break;
                        default:
                            $this->$elementName = (string) $xmlElement;
                            
                            break;
                    }
            }
        }
    }
    
    protected function _parseEmailNamespace(SimpleXMLElement $properties)
    {
        // fetch data from Email namespace
        $children = $properties->children('uri:Email');
    
        foreach ($children as $elementName => $xmlElement) {
    
            switch ($elementName) {
                case 'Categories':
                    $categories = array();
                    
                    foreach ($xmlElement->$elementName as $category) {
                        $categories[] = (string) $category;
                    }
                    
                    $this->$elementName = $categories;
                    
                    break;
                    
                case 'Recurrence':
                    $this->$elementName = new Syncroton_Model_TaskRecurrence($xmlElement);
                    
                    break;
                    
                default:
                    $properties =  $this->_properties['Email'][$elementName];
                    
                    switch ($properties['type']) {
                        case 'datetime':
                            $this->$elementName = new DateTime((string) $xmlElement, new DateTimeZone('UTC'));
                            
                            break;
                            
                        case 'number':
                            $this->$elementName = (int) $xmlElement;
                            
                            break;
                        default:
                            $this->$elementName = (string) $xmlElement;
                            
                            break;
                    }
            }
        }
    }
    
    protected function _parseEmail2Namespace(SimpleXMLElement $properties)
    {
        // fetch data from Email2 namespace
        $children = $properties->children('uri:Email2');
    
        foreach ($children as $elementName => $xmlElement) {
    
            switch ($elementName) {
                default:
                    $properties =  $this->_properties['Email'][$elementName];
                    
                    switch ($properties['type']) {
                        case 'datetime':
                            $this->$elementName = new DateTime((string) $xmlElement, new DateTimeZone('UTC'));
                            
                            break;
                            
                        case 'number':
                            $this->$elementName = (int) $xmlElement;
                            
                            break;
                        default:
                            $this->$elementName = (string) $xmlElement;
                            
                            break;
                    }
            }
        }
    }    
}