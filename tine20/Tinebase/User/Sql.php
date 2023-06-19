<?php

/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
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
    use Tinebase_Controller_Record_ModlogTrait;

    /**
     * Model name
     *
     * @var string
     *
     * @todo perhaps we can remove that and build model name from name of the class (replace 'Controller' with 'Model')
     */
    protected $_modelName = 'Tinebase_Model_User';

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
        'password_must_change'      => 'password_must_change',
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
        'contactId'                 => 'contact_id',
        'xprops'                    => 'xprops',
        'creation_time'             => 'creation_time',
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
     * @param array $_options Options used in connecting, binding, etc.
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);

        $this->_db = Tinebase_Core::getDb();
        $this->_dbCommand = Tinebase_Backend_Sql_Command::factory($this->_db);
    }

    /**
     * registerPlugin
     * 
     * @param Tinebase_User_Plugin_Interface $plugin
     */
    public function registerPlugin(Tinebase_User_Plugin_Interface $plugin)
    {
        parent::registerPlugin($plugin);

        if ($plugin instanceof Tinebase_User_Plugin_SqlInterface) {
            $className = get_class($plugin);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Registering " . $className . ' SQL plugin.');

            $this->_sqlPlugins[$className] = $plugin;
        }
    }

    public function removePlugin($plugin)
    {
        $result = parent::removePlugin($plugin);

        $className = is_object($plugin) ? get_class($plugin) : $plugin;

        if (isset($this->_sqlPlugins[$className]) && $this->_sqlPlugins[$className] instanceof Tinebase_User_Plugin_SqlInterface) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Removing " . $className . ' SQL plugin.');

            $result = $this->_sqlPlugins[$className];
            unset($this->_sqlPlugins[$className]);
        }

        return $result;
    }

    /**
     * @param string $classname
     * @return Tinebase_User_Plugin_SqlInterface
     */
    public function getSqlPlugin($classname)
    {
        return $this->_sqlPlugins[$classname];
    }

    /**
     * @return array
     */
    public function getSqlPluginNames()
    {
        return array_keys($this->_sqlPlugins);
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
            $defaultValues = array(
                $this->rowNameMapping['accountLastName'], 
                $this->rowNameMapping['accountFirstName'], 
                $this->rowNameMapping['accountLoginName'],
                $this->rowNameMapping['accountEmailAddress'],
            );

            // prepare for case insensitive search
            foreach ($defaultValues as $defaultValue) {
                $whereStatement[] = $this->_dbCommand->prepareForILike(
                        $this->_db->quoteIdentifier($this->_db->table_prefix . $this->_tableName . '.' . $defaultValue)
                    ) . ' LIKE ' . $this->_dbCommand->prepareForILike('?');
            }
            
            $select->where('(' . implode(' OR ', $whereStatement) . ')', '%' . $_filter . '%');
        }
        
        // @todo still needed?? either we use contacts from addressbook or full users now
        // return only active users, when searching for simple users
        if ($_accountClass == 'Tinebase_Model_User') {
            $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('status') . ' = ?', 'enabled'));
        }

        $select->where($this->_db->quoteIdentifier($this->_db->table_prefix . $this->_tableName . '.' . 'is_deleted') . ' = 0');

        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $stmt->closeCursor();
        
        $result = new Tinebase_Record_RecordSet($_accountClass, $rows, TRUE);
        $result->runConvertToRecord();

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
            $defaultValues = [
                $this->rowNameMapping['accountLastName'], 
                $this->rowNameMapping['accountFirstName'], 
                $this->rowNameMapping['accountLoginName'],
                $this->rowNameMapping['accountEmailAddress'],
            ];
            
            // prepare for case insensitive search
            foreach ($defaultValues as $defaultValue) {
                $whereStatement[] = $this->_dbCommand->prepareForILike($this->_db->quoteIdentifier($defaultValue)) . ' LIKE ' . $this->_dbCommand->prepareForILike('?');
            }
            
            $select->where('(' . implode(' OR ', $whereStatement) . ')', '%' . $_filter . '%');
        }

        $select->where($this->_db->table_prefix . $this->_tableName . '.' . $this->_db->quoteIdentifier('is_deleted') . ' = 0');

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
     * @param   bool    $_getDeleted
     * @return  Tinebase_Model_User the user object
     */
    public function getUserByProperty($_property, $_value, $_accountClass = Tinebase_Model_User::class, bool $_getDeleted = false)
    {
        $user = $this->getUserByPropertyFromSqlBackend($_property, $_value, $_accountClass, $_getDeleted);
        
        // append data from plugins
        foreach ($this->_sqlPlugins as $plugin) {
            try {
                if ($plugin instanceof Tinebase_User_Plugin_LdapInterface) {
                    throw new Tinebase_Exception_InvalidArgument('LDAP plugin ' . get_class($plugin)
                        . ' should not be registered as sql plugin');
                }
                $this->_inspectCRUD($plugin, $user, null, 'inspectGetUserByProperty');
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__
                         . ' User sql plugin "' . get_class($plugin) . '" failure');
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
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                    __METHOD__ . '::' . __LINE__ . ' user not found in sync backend: ' . $user->getId());
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
     * @param   bool    $_getDeleted
     * @return  Tinebase_Model_User|Tinebase_Model_FullUser the user object
     * @throws  Tinebase_Exception_NotFound
     * @throws  Tinebase_Exception_Record_Validation
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function getUserByPropertyFromSqlBackend($_property, $_value, $_accountClass = Tinebase_Model_User::class, bool $_getDeleted = false)
    {
        if(!(isset($this->rowNameMapping[$_property]) || array_key_exists($_property, $this->rowNameMapping))) {
            throw new Tinebase_Exception_InvalidArgument("invalid property $_property requested");
        }
        
        switch ($_property) {
            case 'accountId':
                $value = Tinebase_Model_User::convertUserIdToInt($_value);
                break;
            default:
                $value = $_value;
                break;
        }
        
        $select = $this->_getUserSelectObject($_getDeleted)
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier( SQL_TABLE_PREFIX . 'accounts.' . $this->rowNameMapping[$_property]) . ' = ?', $value));

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select);

        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        $stmt->closeCursor();
        if ($row === false) {
            throw new Tinebase_Exception_NotFound('User with ' . $_property . ' = ' . $value . ' not found.');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($row, true));

        $account = null;
        try {
            /** @var Tinebase_Model_User $account */
            $account = new $_accountClass(NULL, TRUE);
            $account->hydrateFromBackend($row);
            $account->runConvertToRecord();
        } catch (Tinebase_Exception_Record_Validation $e) {
            $validation_errors = $account ? $account->getValidationErrors() : [];
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
        $stmt->closeCursor();
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_FullUser');
        foreach ($data as $row) {
            $user = new Tinebase_Model_FullUser([], true);
            $user->hydrateFromBackend($row);
            $result->addRecord($user);
        }
        $result->runConvertToRecord();
        return $result;
    }
    
    /**
     * get user select
     *
     * @param bool $_getDeleted
     * @return Zend_Db_Select
     */
    protected function _getUserSelectObject(bool $_getDeleted = false)
    {
        $interval = $this->_dbCommand->getDynamicInterval(
            'SECOND',
            '1',
            'CASE WHEN ' . $this->_db->quoteIdentifier($this->rowNameMapping['loginFailures'])
            . ' > 5 THEN 60 ELSE POWER(2, ' . $this->_db->quoteIdentifier($this->rowNameMapping['loginFailures']) . ') END');
        
        $statusSQL = 'CASE WHEN ' . $this->_db->quoteIdentifier($this->rowNameMapping['accountStatus']) . ' = ' . $this->_db->quote('enabled') . ' THEN ('
            . 'CASE WHEN '.$this->_dbCommand->setDate('NOW()') .' > ' . $this->_db->quoteIdentifier($this->rowNameMapping['accountExpires'])
            . ' THEN ' . $this->_db->quote('expired')
            . ' WHEN ( ' . $this->_db->quoteIdentifier($this->rowNameMapping['loginFailures']) . ' > 0 AND '
            . $this->_db->quoteIdentifier($this->rowNameMapping['lastLoginFailure']) . ' + ' . $interval . ' > NOW()) THEN ' . $this->_db->quote('blocked')
            . ' ELSE ' . $this->_db->quote('enabled') . ' END)'
            . ' WHEN ' . $this->_db->quoteIdentifier($this->rowNameMapping['accountStatus']) . ' = ' . $this->_db->quote('expired')
                . ' THEN ' . $this->_db->quote('expired')
            . ' ELSE ' . $this->_db->quote('disabled') . ' END';

        $fields = array(
            'accountId'             => $this->rowNameMapping['accountId'],
            'accountLoginName'      => $this->rowNameMapping['accountLoginName'],
            'accountLastLogin'      => $this->rowNameMapping['accountLastLogin'],
            'accountLastLoginfrom'  => $this->rowNameMapping['accountLastLoginfrom'],
            'accountLastPasswordChange' => $this->rowNameMapping['accountLastPasswordChange'],
            'password_must_change'      => $this->rowNameMapping['password_must_change'],
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
            'mfa_configs',
            'NOW()', // only needed for debugging
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

        // remove this in 2018.11 as an upgrade to 2017.11 creates the field
        if ($this->_userHasXpropsField()) {
            $fields[] = 'xprops';
        }

        $schema = Tinebase_Db_Table::getTableDescriptionFromCache($this->_db->table_prefix . $this->_tableName, $this->_db);
        foreach ($fields as $index => $field) {
            // status contains $statusSQL
            if (! isset($schema[$field]) && $index !== 'accountStatus') {
                unset($fields[$index]);
            }
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

        if (!$_getDeleted) {
            $select->where($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'accounts.is_deleted') . ' = 0');
        }

        return $select;
    }

    /**
     * set the password for given account
     *
     * @param   string $_userId
     * @param   string $_password
     * @param   bool $_encrypt encrypt password
     * @param   bool $_mustChange
     * @throws Tinebase_Exception_NotFound
     */
    public function setPassword($_userId, $_password, $_encrypt = TRUE, $_mustChange = null)
    {
        $userId = $_userId instanceof Tinebase_Model_User ? $_userId->getId() : $_userId;
        $user = $_userId instanceof Tinebase_Model_FullUser ? $_userId : $this->getFullUserById($userId);
        Tinebase_User_PasswordPolicy::checkPasswordPolicy($_password, $user);

        $accountData = $this->_updatePasswordProperty($userId, $_password, 'password', $_encrypt, $_mustChange);
        $this->_setPluginsPassword($user, $_password, $_encrypt);

        // fire needed events
        $event = new Tinebase_Event_User_ChangePassword();
        $event->userId = $userId;
        $event->password = $_password;
        Tinebase_Event::fireEvent($event);

        $accountData['id'] = $userId;
        $oldPassword = new Tinebase_Model_UserPassword(array('id' => $userId), true);
        $newPassword = new Tinebase_Model_UserPassword($accountData, true);
        $this->_writeModLog($newPassword, $oldPassword);
    }

    /**
     * @param string $_userId
     * @param string $_password
     * @param string $_property
     * @param boolean $_encrypt
     * @param boolean|null $_mustChange user needs to change pw next time
     * @return array $accountData
     * @throws Tinebase_Exception_NotFound
     */
    protected function _updatePasswordProperty($_userId, $_password, $_property = 'password', $_encrypt = true, $_mustChange = null)
    {
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . $this->_tableName));

        $accountData = array();
        $accountData[$_property] = ($_encrypt) ? Hash_Password::generate('SSHA256', $_password) : $_password;
        if ($_property === 'password') {
            if (Tinebase_Auth_NtlmV2::isEnabled()) {
                $accountData['ntlmv2hash'] = Tinebase_Auth_CredentialCache::encryptData(
                    Tinebase_Auth_NtlmV2::getPwdHash($_password),
                    Tinebase_Config::getInstance()->{Tinebase_Config::PASSWORD_NTLMV2_ENCRYPTION_KEY});
            }
            $accountData['last_password_change'] = Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
            $accountData['password_must_change'] = $_mustChange ? 1 : 0;
        }

        $where = array(
            $accountsTable->getAdapter()->quoteInto($accountsTable->getAdapter()->quoteIdentifier('id') . ' = ?', $_userId)
        );

        $result = $accountsTable->update($accountData, $where);

        if ($result != 1) {
            throw new Tinebase_Exception_NotFound('Unable to update password! account not found in authentication backend.');
        }

        return $accountData;
    }

    public function updateNtlmV2Hash($_userId, $_password)
    {
        if (Tinebase_Auth_NtlmV2::isEnabled()) {
            $accountData = ['ntlmv2hash' => Tinebase_Auth_CredentialCache::encryptData(
                Tinebase_Auth_NtlmV2::getPwdHash($_password),
                Tinebase_Config::getInstance()->{Tinebase_Config::PASSWORD_NTLMV2_ENCRYPTION_KEY})];

            $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . $this->_tableName));
            $where = array(
                $accountsTable->getAdapter()->quoteInto($accountsTable->getAdapter()->quoteIdentifier('id') . ' = ?', $_userId)
            );

            $result = $accountsTable->update($accountData, $where);

            if ($result != 1) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                    . __LINE__ . ' update did not work!');
            }
        }
    }

    /**
     * set password in plugins
     * 
     * @param Tinebase_Model_User $user
     * @param string $password
     * @param bool $encrypt encrypt password
     * @throws Tinebase_Exception_Backend
     */
    protected function _setPluginsPassword(Tinebase_Model_User $user, $password, $encrypt = true)
    {
        foreach ($this->_sqlPlugins as $plugin) {
            try {
                $userId = $this->_getPluginUserId($plugin, $user);
                $plugin->inspectSetPassword($userId, $password, $encrypt);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Could not change plugin password: ' . $e);
                throw new Tinebase_Exception_Backend($e->getMessage());
            }
        }
    }

    /**
     * @param object $plugin
     * @param object $user
     * @return string
     */
    protected function _getPluginUserId($plugin, $user)
    {
        return Tinebase_EmailUser::isEmailUserPlugin($plugin) && Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}
            ? $user->getEmailUserId()
            : $user->getId();
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
        if ($this instanceof Tinebase_User_Interface_SyncAble) {
            $this->setStatusInSyncBackend($_accountId, $_status);
        }
        
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $oldUser = $this->getUserByPropertyFromSqlBackend('accountId', $accountId);

        $accountData['seq'] = $oldUser->seq + 1;
        switch($_status) {
            case Tinebase_Model_User::ACCOUNT_STATUS_ENABLED:
                $accountData[$this->rowNameMapping['loginFailures']]  = 0;
                $accountData[$this->rowNameMapping['accountExpires']] = null;
                $accountData['status'] = $_status;
                break;
                
            case Tinebase_Model_User::ACCOUNT_STATUS_DISABLED:
                $accountData['status'] = $_status;
                break;
                
            case Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED:
                $expiryDate = Tinebase_DateTime::now()->subSecond(1);
                $accountData['expires_at'] = $expiryDate->toString();
                if ($this instanceof Tinebase_User_Interface_SyncAble) {
                    $this->setExpiryDateInSyncBackend($_accountId, $expiryDate);
                }

                break;
            
            default:
                throw new Tinebase_Exception_InvalidArgument('$_status can be only enabled, disabled or expired');
        }

        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));

        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $accountId)
        );

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
            . ' ' . $_status . ' user with id ' . $accountId);

        $result = $accountsTable->update($accountData, $where);

        $oldUser = new Tinebase_Model_FullUser(array('accountId' => $accountId, 'seq' => $oldUser->seq), true);
        $newUser = new Tinebase_Model_FullUser(array('accountId' => $accountId, 'accountStatus' => $_status, 'seq' => $oldUser->seq + 1), true);
        $this->_writeModLog($newUser, $oldUser);

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
        if ($this instanceof Tinebase_User_Interface_SyncAble) {
            $this->setExpiryDateInSyncBackend($_accountId, $_expiryDate);
        }
        
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $oldUser = $this->getUserByPropertyFromSqlBackend('accountId', $accountId);

        $accountData['seq'] = $oldUser->seq + 1;
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

        $oldUser = new Tinebase_Model_FullUser(array('accountId' => $accountId, 'seq' => $oldUser->seq), true);
        $newUser = new Tinebase_Model_FullUser(array('accountId' => $accountId, 'accountExpires' => $accountData['expires_at'], 'seq' => $oldUser->seq + 1), true);
        $this->_writeModLog($newUser, $oldUser);

        return $result;
    }
    
    /**
     * set last login failure in accounts table
     * 
     * @param string $_loginName
     * @return Tinebase_Model_FullUser|null user if found
     * @see Tinebase/User/Tinebase_User_Interface::setLastLoginFailure()
     */
    public function setLastLoginFailure($_loginName)
    {
        try {
            $user = $this->getUserByPropertyFromSqlBackend('accountLoginName', $_loginName, 'Tinebase_Model_FullUser');
        } catch (Tinebase_Exception_NotFound $tenf) {
            // nothing todo => is no existing user
            return null;
        }
        
        $values = array(
            'last_login_failure_at' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
            'login_failures'        => new Zend_Db_Expr($this->_db->quoteIdentifier('login_failures') . ' + 1')
        );
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $user->getId())
        );
        
        $this->_db->update(SQL_TABLE_PREFIX . 'accounts', $values, $where);

        return $user;
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
     * @param Addressbook_Model_Contact $_contact
     * @return integer
     */
    public function updateContact(Addressbook_Model_Contact $_contact)
    {
        if ($this instanceof Tinebase_User_Interface_SyncAble) {
            $this->updateContactInSyncBackend($_contact);
        }
        
        return $this->updateContactInSqlBackend($_contact);
    }
    
    /**
     * update contact data(first name, last name, ...) of user in local sql storage
     * 
     * @param Addressbook_Model_Contact $_contact
     * @return integer
     * @throws Exception
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function updateContactInSqlBackend(Addressbook_Model_Contact $_contact)
    {
        $contactId = $_contact->getId();

        // prevent changing of email if it does not match configured domains
        Tinebase_EmailUser::checkDomain($_contact->email, true);

        $oldUser = $this->getUserByProperty('contactId', $contactId, 'Tinebase_Model_FullUser');

        $accountData = array(
            $this->rowNameMapping['accountDisplayName']  => $_contact->n_fileas,
            $this->rowNameMapping['accountFullName']     => $_contact->n_fn,
            $this->rowNameMapping['accountFirstName']    => $_contact->n_given,
            $this->rowNameMapping['accountLastName']     => $_contact->n_family,
            $this->rowNameMapping['accountEmailAddress'] => $_contact->email,
            'seq' => $oldUser->seq + 1,
        );
        
        try {
            $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
            
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('contact_id') . ' = ?', $contactId)
            );
            $result = $accountsTable->update($accountData, $where);

            $newUser = $this->getUserByPropertyFromSqlBackend('contactId', $contactId, 'Tinebase_Model_FullUser');
            $this->_writeModLog($newUser, $oldUser);

            return $result;

        } catch (Exception $e) {
            // TODO FIXME this is bad! we really shouldn't just roll back a transaction for which we are not responsible!
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
        $visibility = $_user->visibility;

        if ($this instanceof Tinebase_User_Interface_SyncAble) {
            $this->updateUserInSyncBackend($_user);
        }

        $updatedUser = $this->updateUserInSqlBackend($_user);
        $this->updatePluginUser($updatedUser, $_user);

        $contactId = $updatedUser->contact_id;
        if (!empty($visibility) && !empty($contactId) && $visibility != $updatedUser->visibility) {
            $updatedUser->visibility = $visibility;
            $updatedUser = $this->updateUserInSqlBackend($updatedUser);
            $this->updatePluginUser($updatedUser, $_user);
        }

        if ($updatedUser->xprops() != $_user->xprops()) {
            // update user xprops if necessary (plugin user with xprops might have been updated)
            $updatedUser = $this->updateUserInSqlBackend($updatedUser);
        }

        return $updatedUser;
    }
    
    /**
     * update data in plugins
     *
     * @param Tinebase_Model_FullUser $updatedUser
     * @param Tinebase_Model_FullUser $newUserProperties
     * @param boolean $skipEmailPlugins
     */
    public function updatePluginUser($updatedUser, $newUserProperties, $skipEmailPlugins = false)
    {
        foreach ($this->_sqlPlugins as $plugin) {
            if (! $skipEmailPlugins || ! Tinebase_EmailUser::isEmailUserPlugin($plugin)) {
                $this->_inspectCRUD($plugin, $updatedUser, $newUserProperties, 'update');
            }
        }
    }

    /**
     * @param mixed $plugin
     * @param mixed $user
     * @param mixed $newUserProperties
     * @param string $method (add|update|inspectGetUserByProperty|delete)
     *
     * TODO support different imap/smtp xprops
     */
    protected function _inspectCRUD($plugin, $user, $newUserProperties, $method)
    {
        if ($method !== 'inspectGetUserByProperty') {
            $method = 'inspect' . ucfirst($method) . 'User';
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Calling CRUD method ' . $method . ' in plugin ' . (is_object($plugin) ? get_class($plugin) : $plugin));

        // add email user xprops here if configured
        if (Tinebase_EmailUser::isEmailUserPlugin($plugin) && Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
            $this->_inspectEmailPluginCRUD($plugin, $user, $newUserProperties, $method);
        } else {
            if ($newUserProperties) {
                $params = [$user, $newUserProperties];
            } else {
                $params = [$user];
            }
            call_user_func_array([$plugin, $method], $params);
        }
    }

    /**
     * @param mixed $plugin
     * @param mixed $user
     * @param mixed $newUserProperties
     * @param string $method (inspectAddUser|inspectUpdateUser|inspectGetUserByProperty|inspectDeleteUser)
     * @throws Zend_Db_Adapter_Exception
     */
    protected function _inspectEmailPluginCRUD($plugin, $user, $newUserProperties, string $method)
    {
        if ($method === 'inspectAddUser' && empty($user->accountEmailAddress)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Do not add plugin user as user has no email address');
            return;
        }

        $xprop = strpos(get_class($plugin), 'Imap') !== false
            ? Tinebase_EmailUser_XpropsFacade::XPROP_EMAIL_USERID_IMAP
            : Tinebase_EmailUser_XpropsFacade::XPROP_EMAIL_USERID_SMTP;

        if ($method === 'inspectUpdateUser' && empty($user->accountEmailAddress)
            && isset($user->xprops()[$xprop]) && $user->xprops()[$xprop]
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Remove plugin user as email address has been removed');
            $this->_inspectEmailPluginCRUD($plugin, $user, $newUserProperties, 'inspectDeleteUser');
            return;
        }

        $pluginUser = clone($user);
        Tinebase_EmailUser_XpropsFacade::setIdFromXprops($user, $pluginUser, $method !== 'inspectGetUserByProperty', $xprop);
        if ($newUserProperties) {
            $updatePluginUser = clone($newUserProperties);
            Tinebase_EmailUser_XpropsFacade::setIdFromXprops($user, $updatePluginUser);
            $params = [$pluginUser, $updatePluginUser];
        } else {
            $params = [$pluginUser];
            $updatePluginUser = null;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' Calling ' . get_class($plugin) . '::' . $method);

        try {
            call_user_func_array([$plugin, $method], $params);
        } catch (Zend_Db_Adapter_Exception $zdae) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . ' ' . $zdae
            );
            if ($method === 'inspectGetUserByProperty') {
                return;
            } else {
                throw $zdae;
            }
        }

        // return email user properties to $user
        // TODO do this always?
        // TODO remove on inspectDeleteUser?
        if ($method === 'inspectGetUserByProperty' || $method === 'inspectUpdateUser') {
            foreach (['smtpUser', 'emailUser', 'imapUser'] as $prop) {
                if ($pluginUser->{$prop}) {
                    $user->{$prop} = $pluginUser->{$prop};
                }
            }
        }

        if ($updatePluginUser) {
            if ($method === 'inspectDeleteUser') {
                $user->xprops()[$xprop] = null;
            } else {
                $user->xprops()[$xprop] = $updatePluginUser->getId();
            }
        }
    }

    /**
     * @param Tinebase_Model_FullUser $_user
     * @param Tinebase_Model_FullUser|null $oldUser
     * @return void
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function treatMFA(Tinebase_Model_FullUser $_user, ?Tinebase_Model_FullUser $oldUser = null): void
    {
        if ($_user->mfa_configs) {
            if (!$_user->mfa_configs->isValid()) {
                $translation = Tinebase_Translation::getTranslation();
                throw new Tinebase_Exception_SystemGeneric($translation->_('MFA configs are not valid:')
                    . ' ' .  print_r($_user->mfa_configs->getValidationErrors(), true));
            }
            if ($oldUser && $oldUser->mfa_configs) {
                /** @var Tinebase_Model_MFA_UserConfig $userCfg */
                foreach ($oldUser->mfa_configs as $userCfg) {
                    $userCfg->updateUserOldRecordCallback($_user, $oldUser);
                }
            }
            foreach ($_user->mfa_configs as $userCfg) {
                $userCfg->updateUserNewRecordCallback($_user, $oldUser);
            }
        }
    }

    /**
     * updates a user
     *
     * @param Tinebase_Model_FullUser $_user
     * @param bool $_getDeleted
     * @return Tinebase_Model_FullUser|Tinebase_Model_User
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function updateUserInSqlBackend(Tinebase_Model_FullUser $_user, bool $_getDeleted = false)
    {
        if(! $_user->isValid()) {
            throw new Tinebase_Exception_Record_Validation('Invalid user object. ' . print_r($_user->getValidationErrors(), TRUE));
        }

        Tinebase_EmailUser::checkDomain($_user->accountEmailAddress, true, null, true);

        $accountId = Tinebase_Model_User::convertUserIdToInt($_user);

        $oldUser = $this->getFullUserById($accountId, $_getDeleted);
        
        if (empty($_user->contact_id)) {
            $_user->visibility = 'hidden';
            $_user->contact_id = null;
        }
        $this->treatMFA($_user, $oldUser);
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($_user, !$_user->is_deleted && $oldUser->is_deleted ? 'undelete' : 'update', $oldUser);
        $accountData = $this->_recordToRawData($_user);
        // don't update id
        unset($accountData['id']);
        
        // ignore all other states (blocked)
        unset($accountData[$this->rowNameMapping['accountStatus']]);
        if ($_user->accountStatus === Tinebase_User::STATUS_ENABLED) {
            $accountData[$this->rowNameMapping['accountStatus']] = $_user->accountStatus;
            
            if ($oldUser->accountStatus === Tinebase_User::STATUS_BLOCKED) {
                $accountData[$this->rowNameMapping['loginFailures']] = 0;
            } elseif ($oldUser->accountStatus === Tinebase_User::STATUS_EXPIRED) {
                $accountData[$this->rowNameMapping['accountExpires']] = null;
            }
        } elseif ($_user->accountStatus === Tinebase_User::STATUS_DISABLED ||
                Tinebase_User::STATUS_EXPIRED === $_user->accountStatus) {
            $accountData[$this->rowNameMapping['accountStatus']] = $_user->accountStatus;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($accountData, true));

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
        
        $newUser = $this->getUserById($accountId, 'Tinebase_Model_FullUser');

        $this->_writeModLog($newUser, $oldUser);

        return $newUser;
    }
    
    /**
     * add an user
     * 
     * @param   Tinebase_Model_FullUser  $_user
     * @return  Tinebase_Model_FullUser
     */
    public function addUser(Tinebase_Model_FullUser $_user)
    {
        $visibility = $_user->visibility;

        $_user->applyTwigTemplates();

        if ($this instanceof Tinebase_User_Interface_SyncAble) {
            $userFromSyncBackend = $this->addUserToSyncBackend($_user);
            if ($userFromSyncBackend !== NULL) {
                // set accountId for sql backend sql backend
                $_user->setId($userFromSyncBackend->getId());
            }
        }

        $addedUser = $this->addUserInSqlBackend($_user);
        $this->addPluginUser($addedUser, $_user);

        $contactId = $addedUser->contact_id;
        if (!empty($visibility) && !empty($contactId) && $visibility != $addedUser->visibility) {
            $addedUser->visibility = $visibility;
            $addedUser = $this->updateUserInSqlBackend($addedUser);
            $this->updatePluginUser($addedUser, $_user, true);
        }

        if ($addedUser->xprops() != $_user->xprops()) {
            // update user xprops
            $addedUser = $this->updateUserInSqlBackend($addedUser);
        }

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
            $this->_inspectCRUD($plugin, $addedUser, $newUserProperties, 'add');
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
        Tinebase_EmailUser::checkDomain($_user->accountEmailAddress, true, null, true);

        $_user->isValid(TRUE);
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        if(!isset($_user->accountId)) {
            $userId = $_user->generateUID();
            $_user->setId($userId);
        }

        $contactId = $_user->contact_id;
        if (empty($contactId)) {
            $_user->visibility = Tinebase_Model_FullUser::VISIBILITY_HIDDEN;
            $_user->contact_id = null;
        }
        $this->treatMFA($_user);
        
        $accountData = $this->_recordToRawData($_user);
        // persist status for new users!
        $accountData['status'] = $_user->accountStatus;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Adding user to SQL backend: ' . $_user->accountLoginName);
        
        $accountsTable->insert($accountData);

        $this->_writeModLog($_user, null);

        // we don't need the data from the plugins yet
        $createdUser = $this->getUserByPropertyFromSqlBackend('accountId', $_user->getId(), 'Tinebase_Model_FullUser');

        Tinebase_Event::fireEvent(new Tinebase_Event_User_CreatedAccount(['account' => $createdUser]));

        return $createdUser;
    }
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Interface $_user
     * @return array
     */
    protected function _recordToRawData(Tinebase_Record_Interface $_user)
    {
        $_user->runConvertToData();

        $accountData = array(
            'id'                => $_user->accountId,
            'login_name'        => $_user->accountLoginName,
            'expires_at'        => ($_user->accountExpires instanceof Tinebase_DateTime ? $_user->accountExpires->get(Tinebase_Record_Abstract::ISO8601LONG) : NULL),
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
            $this->rowNameMapping['password_must_change'] => $_user->password_must_change ? 1 : 0,
            'created_by'            => $_user->created_by,
            'creation_time'         => $_user->creation_time,
            'last_modified_by'      => $_user->last_modified_by,
            'last_modified_time'    => $_user->last_modified_time,
            'is_deleted'            => $_user->is_deleted ?: 0,
            'deleted_time'          => $_user->deleted_time,
            'deleted_by'            => $_user->deleted_by,
            'seq'                   => $_user->seq,
            'xprops'                => $_user->xprops,
            'mfa_configs'     => $_user->mfa_configs,
        );
        
        $unsetIfEmpty = array('seq', 'creation_time', 'created_by', 'last_modified_by', 'last_modified_time');
        foreach ($unsetIfEmpty as $property) {
            if (empty($accountData[$property])) {
                unset($accountData[$property]);
            }
        }

        if (!$this->_userHasXpropsField()) {
            unset($accountData['xprops']);
        }

        $_user->runConvertToRecord();

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
        
        if ($this instanceof Tinebase_User_Interface_SyncAble) {
            $this->deleteUserInSyncBackend($deletedUser);
        }
        
        // update data from plugins
        foreach ($this->_sqlPlugins as $plugin) {
            $this->_inspectCRUD($plugin, $deletedUser, null, 'delete');
        }
    }

    /**
     * delete a user (delayed; its marked deleted, disabled, hidden and stripped from groups and roles immediately. Full delete and event are fired "async" via actionQueue)
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
            . ' Deleting user ' . $user->accountLoginName . ' ID: ' . $user->getId());

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

        try {
            $this->_softDelete($user);
            Tinebase_ActionQueue::getInstance()->queueAction('Tinebase_FOO_User.fireDeleteUserEvent', $user->getId());

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' error while deleting account ' . $e->__toString());
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw($e);
        }

        return $user;
    }

    protected function _softDelete($user)
    {
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $user->getId()),
        );
        $accountsTable->update(array('deleted_by' => Tinebase_Core::getUser()->getId(),
            'deleted_time' => Tinebase_DateTime::now()->toString(),
            'is_deleted' => 1,
            'seq' => $user->seq + 1,
            'status' => Tinebase_Model_User::ACCOUNT_STATUS_DISABLED,
            'visibility' => Tinebase_Model_User::VISIBILITY_HIDDEN), $where);
        $user->seq = $user->seq + 1;
        $this->_writeModLog(null, $user);
    }

    public function undelete(string $loginname)
    {
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('login_name') . ' = ?', $loginname),
        );
        $accountsTable->update(array(
            'deleted_time' => '1970-01-01 00:00:00',
            'is_deleted' => 0,
        ), $where);
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
        $users = $this->getUsers(null, null, 'ASC', null, null,
            Tinebase_Model_FullUser::class);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Deleting ' . count($users) .' users');
        foreach ($users as $user ) {
            try {
                $this->deleteUser($user);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        }
    }

    /**
     * Get multiple users
     *
     * fetch FullUser by default
     *
     * @param  string|array $_id Ids
     * @param  string  $_accountClass  type of model to return
     * @param  bool     $_getDeleted
     * @return Tinebase_Record_RecordSet of 'Tinebase_Model_User' or 'Tinebase_Model_FullUser'
     */
    public function getMultiple($_id, $_accountClass = 'Tinebase_Model_User', bool $_getDeleted = false)
    {
        if (empty($_id)) {
            return new Tinebase_Record_RecordSet($_accountClass);
        }
        
        $select = $this->_getUserSelectObject($_getDeleted)
            ->where($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'accounts.id') . ' in (?)', (array) $_id);
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        $stmt->closeCursor();
        
        $result = new Tinebase_Record_RecordSet($_accountClass, $queryResult, TRUE);
        $result->runConvertToRecord();

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
            $oldValue = Tinebase_Notification::getInstance()->blockDisabledAccounts(false);
            try {
                Tinebase_Notification::getInstance()->send(null, array($recipient), $messageSubject, $messageBody);
            } finally {
                Tinebase_Notification::getInstance()->blockDisabledAccounts($oldValue);
            }
            
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
        }
    }

    /**
     * returns number of current not-disabled, non-system users
     *
     * @return string
     */
    public function countNonSystemUsers()
    {
        $systemUsers = Tinebase_User::getSystemUsernames();
        $select = $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', 'COUNT(id)')
            ->where($this->_db->quoteIdentifier('login_name') . ' not in (?)', $systemUsers)
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('status') . ' != ?', Tinebase_Model_User::ACCOUNT_STATUS_DISABLED))
            ->where($this->_db->quoteIdentifier('is_deleted') . ' = 0');

        $userCount = $this->_db->fetchOne($select);

        return $userCount;
    }

    /**
     * @param string $accountId
     * @return boolean|string
     */
    public function getNtlmV2Hash($accountId)
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', 'ntlmv2hash')
            ->where($this->_db->quoteIdentifier('id') . ' = ?', $accountId);

        return Tinebase_Auth_CredentialCache::decryptData($this->_db->fetchOne($select),
            Tinebase_Config::getInstance()->{Tinebase_Config::PASSWORD_NTLMV2_ENCRYPTION_KEY});
    }

    /**
     * get user pw hash
     *
     * @param string $accountLoginName
     * @return ?string
     */
    public function getPasswordHashByLoginname(string $accountLoginName): ?string
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', 'password')
            ->where($this->_db->quoteIdentifier('login_name') . ' = ?', $accountLoginName);

        return $this->_db->fetchOne($select);
    }

    /**
     * fetch creation time of the first/oldest user
     *
     * @return Tinebase_DateTime
     */
    public function getFirstUserCreationTime()
    {
        $fallback = new Tinebase_DateTime('2014-12-01');
        if (! $this->_userTableHasModlogFields()) {
            return $fallback;
        }

        $systemUsers = Tinebase_User::getSystemUsernames();
        $select = $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', 'creation_time')
            ->where($this->_db->quoteIdentifier('login_name') . ' not in (?)', $systemUsers)
            ->where($this->_db->quoteIdentifier('creation_time') . " is not null")
            ->order('creation_time ASC')
            ->limit(1);
        $creationTime = $this->_db->fetchOne($select);

        $result = (!empty($creationTime)) ? new Tinebase_DateTime($creationTime) : $fallback;
        return $result;
    }

    /**
     * checks if user table already has modlog fields
     *
     * @return bool
     */
    protected function _userTableHasModlogFields()
    {
        $schema = Tinebase_Db_Table::getTableDescriptionFromCache($this->_db->table_prefix . $this->_tableName, $this->_db);
        return isset($schema['creation_time']);
    }

    /**
     * checks if user table already has xprops field
     *
     * @return bool
     */
    protected function _userHasXpropsField()
    {
        $schema = Tinebase_Db_Table::getTableDescriptionFromCache($this->_db->table_prefix . $this->_tableName, $this->_db);
        return isset($schema['xprops']);
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
            'modlogActive' => true,
        ));

        $userIds = $sqlbackend->search(null, null, Tinebase_Backend_Sql_Abstract::IDCOL);
        return $userIds;
    }

    /**
     * get user by property from backend
     *
     * @param   string  $_property      the key to filter
     * @param   string  $_value         the value to search for
     * @param   string  $_accountClass  type of model to return
     *
     * @return  Tinebase_Model_User the user object
     */
    public function getUserByPropertyFromBackend($_property, $_value, $_accountClass = 'Tinebase_Model_User')
    {
        return $this->getUserByPropertyFromSqlBackend($_property, $_value, $_accountClass);
    }

    /**
     * @param Tinebase_Model_ModificationLog $modification
     */
    public function applyReplicationModificationLog(Tinebase_Model_ModificationLog $modification)
    {
        switch ($modification->change_type) {
            case Tinebase_Timemachine_ModificationLog::CREATED:
                $diff = new Tinebase_Record_Diff(json_decode($modification->new_value, true));
                $record = new Tinebase_Model_FullUser($diff->diff);
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($record, 'create');
                $this->_addJustEmailDomainAfterReplication($record);
                $this->addUser($record);
                break;

            case Tinebase_Timemachine_ModificationLog::UPDATED:
                $diff = new Tinebase_Record_Diff(json_decode($modification->new_value, true));

                try {
                    if (isset($diff->diff['password'])) {
                        $diffArray = $diff->diff;
                        $oldDataArray = $diff->oldData;
                        $this->setPassword($modification->record_id, $diffArray['password'], false);
                        unset($diffArray['password']);
                        unset($diffArray['last_password_change']);
                        unset($oldDataArray['password']);
                        unset($oldDataArray['last_password_change']);
                        $diff->diff = $diffArray;
                        $diff->oldData = $oldDataArray;
                    }

                    if (!$diff->isEmpty()) {
                        /** @var Tinebase_Model_FullUser $record */
                        $record = $this->getUserById($modification->record_id, 'Tinebase_Model_FullUser');
                        $currentRecord = clone $record;
                        $record->applyDiff($diff);
                        Tinebase_Timemachine_ModificationLog::setRecordMetaData($record, 'update', $currentRecord);
                        if (isset($diff->diff['accountEmailAddress'])) {
                            $this->_addJustEmailDomainAfterReplication($record);
                        }
                        $this->updateUser($record);
                    }
                } catch (Tinebase_Exception_NotFound $e) {
                    if (strpos($e->getMessage(), 'User with accountId') !== 0) throw $e;
                }
                break;

            case Tinebase_Timemachine_ModificationLog::DELETED:
                try {
                    $this->deleteUser($modification->record_id);
                } catch (Tinebase_Exception_NotFound $e) {
                    if (strpos($e->getMessage(), 'User with accountId') !== 0) throw $e;
                }
                break;

            default:
                throw new Tinebase_Exception('unknown Tinebase_Model_ModificationLog->old_value: ' . $modification->old_value);
        }
    }

    /**
     * @param Tinebase_Model_FullUser $user
     */
    protected function _addJustEmailDomainAfterReplication(Tinebase_Model_FullUser $user)
    {
        if (empty($user->accountEmailAddress) || strpos($user->accountEmailAddress, '@') === false) {
            return;
        }
        if (empty($config = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP)->toArray()) ||
                !isset($config['primarydomain'])) {
            return;
        }
        list($userPart, /*$domainPart*/) = explode('@', $user->accountEmailAddress);
        $user->accountEmailAddress = $userPart . '@' . $config['primarydomain'];
    }
}
