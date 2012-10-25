<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold contact data
 *
 * @property	account_id	id of associated user
 * @property	email		the email address of the contact
 * @property	n_family
 * @property	n_fileas 	display name
 * @property	n_fn		the full name
 * @property	n_given
 * @property	type		type of contact
 * @package     Sipgate
 */
class Sipgate_Model_Line extends Tinebase_Record_Abstract
{

    /**
     * key in $_validators/$_properties array for the filed which
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
    protected $_application = 'Sipgate';

    /**
     * list of zend validator
     *
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'account_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'user_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'uri_alias'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'sip_uri'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tos'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'e164_in'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'e164_out'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_sync'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'creation_time',
        'last_sync'
    );
}
