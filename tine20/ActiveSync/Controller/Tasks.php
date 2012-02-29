<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Controller
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * controller tasks class
 *
 * @package     ActiveSync
 * @subpackage  Controller
 */
class ActiveSync_Controller_Tasks extends ActiveSync_Controller_Abstract 
{
    /**
     * available filters
     * 
     * @var array
     */
    protected $_filterArray = array(
        Syncope_Command_Sync::FILTER_INCOMPLETE
    );
    
    protected $_mapping = array(
        #'Body'              => 'body',
        #'BodySize'          => 'bodysize',
        #'BodyTruncated'     => 'bodytruncated',
        #'Categories'        => 'categories',
        #'Category'          => 'category',
        'Complete'          => 'completed',
        #'DateCompleted'     => 'datecompleted',
        #'DueDate'           => 'duedate',
        'UtcDueDate'        => 'due',
        'Importance'        => 'priority',
        #'Recurrence'        => 'recurrence',
        #'Type'              => 'type',
        #'Start'             => 'start',
        #'Until'             => 'until',
        #'Occurrences'       => 'occurrences',
        #'Interval'          => 'interval',
        #'DayOfWeek'         => 'dayofweek',
        #'DayOfMonth'        => 'dayofmonth',
        #'WeekOfMonth'       => 'weekofmonth',
        #'MonthOfYear'       => 'monthofyear',
        #'Regenerate'        => 'regenerate',
        #'DeadOccur'         => 'deadoccur',
        #'ReminderSet'       => 'reminderset',
        #'ReminderTime'      => 'remindertime',
        #'Sensitivity'       => 'sensitivity',
        #'StartDate'         => 'startdate',
        #'UtcStartDate'      => 'utcstartdate',
        'Subject'           => 'summary',
        #'Rtf'               => 'rtf'
    );
    
    /**
     * name of Tine 2.0 backend application
     * 
     * @var string
     */
    protected $_applicationName     = 'Tasks';
    
    /**
     * name of Tine 2.0 model to use
     * 
     * @var string
     */
    protected $_modelName           = 'Task';
    
    /**
     * type of the default folder
     *
     * @var int
     */
    protected $_defaultFolderType   = Syncope_Command_FolderSync::FOLDERTYPE_TASK;
    
    /**
     * default container for new entries
     * 
     * @var string
     */
    protected $_defaultFolder       = ActiveSync_Preference::DEFAULTTASKLIST;
    
    /**
     * type of user created folders
     *
     * @var int
     */
    protected $_folderType          = Syncope_Command_FolderSync::FOLDERTYPE_TASK_USER_CREATED;
    
    /**
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty = 'tasksfilter_id';        
    
    /**
     * append task data to xml element
     *
     * @param DOMElement  $_domParrent   the parrent xml node
     * @param string      $_folderId  the local folder id
     * @param string      $_serverId  the local entry id
     * @param boolean     $_withBody  retrieve body of entry
     */
    public function appendXML(DOMElement $_domParrent, $_collectionData, $_serverId)
    {
        $data = $_serverId instanceof Tinebase_Record_Abstract ? $_serverId : $this->_contentController->get($_serverId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " task data " . print_r($data->toArray(), true));
        
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Tasks', 'uri:Tasks');
        
        foreach ($this->_mapping as $key => $value) {
            if(!empty($data->$value) || $data->$value == '0') {
                $nodeContent = null;
                
                switch($value) {
                    case 'completed':
                        continue 2;
                        break;
                        
                    case 'due':
                        if($data->$value instanceof DateTime) {
                            $_domParrent->appendChild(new DOMElement($key, $data->$value->toString('Y-m-d\TH:i:s') . '.000Z', 'uri:Tasks'));
                            $data->$value->setTimezone(Tinebase_Core::get('userTimeZone'));
                            $_domParrent->appendChild(new DOMElement('DueDate', $data->$value->toString('Y-m-d\TH:i:s') . '.000Z', 'uri:Tasks'));
                        }
                        break;
                        
                    case 'priority':
                        $nodeContent = ($data->$value <= 2) ? $data->$value : 2;
                        break;
                        
                    default:
                        $nodeContent = $data->$value;
                        
                        break;
                }
                
                // skip empty elements
                if($nodeContent === null || $nodeContent == '') {
                    //Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Value for $key is empty. Skip element.");
                    continue;
                }
                
                // strip off any non printable control characters
                if (!ctype_print($nodeContent)) {
                    $nodeContent = $this->removeControlChars($nodeContent);
                }
                
                $node = $_domParrent->ownerDocument->createElementNS('uri:Tasks', $key);
                $node->appendChild($_domParrent->ownerDocument->createTextNode($nodeContent));
                
                $_domParrent->appendChild($node);
            }
        }

        // body aka description
        if (!empty($data->description) && version_compare($this->_device->acsversion, '12.0', '>=')) {
            $body = $_domParrent->appendChild(new DOMElement('Body', null, 'uri:AirSyncBase'));
            
            $body->appendChild(new DOMElement('Type', 1, 'uri:AirSyncBase'));
            
            $dataTag = $body->appendChild(new DOMElement('Data', null, 'uri:AirSyncBase'));
            $dataTag->appendChild(new DOMText(preg_replace("/(\r\n?|\n)/", "\r\n", $data->description)));
        }
        
        // Completed is required
        if ($data->completed instanceof DateTime) {
            $_domParrent->appendChild(new DOMElement('Complete', 1, 'uri:Tasks'));
            $_domParrent->appendChild(new DOMElement('DateCompleted', $data->completed->toString('Y-m-d\TH:i:s') . '.000Z', 'uri:Tasks'));
        } else {
            $_domParrent->appendChild(new DOMElement('Complete', 0, 'uri:Tasks'));
        }
        
        if (isset($data->tags) && count($data->tags) > 0) {
            $categories = $_domParrent->appendChild(new DOMElement('Categories', null, 'uri:Tasks'));
            foreach ($data->tags as $tag) {
                $categories->appendChild(new DOMElement('Category', $tag, 'uri:Tasks'));
            }
        }
        
    }
        
