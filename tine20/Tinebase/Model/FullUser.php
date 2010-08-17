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
 * defines the datatype for a full users
 * 
 * this datatype contains all information about an user
 * the usage of this datatype should be restricted to administrative tasks only
 * 
 * @package     Tinebase
 * @property    string                  accountStatus
 * @property    Tinebase_Model_SAMUser  sambaSAM            object holding samba settings
 * @property    Zend_Date               accountExpires      date when account expires  
 * @property    string                  accountFullName     fullname of the account
 * @property    string                  accountDisplayName  displayname of the account
 * @property    string                  accountLoginName    account login name
 * @subpackage  User
 */
class Tinebase_Model_FullUser extends Tinebase_Model_User
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
        'accountLoginName'      => array('StringTrim', 'StringToLower'),
        //'accountPrimaryGroup'   => 'Digits',
        'accountDisplayName'    => 'StringTrim',
        'accountLastName'       => 'StringTrim',
        'accountFirstName'      => 'StringTrim',
        'accountFullName'       => 'StringTrim',
        'accountEmailAddress'   => array('StringTrim', 'StringToLower'),
        'openid'                => array(array('Empty', null))
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
            'accountId'             => array('allowEmpty' => true),
            'accountLoginName'      => array('presence' => 'required'),
            'accountLastLogin'      => array('allowEmpty' => true),
            'accountLastLoginfrom'  => array('allowEmpty' => true),
            'accountLastPasswordChange' => array('allowEmpty' => true),
            'accountStatus'         => array(new Zend_Validate_InArray(array('enabled', 'disabled', 'expired')), Zend_Filter_Input::DEFAULT_VALUE => 'enabled'),
            'accountExpires'        => array('allowEmpty' => true),
            'accountPrimaryGroup'   => array('presence' => 'required'),
            'accountDisplayName'    => array('presence' => 'required'),
            'accountLastName'       => array('presence' => 'required'),
            'accountFirstName'      => array('allowEmpty' => true),
            'accountFullName'       => array('presence' => 'required'),
            'accountEmailAddress'   => array('allowEmpty' => true),
            'accountHomeDirectory'  => array('allowEmpty' => true),
            'accountLoginShell'     => array('allowEmpty' => true),
            'sambaSAM'              => array('allowEmpty' => true),
            'openid'                => array('allowEmpty' => true),
            'contact_id'            => array('allowEmpty' => true),
            'emailUser'             => array('allowEmpty' => true),
            'visibility'            => array(new Zend_Validate_InArray(array('hidden', 'displayed')), Zend_Filter_Input::DEFAULT_VALUE => 'displayed'),
            
        );
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * return the public informations of this user only
     *
     * @return Tinebase_Model_User
     */
    public function getPublicUser()
    {
        $result = new Tinebase_Model_User($this->toArray(), true);
        
        return $result;
    }
    
    /**
     * returns user login name
     *
     * @return string
     */
    public function __toString()
    {
        return $this->accountLoginName;
    }
    
    /**
     * returns TRUE if user has to change his/her password (compare sambaSAM->pwdMustChange with Zend_Date::now())
     * NOTE: this only applies for user with samba settings atm
     * 
     * @return boolean
     */
    public function mustChangePassword()
    {
        $result = FALSE;
        
        if ($this->sambaSAM instanceof Tinebase_Model_SAMUser 
            && isset($this->sambaSAM->pwdMustChange) 
            && $this->sambaSAM->pwdMustChange instanceof Zend_Date) 
        {
            if ($this->sambaSAM->pwdMustChange->compare(Zend_Date::now()) < 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                    . ' User ' . $this->accountLoginName . ' has to change his pw: ' . $this->sambaSAM->pwdMustChange . ' < ' . Zend_Date::now());
                    
                $result = TRUE;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Password is up to date.');
            }
        }
        
        return $result;
    }
}
