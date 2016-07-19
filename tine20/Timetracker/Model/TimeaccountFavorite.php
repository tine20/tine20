<?php
/**
 * class to hold Timeaccount data
 *
 * @package     Timetracker
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <M.Spahn@bitExpert.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Timeaccount data
 *
 * @package     Timetracker
 * @subpackage  Model
 */
class Timetracker_Model_TimeaccountFavorite extends Tinebase_Record_Abstract
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
    protected $_application = 'Timetracker';

    /**
     * Validators
     *
     * @var array
     */
    protected $_validators = array (
        // tine 2.0 generic fields
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),

        // model specific fields
        'account_id'      => array(Zend_Filter_Input::ALLOW_EMPTY => false, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'timeaccount_id'  => array('presence' => 'required')
    );
}
