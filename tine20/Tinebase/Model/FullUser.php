<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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
 * @property    string                  accountEmailAddress email address of user
 * @property    DateTime                accountExpires      date when account expires  
 * @property    string                  accountFullName     fullname of the account
 * @property    string                  accountDisplayName  displayname of the account
 * @property    string                  accountLoginName    account login name
 * @property    string                  accountLoginShell   account login shell
 * @property    string                  accountPrimaryGroup primary group id
 * @property    string                  container_id        
 * @property    array                   groups              list of group memberships
 * @property    DateTime                lastLoginFailure    time of last login failure
 * @property    int                     loginFailures       number of login failures
 * @property    string                  visibility          displayed/hidden in/from addressbook
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
        'accountExpires',
        'lastLoginFailure'
    );
    
    /**
     * adds email and samba users, generates username + user password and 
     *   applies multiple options (like accountLoginNamePrefix, accountHomeDirectoryPrefix, ...)
     * 
     * @param array $options
     * @param string $password
     * @return string
     */
    public function applyOptionsAndGeneratePassword($options, $password = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($options, TRUE));
        
        // create valid login name
        if (! isset($this->accountLoginName)) {
            $this->accountLoginName = Tinebase_User::getInstance()->generateUserName($this, (isset($options['userNameSchema'])) ? $options['userNameSchema'] : 1);
        }
        
        if (! isset($this->accountPrimaryGroup)) {
            if (! empty($options['group_id'])) {
                $groupId = $options['group_id'];
            } else {
                // use default user group
                $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
                $groupId = $defaultUserGroup->getId();
            }
            $this->accountPrimaryGroup = $groupId;
        }
        
        // add prefix to login name if given
        if (! empty($options['accountLoginNamePrefix'])) {
            $this->accountLoginName = $options['accountLoginNamePrefix'] . $this->accountLoginName;
        }
        
        // add home dir if empty and prefix is given (append login name)
        if (empty($this->accountHomeDirectory) && ! empty($options['accountHomeDirectoryPrefix'])) {
            $this->accountHomeDirectory = $options['accountHomeDirectoryPrefix'] . $this->accountLoginName;
        }
        
        // create email address if accountEmailDomain if given
        if (empty($this->accountEmailAddress) && ! empty($options['accountEmailDomain'])) {
            $this->accountEmailAddress = $this->accountLoginName . '@' . $options['accountEmailDomain'];
        }
        
        if (! empty($options['samba'])) {
            $this->_addSambaSettings($options['samba']);
        }
        
        if (isset($options['accountLoginShell'])) {
            $this->accountLoginShell = $options['accountLoginShell'];
        }
        
        // generate passwd (use accountLoginName or password from options or password from csv in this order)
        $userPassword = $this->accountLoginName;
        
        if (! empty($password)) {
            $userPassword = $password;
        } else if (! empty($options['password'])) {
            $userPassword = $options['password'];
        }
        
        $this->_addEmailUser($userPassword);
        
        return $userPassword;
    }
    
    /**
    * add samba settings to user
    *
    * @param array $options
    */
    protected function _addSambaSettings($options)
    {
        $samUser = new Tinebase_Model_SAMUser(array(
            'homePath'      => (isset($options['homePath'])) ? $options['homePath'] . $this->accountLoginName : '',
            'homeDrive'     => (isset($options['homeDrive'])) ? $options['homeDrive'] : '',
            'logonScript'   => (isset($options['logonScript'])) ? $options['logonScript'] : '',
            'profilePath'   => (isset($options['profilePath'])) ? $options['profilePath'] . $this->accountLoginName : '',
            'pwdCanChange'  => isset($options['pwdCanChange'])  ? $options['pwdCanChange']  : new Tinebase_DateTime('@1'),
            'pwdMustChange' => isset($options['pwdMustChange']) ? $options['pwdMustChange'] : new Tinebase_DateTime('@2147483647')
        ));
    
        $this->sambaSAM = $samUser;
    }
    
    /**
     * add email users to record (if email set + config exists)
     *
     * @param string $_password
     */
    protected function _addEmailUser($password)
    {
        if (! empty($this->accountEmailAddress)) {
            $this->imapUser = new Tinebase_Model_EmailUser(array(
                'emailPassword' => $password
            ));
            $this->smtpUser = new Tinebase_Model_EmailUser(array(
                'emailPassword' => $password
            ));
        }
    }
    
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
            'accountStatus'         => array(new Zend_Validate_InArray(array(
                Tinebase_Model_User::ACCOUNT_STATUS_ENABLED,
                Tinebase_Model_User::ACCOUNT_STATUS_DISABLED,
                Tinebase_Model_User::ACCOUNT_STATUS_BLOCKED,
                Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED)
            ), Zend_Filter_Input::DEFAULT_VALUE => Tinebase_Model_User::ACCOUNT_STATUS_ENABLED),
            'accountExpires'        => array('allowEmpty' => true),
            'accountPrimaryGroup'   => array('presence' => 'required'),
            'accountDisplayName'    => array('presence' => 'required'),
            'accountLastName'       => array('presence' => 'required'),
            'accountFirstName'      => array('allowEmpty' => true),
            'accountFullName'       => array('presence' => 'required'),
            'accountEmailAddress'   => array('allowEmpty' => true),
            'accountHomeDirectory'  => array('allowEmpty' => true),
            'accountLoginShell'     => array('allowEmpty' => true),
            'lastLoginFailure'      => array('allowEmpty' => true),
            'loginFailures'         => array('allowEmpty' => true),
            'sambaSAM'              => array('allowEmpty' => true),
            'openid'                => array('allowEmpty' => true),
            'contact_id'            => array('allowEmpty' => true),
            'container_id'          => array('allowEmpty' => true),
            'emailUser'             => array('allowEmpty' => true),
            'groups'                => array('allowEmpty' => true),
            'imapUser'              => array('allowEmpty' => true),
            'smtpUser'              => array('allowEmpty' => true),
            'visibility'            => array(new Zend_Validate_InArray(array(
                Tinebase_Model_User::VISIBILITY_HIDDEN, 
                Tinebase_Model_User::VISIBILITY_DISPLAYED)
            ), Zend_Filter_Input::DEFAULT_VALUE => Tinebase_Model_User::VISIBILITY_DISPLAYED),
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
     * returns TRUE if user has to change his/her password (compare sambaSAM->pwdMustChange with Tinebase_DateTime::now())
     * NOTE: this only applies for user with samba settings atm
     * 
     * @return boolean
     */
    public function mustChangePassword()
    {
        $result = FALSE;
        
        if ($this->sambaSAM instanceof Tinebase_Model_SAMUser 
            && isset($this->sambaSAM->pwdMustChange) 
            && $this->sambaSAM->pwdMustChange instanceof DateTime) 
        {
            if ($this->sambaSAM->pwdMustChange->compare(Tinebase_DateTime::now()) < 0) {
                if (!isset($this->sambaSAM->pwdLastSet)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                        . ' User ' . $this->accountLoginName . ' has to change his pw: it got never set by user');
                        
                    $result = TRUE;
                    
                } else if (isset($this->sambaSAM->pwdLastSet) && $this->sambaSAM->pwdLastSet instanceof DateTime) {
                    $dateToCompare = $this->sambaSAM->pwdLastSet;
                    
                    if ($this->sambaSAM->pwdMustChange->compare($dateToCompare) > 0) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                            . ' User ' . $this->accountLoginName . ' has to change his pw: ' . $this->sambaSAM->pwdMustChange . ' > ' . $dateToCompare);
                            
                        $result = TRUE;
                    }
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Password is up to date.');
                }
            }
        }
        
        return $result;
    }
}
