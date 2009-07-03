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
class Tinebase_Alarm
{
    /**
     * @var Tinebase_Alarm_Backend
     */
    protected $_backend;
    
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
                $appController->sendAlarm($alarm);
            }
        }
    }
    
    /**
     * create new alarm
     *
     * @param Tinebase_Model_Alarm $_alarm
     * @return Tinebase_Model_Alarm
     */
    public function create(Tinebase_Model_Alarm $_alarm)
    {
        return $this->_backend->create($_alarm);
    }
    
    /**
     * get all alarms of a given record
     * 
     * @param  string       $_model     own model to get relations for
     * @param  string|array $_id        own id to get relations for
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Alarm
     * 
     * @todo add backend?
     * @todo add grants?
     */
    public function getAlarmsOfRecord($_model, $_id)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  model: '$_model' ids:" . print_r((array)$_id, true));
    
        $filter = new Tinebase_Model_AlarmFilter(array(
            array(
                'field'     => 'model', 
                'operator'  => 'equals', 
                'value'     => $_model
            ),
            array(
                'field'     => 'record_id', 
                'operator'  => 'equals', 
                'value'     => $_id
            ),
        ));
        $result = $this->_backend->search($filter);
            
        return $result;
    }
    
}
