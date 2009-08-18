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
     * @return void
     * 
     * @todo sort alarms (by model/...)?
     * @todo what to do about Tinebase_Model_Alarm::STATUS_FAILURE alarms?
     */
    public function sendPendingAlarms()
    {
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Sending ' . count($alarms) . ' alarms.');
        
        // loop alarms and call sendAlarm in controllers
        foreach ($alarms as $alarm) {
            list($appName, $i, $itemName) = explode('_', $alarm->model);
            $appController = Tinebase_Core::getApplicationInstance($appName, $itemName);
            
            if ($appController instanceof Tinebase_Controller_Alarm_Interface) {
                
                $alarm->sent_time = Zend_Date::now();
                
                try {
                    $appController->sendAlarm($alarm);
                    $alarm->sent_status = Tinebase_Model_Alarm::STATUS_SUCCESS;
                    
                } catch (Tinebase_Exception $te) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $te->getMessage());
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $te->getTraceAsString());
                    
                    $alarm->sent_message = $te->getMessage();
                    $alarm->sent_status = Tinebase_Model_Alarm::STATUS_FAILURE;
                    //throw $te;
                }
                
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updating alarm status: ' . $alarm->sent_status);
                
                $this->update($alarm);
            }
        }
    }
    
    /**
     * get all alarms of a given record
     * 
     * @param  string $_model model to get alarms for
     * @param  string|array|Tinebase_Record_Interface|Tinebase_Record_RecordSet $_recordId record id(s) to get alarms for
     * @param  boolean $_onlyIds
     * @param  boolean $_resolve
     * @return Tinebase_Record_RecordSet|array of Tinebase_Model_Alarm|ids
     * 
     * @todo add grants?
     */
    public function getAlarmsOfRecord($_model, $_recordId, $_onlyIds = FALSE, $_resolve = FALSE)
    {
        if ($_recordId instanceof Tinebase_Record_RecordSet) {
            $recordId = $_recordId->getArrayOfIds();
        } else if ($_recordId instanceof Tinebase_Record_Interface) {
            $recordId = $_recordId->getId();
        } else {
            $recordId = $_recordId;
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  model: '$_model' id:" . print_r((array)$recordId, true));
    
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

        if ($_resolve) {
            if ($_recordId instanceof Tinebase_Record_RecordSet) {
                
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Resolving alarms and add them to record set.");
                
                $result->addIndices(array('record_id'));
                foreach ($_recordId as $record) {
                    $alarmField = $record->getAlarmDateTimeField();
                    $record->alarms = $result->filter('record_id', $record->getId());
                    
                    // calc minutes_before
                    if ($record->has($alarmField)) {
                        $record->alarms->setMinutesBefore($record->{$alarmField});
                    }
                }
                
            } else if ($_recordId instanceof Tinebase_Record_Interface) {
                
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Resolving alarms and add them to record.");
                
                $alarmField = $_recordId->getAlarmDateTimeField();
                $_recordId->alarms = $result;

                // calc minutes_before
                if ($_recordId->has($alarmField)) {
                    $_recordId->alarms->setMinutesBefore($_recordId->{$alarmField});
                }
            }
        }
        
        return $result;
    }
    
    /**
     * save alarms of record
     *
     * @param string $_model
     * @param Tinebase_Record_Abstract $_record
     */
    public function saveAlarmsOfRecord($_model, Tinebase_Record_Abstract $_record)
    {
        $alarmField = $_record->getAlarmDateTimeField();
        
        $alarms = $_record->alarms instanceof Tinebase_Record_RecordSet ? 
            $_record->alarms : 
            new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        
        if (count($alarms) == 0) {
            // no alarms
            return $alarms;
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " About to save " . count($alarms) . " alarms for $_model {$_record->id} " 
            //.  print_r($alarms->toArray(), true)
        );
        
        $currentAlarms = Tinebase_Alarm::getInstance()->getAlarmsOfRecord($_model, $_record->id);
        $diff = $currentAlarms->getMigration($alarms->getArrayOfIds());
        Tinebase_Alarm::getInstance()->delete($diff['toDeleteIds']);
        
        // create / update alarms
        foreach ($alarms as $alarm) {
            $id = $alarm->getId();
            
            if ($id) {
                if ($_record->has($alarmField) && $alarm->minutes_before) {
                    $alarm->setTime($_record->{$alarmField});
                }
                $alarm = Tinebase_Alarm::getInstance()->update($alarm);
                
            } else {
                $alarm->record_id = $_record->getId();
                if (! $alarm->model) {
                    $alarm->model = $_model;
                }
                if ($_record->has($alarmField) && ! $alarm->alarm_time) {
                    $alarm->setTime($_record->{$alarmField});
                }
                $alarm = Tinebase_Alarm::getInstance()->create($alarm);
            }
        }
        
        $_record->alarms = $alarms;
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
