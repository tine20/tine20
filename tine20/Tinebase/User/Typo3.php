<?php

/**
 * Simple Read-Only Typo3 User Backend
 * 
 * Setup: 
 *  - Do a normal Tine 2.0 install with SQL Users Backend
 *  - run the following SQL:
 *      UPDATE `tine20_config` SET `value` = 'Typo3' WHERE `name` LIKE 'Tinebase_User_BackendType';
 *      DELETE FROM `tine20_group_members` WHERE 1;
 *      DELETE FROM `tine20_accounts` WHERE 1;
 *      
 * NOTE: At the moment we assume typo3 and tine20 share a common database
 * 
 * NOTE: As we import the password, normal SQL auth adapter could be taken
 * 
 * NOTE: We assume the Tine 2.0 Installation to have the default user and admin groups
 *       which are not part of the typo3 group system. Typo3 admins will be imported
 *       into the default admin group, others into the default user group.
 *       
 * This class does nothing more than importing Typo3 backendusers
 * into the Tine 2.0 user tables.
 */
class Tinebase_User_Typo3 extends Tinebase_User_Sql
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_t3db;
    
    /**
     * construct a typo3 user backend
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->_t3db = Tinebase_Core::getDb();
        $this->_sqlUserBackend = new Tinebase_User_Sql();
    }
    
    /**
     * direct mapping
     *
     * @var array
     */
    protected $_rowNameMapping = array(
        'accountId'                 => 'uid',
        'accountDisplayName'        => 'realName',
        //'accountFullName'           => 'cn',
        //'accountFirstName'          => 'givenname',
        //'accountLastName'           => 'sn',
        'accountLoginName'          => 'username',
        'accountLastLogin'          => 'lastlogin',
        'accountExpires'            => 'endtime',
        'accountStatus'             => 'disable',
        'accountPrimaryGroup'       => 'usergroup',
        'accountEmailAddress'       => 'email',
    );
    
    /**
     * setPassword() - sets / updates the password in the account backend
     *
     * @param string $_loginName
     * @param string $_password
     * @param bool   $_encrypt encrypt password
     * @return void
     */
    public function setPassword($_loginName, $_password, $_encrypt = TRUE)
    {
        throw new Tinebase_Exception_AccessDenied();
    }
    
    /**
     * update user status
     *
     * @param   int         $_accountId
     * @param   string      $_status
     */
    public function setStatus($_accountId, $_status)
    {
        throw new Tinebase_Exception_AccessDenied();
    }

    /**
     * sets/unsets expiry date (calls backend class with the same name)
     *
     * @param   int         $_accountId
     * @param   Zend_Date   $_expiryDate
    */
    public function setExpiryDate($_accountId, $_expiryDate)
    {
        throw new Tinebase_Exception_AccessDenied();
    }

    /**
     * blocks/unblocks the user (calls backend class with the same name)
     *
     * @param   int $_accountId
     * @param   Zend_Date   $_blockedUntilDate
    */
    public function setBlockedDate($_accountId, $_blockedUntilDate)
    {
        throw new Tinebase_Exception_AccessDenied();
    }
    
    /**
     * updates an existing user
     *
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    public function updateUser(Tinebase_Model_FullUser $_account)
    {
        throw new Tinebase_Exception_AccessDenied();
    }

    /**
     * adds a new user
     *
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    public function addUser(Tinebase_Model_FullUser $_account)
    {
        throw new Tinebase_Exception_AccessDenied();
    }
    
    /**
     * add or update an user
     *
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    public function addOrUpdateUser(Tinebase_Model_FullUser $_account)
    {
        throw new Tinebase_Exception_AccessDenied();
    }
    
    /**
     * delete an user
     *
     * @param int $_accountId
     */
    public function deleteUser($_accountId)
    {
        throw new Tinebase_Exception_AccessDenied();
    }

    /**
     * delete multiple users
     *
     * @param array $_accountIds
     */
    public function deleteUsers(array $_accountIds)
    {
        throw new Tinebase_Exception_AccessDenied();
    }
    
    /**
     * import users from typo3
     * 
     * @param array | optional $_options [options hash passed through the whole setup initialization process]
     *
     */
    public function importUsers($_options = null)
    {
        $sqlGroupBackend = new Tinebase_Group_Sql();
        
        $t3users = $this->_getUsersFromBackend(NULL, 'Tinebase_Model_FullUser');
        
        foreach($t3users as $user) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' user: ' . print_r($user->toArray(), true));
            $user->sanitizeAccountPrimaryGroup();
            $user = $this->_sqlUserBackend->addOrUpdateUser($user);
            if (!$user instanceof Tinebase_Model_FullUser) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not add user "' . $user->accountLoginName . '" => Skipping');
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' classname ' . get_class($user). ' attributes: ' . print_r($user,1));
                continue;
            }
            $sqlGroupBackend->addGroupMember($user->accountPrimaryGroup, $user);
            
            // we directly can import password as its also md5
            $select = $this->_t3db->select()->from('be_users')->where("`uid` LIKE '{$user->getId()}'");
            $t3user = $select->query()->fetchAll(Zend_Db::FETCH_ASSOC);
            $md5passwd = $t3user[0]['password'];
            
            // import contactdata(phone, address, fax, birthday. photo)
            //$contact = $this->_getContactFromBackend($user);
            //Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL)->update($contact);
        }
    }
    
    
    protected function _getUsersFromBackend($_filter, $_accountClass = 'Tinebase_Model_User')
    {
        $users = new Tinebase_Record_RecordSet($_accountClass);
        
        $select = $this->_t3db->select()
            ->from('be_users');
            
        $usersData = $select->query()->fetchAll(Zend_Db::FETCH_ASSOC);
        
        foreach ((array) $usersData as $userData) {
            $userObject = $this->_t32user($userData);
            $users->addRecord($userObject);
        }
        
        return $users;
    }
    
    protected function _t32user($t3userData)
    {
        $userData = array();
        foreach($this->_rowNameMapping as $tineField => $t3field) {
            $userData[$tineField] = $t3userData[$t3field];
        }
        
        // additional names required by tine 2.0
        $userData['accountFullName'] = empty($userData['accountFullName']) ? $userData['accountLoginName'] : $userData['accountFullName'];
        $userData['accountLastName'] = strrpos($userData['accountFullName'], ' ') !== FALSE ? substr($userData['accountFullName'], strrpos($userData['accountFullName'], ' ')) : $userData['accountFullName'];
        $userData['accountDisplayName'] = $userData['accountFullName'];
        
        // NOTE: typo3 users might have no group at all
        if ($userData['admin'] === 1) {
            $userData['accountPrimaryGroup'] = Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId();
        } else {
            $userData['accountPrimaryGroup'] = Tinebase_Group::getInstance()->getDefaultGroup()->getId();
        } 
        
        // convert state
        $userData['accountStatus'] = $userData['accountStatus'] ? 'disabled' : 'enabled';
        
        // convert datetimes
        foreach (array('accountLastLogin', 'accountExpires') as $dateTimeField) {
            if (empty($userData[$dateTimeField])) {
                unset($userData[$dateTimeField]);
            } else {
                $userData[$dateTimeField] = new Zend_Date($userData[$dateTimeField], Zend_Date::TIMESTAMP);
            }
        }
        
        $user = new Tinebase_Model_FullUser($userData);
        return $user;
    }

    
}