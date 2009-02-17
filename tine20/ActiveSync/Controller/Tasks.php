<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
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
    
    /**
     * get estimate of add,changed or deleted contacts
     *
     * @todo improve filter usage. Filter need to support OR and need to return count only
     * @param Zend_Date $_startTimeStamp
     * @param Zend_Date $_endTimeStamp
     * @return int total count of changed items
     */
    public function getItemEstimate($_startTimeStamp = NULL, $_endTimeStamp = NULL)
    {
        $count = 0;
        $startTimeStamp = ($_startTimeStamp instanceof Zend_Date) ? $_startTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;

        if($_startTimeStamp === NULL && $_endTimeStamp === NULL) {
            $filter = new Tasks_Model_TaskFilter(); 
            $count = Tasks_Controller_Task::getInstance()->searchCount($filter);
        } elseif($_endTimeStamp === NULL) {
            foreach(array('creation_time', 'last_modified_time', 'deleted_time') as $fieldName) {
                $filter = new Tasks_Model_TaskFilter(array(
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'greater',
                        'value'     => $startTimeStamp
                    ),
                )); 
                $count += Tasks_Controller_Task::getInstance()->searchCount($filter);
            }
        } else {
            $endTimeStamp = ($_endTimeStamp instanceof Zend_Date) ? $_endTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
            
            foreach(array('creation_time', 'last_modified_time', 'deleted_time') as $fieldName) {
                $filter = new Tasks_Model_TaskFilter(array(
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'after',
                        'value'     => $startTimeStamp
                    ),
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'before',
                        'value'     => $endTimeStamp
                    ),
                )); 
                $count += Tasks_Controller_Task::getInstance()->searchCount($filter);
            }
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Count: $count Timestamps: ($startTimeStamp / $endTimeStamp)");
                    
        return $count;
    }
    
    public function getSince($_field, $_startTimeStamp, $_endTimeStamp)
    {
        switch($_field) {
            case 'added':
                $fieldName = 'creation_time';
                break;
            case 'changed':
                $fieldName = 'last_modified_time';
                break;
            case 'deleted':
                $fieldName = 'deleted_time';
                break;
            default:
                throw new Exception("$_field must be either added, changed or deleted");                
        }
        
        $startTimeStamp = ($_startTimeStamp instanceof Zend_Date) ? $_startTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        $endTimeStamp = ($_endTimeStamp instanceof Zend_Date) ? $_endTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
        
        $filter = new Tasks_Model_TaskFilter(array(
            array(
                'field'     => $fieldName,
                'operator'  => 'after',
                'value'     => $startTimeStamp
            ),
            array(
                'field'     => $fieldName,
                'operator'  => 'before',
                'value'     => $endTimeStamp
            ),
        ));
        $result = Tasks_Controller_Task::getInstance()->search($filter);
        
        return $result;
    }    
    
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
    
    public function add($_collectionId, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId Data: " .print_r($_data, true));
        
        $task = $this->_toTine20Task($_data);
        $task->creation_time = $this->_syncTimeStamp;
        
        $task = Tasks_Controller_Task::getInstance()->create($task);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " added task id " . $task->getId());

        return $task;
    }
    
    public function search($_collectionId, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId Data: " .print_r($_data, true));
        
        $filter = $this->_toTine20TaskFilter($_data);
        
        $foundTasks = Tasks_Controller_Task::getInstance()->search($filter);

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found " . count($foundTasks));
            
        return $foundTasks;
    }
    
    public function change($_collectionId, $_id, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId Data: " .print_r($_data, true));
        
        $tasksController = Tasks_Controller_Task::getInstance();
        
        $oldTask = $tasksController->get($_id); 
        
        $task = $this->_toTine20Task($_data);
        $task->setId($_id);
        $task->container_id = $oldTask->container_id;
        $task->last_modified_time = $this->_syncTimeStamp;
        
        $task = $tasksController->update($task);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " updated contact id " . $task->getId());

        return $task;
    }
    
    /**
     * delete contact
     *
     * @param unknown_type $_collectionId
     * @param unknown_type $_id
     */
    public function delete($_collectionId, $_id)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " ColectionId: $_collectionId Id: $_id");
        
        Tasks_Controller_Task::getInstance()->delete($_id);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " deleted task id " . $_id);
    }
    
    /**
     * convert contact from xml to Addressbook_Model_Contact
     *
     * @todo handle images
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_Contact
     */
    protected function _toTine20Task(SimpleXMLElement $_data)
    {
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
    protected function _toTine20TaskFilter(SimpleXMLElement $_data)
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