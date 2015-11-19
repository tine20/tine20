<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 * @todo        extend Tinebase_Application_Backend_Sql and replace some functions
 */

/**
 * sql implementation of the SQL users interface
 * 
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User_Sql extends Tinebase_User_Abstract
{
    /**
     * row name mapping 
     * 
     * @var array
     */
    protected $rowNameMapping = array(
        'accountId'                 => 'id',
        'accountDisplayName'        => 'display_name',
        'accountFullName'           => 'full_name',
        'accountFirstName'          => 'first_name',
        'accountLastName'           => 'last_name',
        'accountLoginName'          => 'login_name',
        'accountLastLogin'          => 'last_login',
        'accountLastLoginfrom'      => 'last_login_from',
        'accountLastPasswordChange' => 'last_password_change',
        'accountStatus'             => 'status',
        'accountExpires'            => 'expires_at',
        'accountPrimaryGroup'       => 'primary_group_id',
        'accountEmailAddress'       => 'email',
        'accountHomeDirectory'      => 'home_dir',
        'accountLoginShell'         => 'login_shell',
        'lastLoginFailure'          => 'last_login_failure_at',
        'loginFailures'             => 'login_failures',
        'openid'                    => 'openid',
        'visibility'                => 'visibility',
        'contactId'                 => 'contact_id'
    );
    
    /**
     * copy of Tinebase_Core::get('dbAdapter')
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * sql user plugins
     * 
     * @var array
     */
    protected $_sqlPlugins = array();
    
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'accounts';
    
    /**
     * @var Tinebase_Backend_Sql_Command_Interface
     */
    protected $_dbCommand;

    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);

        $this->_db = Tinebase_Core::getDb();
        $this->_dbCommand = Tinebase_Backend_Sql_Command::factory($this->_db);
    }

    /**
     * registerPlugins
     * 
     * @param array $plugins
     */
    public function registerPlugins($plugins)
    {
        parent::registerPlugins($plugins);
        
        foreach ($this->_plugins as $plugin) {
            if ($plugin instanceof Tinebase_User_Plugin_SqlInterface) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " Registering " . get_class($plugin) . ' SQL plugin.');
                $this->_sqlPlugins[] = $plugin;
            }
        }
    }
    
    /**
     * unregisterAllPlugins
     */
    public function unregisterAllPlugins()
    {
        parent::unregisterAllPlugins();
        $this->_sqlPlugins = array();
    }
    
    /**
     * get list of users
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @param string $_accountClass the type of subclass for the Tinebase_Record_RecordSet to return
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_User
     */
    public function getUsers($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL, $_accountClass = 'Tinebase_Model_User')
    {
        $select = $this->_getUserSelectObject()
            ->limit($_limit, $_start);
            
        if ($_sort !== NULL && isset($this->rowNameMapping[$_sort])) {
            $select->order($this->_db->table_prefix . $this->_tableName . '.' . $this->rowNameMapping[$_sort] . ' ' . $_dir);
        }
        
        if (!empty($_filter)) {
            $whereStatement = array();
            $defaultValues  = array(
                $this->rowNameMapping['accountLastName'], 
                $this->rowNameMapping['accountFirstName'], 
                $this->rowNameMapping['accountLoginName']
            );
            
            // prepare for case insensitive search
            foreach ($defaultValues as $defaultValue) {
                $whereStatement[] = $this->_dbCommand->prepareForILike($this->_db->quoteIdentifier($defaultValue)) . ' LIKE ' . $this->_dbCommand->prepareForILike('?');
            }
            
            $select->where('(' . implode(' OR ', $whereStatement) . ')', '%' . $_filter . '%');
        }
        
        // @todo still needed?? either we use contacts from addressboook or full users now
        // return only active users, when searching for simple users
        if ($_accountClass == 'Tinebase_Model_User') {
            $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('status') . ' = ?', 'enabled'));
        }
        
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $result = new Tinebase_Record_RecordSet($_accountClass, $rows, TRUE);
        
        return $result;
    }
    
    /**
     * get total count of users
     *
     * @param string $_filter
     * @return int
     */
    public function getUsersCount($_filter = null)
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', array('count' => 'COUNT(' . $this->_db->quoteIdentifier('id') . ')'));
        
        if (!empty($_filter)) {
            $whereStatement = array();
            $defaultValues  = array(
                $this->rowNameMapping['accountLastName'], 
                $this->rowNameMapping['accountFirstName'], 
                $this->rowNameMapping['accountLoginName']
            );
            
            // prepare for case insensitive search
            foreach ($defaultValues as $defaultValue) {
                $whereStatement[] = $this->_dbCommand->prepareForILike($this->_db->quoteIdentifier($defaultValue)) . ' LIKE ' . $this->_dbCommand->prepareForILike('?');
            }
            
            $select->where('(' . implode(' OR ', $whereStatement) . ')', '%' . $_filter . '%');
        }
        
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
        
        return $rows[0];
    }
    
    /**
     * get user by property
     *
     * @param   string  $_property      the key to filter
     * @param   string  $_value         the value to search for
     * @param   string  $_accountClass  type of model to return
     * 
     * @return  Tinebase_Model_User the user object
     */
    public function getUserByProperty($_property, $_value, $_accountClass = 'Tinebase_Model_User')
    {
        $user = $this->getUserByPropertyFromSqlBackend($_property, $_value, $_accountClass);
        
        // append data from plugins
        foreach ($this->_sqlPlugins as $plugin) {
            try {
                $plugin->inspectGetUserByProperty($user);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' User sql plugin failure');
                Tinebase_Exception::log($e);
            }
        }
            
        if ($this instanceof Tinebase_User_Interface_SyncAble) {
            try {
                $syncUser = $this->getUserByPropertyFromSyncBackend('accountId', $user, $_accountClass);
                
                if (!empty($syncUser->emailUser)) {
                    $user->emailUser  = $syncUser->emailUser;
                }
                if (!empty($syncUser->imapUser)) {
                    $user->imapUser  = $syncUser->imapUser;
                }
                if (!empty($syncUser->smtpUser)) {
                    $user->smtpUser  = $syncUser->smtpUser;
                }
                if (!empty($syncUser->sambaSAM)) {
                    $user->sambaSAM  = $syncUser->sambaSAM;
                }
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' user not found in sync backend: ' . $user->getId());
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($user->toArray(), true));
        
        return $user;
    }

    /**
     * get user by property
     *
     * @param   string  $_property      the key to filter
     * @param   string  $_value         the value to search for
     * @param   string  $_accountClass  type of model to return
     * 
     * @return  Tinebase_Model_User the user object
     * @throws  Tinebase_Exception_NotFound
     * @throws  Tinebase_Exception_Record_Validation
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function getUserByPropertyFromSqlBackend($_property, $_value, $_accountClass = 'Tinebase_Model_User')
    {
        if(!(isset($this->rowNameMapping[$_property]) || array_key_exists($_property, $this->rowNameMapping))) {
            throw new Tinebase_Exception_InvalidArgument("invalid property $_property requested");
        }
        
        switch($_property) {
            case 'accountId':
                $value = Tinebase_Model_User::convertUserIdToInt($_value);
                break;
            default:
                $value = $_value;
                break;
        }
        
        $select = $this->_getUserSelectObject()
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier( SQL_TABLE_PREFIX . 'accounts.' . $this->rowNameMapping[$_property]) . ' = ?', $value));
        
        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        if ($row === false) {
            throw new Tinebase_Exception_NotFound('User with ' . $_property . ' = ' . $value . ' not found.');
        }

        try {
            $account = new $_accountClass(NULL, TRUE);
            $account->setFromArray($row);
        } catch (Tinebase_Exception_Record_Validation $e) {
            $validation_errors = $account->getValidationErrors();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage() . "\n" .
                "Tinebase_Model_User::validation_errors: \n" .
                print_r($validation_errors,true));
            throw ($e);
        }
        
        return $account;
    }
    
    /**
     * get users by primary group
     * 
     * @param string $groupId
     * @return Tinebase_Record_RecordSet of Tinebase_Model_FullUser
     */
    public function getUsersByPrimaryGroup($groupId)
    {
        $select = $this->_getUserSelectObject()
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'accounts.primary_group_id') . ' = ?', $groupId));
        $stmt = $select->query();
        $data = (array) $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_FullUser', $data, true);
        return $result;
    }
    
    /**
     * get full user by id
     *
     * @param   int         $_accountId
     * @return  Tinebase_Model_FullUser full user
     */
    public function getFullUserById($_accountId)
    {
        return $this->getUserById($_accountId, 'Tinebase_Model_FullUser');
    }
    
    /**
     * get user select
     *
     * @return Zend_Db_Select
     */
    protected function _getUserSelectObject()
    {
        /*
         * CASE WHEN `status` = 'enabled' THEN (CASE WHEN DATE(NOW()) > `expires_at` THEN 'expired'
         * WHEN ( `login_failures` > 5 AND DATE(`last_login_failure_at`) + INTERVAL '15' MINUTE > DATE(NOW())) THEN 'blocked'
         * ELSE 'enabled' END) WHEN `status` = 'expired' THEN 'expired' ELSE 'disabled' END
         */
        
        $maxLoginFailures = Tinebase_Config::getInstance()->get(Tinebase_Config::MAX_LOGIN_FAILURES, 5);
        if ($maxLoginFailures > 0) {
            $loginFailuresCondition = 'WHEN ( ' . $this->_db->quoteIdentifier($this->rowNameMapping['loginFailures']) . " > {$maxLoginFailures} AND "
                . $this->_dbCommand->setDate($this->_db->quoteIdentifier($this->rowNameMapping['lastLoginFailure'])) . " + INTERVAL '{$this->_blockTime}' MINUTE > "
                . $this->_dbCommand->setDate('NOW()') .") THEN 'blocked'";
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' User blocking disabled.');
            $loginFailuresCondition = '';
        }
        
        $statusSQL = 'CASE WHEN ' . $this->_db->quoteIdentifier($this->rowNameMapping['accountStatus']) . ' = ' . $this->_db->quote('enabled') . ' THEN ('
            . 'CASE WHEN '.$this->_dbCommand->setDate('NOW()') .' > ' . $this->_db->quoteIdentifier($this->rowNameMapping['accountExpires'])
            . ' THEN ' . $this->_db->quote('expired')
            . ' ' . $loginFailuresCondition
            . ' ELSE ' . $this->_db->quote('enabled') . ' END)'
            . ' WHEN ' . $this->_db->quoteIdentifier($this->rowNameMapping['accountStatus']) . ' = ' . $this->_db->quote('expired')
                . ' THEN ' . $this->_db->quote('expired')
            . ' ELSE ' . $this->_db->quote('disabled') . ' END';

        $fields =  array(
            'accountId'             => $this->rowNameMapping['accountId'],
            'accountLoginName'      => $this->rowNameMapping['accountLoginName'],
            'accountLastLogin'      => $this->rowNameMapping['accountLastLogin'],
            'accountLastLoginfrom'  => $this->rowNameMapping['accountLastLoginfrom'],
            'accountLastPasswordChange' => $this->rowNameMapping['accountLastPasswordChange'],
            'accountStatus'         => $statusSQL,
            'accountExpires'        => $this->rowNameMapping['accountExpires'],
            'accountPrimaryGroup'   => $this->rowNameMapping['accountPrimaryGroup'],
            'accountHomeDirectory'  => $this->rowNameMapping['accountHomeDirectory'],
            'accountLoginShell'     => $this->rowNameMapping['accountLoginShell'],
            'accountDisplayName'    => $this->rowNameMapping['accountDisplayName'],
            'accountFullName'       => $this->rowNameMapping['accountFullName'],
            'accountFirstName'      => $this->rowNameMapping['accountFirstName'],
            'accountLastName'       => $this->rowNameMapping['accountLastName'],
            'accountEmailAddress'   => $this->rowNameMapping['accountEmailAddress'],
            'lastLoginFailure'      => $this->rowNameMapping['lastLoginFailure'],
            'loginFailures'         => $this->rowNameMapping['loginFailures'],
            'contact_id',
            'openid',
            'visibility',
        );

        // modlog fields have been added later
        if ($this->_userTableHasModlogFields()) {
            $fields = array_merge($fields, array(
                'created_by',
                'creation_time',
                'last_modified_by',
                'last_modified_time',
                'is_deleted',
                'deleted_time',
                'deleted_by',
                'seq',
            ));
        }

        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', $fields)
            ->joinLeft(
               SQL_TABLE_PREFIX . 'addressbook',
               $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'accounts.contact_id') . ' = ' 
                . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'addressbook.id'), 
                array(
                    'container_id'            => 'container_id'
                )
            );

        return $select;
    }
    
    /**
     * set the password for given account
     *
     * @param   string  $_userId
     * @param   string  $_password
     * @param   bool    $_encrypt encrypt password
     * @param   bool    $_mustChange
     * @return  void
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setPassword($_userId, $_password, $_encrypt = TRUE, $_mustChange = null)
    {
        $userId = $_userId instanceof Tinebase_Model_User ? $_userId->getId() : $_userId;
        $user = $_userId instanceof Tinebase_Model_FullUser ? $_userId : $this->getFullUserById($userId);
        $this->checkPasswordPolicy($_password, $user);
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        $accountData['password'] = ($_encrypt) ? Hash_Password::generate('SSHA256', $_password) : $_password;
        $accountData['last_password_change'] = Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        $where = array(
            $accountsTable->getAdapter()->quoteInto($accountsTable->getAdapter()->quoteIdentifier('id') . ' = ?', $userId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        if ($result != 1) {
            throw new Tinebase_Exception_NotFound('Unable to update password! account not found in authentication backend.');
        }
        
        $this->_setPluginsPassword($userId, $_password, $_encrypt);
    }
    
    /**
     * set password in plugins
     * 
     * @param string $userId
     * @param string $password
     * @param bool   $encrypt encrypt password
     * @throws Tinebase_Exception_Backend
     */
    protected function _setPluginsPassword($userId, $password, $encrypt = TRUE)
    {
        foreach ($this->_sqlPlugins as $plugin) {
            try {
                $plugin->inspectSetPassword($userId, $password, $encrypt);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Could not change plugin password: ' . $e);
                throw new Tinebase_Exception_Backend($e->getMessage());
            }
        }
    }
    
    /**
     * ensure password policy
     * 
     * @param string $password
     * @param Tinebase_Model_FullUser $user
     * @throws Tinebase_Exception_PasswordPolicyViolation
     */
    public function checkPasswordPolicy($password, Tinebase_Model_FullUser $user)
    {
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::PASSWORD_POLICY_ACTIVE, false)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' No password policy enabled');
            return;
        }
        
        $failedTests = array();
        
        $policy = array(
            Tinebase_Config::PASSWORD_POLICY_ONLYASCII              => '/[^\x00-\x7F]/',
            Tinebase_Config::PASSWORD_POLICY_MIN_LENGTH             => null,
            Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS         => '/[\W]*/',
            Tinebase_Config::PASSWORD_POLICY_MIN_UPPERCASE_CHARS    => '/[^A-Z]*/',
            Tinebase_Config::PASSWORD_POLICY_MIN_SPECIAL_CHARS      => '/[\w]*/',
            Tinebase_Config::PASSWORD_POLICY_MIN_NUMBERS            => '/[^0-9]*/',
            Tinebase_Config::PASSWORD_POLICY_FORBID_USERNAME        => $user->accountLoginName,
        );
        
        foreach ($policy as $key => $regex) {
            $test = $this->_testPolicy($password, $key, $regex);
            if ($test !== true) {
                $failedTests[$key] = $test;
            }
        }
        
        if (! empty($failedTests)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' ' . print_r($failedTests, true));
            
            $policyException = new Tinebase_Exception_PasswordPolicyViolation('Password failed to match the following policy requirements: ' 
                . implode('|', array_keys($failedTests)));
            throw $policyException;
        }
    }
    
    /**
     * test password policy
     * 
     * @param string $password
     * @param string $configKey
     * @param string $regex
     * @return mixed
     */
    protected function _testPolicy($password, $configKey, $regex = null)
    {
        $result = true;
        
        switch ($configKey) {
            case Tinebase_Config::PASSWORD_POLICY_ONLYASCII:
                if (Tinebase_Config::getInstance()->get($configKey, 0) && $regex !== null) {
                    $nonAsciiFound = preg_match($regex, $password, $matches);
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . ' ' . print_r($matches, true));
                    
                    $result = ($nonAsciiFound) ? array('expected' => 0, 'got' => count($matches)) : true;
                }
                
                break;
                
            case Tinebase_Config::PASSWORD_POLICY_FORBID_USERNAME:
                if (Tinebase_Config::getInstance()->get($configKey, 0)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . ' Testing if password is part of username "' . $regex . '"');
                    
                    if (! empty($password)) {
                        $result = ! preg_match('/' . preg_quote($password) . '/i', $regex);
                    }
                }
                
                break;
                
            default:
                // check min length restriction
                $minLength = Tinebase_Config::getInstance()->get($configKey, 0);
                if ($minLength > 0) {
                    $reduced = ($regex) ? preg_replace($regex, '', $password) : $password;
                    $charCount = strlen(utf8_decode($reduced));
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Found ' . $charCount . '/' . $minLength . ' chars for ' . $configKey /*. ': ' . $reduced */);
                    
                    if ($charCount < $minLength) {
                        $result = array('expected' => $minLength, 'got' => $charCount);
                    }
                }
                
                break;
        }
        
        return $result;
    }
    
    /**
     * set the status of the user
     *
     * @param mixed   $_accountId
     * @param string  $_status
     * @return integer
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function setStatus($_accountId, $_status)
    {
        if($this instanceof Tinebase_User_Interface_SyncAble) {
            $this->setStatusInSyncBackend($_accountId, $_status);
        }
        
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        switch($_status) {
            case 'enabled':
                $accountData[$this->rowNameMapping['loginFailures']]  = 0;
                $accountData[$this->rowNameMapping['accountExpires']] = null;
                $accountData['status'] = $_status;
                break;
                
            case 'disabled':
                $accountData['status'] = $_status;
                break;
                
            case 'expired':
                $accountData['expires_at'] = Tinebase_DateTime::now()->getTimestamp();
                break;
            
            default:
                throw new Tinebase_Exception_InvalidArgument('$_status can be only enabled, disabled or expired');
                break;
        }
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));

        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        return $result;
    }

    /**
     * sets/unsets expiry date 
     *
     * @param     mixed      $_accountId
     * @param     Tinebase_DateTime  $_expiryDate set to NULL to disable expirydate
    */
    public function setExpiryDate($_accountId, $_expiryDate)
    {
        if($this instanceof Tinebase_User_Interface_SyncAble) {
            $this->setExpiryDateInSyncBackend($_accountId, $_expiryDate);
        }
        
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        if($_expiryDate instanceof DateTime) {
            $accountData['expires_at'] = $_expiryDate->get(Tinebase_Record_Abstract::ISO8601LONG);
        } else {
            $accountData['expires_at'] = NULL;
        }
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));

        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        return $result;
    }
    
    /**
     * set last login failure in accounts table
     * 
     * @param string $_loginName
     * @see Tinebase/User/Tinebase_User_Interface::setLastLoginFailure()
     */
    public function setLastLoginFailure($_loginName)
    {
        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Login of user ' . $_loginName . ' failed.');
        
        try {
            $user = $this->getUserByPropertyFromSqlBackend('accountLoginName', $_loginName);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // nothing todo => is no existing user
            return;
        }
        
        $values = array(
            'last_login_failure_at' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
            'login_failures'        => new Zend_Db_Expr($this->_db->quoteIdentifier('login_failures') . ' + 1')
        );
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $user->getId())
        );
        
        $this->_db->update(SQL_TABLE_PREFIX . 'accounts', $values, $where);
    }
    
    /**
     * update the lastlogin time of user
     *
     * @param int $_accountId
     * @param string $_ipAddress
     * @return integer
     */
    public function setLoginTime($_accountId, $_ipAddress) 
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        $accountData['last_login_from'] = $_ipAddress;
        $accountData['last_login']      = Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
        $accountData['login_failures']  = 0;
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        return $result;
    }
    
    /**
     * update contact data(first name, last name, ...) of user
     * 
     * @param Addressbook_Model_Contact $contact
     */
    public function updateContact(Addressbook_Model_Contact $_contact)
    {
        if($this instanceof Tinebase_User_Interface_SyncAble) {
            $this->updateContactInSyncBackend($_contact);
        }
        
        return $this->updateContactInSqlBackend($_contact);
    }
    
    /**
     * update contact data(first name, last name, ...) of user in local sql storage
     * 
     * @param Addressbook_Model_Contact $contact
     * @return integer
     * @throws Exception
     */
    public function updateContactInSqlBackend(Addressbook_Model_Contact $_contact)
    {
        $contactId = $_contact->getId();

        $accountData = array(
            $this->rowNameMapping['accountDisplayName']  => $_contact->n_fileas,
            $this->rowNameMapping['accountFullName']     => $_contact->n_fn,
            $this->rowNameMapping['accountFirstName']    => $_contact->n_given,
            $this->rowNameMapping['accountLastName']     => $_contact->n_family,
            $this->rowNameMapping['accountEmailAddress'] => $_contact->email
        );
        
        try {
            $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
            
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('contact_id') . ' = ?', $contactId)
            );
            return $accountsTable->update($accountData, $where);

        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw($e);
        }
    }
    
    /**
     * updates an user
     * 
     * this function updates an user 
     *
     * @param Tinebase_Model_FullUser $_user
     * @return Tinebase_Model_FullUser
     */
    public function updateUser(Tinebase_Model_FullUser $_user)
    {
        if($this instanceof Tinebase_User_Interface_SyncAble) {
            $this->updateUserInSyncBackend($_user);
        }
        
        $updatedUser = $this->updateUserInSqlBackend($_user);
        $this->updatePluginUser($updatedUser, $_user);
        
        return $updatedUser;
    }
    
    /**
    * update data in plugins
    *
    * @param Tinebase_Model_FullUser $updatedUser
    * @param Tinebase_Model_FullUser $newUserProperties
    */
    public function updatePluginUser($updatedUser, $newUserProperties)
    {
        foreach ($this->_sqlPlugins as $plugin) {
            $plugin->inspectUpdateUser($updatedUser, $newUserProperties);
        }
    }
    
    /**
     * updates an user
     * 
     * this function updates an user 
     *
     * @param Tinebase_Model_FullUser $_user
     * @return Tinebase_Model_FullUser
     * @throws 
     */
    public function updateUserInSqlBackend(Tinebase_Model_FullUser $_user)
    {
        if(! $_user->isValid()) {
            throw new Tinebase_Exception_Record_Validation('Invalid user object. ' . print_r($_user->getValidationErrors(), TRUE));
        }

        $accountId = Tinebase_Model_User::convertUserIdToInt($_user);

        $oldUser = $this->getFullUserById($accountId);
        
        if (empty($_user->contact_id)) {
            $_user->visibility = 'hidden';
            $_user->contact_id = null;
        }
        $accountData = $this->_recordToRawData($_user);
        // don't update id
        unset($accountData['id']);
        
        // ignore all other states (expired and blocked)
        if ($_user->accountStatus == Tinebase_User::STATUS_ENABLED) {
            $accountData[$this->rowNameMapping['accountStatus']] = $_user->accountStatus;
            
            if ($oldUser->accountStatus === Tinebase_User::STATUS_BLOCKED) {
                $accountData[$this->rowNameMapping['loginFailures']] = 0;
            } elseif ($oldUser->accountStatus === Tinebase_User::STATUS_EXPIRED) {
                $accountData[$this->rowNameMapping['accountExpires']] = null;
            }
        } elseif ($_user->accountStatus == Tinebase_User::STATUS_DISABLED) {
            $accountData[$this->rowNameMapping['accountStatus']] = $_user->accountStatus;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($accountData, true));

        try {
            $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
            
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $accountId)
            );
            $accountsTable->update($accountData, $where);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw($e);
        }
        
        return $this->getUserById($accountId, 'Tinebase_Model_FullUser');
    }
    
    /**
     * add an user
     * 
     * @param   Tinebase_Model_FullUser  $_user
     * @return  Tinebase_Model_FullUser
     */
    public function addUser(Tinebase_Model_FullUser $_user)
    {
        if ($this instanceof Tinebase_User_Interface_SyncAble) {
            $userFromSyncBackend = $this->addUserToSyncBackend($_user);
            if ($userFromSyncBackend !== NULL) {
                // set accountId for sql backend sql backend
                $_user->setId($userFromSyncBackend->getId());
            }
        }
        
        $addedUser = $this->addUserInSqlBackend($_user);
        $this->addPluginUser($addedUser, $_user);
        
        return $addedUser;
    }
    
    /**
     * add data from/to plugins
     * 
     * @param Tinebase_Model_FullUser $addedUser
     * @param Tinebase_Model_FullUser $newUserProperties
     */
    public function addPluginUser($addedUser, $newUserProperties)
    {
        foreach ($this->_sqlPlugins as $plugin) {
            $plugin->inspectAddUser($addedUser, $newUserProperties);
        }
    }
    
    /**
     * add an user
     * 
     * @todo fix $contactData['container_id'] = 1;
     *
     * @param   Tinebase_Model_FullUser  $_user
     * @return  Tinebase_Model_FullUser
     */
    public function addUserInSqlBackend(Tinebase_Model_FullUser $_user)
    {
        $_user->isValid(TRUE);
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        if(!isset($_user->accountId)) {
            $userId = $_user->generateUID();
            $_user->setId($userId);
        }
        
        if (empty($_user->contact_id)) {
            $_user->visibility = 'hidden';
            $_user->contact_id = null;
        }
        
        $accountData = $this->_recordToRawData($_user);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding user to SQL backend: ' . $_user->accountLoginName);
        
        $accountsTable->insert($accountData);
            
        return $this->getUserById($_user->getId(), 'Tinebase_Model_FullUser');
    }
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Abstract $_user
     * @return array
     */
    protected function _recordToRawData($_user)
    {
        $accountData = array(
            'id'                => $_user->accountId,
            'login_name'        => $_user->accountLoginName,
            'status'            => $_user->accountStatus,
            'expires_at'        => ($_user->accountExpires instanceof DateTime ? $_user->accountExpires->get(Tinebase_Record_Abstract::ISO8601LONG) : NULL),
            'primary_group_id'  => $_user->accountPrimaryGroup,
            'home_dir'          => $_user->accountHomeDirectory,
            'login_shell'       => $_user->accountLoginShell,
            'openid'            => $_user->openid,
            'visibility'        => $_user->visibility,
            'contact_id'        => $_user->contact_id,
            $this->rowNameMapping['accountDisplayName']  => $_user->accountDisplayName,
            $this->rowNameMapping['accountFullName']     => $_user->accountFullName,
            $this->rowNameMapping['accountFirstName']    => $_user->accountFirstName,
            $this->rowNameMapping['accountLastName']     => $_user->accountLastName,
            $this->rowNameMapping['accountEmailAddress'] => $_user->accountEmailAddress,
            'created_by'            => $_user->created_by,
            'creation_time'         => $_user->creation_time,
            'last_modified_by'      => $_user->last_modified_by,
            'last_modified_time'    => $_user->last_modified_time,
            'is_deleted'            => $_user->is_deleted,
            'deleted_time'          => $_user->deleted_time,
            'deleted_by'            => $_user->deleted_by,
            'seq'                   => $_user->seq,
        );
        
        $unsetIfEmpty = array('seq', 'creation_time', 'created_by', 'last_modified_by', 'last_modified_time', 'is_deleted', 'deleted_time', 'deleted_by');
        foreach ($unsetIfEmpty as $property) {
            if (empty($accountData[$property])) {
                unset($accountData[$property]);
            }
        }
        
        return $accountData;
    }
    
    /**
     * delete a user
     *
     * @param  mixed  $_userId
     */
    public function deleteUser($_userId)
    {
        $deletedUser = $this->deleteUserInSqlBackend($_userId);
        
        if($this instanceof Tinebase_User_Interface_SyncAble) {
            $this->deleteUserInSyncBackend($deletedUser);
        }
        
        // update data from plugins
        foreach ($this->_sqlPlugins as $plugin) {
            $plugin->inspectDeleteUser($deletedUser);
        }
        
    }
    
    /**
     * delete a user
     *
     * @param  mixed  $_userId
     * @return Tinebase_Model_FullUser  the delete user
     */
    public function deleteUserInSqlBackend($_userId)
    {
        if ($_userId instanceof Tinebase_Model_FullUser) {
            $user = $_userId;
        } else {
            $user = $this->getFullUserById($_userId);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Deleting user' . $user->accountLoginName);
        
        $accountsTable          = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        $groupMembersTable      = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'group_members'));
        $roleMembersTable       = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'role_accounts'));
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            
            $where  = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('account_id') . ' = ?', $user->getId()),
            );
            $groupMembersTable->delete($where);

            $where  = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('account_id')   . ' = ?', $user->getId()),
                $this->_db->quoteInto($this->_db->quoteIdentifier('account_type') . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_USER),
            );
            $roleMembersTable->delete($where);
            
            $where  = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $user->getId()),
            );
            $accountsTable->delete($where);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' error while deleting account ' . $e->__toString());
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw($e);
        }
        
        return $user;
    }
    
    /**
     * delete users
     * 
     * @param array $_accountIds
     */
    public function deleteUsers(array $_accountIds)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Deleting ' . count($_accountIds) .' users');

        foreach ($_accountIds as $accountId) {
            $this->deleteUser($accountId);
        }
    }
    
    /**
     * Delete all users returned by {@see getUsers()} using {@see deleteUsers()}
     * 
     * @return void
     */
    public function deleteAllUsers()
    {
        // need to fetch FullUser because otherwise we would get only enabled accounts :/
        $users = $this->getUsers(NULL, NULL, 'ASC', NULL, NULL, 'Tinebase_Model_FullUser');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting ' . count($users) .' users');
        foreach ( $users as $user ) {
            $this->deleteUser($user);
        }
    }

    /**
     * Get multiple users
     *
     * fetch FullUser by default
     *
     * @param  string|array $_id Ids
     * @param  string  $_accountClass  type of model to return
     * @return Tinebase_Record_RecordSet of 'Tinebase_Model_User' or 'Tinebase_Model_FullUser'
     */
    public function getMultiple($_id, $_accountClass = 'Tinebase_Model_FullUser') 
    {
        if (empty($_id)) {
            return new Tinebase_Record_RecordSet($_accountClass);
        }
        
        $select = $this->_getUserSelectObject()
            ->where($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'accounts.id') . ' in (?)', (array) $_id);
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        $result = new Tinebase_Record_RecordSet($_accountClass, $queryResult, TRUE);
        
        return $result;
    }

    /**
     * send deactivation email to user
     * 
     * @param mixed $accountId
     */
    public function sendDeactivationNotification($accountId)
    {
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::ACCOUNT_DEACTIVATION_NOTIFICATION)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Deactivation notification disabled.');
            return;
        }
        
        try {
            $user = $this->getFullUserById($accountId);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Send deactivation notification to user ' . $user->accountLoginName);
            
            $translate = Tinebase_Translation::getTranslation('Tinebase');
            
            $view = new Zend_View();
            $view->setScriptPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views');
            
            $view->translate            = $translate;
            $view->accountLoginName     = $user->accountLoginName;
            // TODO add this?
            //$view->deactivationDate     = $user->deactivationDate;
            $view->tine20Url            = Tinebase_Core::getHostname();
            
            $messageBody = $view->render('deactivationNotification.php');
            $messageSubject = $translate->_('Your Tine 2.0 account has been deactivated');
            
            $recipient = Addressbook_Controller_Contact::getInstance()->getContactByUserId($user->getId(), /* $_ignoreACL = */ true);
            Tinebase_Notification::getInstance()->send(/* sender = */ null, array($recipient), $messageSubject, $messageBody);
            
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
        }
    }


    /**
     * returns number of current non-system users
     *
     * @return number
     */
    public function countNonSystemUsers()
    {
        $select = $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', 'COUNT(id)')
            ->where($this->_db->quoteIdentifier('login_name') . " not in ('cronuser', 'calendarscheduling')");
        $userCount = $this->_db->fetchOne($select);
        return $userCount;
    }

    /**
     * fetch creation time of the first/oldest user
     *
     * @return Tinebase_DateTime
     */
    public function getFirstUserCreationTime()
    {
        if (! $this->_userTableHasModlogFields()) {
            $fallback = new Tinebase_DateTime('2014-12-01');
            return $fallback;
        }

        $select = $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', 'creation_time')
            ->where($this->_db->quoteIdentifier('login_name') . " not in ('cronuser', 'calendarscheduling')")
            ->where($this->_db->quoteIdentifier('creation_time') . " is not null")
            ->order('creation_time ASC')
            ->limit(1);
        $creationTime = $this->_db->fetchOne($select);

        $result = (!empty($creationTime)) ? new Tinebase_DateTime($creationTime) : $fallback;
        return $result;
    }

    /**
     * checks if use table already has modlog fields
     *
     * @return bool
     */
    protected function _userTableHasModlogFields()
    {
        $schema = Tinebase_Db_Table::getTableDescriptionFromCache($this->_db->table_prefix . $this->_tableName, $this->_db);
        return isset($schema['creation_time']);
    }

    /**
     * fetch all user ids from accounts table: updating from an old version fails if the modlog fields don't exist
     *
     * @return array
     */
    public function getAllUserIdsFromSqlBackend()
    {
        $sqlbackend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_FullUser',
            'tableName' => $this->_tableName,
        ));

        $userIds = $sqlbackend->search(null, null, Tinebase_Backend_Sql_Abstract::IDCOL);
        return $userIds;
    }
}
