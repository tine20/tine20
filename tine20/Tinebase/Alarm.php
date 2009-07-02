<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 * @todo        make this a real controller + singleton (create extra sql backend)
 */

/**
 * backend for alarms / reminder messages
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Alarm extends Tinebase_Backend_Sql_Abstract
{
    /**************************** backend settings *********************************/
    
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'alarm';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Alarm';
    
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
        $alarms = $this->search($filter);
        
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
}
