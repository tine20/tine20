<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <alex@stintzing.net>
 */

/**
 * class Sipgate_Model_Account
 *
 * @package     Sipgate
 * @subpackage  Record
 */
class Sipgate_Model_Account extends Tinebase_Record_Abstract
{
    /**
     * identifier
     *
     * @var string
     */
    protected $_identifier = 'id';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Sipgate';

    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'               => array('allowEmpty' => true),
        'description'      => array('allowEmpty' => false),
        'credential_id'    => array('allowEmpty' => true),
        'accounttype'      => array('allowEmpty' => false),
        'type'      => array('allowEmpty' => false),
        'username'         => array('allowEmpty'  => true),
        'password'         => array('allowEmpty'  => true),
        'is_valid'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mobile_number'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),

        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        
        // appended on the fly
        
        'lines'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time'
    );
}
