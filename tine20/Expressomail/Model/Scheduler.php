<?php
/**
 * class to hold Account data
 *
 * @package     Expressomail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Fernando Alberto Reuter Wendt
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *              Copyright (c) 2015 SERPRO GmbH (https://www.serpro.gov.br)
 *
 */

/**
 * class to hold folder export scheduler data
 *
 * @property  string    _identifier
 * @property  string    _application
 * @property  array     _validators
 * @property  array     _datetimeFields
 * @package   Expressomail
 * @subpackage    Model
 */
class Expressomail_Model_Scheduler extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the field which
     * represents the identifier
     *
     * @var string
     */
    protected $_identifier = 'id';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Expressomail';

    /**
     * list of zend validator
     *
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'account_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'folder'            => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'scheduler_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'start_time'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'end_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'status'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'priority'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'expunged_time'     => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'scheduler_time',
        'start_time',
        'end_time',
        'deleted_time',
        'expunged_time'
    );

    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format:
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     *
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User' => array('account_id')
    );
}