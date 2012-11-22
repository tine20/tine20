<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * abstract class for user plugins
 * 
 * @package Tinebase
 * @subpackage User
 */
abstract class Tinebase_User_Plugin_Abstract implements Tinebase_User_Plugin_SqlInterface
{
    /**
    * @var Zend_Db_Adapter
    */
    protected $_db = NULL;
    
    /**
     * @var Tinebase_Backend_Sql_Command_Interface
     */
    protected $_dbCommand;
    
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
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No email properties found!');
            return;
        }
        
        if ($this->_userExists($_updatedUser) === true) {
            $this->_updateUser($_updatedUser, $_newUserProperties);
        } else {
            $this->_addUser($_updatedUser, $_newUserProperties);
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
     * Check if we should append domain name or not
     *
     * @param  string $_userName
     * @return string
     */
    protected function _appendDomain($_userName)
    {
        if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
            $domain = '@' . $this->_config['domain'];
            if (strpos($_userName, $domain) === FALSE) {
                $_userName .= $domain;
            }
        }
        
        return $_userName;
    }
    
    /**
     * set database
     * 
     * @param array $_config
     */
    protected function _getDb($_config)
    {
        $tine20DbConfig = Tinebase_Core::getDb()->getConfig();
        
        if ($this->_config['host'] == $tine20DbConfig['host'] && 
            $this->_config['dbname'] == $tine20DbConfig['dbname'] &&
            $this->_config['username'] == $tine20DbConfig['username'] &&
            Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Mysql) {
            
            $this->_db = Tinebase_Core::getDb();
        } else {
            $dbConfig = array_intersect_key($_config, array_flip(array('host', 'dbname', 'username', 'password', 'prefix', 'port')));
            $this->_db = Zend_Db::factory('Pdo_Mysql', $dbConfig);
        }
    }
    
    /**
     * get database object
     * 
     * @return Zend_Db_Adapter
     */
    public function getDb()
    {
        return $this->_db;
    }
    
    /**
     * generate salt for password scheme
     * 
     * @param $_scheme
     * @return string
     */
    protected function _salt($_scheme)
    {
        // create a salt that ensures crypt creates an sha2 hash
        $base64_alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ'
            .'abcdefghijklmnopqrstuvwxyz0123456789+/';
        
        for($i=0; $i<16; $i++){
            $salt .= $base64_alphabet[rand(0,63)];
        }
        
        switch ($_scheme) {
            case 'SSHA256':
                $salt = '$5$' . $salt . '$';
                break;
                
            case 'SSHA512':
                $salt = '$6$' . $salt . '$';
                break;
                
            case 'MD5-CRYPT':
            default:
                $salt = crypt($_scheme);
                break;
        }

        return $salt;
    }
    
    /**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser  $_updatedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    abstract protected function _updateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties);
    
    /**
     * check if user exists already in plugin user table
     * 
     * @param Tinebase_Model_FullUser $_user
     */
    abstract protected function _userExists(Tinebase_Model_FullUser $_user);
}
