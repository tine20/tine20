<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * class Tinebase_Model_Alarm
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Alarm extends Tinebase_Record_Abstract 
{
    /**
     * pending status
     *
     */
    const STATUS_PENDING = 'pending';
    
    /**
     * failure status
     *
     */
    const STATUS_FAILURE = 'failure';

    /**
     * success status
     *
     */
    const STATUS_SUCCESS = 'success';

    /**
     * identifier field name
     *
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                => array('allowEmpty' => TRUE),
        'record_id'         => array('allowEmpty' => TRUE, 'Alnum'),
        'model'             => array('presence' => 'required'),
        'alarm_time'        => array('presence' => 'required'),
        'minutes_before'    => array('allowEmpty' => TRUE),
        'sent_time'         => array('allowEmpty' => TRUE),
        'sent_status'       => array('presence' => 'required', 'InArray' => array(
            self::STATUS_PENDING, 
            self::STATUS_FAILURE, 
            self::STATUS_SUCCESS,
        ), Zend_Filter_Input::DEFAULT_VALUE => self::STATUS_PENDING),
        'sent_message'      => array('allowEmpty' => TRUE),
    // xml field with app/model specific options
        'options'           => array('allowEmpty' => TRUE),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'alarm_time',
        'sent_time',
    );
    
    /**
     * set alarm time depending on another date with minutes_before
     *
     * @param Zend_Date $_date
     */
    public function setTime(Zend_Date $_date)
    {
        if (! isset($this->minutes_before)) {
            throw new Tinebase_Exception_Record_Validation('minutes_before is needed to set the alarm_time!');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Calculating alarm_time ...');
        
        $date = clone $_date;
        
        $this->alarm_time = $date->subMinute($this->minutes_before);
    }

    /**
     * set minutes_before depending on another date with alarm_time
     *
     * @param Zend_Date $_date
     */
    public function setMinutesBefore(Zend_Date $_date)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->toArray(), TRUE));
        
        $dtStartTS = $_date->getTimestamp();
        $alarmTimeTS = $this->alarm_time->getTimestamp();
        
        if ($dtStartTS < $alarmTimeTS) {
            $this->minutes_before = 0;
        } else {
            $this->minutes_before = ($dtStartTS - $alarmTimeTS) / 60;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' result: ' . $this->minutes_before);
    }
}
