<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke
 */

/**
 * plugin to handle sql email accounts
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Imap_Standard extends Tinebase_User_Plugin_SqlAbstract implements Tinebase_EmailUser_Imap_Interface
{
    /**
     * config defaults
     *
     * @var array
     */
    protected $_defaults = array(
        'emailPort'   => 143,
        'emailSecure' => Tinebase_EmailUser_Model_Account::SECURE_TLS,
        'emailAuth'   => 'plain'
    );

    /**
     * the constructor
     * 
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        // get email user backend config options (host, dbname, username, password, port)
        $emailConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        
        // merge _config and email backend config
        $this->_config = array_merge($this->_config, $emailConfig);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_config, TRUE));
    }
    
    /**
     * update/set email user password
     *
     * @param string $_userId
     * @param string $_password
     * @param bool $_encrypt
     * @param bool $_mustChange
     * @param array $_additionalData
     * @return void
     */
    public function inspectSetPassword($_userId, string $_password, bool $_encrypt = true, bool $_mustChange = false, array &$_additionalData = [])
    {
        // do nothing
    }
    
    /**
    * delete user by id
    *
    * @param  Tinebase_Model_FullUser  $_user
    */
    public function inspectDeleteUser(Tinebase_Model_FullUser $_user)
    {
        // do nothing
    }
    
    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser  $_addedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    protected function _addUser(Tinebase_Model_FullUser $_addedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        // do nothing
    }

    /**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser  $_updatedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    protected function _updateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        // do nothing
    }
    
    /**
     * check if user exists already in plugin user table
     * 
     * @param Tinebase_Model_FullUser $_user
     */
    protected function _userExists(Tinebase_Model_FullUser $_user)
    {
        return false;
    }
}
