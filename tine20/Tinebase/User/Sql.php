<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
        'lastLoginFailure'			=> 'last_login_failure_at',
        'loginFailures'				=> 'login_failures',
        'openid'                    => 'openid',
        'visibility'                => 'visibility',
        'contactId'					=> 'contact_id'
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
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);

        $this->_db = Tinebase_Core::getDb();
        
        foreach ($this->_plugins as $plugin) {
            if ($plugin instanceof Tinebase_User_Plugin_SqlInterface) {
                $this->_sqlPlugins[] = $plugin;
            }
        }
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
                $this->rowNameMapping['accountLoginName']
            );
            foreach ($defaultValues as $defaultValue) {
                $whereStatement[] = $this->_db->quoteIdentifier($defaultValue) . 'LIKE ?';
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
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' User sql plugin failure: ' . $e->getMessage());
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
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
     */
    public function getUserByPropertyFromSqlBackend($_property, $_value, $_accountClass = 'Tinebase_Model_User')
    {
        if(!array_key_exists($_property, $this->rowNameMapping)) {
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
         * CASE WHEN `status` = 'enabled' THEN (CASE WHEN NOW() > `expires_at` THEN 'expired' 
         * WHEN (`login_failures` > 5 AND `last_login_failure_at` + INTERVAL 15 MINUTE > NOW()) 
         * THEN 'blocked' ELSE 'enabled' END) ELSE 'disabled' END
         */
        $statusSQL = 'CASE WHEN ' . $this->_db->quoteIdentifier($this->rowNameMapping['accountStatus']) . ' = ' . $this->_db->quote('enabled') . ' THEN (CASE WHEN NOW() > ' . $this->_db->quoteIdentifier($this->rowNameMapping['accountExpires']) . ' THEN ' . $this->_db->quote('expired') . 
            ' WHEN (' . $this->_db->quoteIdentifier($this->rowNameMapping['loginFailures']) . " > {$this->_maxLoginFailures} AND " . 
                $this->_db->quoteIdentifier($this->rowNameMapping['lastLoginFailure']) . " + INTERVAL '{$this->_blockTime}' MINUTE > NOW()) THEN 'blocked'" . 
            ' ELSE ' . $this->_db->quote('enabled') . ' END) ELSE ' . $this->_db->quote('disabled') . ' END';
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', 
                array(
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
                    'visibility'
                )
            )
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
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        $accountData['password'] = ( $_encrypt ) ? Hash_Password::generate('SSHA256', $_password) : $_password;
        $accountData['last_password_change'] = Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        $where = array(
            $accountsTable->getAdapter()->quoteInto($accountsTable->getAdapter()->quoteIdentifier('id') . ' = ?', $userId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        if ($result != 1) {
            throw new Tinebase_Exception_NotFound('Unable to update password! account not found in authentication backend.');
        }
        
        // add data from plugins
        foreach ($this->_sqlPlugins as $plugin) {
            $plugin->inspectSetPassword($userId, $_password);
        }
    }
    
    /**
     * set the status of the user
     *
     * @param mixed   $_accountId
     * @param string  $_status
     * @return unknown
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
                $accountData['expires_at'] = Tinebase_DateTime::getTimestamp();
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
        Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' Login of user ' . $_loginName . ' failed.');
        
        try {
            $user = $this->getUserByLoginName($_loginName);
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
     * @return void
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
     */
    public function updateContactInSqlBackend(Addressbook_Model_Contact $_contact)
    {
        $contactId = $_contact->getId();

        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));

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
            $accountsTable->update($accountData, $where);

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
        
        // update data from plugins
        foreach ($this->_sqlPlugins as $plugin) {
            $plugin->inspectUpdateUser($updatedUser, $_user);
        }
        
        return $updatedUser;
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
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        if (empty($_user->contact_id)) {
            $_user->visibility = 'hidden';
            $_user->contact_id = null;
        }
        
        $accountData = array(
            'login_name'        => $_user->accountLoginName,
            'expires_at'        => ($_user->accountExpires instanceof DateTime ? $_user->accountExpires->get(Tinebase_Record_Abstract::ISO8601LONG) : NULL),
            'primary_group_id'  => $_user->accountPrimaryGroup,
            'home_dir'          => $_user->accountHomeDirectory,
            'login_shell'       => $_user->accountLoginShell,
            'openid'            => $_user->openid,
            'visibility'        => $_user->visibility,
        	'contact_id'		=> $_user->contact_id,
            $this->rowNameMapping['accountDisplayName']  => $_user->accountDisplayName,
            $this->rowNameMapping['accountFullName']     => $_user->accountFullName,
            $this->rowNameMapping['accountFirstName']    => $_user->accountFirstName,
            $this->rowNameMapping['accountLastName']     => $_user->accountLastName,
            $this->rowNameMapping['accountEmailAddress'] => $_user->accountEmailAddress,
        );
        
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
            // set accountId for sql backend sql backend
            $_user->setId($userFromSyncBackend->getId());
        }
        
        $addedUser = $this->addUserInSqlBackend($_user);
        
        // add data from plugins
        foreach ($this->_sqlPlugins as $plugin) {
            $plugin->inspectAddUser($addedUser, $_user);
        }
        
        return $addedUser;
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
        if(!$_user->isValid()) {
            throw(new Exception('invalid user object'));
        }
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        if(!isset($_user->accountId)) {
            $userId = $_user->generateUID();
            $_user->setId($userId);
        }
        
        if (empty($_user->contact_id)) {
            $_user->visibility = 'hidden';
            $_user->contact_id = null;
        }
        
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
            'contact_id'		=> $_user->contact_id,
            $this->rowNameMapping['accountDisplayName']  => $_user->accountDisplayName,
            $this->rowNameMapping['accountFullName']     => $_user->accountFullName,
            $this->rowNameMapping['accountFirstName']    => $_user->accountFirstName,
            $this->rowNameMapping['accountLastName']     => $_user->accountLastName,
            $this->rowNameMapping['accountEmailAddress'] => $_user->accountEmailAddress,
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding user to SQL backend: ' . $_user->accountLoginName);
        
        $accountsTable->insert($accountData);
            
        return $this->getUserById($_user->getId(), 'Tinebase_Model_FullUser');
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
        
        $accountsTable          = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        $groupMembersTable      = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'group_members'));
        $roleMembersTable       = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'role_accounts'));
        $userRegistrationsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'registrations'));
        
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

            $where  = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('login_name') . ' = ?', $user->accountLoginName),
            );
            $userRegistrationsTable->delete($where);
            
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
    public function deleteUsers(array $_accountIds) {
        foreach ( $_accountIds as $accountId ) {
            $this->deleteUser($accountId);
        }
    }
    
    /**
     * Delete all users returned by {@see getUsers()} using {@see deleteUsers()}
     * @return void
     */
    public function deleteAllUsers()
    {
        $users = $this->getUsers();
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting ' . count($users) .' users');
        foreach ( $users as $user ) {
            $this->deleteUser($user);
        }
    }

    /**
     * Get multiple users
     *
     * @param 	string|array $_id Ids
     * @param   string  $_accountClass  type of model to return
     * @return Tinebase_Record_RecordSet of 'Tinebase_Model_User' or 'Tinebase_Model_FullUser'
     */
    public function getMultiple($_id, $_accountClass = 'Tinebase_Model_User') 
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
}
