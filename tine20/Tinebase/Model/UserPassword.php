<?php
/**
 * Tine 2.0 model to create modlog for user password changes
 *
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 *
 * @package     Tinebase
 * @subpackage  User
 *
 * @property    string  password
 * @property    string  last_password_change
 * @property    string  created_by
 */
class Tinebase_Model_UserPassword extends Tinebase_Record_Abstract
{

    /**
     * key in $_validators/$_properties array for the field which
     * represents the identifier
     * NOTE: _Must_ be set by the derived classes!
     *
     * @var string
     */
    protected $_identifier = 'id';

    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'last_password_change',
    );


    /**
     * list of zend validator
     *
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array('allowEmpty' => true),
        'password'              => array('allowEmpty' => true),
        'last_password_change'  => array('allowEmpty' => true),
        'created_by'            => array('allowEmpty' => true),
    );

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return true;
    }
}
