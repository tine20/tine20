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
class Tinebase_EmailUser_Smtp_Standard extends Tinebase_User_Plugin_Abstract
{
    /**
     * email user config
     * 
     * @var array 
     */
    protected $_config = array();
    
    /**
     * config key (Tinebase_Config::IMAP || Tinebase_Config::SMTP)
     * 
     * @var string
     */
    protected $_configKey = Tinebase_Config::SMTP;
    
    /**
     * subconfig for user email backend (for example: dovecot)
     * 
     * @var string
     */
    protected $_subconfigKey =  NULL;
    
    /**
     * the constructor
     * 
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        if ($this->_configKey === NULL) {
            throw new Tinebase_Exception_UnexpectedValue('$this->_configKey can not be emoty');
        }
        
        // get email user backend config options (host, dbname, username, password, port)
        $emailConfig = Tinebase_Config::getInstance()->get($this->_configKey, new Tinebase_Config_Struct())->toArray();
        
        // merge _config and email backend config
        if ($this->_subconfigKey) {
            $this->_config = array_merge($emailConfig[$this->_subconfigKey], $this->_config);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_config, TRUE));
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
