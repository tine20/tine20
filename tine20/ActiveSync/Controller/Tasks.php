<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * controller tasks class
 *
 * @package     ActiveSync
 */
class ActiveSync_Controller_Tasks extends ActiveSync_Controller_Abstract 
{
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
    
    protected $_folders = array(array(
        'folderId'      => 'tasksroot',
        'parentId'      => 0,
        'displayName'   => 'Tasks',
        'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_TASK
    ));
    
    protected $_applicationName     = 'Tasks';
    
    protected $_modelName           = 'Task';    
    
    public function appendXML($_xmlDocument, $_xmlNode, $_data)
    {
        foreach($this->_mapping as $key => $value) {
            if(isset($_data->$value)) {
                switch($value) {
                    case 'completed':
                        continue 2;
                        break;
                    case 'due':
                        if($_data->$value instanceof Zend_Date) {
                            $_xmlNode->appendChild($_xmlDocument->createElementNS('POOMTASKS', $key, $_data->$value->getIso()));
                            #$_xmlNode->appendChild($_xmlDocument->createElementNS('POOMTASKS', $key, '2008-12-30T23:00:00.000Z'));
                            $_data->$value->setTimezone(Tinebase_Core::get('userTimeZone'));
                            $_xmlNode->appendChild($_xmlDocument->createElementNS('POOMTASKS', 'DueDate', $_data->$value->getIso()));
                        }
                        break;
                    case 'priority':
                        $priority = $_data->$value <= 2 ? $_data->$value : 2;
                        $_xmlNode->appendChild($_xmlDocument->createElementNS('POOMTASKS', $key, $priority));
                        break;
                    default:
                        $_xmlNode->appendChild($_xmlDocument->createElementNS('POOMTASKS', $key, $_data->$value));
                        break;
                }
            }
        }
        // Complete is required
        if($_data->completed instanceof Zend_Date) {
            $_xmlNode->appendChild($_xmlDocument->createElementNS('POOMTASKS', 'Complete', 1));
            $_xmlNode->appendChild($_xmlDocument->createElementNS('POOMTASKS', 'DateCompleted', $_data->completed->getIso()));
        } else {
            $_xmlNode->appendChild($_xmlDocument->createElementNS('POOMTASKS', 'Complete', 0));
        }
        
    }
    
    public function search($_collectionId, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId Data: " .print_r($_data, true));
        
        $filter = $this->_toTineFilter($_data);
        
        $foundTasks = Tasks_Controller_Task::getInstance()->search($filter);

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found " . count($foundTasks));
            
        return $foundTasks;
    }
    
    public function change($_collectionId, $_id, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId Data: " .print_r($_data, true));
        
        $tasksController = Tasks_Controller_Task::getInstance();
        
        $oldTask = $tasksController->get($_id); 
        
        $task = $this->_toTineModel($_data);
        $task->setId($_id);
        $task->container_id = $oldTask->container_id;
        $task->last_modified_time = $this->_syncTimeStamp;
        
        $task = $tasksController->update($task);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " updated contact id " . $task->getId());

        return $task;
    }
        
    /**
     * convert contact from xml to Addressbook_Model_Contact
     *
     * @todo handle images
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_Contact
     */
    protected function _toTineModel(SimpleXMLElement $_data, $_entry = null)
    {
        if($_entry instanceof Tasks_Model_Task) {
            $task = $_entry;
        } else {
            $task = new Tasks_Model_Task(null, true);
        }
        
        die('fix me');
        
        $taskData = array();
        
        foreach($this->_mapping as $fieldName => $value) {
            if(isset($_data->$fieldName)) {
                switch($value) {
                    case 'completed':
                        if((int)$_data->$fieldName === 1) {
                            $taskData['status_id'] = 2;
                            $taskData['completed'] = (string)$_data->DateCompleted;
                        } else {
                            $taskData['status_id'] = 3; 
                            $taskData['completed'] = NULL;
                        }
                        break;
                    case 'picture':
                        #$contactData[$value] = base64_decode((string)$_data->$fieldName);
                        #$fp = fopen('/tmp/data.txt', 'w');
                        #fwrite($fp, base64_decode((string)$_data->Picture));
                        #fclose($fp);
                        break;
                    default:
                        $taskData[$value] = (string)$_data->$fieldName;
                        break;
                }
            }
        }
        
        $task = new Tasks_Model_Task($taskData);
        
        return $task;
    }
    
    /**
     * convert contact from xml to Addressbook_Model_ContactFilter
     *
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_ContactFilter
     */
    protected function _toTineFilter(SimpleXMLElement $_data)
    {
        $taskFilter = new Tasks_Model_TaskFilter(array(
            array(
                'field'     => 'containerType',
                'operator'  => 'equals',
                'value'     => 'all'
            )
        )); 
    
        foreach($this->_mapping as $fieldName => $value) {
            if($taskFilter->has($value)) {
                $taskFilter->$value = array(
                    'operator'  => 'equals',
                    'value'     => (string)$_data->$fieldName
                );
            }
        }
        
        return $taskFilter;
    }
}