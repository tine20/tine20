<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle
 * */

/**
 * plugin to handle sql email accounts
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
abstract class Tinebase_EmailUser_Sql extends Tinebase_User_Plugin_Abstract
{
    /**
     * user table name with prefix
     *
     * @var string
     */
    protected $_userTable = NULL;
    
    /**
     * email user config
     * 
     * @var array 
     */
     protected $_config = array();
    
    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_propertyMapping = array();

    /**
     * config key (IMAP/SMTP)
     * 
     * @var string
     */
    protected $_configKey = NULL;
    
    /**
     * subconfig for user email backend (for example: dovecot)
     * 
     * @var string
     */
    protected $_subconfigKey =  NULL;
    
    /**
    * client id
    *
    * @var string
    */
    protected $_clientId = NULL;
    
    /**
     * the constructor
     * 
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        if ($this->_configKey === NULL || $this->_subconfigKey === NULL) {
            throw new Tinebase_Exception_UnexpectedValue('Need config keys for this backend');
        }
        
        // get email user backend config options (host, dbname, username, password, port)
        $emailConfig = Tinebase_Config::getInstance()->get($this->_configKey, new Tinebase_Config_Struct())->toArray();
        
        // merge _config and email backend config
        $this->_config = array_merge($emailConfig[$this->_subconfigKey], $this->_config);
        
        // _tablename (for example "dovecot_users")
        $this->_userTable = $this->_config['prefix'] . $this->_config['userTable'];
        
        // connect to DB
        $this->_getDB($this->_config);
        $this->_dbCommand = Tinebase_Backend_Sql_Command::factory($this->_db);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_config, TRUE));
    }
    
    /**
     * get new email user
     * 
     * @param  Tinebase_Model_FullUser   $_user
     * @return Tinebase_Model_EmailUser
     */
    public function getNewUser(Tinebase_Model_FullUser $_user)
    {
        $result = new Tinebase_Model_EmailUser(array(
            'emailUserId'     => $_user->getId(),
            'emailUsername' => $this->_appendDomain($_user->accountLoginName)
        ));
        
        return $result;
    }
    
    /**
    * delete user by id
    *
    * @param  Tinebase_Model_FullUser  $_user
    */
    public function inspectDeleteUser(Tinebase_Model_FullUser $_user)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Delete ' . $this->_configKey . ' email settings for user ' . $_user->accountLoginName);
        
        $this->_deleteUserById($_user->getId());
    }
    
    /**
     * delete user by id
     * 
     * @param string $id
     */
    protected function _deleteUserById($id)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $id)
        );
        $this->_appendClientIdOrDomain($where);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' ' . print_r($where, TRUE));
    
        $this->_db->delete($this->_userTable, $where);
    }
    
    /**
     * append domain if set or domain IS NULL
     * 
     * @param array $where
     * @return string
     * 
     * @todo check if user table has domain or client_idnr field and use mapping for the field identifier
     */
    protected function _appendClientIdOrDomain(&$where = NULL)
    {
        if ($this->_clientId !== NULL) {
            $cond = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userTable . '.' . 'client_idnr') . ' = ?', $this->_clientId);
        } else {
            if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
                $cond = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . ' = ?',   $this->_config['domain']);
            } else {
                $cond = $this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . " =''";
            }
        }
        
        if ($where !== NULL) {
            $where[] = $cond;
        }
        
        return $cond;
    }
    
    /**
     * inspect get user by property
     * 
     * @param Tinebase_Model_User  $_user  the user object
     */
    public function inspectGetUserByProperty(Tinebase_Model_User $_user)
    {
        if (! $_user instanceof Tinebase_Model_FullUser) {
            return;
        }
        
        $userId = $_user->getId();
        
        $select = $this->_getSelect()
            ->where($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUserId']) . ' = ?',   $userId);
        
        // Perform query - retrieve user from database
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
        
        if (!$queryResult) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' ' . $this->_subconfigKey . ' config for user ' . $userId . ' not found!');
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));
        
        // convert data to Tinebase_Model_EmailUser       
        $emailUser = $this->_rawDataToRecord($queryResult);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($emailUser->toArray(), TRUE));
        
        // modify/correct user name
        // set emailUsername to Tine accout login name and append domain for login purposes if set
        $emailUser->emailUsername = $this->_appendDomain($_user->accountLoginName);

        $userProperty = $this->_configKey . 'User';
        $_user->{$userProperty} = $emailUser;
        
        $_user->emailUser = isset($_user->emailUser) ? $_user->emailUser : null;
        $imapUser = ($this->_configKey === Tinebase_Config::SMTP) ? $_user->emailUser : clone($emailUser);
        $smtpUser = ($this->_configKey === Tinebase_Config::SMTP) ? clone($emailUser) : $_user->emailUser;
        $_user->emailUser = Tinebase_EmailUser::merge($imapUser, $smtpUser);
    }
    
    /**
     * update/set email user password
     * 
     * @param  string  $_userId
     * @param  string  $_password
     * @param  bool    $_encrypt encrypt password
     */
    public function inspectSetPassword($_userId, $_password, $_encrypt = TRUE)
    {
        $values = array(
            $this->_propertyMapping['emailPassword'] => ($_encrypt) ? Hash_Password::generate($this->_config['emailScheme'], $_password) : $_password
        );
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $_userId)
        );
        $this->_appendClientIdOrDomain($where);
        
        $this->_db->update($this->_userTable, $values, $where);
    }
    
    /*********  protected functions  *********/
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param  array|string|Zend_Db_Expr  $_cols        columns to get, * per default
     * @param  boolean                    $_getDeleted  get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    abstract protected function _getSelect($_cols = '*', $_getDeleted = FALSE);
    
    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser  $_addedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    protected function _addUser(Tinebase_Model_FullUser $_addedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        if (! $_addedUser->accountEmailAddress) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
            . ' User ' . $_addedUser->accountDisplayName . ' has no email address defined. Skipping email user creation.');
            return;
        }
        
        $emailUserData = $this->_recordToRawData($_addedUser, $_newUserProperties);

        $emailUsername = $emailUserData[$this->_propertyMapping['emailUsername']];
        
        $this->_checkEmailExistance($emailUsername);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Adding new ' . $this->_configKey . ' email user ' . $emailUsername);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' 
        	. print_r($emailUserData, TRUE));

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            
            // generate random password if not set
            if (empty($emailUserData[$this->_propertyMapping['emailPassword']])) {
                $emailUserData[$this->_propertyMapping['emailPassword']] = Hash_Password::generate($this->_config['emailScheme'], Tinebase_Record_Abstract::generateUID());
            }
            
            $insertData = $emailUserData;
            $this->_beforeAddOrUpdate($insertData);
            
            $this->_db->insert($this->_userTable, $insertData);
            
            $this->_afterAddOrUpdate($emailUserData);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $this->inspectGetUserByProperty($_addedUser);
            
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error while creating email user: ' . $zdse);
        }
    }
    
    /**
     * interceptor before add
     * 
     * @param array $emailUserData
     */
    protected function _beforeAddOrUpdate(&$emailUserData)
    {
        
    }

    /**
     * interceptor after add
     * 
     * @param array $emailUserData
     */
    protected function _afterAddOrUpdate(&$emailUserData)
    {
        
    }

    /**
     * check if user email already exists in table
     * 
     * @param  string  $email
     */
    protected function _checkEmailExistance($email)
    {
        $select = $this->_getSelect()
            ->where($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUsername']) . ' = ?',   $email)
            ->where($this->_appendClientIdOrDomain());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select);
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
        
        if (! $queryResult) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));
        
        $userId = $queryResult['userid'];
        try {
            Tinebase_User::getInstance()->getFullUserById($userId);
            throw new Tinebase_Exception_SystemGeneric('Could not overwrite existing email user.');
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Delete obsolete email user ' .$userId);
            $this->_deleteUserById($userId);
        }
    }
    
    /**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser  $_updatedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    protected function _updateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        $emailUserData = $this->_recordToRawData($_updatedUser, $_newUserProperties);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))  Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating Dovecot user ' . $emailUserData[$this->_propertyMapping['emailUsername']]);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($emailUserData, TRUE));
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $emailUserData[$this->_propertyMapping['emailUserId']])
        );
        $this->_appendClientIdOrDomain($where);
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            
            $updateData = $emailUserData;
            
            $this->_beforeAddOrUpdate($updateData);
            
            $this->_db->update($this->_userTable, $updateData, $where);
            
            $this->_afterAddOrUpdate($emailUserData);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $this->inspectGetUserByProperty($_updatedUser);
            
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error while updating email user: ' . $zdse->getMessage());
        }
    }
    
    /**
     * check if user exists already in email backjend user table
     * 
     * @param  Tinebase_Model_FullUser  $_user
     * @throws Tinebase_Exception_Backend_Database
     */
    protected function _userExists(Tinebase_Model_FullUser $_user)
    {
        $select = $this->_getSelect();
        
        $select
          ->where($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUserId']) . ' = ?',   $_user->getId());
          
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        // Perform query - retrieve user from database
        try {
            $stmt = $this->_db->query($select);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $zdse);
            throw new Tinebase_Exception_Backend_Database($zdse->getMessage());
        }
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
        
        if (!$queryResult) {
            return false;
        }
        
        return true;
    }
    
    /**
     * converts raw data from adapter into a single record / do mapping
     *
     * @param  array                    $_data
     * @return Tinebase_Model_EmailUser
     */
    abstract protected function _rawDataToRecord(array $_rawdata);
     
    /**
     * returns array of raw user data
     *
     * @param  Tinebase_Model_FullUser  $_user
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     * @return array
     */
    abstract protected function _recordToRawData(Tinebase_Model_FullUser $_user, Tinebase_Model_FullUser $_newUserProperties);
}
