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
        'record_id'         => array('presence' => 'required', 'allowEmpty' => FALSE, 'Alnum'),
        'model'             => array('presence' => 'required', 'allowEmpty' => FALSE, 'Alnum'),
        'alarm_time'        => array('presence' => 'required'),
        'sent_time'         => array('presence' => 'required'),
        'sent_status'       => array('presence' => 'required', 'allowEmpty' => FALSE, 'InArray' => array(
            self::STATUS_PENDING, 
            self::STATUS_FAILURE, 
            self::STATUS_SUCCESS,
        )),
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
}