    /**
     * convert contact from xml to Addressbook_Model_Contact
     *
     * @todo handle images
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_Contact
     */
    public function toTineModel(SimpleXMLElement $_data, $_entry = null)
    {
        if($_entry instanceof Tasks_Model_Task) {
            $task = $_entry;
        } else {
            $task = new Tasks_Model_Task(null, true);
        }
        
        $xmlData = $_data->children('uri:Tasks');

        foreach($this->_mapping as $fieldName => $value) {
            switch($value) {
                case 'completed':
                    if((int)$xmlData->$fieldName === 1) {
                        $task->status = 'COMPLETED';
                        $task->completed = (string)$xmlData->DateCompleted;
                    } else {
                        $task->status = 'IN-PROCESS';
                        $task->completed = NULL;
                    }
                    break;
                case 'due':
                    if(isset($xmlData->$fieldName)) {
                        $task->$value = new Tinebase_DateTime((string)$xmlData->$fieldName);
                    } else {
                        $task->$value = null;
                    }
                    break;
                default:
                    if(isset($xmlData->$fieldName)) {
                        $task->$value = (string)$xmlData->$fieldName;
                    } else {
                        $task->$value = null;
                    }
                    break;
            }
        }
        
        if (version_compare($this->_device->acsversion, '12.0', '>=')) {
            $airSyncBase = $_data->children('uri:AirSyncBase');
            
            if (isset($airSyncBase->Body) && isset($airSyncBase->Body->Data)) {
                $task->description = preg_replace("/(\r\n?|\n)/", "\r\n", (string)$airSyncBase->Body->Data);
            }
        }
        
        // task should be valid now
        $task->isValid();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " taskData " . print_r($task->toArray(), true));
        
        return $task;
    }
    
    /**
     * convert contact from xml to Addressbook_Model_ContactFilter
     *
     * @param SimpleXMLElement $_data
     * @return array
     */
    protected function _toTineFilterArray(SimpleXMLElement $_data)
    {
        $xmlData = $_data->children('uri:Tasks');
        
        foreach($this->_mapping as $fieldName => $field) {
            if(isset($xmlData->$fieldName)) {
                switch ($field) {
                    case 'due':
                        $value = new Tinebase_DateTime((string)$xmlData->$fieldName);
                        break;
                        
                    default:
                        $value = (string)$xmlData->$fieldName;
                        break;
                        
                }
                $filterArray[] = array(
                    'field'     => $field,
                    'operator'  => 'equals',
                    'value'     => $value
                );
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filterArray, true));
        
        return $filterArray;
    }
    
    /**
     * return contentfilter array
     * 
     * @param  int $_filterType
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getContentFilter($_filterType)
    {
        $filter = parent::_getContentFilter($_filterType);
        
        // no persistent filter set -> add default filter
        if (! $filter ->getId()) {
            $defaultFilter = $filter->createFilter('container_id', 'equals', array(
                'path' => '/personal/' . Tinebase_Core::getUser()->getId()
            ));
            
            $filter->addFilter($defaultFilter);
        }
        
        if(in_array($_filterType, $this->_filterArray)) {
            switch($_filterType) {
                case Syncope_Command_Sync::FILTER_INCOMPLETE:
                    $filter->removeFilter('status');
                    $openStatus = Tasks_Config::getInstance()->get(Tasks_Config::TASK_STATUS)->records->filter('is_open', 1);
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filter by status ids " . print_r($openStatus->getId(), true));
                    
                    $filter->addFilter(new Tinebase_Model_Filter_Text(
                        'status', 
                        'in', 
                        $openStatus->getId()
                    ));
                    
                    break;
            }
        }
        
        return $filter;
    }
}
