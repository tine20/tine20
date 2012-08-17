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
 * @property    string  class
 * @property    string  collectionId
 * @property    bool    deletesAsMoves
 * @property    bool    getChanges
 * @property    string  syncKey
 * @property    int     windowSize
 */
class Syncroton_Model_EmailAttachment extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'Attachment';
    
    protected $_properties = array(
        'AirSyncBase' => array(
            'ContentId'               => array('type' => 'string'),
            'ContentLocation'         => array('type' => 'string'),
            'DisplayName'             => array('type' => 'string'),
            'EstimatedDataSize'       => array('type' => 'string'),
            'FileReference'           => array('type' => 'string'),
            'IsInline'                => array('type' => 'number'),
            'Method'                  => array('type' => 'string'),
        ),
        'Email2' => array(
            'UmAttDuration'         => array('type' => 'number'),
            'UmAttOrder'            => array('type' => 'number'),
        ),
    );
    
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
    
    public function &__get($name)
    {
        if (!array_key_exists($name, $this->_properties['AirSyncBase']) && !array_key_exists($name, $this->_properties['Email2'])) {
            throw new InvalidArgumentException("$name is no valid property of this object");
        }
        
        return $this->_elements[$name];
    }
    
    public function __set($name, $value)
    {
        if (!array_key_exists($name, $this->_properties['AirSyncBase']) && !array_key_exists($name, $this->_properties['Email2'])) {
            throw new InvalidArgumentException("$name is no valid property of this object");
        }
        
        $properties = isset($this->_properties['AirSyncBase'][$name]) ? $this->_properties['AirSyncBase'][$name] : $this->_properties['Email2'][$name];
        
        if ($properties['type'] == 'datetime' && !$value instanceof DateTime) {
            throw new InvalidArgumentException("value for $name must be an instance of DateTime");
        }
        
        $this->_elements[$name] = $value;
    }
}