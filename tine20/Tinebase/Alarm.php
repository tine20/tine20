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
 * @todo        add deleteAlarmsOfRecord() function
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
     * @var Tinebase_Alarm_Backend
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
     * holdes the instance of the singleton
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
        $this->_backend = new Tinebase_Alarm_Backend();
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
     * @todo update alarms (Tinebase_Model_Alarm::STATUS_SUCCESS)
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
                if ($appController->sendAlarm($alarm)) {
                    $alarm->sent_status = Tinebase_Model_Alarm::STATUS_SUCCESS;
                } else {
                    $alarm->sent_status = Tinebase_Model_Alarm::STATUS_FAILURE;
                }
                $this->update($alarm);
            }
        }
    }
    
    /**
     * get all alarms of a given record
     * 
     * @param  string $_model model to get alarms for
     * @param  string|array|Tinebase_Record_Interface|Tinebase_Record_RecordSet $_recordId record id(s) to get alarms for
     * @return Tinebase_Record_RecordSet|array of Tinebase_Model_Alarm|ids
     * 
     * @todo add backend?
     * @todo add grants?
     */
    public function getAlarmsOfRecord($_model, $_recordId, $_onlyIds = FALSE)
    {
        if ($_recordId instanceof Tinebase_Record_RecordSet) {
            $_recordId = $_recordId->getArrayOfIds();
        } else if ($_recordId instanceof Tinebase_Record_Interface) {
            $_recordId = $_recordId->getId();
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  model: '$_model' id:" . print_r((array)$_recordId, true));
    
        $filter = new Tinebase_Model_AlarmFilter(array(
            array(
                'field'     => 'model', 
                'operator'  => 'equals', 
                'value'     => $_model
            ),
            array(
                'field'     => 'record_id', 
                'operator'  => 'in', 
                'value'     => (array)$_recordId
            ),
        ));
        $result = $this->_backend->search($filter, NULL, $_onlyIds);
            
        return $result;
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
