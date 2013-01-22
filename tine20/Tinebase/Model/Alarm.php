<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
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
     * minutes_before value for custom alarm time
     * 
     */
    const OPTION_CUSTOM = 'custom';
    
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
        'record_id'         => array('allowEmpty' => TRUE, /*'Alnum'*/),
        'model'             => array('presence' => 'required'),
        'alarm_time'        => array('presence' => 'required'),
        'minutes_before'    => array('allowEmpty' => TRUE),
        'sent_time'         => array('allowEmpty' => TRUE),
        'sent_status'       => array('presence' => 'required', array('InArray', array(
            self::STATUS_PENDING, 
            self::STATUS_FAILURE, 
            self::STATUS_SUCCESS,
        )), Zend_Filter_Input::DEFAULT_VALUE => self::STATUS_PENDING),
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
     * @param Tinebase_DateTime $_date
     */
    public function setTime(DateTime $_date)
    {
        if (! isset($this->minutes_before)) {
            throw new Tinebase_Exception_Record_Validation('minutes_before is needed to set the alarm_time!');
        }
        
        if ($this->minutes_before !== self::OPTION_CUSTOM) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Calculating alarm_time ...');
            $date = clone $_date;
            $this->alarm_time = $date->subMinute(round($this->minutes_before));
        }
        
        $this->setOption(self::OPTION_CUSTOM, $this->minutes_before === self::OPTION_CUSTOM);
    }

    /**
     * set minutes_before depending on another date with alarm_time
     *
     * @param Tinebase_DateTime $_date
     */
    public function setMinutesBefore(DateTime $_date)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->toArray(), TRUE));
        
        if ($this->getOption(self::OPTION_CUSTOM) !== TRUE) {
            $dtStartTS = $_date->getTimestamp();
            $alarmTimeTS = $this->alarm_time->getTimestamp();
            
            $this->minutes_before = $dtStartTS < $alarmTimeTS ? 0 : round(($dtStartTS - $alarmTimeTS) / 60);
        } else {
            $this->minutes_before = self::OPTION_CUSTOM;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' result: ' . $this->minutes_before);
    }
    
    /**
     * sets an option
     * 
     * @param string $_key
     * @param scalar|array of scalar $_value
     */
    public function setOption($_key, $_value)
    {
        $options = $this->options ? Zend_Json::decode($this->options) : array();
        $options[$_key] = $_value;
        
        $this->options = Zend_Json::encode($options);
    }
    
    /**
     * gets an option
     * 
     * @param  string $_key
     * @return scalar|array of scalar
     */
    public function getOption($_key)
    {
        $options = $this->options ? Zend_Json::decode($this->options) : array();
        return array_key_exists($_key, $options) ? $options[$_key] : NULL;
    }
}
