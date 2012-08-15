<?php

/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 */

class Syncroton_Model_Folder extends Syncroton_Model_AEntry implements Syncroton_Model_IFolder
{
    protected $_xmlBaseElement = array('FolderUpdate', 'FolderCreate');
    
    protected $_properties = array(
        'FolderHierarchy' => array(
            'parentId'     => array('type' => 'string'),
            'serverId'     => array('type' => 'string'),
            'displayName'  => array('type' => 'string'),
            'type'         => array('type' => 'number')
        ),
        'Internal' => array(
            'id'             => array('type' => 'string'),
            'deviceId'       => array('type' => 'string'),
            'class'          => array('type' => 'string'),
            'creationTime'   => array('type' => 'datetime'),
            'lastfiltertype' => array('type' => 'number')
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
            
            if ($nameSpace == 'Internal') {
                continue;
            }
            
            $nameSpace = 'uri:' . $nameSpace;
    
            // strip off any non printable control characters
            if (!ctype_print($value)) {
                #$value = $this->removeControlChars($value);
            }
    
            switch($elementName) {
                default:
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, ucfirst($elementName));
    
                    if ($value instanceof DateTime) {
                        $value = $value->format("Y-m-d\TH:i:s.000\Z");
                    }
                    $element->appendChild($_domParrent->ownerDocument->createTextNode($value));
    
                    $_domParrent->appendChild($element);
            }
        }
    
    }
    
    protected function _parseFolderHierarchyNamespace(SimpleXMLElement $properties)
    {
        // fetch data from Contacts namespace
        $children = $properties->children('uri:FolderHierarchy');
    
        foreach ($children as $elementName => $xmlElement) {
            $elementName = lcfirst($elementName);
            
            if (!isset($this->_properties['FolderHierarchy'][$elementName])) {
                continue;
            }
            
            switch ($elementName) {
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
}

