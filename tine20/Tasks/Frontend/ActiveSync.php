<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * ActiveSync frontend class
 *
 * @package     Tasks
 * @subpackage  Frontend
 */
class Tasks_Frontend_ActiveSync extends ActiveSync_Frontend_Abstract
{
    /**
     * available filters
     * 
     * @var array
     */
    protected $_filterArray = array(
        Syncroton_Command_Sync::FILTER_INCOMPLETE
    );
    
    /**
     * ActiveSync -> record field mapping
     * 
     * @var array
     */
    protected $_mapping = array(
        'body'              => 'description',
        'categories'        => 'tags',
        'complete'          => 'completed',
        #'DateCompleted'     => 'datecompleted',
        #'DueDate'           => 'duedate',
        'utcDueDate'        => 'due',
        'importance'        => 'priority',
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
        'subject'           => 'summary',
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
    protected $_defaultFolderType   = Syncroton_Command_FolderSync::FOLDERTYPE_TASK;
    
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
    protected $_folderType          = Syncroton_Command_FolderSync::FOLDERTYPE_TASK_USER_CREATED;
    
    /**
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty = 'tasksfilterId';
    
    protected $_defaultContainerPreferenceName = Tasks_Preference::DEFAULTTASKLIST;
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::toSyncrotonModel()
     */
    public function toSyncrotonModel($entry, array $options = array())
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . " task data " . print_r($entry->toArray(), TRUE));
        
        $syncrotonTask = new Syncroton_Model_Task();
        
        foreach ($this->_mapping as $syncrotonProperty => $tine20Property) {
            if ($this->_isEmptyValue($entry->$tine20Property)) {
                // skip empty values
                continue;
            }

            switch($tine20Property) {
                case 'completed':
                    break;
                    
                case 'description':
                    $syncrotonTask->$syncrotonProperty = new Syncroton_Model_EmailBody(array(
                        'type' => Syncroton_Model_EmailBody::TYPE_PLAINTEXT,
                        'data' => $entry->$tine20Property
                    ));
                
                    break;
                
                case 'due':
                    if($entry->$tine20Property instanceof DateTime) {
                        $syncrotonTask->$syncrotonProperty = $entry->$tine20Property;
                        
                        $dueDateTime = clone $entry->$tine20Property;
                        $dueDateTime->setTimezone(Tinebase_Core::getUserTimezone());
                        $syncrotonTask->dueDate = $dueDateTime;
                    }
                    
                    break;
                    
                case 'priority':
                    $prioMapping = array_flip(Tasks_Model_Priority::getMapping());
                    $priority = isset($prioMapping[$entry->$tine20Property]) ? $prioMapping[$entry->$tine20Property] : 2;
                    
                    // ActiveSync does not support URGENT (3) priority
                    $syncrotonTask->$syncrotonProperty = ($priority <= 2) ? $priority : 2;
                    
                    break;
                    
                // @todo validate tags are working
                case 'tags':
                    $syncrotonTask->$syncrotonProperty = $entry->$tine20Property->name;
                    
                    break;
                    
                default:
                    $syncrotonTask->$syncrotonProperty = $entry->$tine20Property;
                    
                    break;
            }
        }

        // Completed is required
        if ($entry->completed instanceof DateTime) {
            $syncrotonTask->complete = 1;
            $syncrotonTask->dateCompleted = $entry->completed;
        } else {
            $syncrotonTask->complete = 0;
        }
        
        return $syncrotonTask;
    }
        
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::toTineModel()
     */
    public function toTineModel(Syncroton_Model_IEntry $data, $entry = null)
    {
        if($entry instanceof Tasks_Model_Task) {
            $task = $entry;
        } else {
            $task = new Tasks_Model_Task(array(
                'organizer' => Tinebase_Core::getUser()->getId()
            ), TRUE);
        }        

        foreach($this->_mapping as $syncrotonProperty => $tine20Property) {
            if (!isset($data->$syncrotonProperty)) {
                if ($tine20Property === 'priority') {
                    $task->$tine20Property = Tasks_Model_Priority::NORMAL;
                } else {
                    $task->$tine20Property = null;
                }
            
                continue;
            }
            
            switch ($tine20Property) {
                case 'completed':
                    if ($data->$syncrotonProperty === 1) {
                        $task->status = 'COMPLETED';
                        $task->$tine20Property = $data->dateCompleted;
                    } else {
                        $task->status = 'IN-PROCESS';
                        $task->$tine20Property = NULL;
                    }
                    
                    break;
                    
                case 'description':
                    // @todo check $data->$fieldName->Type and convert to/from HTML if needed
                    if ($data->$syncrotonProperty instanceof Syncroton_Model_EmailBody) {
                        $task->$tine20Property = preg_replace("/(\r\n?|\n)/", "\r\n", $data->$syncrotonProperty->data);
                    } else {
                        $task->$tine20Property = null;
                    }
                
                    break;
                    
                case 'priority':
                    $prioMapping = Tasks_Model_Priority::getMapping();
                    $task->$tine20Property = (isset($data->$syncrotonProperty) && isset($prioMapping[$data->$syncrotonProperty]))
                        ? $prioMapping[$data->$syncrotonProperty]
                        : Tasks_Model_Priority::NORMAL;
                    break;

                case 'summary':
                    $this->_truncateField($task, $tine20Property, $data->$syncrotonProperty);
                    break;

                default:
                    if ($data->$syncrotonProperty instanceof DateTime) {
                        $task->$tine20Property = new Tinebase_DateTime($data->$syncrotonProperty);
                    } else {
                        $task->$tine20Property = $data->$syncrotonProperty;
                    }
                    
                    break;
            }
        }

        // task should be valid now
        $task->isValid();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . " taskData " . print_r($task->toArray(), TRUE));
        
        return $task;
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
        // @todo what is this good for? I think should be handled in _addContainerFilter. LK
        #if ($filter->isEmpty()) {
        #    $defaultFilter = $filter->createFilter('container_id', 'equals', array(
        #        'path' => '/personal/' . Tinebase_Core::getUser()->getId()
        #    ));
        #    
        #    $filter->addFilter($defaultFilter);
        #}
        
        if(in_array($_filterType, $this->_filterArray)) {
            switch($_filterType) {
                case Syncroton_Command_Sync::FILTER_INCOMPLETE:
                    $filter->removeFilter('status');
                    $openStatus = Tasks_Config::getInstance()->get(Tasks_Config::TASK_STATUS)->records->filter('is_open', 1);
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filter by status ids " . print_r($openStatus->getId(), TRUE));
                    
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
