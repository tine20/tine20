<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * abstract class for user plugins
 * 
 * @package Tinebase
 * @subpackage User
 */
abstract class Tinebase_User_Plugin_SqlAbstract extends Tinebase_User_Plugin_Abstract implements Tinebase_User_Plugin_SqlInterface
{
    /**
     * email user config defaults
     *
     * @var array
     */
    protected $_defaults = array(
    );

    /**
    * @var Zend_Db_Adapter_Abstract
    */
    protected $_db = NULL;
    
    /**
     * @var Tinebase_Backend_Sql_Command_Interface
     */
    protected $_dbCommand;

    /**
     * email user config
     *
     * @var array
     */
    protected $_config = array();

    /**
     * list of all db connections other than Tinebase_Core::getDb()
     *
     * @var array
     */
    protected static $_dbConnections =  [];

    /**
     * inspect data used to create user
     * 
     * @param Tinebase_Model_FullUser  $_addedUser
     * @param Tinebase_Model_FullUser  $_newUserProperties
     */
    public function inspectAddUser(Tinebase_Model_FullUser $_addedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        $this->inspectUpdateUser($_addedUser, $_newUserProperties);
    }
    
    /**
     * inspect data used to update user
     * 
     * @param Tinebase_Model_FullUser  $_updatedUser
     * @param Tinebase_Model_FullUser  $_newUserProperties
     */
    public function inspectUpdateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        if (! isset($_newUserProperties->imapUser) && ! isset($_newUserProperties->smtpUser)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' No email properties found!');
            return;
        }

        try {
            if ($this->_userExists($_updatedUser) === true) {
                $this->_updateUser($_updatedUser, $_newUserProperties);
            } else {
                $this->_addUser($_updatedUser, $_newUserProperties);
            }
        } catch (Tinebase_Exception_EmailInAdditionalDomains $teeiad) {
            // TODO delete existing?
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' ' . $teeiad->getMessage());
        }
    }

    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser  $_addedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    abstract protected function _addUser(Tinebase_Model_FullUser $_addedUser, Tinebase_Model_FullUser $_newUserProperties);
    
    /**
     * set database
     */
    protected function _getDb()
    {
        if (isset($this->_subconfigKey)) {
            $mailDbConfig = $this->_config[$this->_subconfigKey];
            $mailDbConfig['adapter'] = !empty($mailDbConfig['adapter']) ?
                strtolower($mailDbConfig['adapter']) :
                strtolower($this->_config['adapter']);

            $tine20DbConfig = Tinebase_Core::getDb()->getConfig();
            $tine20DbConfig['adapter'] = strtolower(str_replace('Tinebase_Backend_Sql_Adapter_', '', get_class(Tinebase_Core::getDb())));

            if ($mailDbConfig['adapter'] == $tine20DbConfig['adapter'] &&
                $mailDbConfig['host'] == $tine20DbConfig['host'] &&
                $mailDbConfig['dbname'] == $tine20DbConfig['dbname'] &&
                $mailDbConfig['username'] == $tine20DbConfig['username']
            ) {
                $this->_db = Tinebase_Core::getDb();
            } else {
                $dbConfig = array_intersect_key($mailDbConfig, array_flip(array('adapter', 'host', 'dbname', 'username', 'password', 'port')));
                $dbConfig['driver_options'] = [
                    // use lower timeouts (in seconds) as we don't want this to block tine (for example the login)
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3,
                    PDO::ATTR_TIMEOUT => 3,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ];
                $this->_db = Tinebase_Core::createAndConfigureDbAdapter($dbConfig);
                static::$_dbConnections[] = $this->_db;
            }
        } else {
            $this->_db = Tinebase_Core::getDb();
        }
    }

    public static function disconnectDbConnections()
    {
        /** @var Zend_Db_Adapter_Abstract $con */
        foreach (static::$_dbConnections as $con) {
            $con->closeConnection();
            Tinebase_TransactionManager::getInstance()->removeTransactionable($con);
        }
    }
    
    /**
     * get database object
     * 
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDb()
    {
        if ($this->_db === null) {
            $this->_db = $this->getDb();
        }
        return $this->_db;
    }

    /**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser  $_updatedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    abstract protected function _updateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties);

    /**
     * updates email properties for an existing user
     *
     * @param  Tinebase_Model_FullUser  $_updatedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     *
     * @todo this is not in LdapAbstract - we should allow email user updates there, too ( + rename to updateEmailUser)
     */
    public function updateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        return $this->_updateUser($_updatedUser, $_newUserProperties);
    }
    
    /**
     * check if user exists already in plugin user table
     *
     * @param Tinebase_Model_FullUser $_user
     * @return boolean
     */
    public function userExists(Tinebase_Model_FullUser $_user)
    {
        return $this->_userExists($_user);
    }

    /**
     * check if user exists already in plugin user table
     * 
     * @param Tinebase_Model_FullUser $_user
     * @return boolean
     */
    abstract protected function _userExists(Tinebase_Model_FullUser $_user);

    // hack, should go into Tinebase_EmailUser_Sql but not all EmailUser Backends actually use that SQL one
    // well not every backend is using sql, its just a bit messy around here
    public function _getConfiguredSystemDefaults()
    {
        $systemDefaults = array();

        $hostAttribute = ($this instanceof Tinebase_EmailUser_Imap_Interface) ? 'host' : 'hostname';
        if (!empty($this->_config[$hostAttribute])) {
            $systemDefaults['emailHost'] = $this->_config[$hostAttribute];
        }

        if (!empty($this->_config['port'])) {
            $systemDefaults['emailPort'] = $this->_config['port'];
        }

        if (!empty($this->_config['ssl'])) {
            $systemDefaults['emailSecure'] = $this->_config['ssl'];
        }

        if (!empty($this->_config['auth'])) {
            $systemDefaults['emailAuth'] = $this->_config['auth'];
        }

        return $systemDefaults;
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

        // convert data to Tinebase_Model_EmailUser
        $data = [];
        $emailUser = $this->_rawDataToRecord($data);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($emailUser->toArray(), TRUE));

        $emailUser->emailUsername = $this->getEmailUserName($_user);

        if ($this instanceof Tinebase_EmailUser_Smtp_Interface) {
            $_user->smtpUser  = $emailUser;
            $_user->emailUser = Tinebase_EmailUser::merge($_user->emailUser, clone $_user->smtpUser);
        } else {
            $_user->imapUser  = $emailUser;
            $_user->emailUser = Tinebase_EmailUser::merge(clone $_user->imapUser, $_user->emailUser);
        }
    }

    /**
     * converts raw data from adapter into a single record / do mapping
     *
     * @param array $_rawdata
     * @return Tinebase_Model_EmailUser
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _rawDataToRecord(array &$_rawdata)
    {
        $data = array_merge($this->_defaults, $this->_getConfiguredSystemDefaults());

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' raw data: ' . print_r($_rawdata, true));

        return new Tinebase_Model_EmailUser($data, TRUE);
    }
}
