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
 * class to handle ActiveSync contact
 *
 * @package     Model
 * @property    string  class
 * @property    string  collectionId
 * @property    bool    deletesAsMoves
 * @property    bool    getChanges
 * @property    string  syncKey
 * @property    int     windowSize
 */

class Syncroton_Model_Contact extends Syncroton_Model_AEntry
{
    // @todo handle body
    protected $_properties = array(
        'Contacts' => array(
            'Alias'                  => array('type' => 'string'),
            'Anniversary'            => array('type' => 'datetime'),
            'AssistantName'          => array('type' => 'string'),
            'AssistantPhoneNumber'   => array('type' => 'string'),
            'Birthday'               => array('type' => 'datetime'),
            //'Body'                  => 0x09,
            //'BodySize'              => 0x0a,
            //'BodyTruncated'         => 0x0b,
            'Business2PhoneNumber'   => array('type' => 'string'),
            'BusinessAddressCity'    => array('type' => 'string'),
            'BusinessAddressCountry' => array('type' => 'string'),
            'BusinessAddressPostalCode' => array('type' => 'string'),
            'BusinessAddressState'   => array('type' => 'string'),
            'BusinessAddressStreet'  => array('type' => 'string'),
            'BusinessFaxNumber'      => array('type' => 'string'),
            'BusinessPhoneNumber'    => array('type' => 'string'),
            'CarPhoneNumber'         => array('type' => 'string'),
            'Categories'             => array('type' => 'container'),
            //'Category'              => array('type' => 'string'),
            'Children'               => array('type' => 'container'),
            //'Child'                 => array('type' => 'string'),
            'CompanyName'            => array('type' => 'string'),
            'Department'             => array('type' => 'string'),
            'Email1Address'          => array('type' => 'string'),
            'Email2Address'          => array('type' => 'string'),
            'Email3Address'          => array('type' => 'string'),
            'FileAs'                 => array('type' => 'string'),
            'FirstName'              => array('type' => 'string'),
            'Home2PhoneNumber'       => array('type' => 'string'),
            'HomeAddressCity'        => array('type' => 'string'),
            'HomeAddressCountry'     => array('type' => 'string'),
            'HomeAddressPostalCode'  => array('type' => 'string'),
            'HomeAddressState'       => array('type' => 'string'),
            'HomeAddressStreet'      => array('type' => 'string'),
            'HomeFaxNumber'          => array('type' => 'string'),
            'HomePhoneNumber'        => array('type' => 'string'),
            'JobTitle'               => array('type' => 'string'),
            'LastName'               => array('type' => 'string'),
            'MiddleName'             => array('type' => 'string'),
            'MobilePhoneNumber'      => array('type' => 'string'),
            'OfficeLocation'         => array('type' => 'string'),
            'OtherAddressCity'       => array('type' => 'string'),
            'OtherAddressCountry'    => array('type' => 'string'),
            'OtherAddressPostalCode' => array('type' => 'string'),
            'OtherAddressState'      => array('type' => 'string'),
            'OtherAddressStreet'     => array('type' => 'string'),
            'PagerNumber'            => array('type' => 'string'),
            'Picture'                => array('type' => 'string'),
            'RadioPhoneNumber'       => array('type' => 'string'),
            'Rtf'                    => array('type' => 'string'),
            'Spouse'                 => array('type' => 'string'),
            'Suffix'                 => array('type' => 'string'),
            'Title'                  => array('type' => 'string'),
            'WebPage'                => array('type' => 'string'),
            'WeightedRank'           => array('type' => 'string'),
            'YomiCompanyName'        => array('type' => 'string'),
            'YomiFirstName'          => array('type' => 'string'),
            'YomiLastName'           => array('type' => 'string'),
        ),
        'Contacts2' => array(
            'AccountName'            => array('type' => 'string'),
            'CompanyMainPhone'       => array('type' => 'string'),
            'CustomerId'             => array('type' => 'string'),
            'GovernmentId'           => array('type' => 'string'),
            'IMAddress'              => array('type' => 'string'),
            'IMAddress2'             => array('type' => 'string'),
            'IMAddress3'             => array('type' => 'string'),
            'ManagerName'            => array('type' => 'string'),
            'MMS'                    => array('type' => 'string'),
            'NickName'               => array('type' => 'string'),
        )
    );
    
