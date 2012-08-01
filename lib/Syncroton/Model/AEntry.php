<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * abstract class to handle ActiveSync entry
 *
 * @package     Syncroton
 * @subpackage  Model
 */

abstract class Syncroton_Model_AEntry implements Syncroton_Model_IEntry, IteratorAggregate, Countable
{
    protected $_elements = array();
    
    protected $_properties = array();
    
    public function __construct($properties = null)
    {
        if ($properties instanceof SimpleXMLElement) {
            $this->setFromSimpleXMLElement($properties);
        } elseif (is_array($properties)) {
            $this->setFromArray($properties);
        }
    }
    
    #abstract public function appendXML(DOMElement $_domParrent);
        
    public function count()
    {
        return count($this->_elements);
    }
    
    public function getIterator() 
    {
        return new ArrayIterator($this->_elements);
    }
    
    public function setFromArray(array $properties)
    {
        $this->_elements = array();
        
        foreach($properties as $key => $value) {
            try {
                $this->$key = $value; //echo __LINE__ . PHP_EOL;
            } catch (InvalidArgumentException $iae) {
                //ignore invalid properties
                //echo __LINE__ . PHP_EOL; echo $iae->getMessage(); echo $iae->getTraceAsString();
            }
        }
    }
    
    /**
     * set properties from SimpleXMLElement object
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
    
        return;
    }
    
    /**
     * add needed xml namespaces to DomDocument
     * 
     * @param unknown_type $_domParrent
     */
    protected function _addXMLNamespaces(DOMElement $_domParrent)
    {
        foreach($this->_properties as $namespace => $namespaceProperties) {
            $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:'.$namespace, 'uri:'.$namespace);
        }
    }
    
    /**
     * 
     * @param unknown_type $element
     * @throws InvalidArgumentException
     * @return multitype:unknown
     */
    protected function _getElementProperties($element)
    {
        foreach($this->_properties as $namespace => $namespaceProperties) {
            if (array_key_exists($element, $namespaceProperties)) {
                return array($namespace, $namespaceProperties[$element]);
            }
        }
    
        throw new InvalidArgumentException("$element is no valid property of this object");
    }
    
    protected function _parseAirSyncBaseNamespace(SimpleXMLElement $properties)
    {
        // fetch data from Contacts2 namespace
        $children = $properties->children('uri:AirSyncBase');
    
        foreach ($children as $elementName => $xmlElement) {
    
            switch ($elementName) {
                case 'Body':
                    $this->$elementName = new Syncroton_Model_EmailBody($xmlElement);
    
                    break;
    
                default:
                    $this->$elementName = (string) $xmlElement;
            }
        }
    }
    
    public function &__get($name)
    {
        $this->_getElementProperties($name);
    
        return $this->_elements[$name];
    }
    
    public function __set($name, $value)
    {
        list ($namespace, $properties) = $this->_getElementProperties($name);
    
        if ($properties['type'] == 'datetime' && !$value instanceof DateTime) {
            throw new InvalidArgumentException("value for $name must be an instance of DateTime");
        }
    
        $this->_elements[$name] = $value;
    }
    
    public function __isset($name)
    {
        return isset($this->_elements[$name]);
    }
    
    public function __unset($name)
    {
        unset($this->_elements[$name]);
    }
}