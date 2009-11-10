<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Tasks Controller for Tasks
 * 
 * The Tasks 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Tasks
 * @subpackage  Controller
 */
class Tasks_Controller_Task extends Tinebase_Controller_Record_Abstract implements Tinebase_Controller_Alarm_Interface
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Tasks';
        $this->_modelName = 'Tasks_Model_Task';
        $this->_backend = Tasks_Backend_Factory::factory(Tasks_Backend_Factory::SQL);
        $this->_currentAccount = Tinebase_Core::getUser();
        $this->_recordAlarmField = 'due';
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }

    /**
     * holds self
     * @var Tasks_Controller_Task
     */
    private static $_instance = NULL;
    
    /**
     * holds backend instance
     * (only sql atm.)
     *
     * @var Tasks_Backend_Sql
     */
    protected $_backend;
    
    /**
     * singleton
     *
     * @return Tasks_Controller_Task
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tasks_Controller_Task();
        }
        return self::$_instance;
    }
    
    /****************************** overwritten functions ************************/

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_task)
    {
        if(empty($_task->class_id)) {
            $_task->class_id = NULL;
        }
        $this->_handleCompleted($_task);
        $_task->originator_tz = $_task->originator_tz ? $_task->originator_tz : Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        
        $task = parent::create($_task);
        
        $this->_addAutomaticAlarms($task);
        
        return $task;
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_task)
    {
        $this->_handleCompleted($_task);
        return parent::update($_task);
    }
    
    /**
     * handles completed date and sets task to 100%
     * 
     * @param Tasks_Model_Task $_task
     */
    protected function _handleCompleted($_task)
    {
        $allStatus = Tasks_Controller_Status::getInstance()->getAllStatus();
        
        $statusId = $allStatus->getIndexById($_task->status_id);
        
        if (is_int($statusId)){
            $status = $allStatus[$statusId];
            
            if($status->status_is_open) {
                $_task->completed = NULL;
            } elseif (! $_task->completed instanceof Zend_Date) {
                $_task->completed = Zend_Date::now();
                $_task->percent = 100;
            }
        }
    }

    /**
     * send an alarm (to responsible person and if it does not exist, to creator)
     *
     * @param  Tinebase_Model_Alarm $_alarm
     * @return void
     */
    public function sendAlarm(Tinebase_Model_Alarm $_alarm) 
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " About to send alarm " . print_r($_alarm->toArray(), TRUE)
        );

        $task = $this->get($_alarm->record_id);
        
        if ($task->organizer) {
            $organizerContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($task->organizer);
        } else {
            // use creator as organizer
            $organizerContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($task->created_by);
        }
        
        // create message
        $translate = Tinebase_Translation::getTranslation($this->_applicationName);
        $messageSubject = $translate->_('Notification for Task ' . $task->summary);
        $messageBody = $task->getNotificationMessage();
        
        $notificationsBackend = Tinebase_Notification_Factory::getBackend(Tinebase_Notification_Factory::SMTP);
        
        // send message
        if ($organizerContact->email && ! empty($organizerContact->email)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Trying to send alarm email to ' . $organizerContact->email);
            $notificationsBackend->send(NULL, $organizerContact, $messageSubject, $messageBody);
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Organizer has no email address.');
        }
    }
    
    /**
     * add automatic alarms to record (if configured)
     * 
     * @param Tinebase_Record_Abstract $_record
     * @return void
     * 
     * @todo    move this to Tinebase_Controller_Record_Abstract
     */
    protected function _addAutomaticAlarms(Tinebase_Record_Abstract $_record)
    {
        $automaticAlarms = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::AUTOMATICALARM, 'Tasks');
        if (count($automaticAlarms) == 0) {
            return;
        }
        
        if (! $_record->alarms instanceof Tinebase_Record_RecordSet) {
            $_record->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        }

        if (count($_record->alarms) > 0) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Do not overwrite existing alarm.');
            return;
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Add automatic alarms / minutes before: ' . implode(',', $automaticAlarms));
        foreach ($automaticAlarms as $minutesBefore) {
            $_record->alarms->addRecord(new Tinebase_Model_Alarm(array(
                'minutes_before' => $minutesBefore,
            ), TRUE));
        }
        
        $this->_saveAlarms($_record);
    }
}