    public function appendXML(DOMElement $_domParrent)
    {
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Contacts', 'uri:Contacts');
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Contacts2', 'uri:Contacts2');
        
        
        foreach($this->_elements as $elementName => $value) {
            // skip empty values
            if($value === null || $value == '' || (is_array($value) && empty($value))) {
                continue;
            }
            
            $elementProperties = (isset($this->_properties['Contacts'][$elementName])) ? 
                $this->_properties['Contacts'][$elementName] : 
                $this->_properties['Contacts2'][$elementName];
            
            $nameSpace = isset($this->_properties['Contacts'][$elementName]) ? 'uri:Contacts' : 'uri:Contacts2';
            
            // strip off any non printable control characters
            if (!ctype_print($value)) {
                #$value = $this->removeControlChars($value);
            }
            
            switch($elementName) {
                case 'Categories':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    foreach($value as $category) {
                        $categoryElement = $_domParrent->ownerDocument->createElementNS($nameSpace, 'Category');
                        $categoryElement->appendChild($_domParrent->ownerDocument->createTextNode($category));
                        
                        $element->appendChild($categoryElement);
                    }
                    
                    $_domParrent->appendChild($element);
                    
                    break;

                case 'Children':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    foreach($value as $child) {
                        $childElement = $_domParrent->ownerDocument->createElementNS($nameSpace, 'Child');
                        $childElement->appendChild($_domParrent->ownerDocument->createTextNode($child));
                        
                        $element->appendChild($childElement);
                    }
                    
                    $_domParrent->appendChild($element);
                    
                    break;

                case 'Picture':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    if (is_resource($value)) {
                        stream_filter_append($value, 'convert.base64-encode');
                        $element->appendChild($_domParrent->ownerDocument->createTextNode(stream_get_contents($value)));
                    } else {
                        $element->appendChild($_domParrent->ownerDocument->createTextNode(base64_encode($value)));
                    }
                    
                    $_domParrent->appendChild($element);
                    
                    break;
                    
                default:
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    if ($value instanceof DateTime) {
                        $value = $value->format("Ymd\THis\Z");
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
        if ($properties->getName() !== 'ApplicationData') {
            throw new InvalidArgumentException('Unexpected element name: ' . $properties->getName());
        }
        
        $this->_elements = array();
        
        $this->_parseContactsNamespace($properties);
        $this->_parseContacts2Namespace($properties);
        
        $airSyncBaseData = $properties->children('uri:AirSyncBase');
        
        return;
    }
    
    protected function _parseContactsNamespace(SimpleXMLElement $properties)
    {
        // fetch data from Contacts namespace
        $children = $properties->children('uri:Contacts');
    
        foreach ($children as $elementName => $xmlElement) {
    
            switch ($elementName) {
                case 'Categories':
                    $categories = array();
                    
                    foreach ($xmlElement->Category as $category) {
                        $categories[] = (string) $category;
                    }
                    
                    $this->$elementName = $categories;
                    
                    break;
                    
                case 'Children':
                    $children = array();
                    
                    foreach ($xmlElement->Child as $child) {
                        $children[] = (string) $child;
                    }
                    
                    $this->$elementName = $children;
                    
                    break;
                    
                case 'Picture':
                    $this->$elementName = base64_decode((string) $xmlElement);
                    
                    break;
                    
                default:
                    $properties = isset($this->_properties['Contacts'][$elementName]) ? 
                        $this->_properties['Contacts'][$elementName] : 
                        $this->_properties['Contacts2'][$elementName];
                    
                    if ($properties['type'] == 'datetime') {
                        $this->$elementName = new DateTime((string) $xmlElement, new DateTimeZone('UTC'));
                    } else {
                        $this->$elementName = (string) $xmlElement;
                    }
            }
        }
    }
    
    protected function _parseContacts2Namespace(SimpleXMLElement $properties)
    {
        // fetch data from Contacts2 namespace
        $children = $properties->children('uri:Contacts2');
    
        foreach ($children as $elementName => $xmlElement) {
    
            switch ($elementName) {
                default:
                    $this->$elementName = (string) $xmlElement;
            }
        }
    }
    
    public function &__get($name)
    {
        if (!array_key_exists($name, $this->_properties['Contacts']) && !array_key_exists($name, $this->_properties['Contacts2'])) {
            throw new InvalidArgumentException("$name is no valid property of this object");
        }
        
        return $this->_elements[$name];
    }
    
    public function __set($name, $value)
    {
        if (!array_key_exists($name, $this->_properties['Contacts']) && !array_key_exists($name, $this->_properties['Contacts2'])) {
            throw new InvalidArgumentException("$name is no valid property of this object");
        }
        
        $properties = isset($this->_properties['Contacts'][$name]) ? $this->_properties['Contacts'][$name] : $this->_properties['Contacts2'][$name];
        
        if ($properties['type'] == 'datetime' && !$value instanceof DateTime) {
            throw new InvalidArgumentException("value for $name must be an instance of DateTime");
        }
        
        $this->_elements[$name] = $value;
    }
}