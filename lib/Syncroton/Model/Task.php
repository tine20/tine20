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

class Syncroton_Model_Task extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'ApplicationData';
    
    protected $_properties = array(
        'AirSyncBase' => array(
            'Body'                   => array('type' => 'container')
        ),
        'Tasks' => array(
            'Categories'              => array('type' => 'container', 'childName' => 'Category'),
            'Complete'                => array('type' => 'number'),
            'DateCompleted'           => array('type' => 'datetime'),
            'DueDate'                 => array('type' => 'datetime'),
            'Importance'              => array('type' => 'number'),
            'Recurrence'              => array('type' => 'container'),
            'ReminderSet'             => array('type' => 'number'),
            'ReminderTime'            => array('type' => 'datetime'),
            'Sensitivity'             => array('type' => 'number'),
            'StartDate'               => array('type' => 'datetime'),
            'Subject'                 => array('type' => 'string'),
            'UtcDueDate'              => array('type' => 'datetime'),
            'UtcStartDate'            => array('type' => 'datetime'),
        )
    );
    
    protected function _parseTasksNamespace(SimpleXMLElement $properties)
    {
        // fetch data from Contacts namespace
        $children = $properties->children('uri:Tasks');
    
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