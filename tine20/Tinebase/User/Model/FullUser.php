<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for a full user account
 * 
 * this datatype contains all information about an account
 * the usage of this datatype should be restricted to administrative tasks only
 * 
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User_Model_FullUser extends Tinebase_User_Model_User
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        //'accountId'             => 'Digits',
        'accountLoginName'      => 'StringTrim',
        'account_primary_group' => 'Digits',
        'accountDisplayName'    => 'StringTrim',
        'accountLastName'       => 'StringTrim',
        'accountFirstName'      => 'StringTrim',
        'accountFullName'       => 'StringTrim',
        'accountEmailAddress'   => 'StringTrim',
    ); // _/-\_
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     * @todo add valid values for status
     */
    protected $_validators;

    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'accountLastLogin',
        'accountLastPasswordChange',
        'accountExpires'
    );
    
    /**
     * @see Tinebase_Record_Abstract
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_validators = array(
            'accountId'             => array('Digits', 'allowEmpty' => true),
            'accountLoginName'      => array('presence' => 'required'),
            'accountLastLogin'      => array('allowEmpty' => true),
            'accountLastLoginfrom'  => array('allowEmpty' => true),
            'accountLastPasswordChange' => array('allowEmpty' => true),
            'accountStatus'         => array(new Zend_Validate_InArray(array('enabled', 'disabled'))),
            'accountExpires'        => array('allowEmpty' => true),
            'accountPrimaryGroup'   => array('presence' => 'required'),
            'accountDisplayName'    => array('presence' => 'required'),
            'accountLastName'       => array('presence' => 'required'),
            'accountFirstName'      => array('allowEmpty' => true),
            'accountFullName'       => array('presence' => 'required'),
            'accountEmailAddress'   => array('allowEmpty' => true)
        );
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * return the public informations of this account only
     *
     * @return Tinebase_User_Model_User
     */
    public function getPublicUser()
    {
        $result = new Tinebase_User_Model_User($this->toArray(), true);
        
        return $result;
    }
    
    /**
     * returns account login name
     *
     * @return string
     */
    public function __toString()
    {
        return $this->accountLoginName;
    }    
}