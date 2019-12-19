<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke
 */

/**
 * plugin to handle sql email accounts
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Smtp_Standard extends Tinebase_User_Plugin_Abstract implements Tinebase_EmailUser_Smtp_Interface
{
    /**
     * email user config defaults
     * 
     * @var array 
     */
    protected $_defaults = array(
        'emailPort'   => 25,
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
        $emailConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        
        // merge _config and email backend config
        $this->_config = array_merge($this->_config, $emailConfig);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_config, TRUE));
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
     * update/set email user password
     * 
     * @param  string  $_userId
     * @param  string  $_password
     * @param  bool    $_encrypt encrypt password
     */
    public function inspectSetPassword($_userId, $_password, $_encrypt = TRUE)
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
     * converts raw data from adapter into a single record / do mapping
     *
     * @param  array $_data
     * @return Tinebase_Record_Interface
     */
    protected function _rawDataToRecord(array &$_rawdata)
    {
        $data = array_merge($this->_defaults, $this->_getConfiguredSystemDefaults());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' raw data: ' . print_r($_rawdata, true));
        
        $emailUser = new Tinebase_Model_EmailUser($data, TRUE);
        
        return $emailUser;
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
