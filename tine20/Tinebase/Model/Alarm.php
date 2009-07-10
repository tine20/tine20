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
        'record_id'         => array('presence' => 'required', 'Alnum'),
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
        if (! isset($this->minutes_before)/* || empty($this->minutes_before)*/) {
            throw new Tinebase_Exception_Record_Validation('minutes_before is needed to set the alarm_time!');
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Calculating alarm_time ...');
        
        $this->alarm_time = $_date->subMinute($this->minutes_before);
    }

    /**
     * set minutes_before depending on another date with alarm_time
     *
     * @param Zend_Date $_date
     * 
     * @todo compare dates to make sure $_date > $this->alarm_time
     */
    public function setMinutesBefore(Zend_Date $_date)
    {
        $this->minutes_before = $_date->subDate($this->alarm_time)->getMinute();
    }
}
