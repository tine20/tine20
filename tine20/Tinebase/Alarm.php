<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Alarm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 */

/**
 * controller for alarms / reminder messages
 *
 * @package     Tinebase
 * @subpackage  Alarm
 */
class Tinebase_Alarm extends Tinebase_Controller_Record_Abstract
{
    /**
     * @var Tinebase_Backend_Sql
     */
    protected $_backend;
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Alarm';
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = FALSE;
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Alarm
     */
    private static $instance = NULL;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_backend = new Tinebase_Backend_Sql($this->_modelName, 'alarm');
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Alarm
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Alarm();
        }
        return self::$instance;
    }
    
    /**************************** public funcs *************************************/
    
    /**
     * send pending alarms
     *
     * @param mixed $_eventName
     * @return void
     * 
     * @todo sort alarms (by model/...)?
     * @todo what to do about Tinebase_Model_Alarm::STATUS_FAILURE alarms?
     */
    public function sendPendingAlarms($_eventName)
    {        
        $eventName = (is_array($_eventName)) ? $_eventName['eventName'] : $_eventName;
        
        if (! Tinebase_AsyncJob::getInstance()->jobIsRunning($eventName)) {
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No ' . $eventName . ' is running. Starting new one.');
 
            $job = Tinebase_AsyncJob::getInstance()->startJob($eventName);
         
            try { 
                // get all pending alarms
                $filter = new Tinebase_Model_AlarmFilter(array(
                    array(
                        'field'     => 'alarm_time', 
                        'operator'  => 'before', 
                        'value'     => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
                    ),
                    array(
                        'field'     => 'sent_status', 
                        'operator'  => 'equals', 
                        'value'     => Tinebase_Model_Alarm::STATUS_PENDING // STATUS_FAILURE?
                    ),
                ));
            
                $alarms = $this->_backend->search($filter);
        
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Sending ' . count($alarms) . ' alarms.');
        
                // loop alarms and call sendAlarm in controllers
                foreach ($alarms as $alarm) {
                    list($appName, $i, $itemName) = explode('_', $alarm->model);
                    $appController = Tinebase_Core::getApplicationInstance($appName, $itemName);
                
                    if ($appController instanceof Tinebase_Controller_Alarm_Interface) {
                    
                        $alarm->sent_time = Zend_Date::now();
                    
                        try {
                            $appController->sendAlarm($alarm);
                            $alarm->sent_status = Tinebase_Model_Alarm::STATUS_SUCCESS;
                        
                        } catch (Exception $e) {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                        
                            $alarm->sent_message = $e->getMessage();
                            $alarm->sent_status = Tinebase_Model_Alarm::STATUS_FAILURE;
                            //throw $e;
                        } 
                    
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updating alarm status: ' . $alarm->sent_status);
                    
                        $this->update($alarm);
                    }
                }
            
                $job = Tinebase_AsyncJob::getInstance()->finishJob($job);
            
            } catch (Exception $e) {
                // save new status 'failure'
                $job = Tinebase_AsyncJob::getInstance()->finishJob($job, Tinebase_Model_AsyncJob::STATUS_FAILURE, $e->getMessage());
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Job ' . $eventName . ' is already running. Skipping event.');           
            
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Job ' . $eventName . ' is already running. Skipping event.');
        }
    }
    
    /**
     * get all alarms of given record(s)
     * 
     * @param  string $_model model to get alarms for
     * @param  string|array|Tinebase_Record_Interface|Tinebase_Record_RecordSet $_recordId record id(s) to get alarms for
     * @param  boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array of ids
     */
    public function getAlarmsOfRecord($_model, $_recordId, $_onlyIds = FALSE)
    {
        if ($_recordId instanceof Tinebase_Record_RecordSet) {
            $recordId = $_recordId->getArrayOfIds();
        } else if ($_recordId instanceof Tinebase_Record_Interface) {
            $recordId = $_recordId->getId();
        } else {
            $recordId = $_recordId;
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  model: '$_model' id:" . print_r((array)$recordId, true));
    
        $filter = new Tinebase_Model_AlarmFilter(array(
            array(
                'field'     => 'model', 
                'operator'  => 'equals', 
                'value'     => $_model
            ),
            array(
                'field'     => 'record_id', 
                'operator'  => 'in', 
                'value'     => (array)$recordId
            ),
        ));
        $result = $this->_backend->search($filter, NULL, $_onlyIds);
        
        return $result;
    }
    
    /**
     * set alarms of record
     *
     * @param Tinebase_Record_Abstract $_record
     * @param string $_alarmsProperty
     * @return void
     */
    public function setAlarmsOfRecord(Tinebase_Record_Abstract $_record, $_alarmsProperty = 'alarms')
    {
        $model = get_class($_record);
        $alarms = $_record->{$_alarmsProperty};
        
        $currentAlarms = $this->getAlarmsOfRecord($model, $_record);
        $diff = $currentAlarms->getMigration($alarms->getArrayOfIds());
        $this->_backend->delete($diff['toDeleteIds']);
        
        // create / update alarms
        foreach ($alarms as $alarm) {
            $id = $alarm->getId();
            
            if ($id) {
                $alarm = $this->_backend->update($alarm);
                
            } else {
                $alarm->record_id = $_record->getId();
                if (! $alarm->model) {
                    $alarm->model = $model;
                }
                $alarm = $this->_backend->create($alarm);
            }
        }
    }
    
    /**
     * delete all alarms of a given record(s)
     *
     * @param string $_model
     * @param string|array|Tinebase_Record_Interface|Tinebase_Record_RecordSet $_recordId
     * @return void
     */
    public function deleteAlarmsOfRecord($_model, $_recordId)
    {
        $ids = $this->getAlarmsOfRecord($_model, $_recordId, TRUE);
        $this->delete($ids);
    }
}
