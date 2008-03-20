<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * main authentication class
 * 
 * @package     Tinebase
 * @subpackage  Auth 
 */

class Tinebase_Auth
{
    /**
     * constant for Sql contacts backend class
     *
     */
    const SQL = 'Sql';
    
    /**
     * constant for LDAP contacts backend class
     *
     */
    const LDAP = 'Ldap';

    /**
     * General Failure
     */
    const FAILURE                       =  0;

    /**
     * Failure due to identity not being found.
     */
    const FAILURE_IDENTITY_NOT_FOUND    = -1;

    /**
     * Failure due to identity being ambiguous.
     */
    const FAILURE_IDENTITY_AMBIGUOUS    = -2;

    /**
     * Failure due to invalid credential being supplied.
     */
    const FAILURE_CREDENTIAL_INVALID    = -3;

    /**
     * Failure due to uncategorized reasons.
     */
    const FAILURE_UNCATEGORIZED         = -4;
    
    /**
     * Failure due the account is disabled
     */
    const FAILURE_DISABLED              = -100;

    /**
     * Failure due the account is expired
     */
    const FAILURE_EXPIRED               = -101;
    
    /**
     * Failure due the account is temporarly blocked
     */
    const FAILURE_BLOCKED               = -102;
        
    /**
     * Authentication success.
     */
    const SUCCESS                        =  1;

    /**
     * the name of the authenticationbackend
     *
     * @var string
     */
    protected $_backendType = Tinebase_Auth_Factory::SQL;
    
/**
     * the instance of the authenticationbackend
     *
     * @var Tinebase_Auth_Sql
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     * @todo use the config setting from the registry
     */
    private function __construct() {
        try {
            $authConfig = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini', 'authentication');
            
            $this->_backendType = $authConfig->get('backend', Tinebase_Auth_Factory::SQL);
            
        } catch (Zend_Config_Exception $e) {
            $authConfig = new Zend_Config(array(
                'backend'   => Tinebase_Auth_Factory::SQL
            ));
        }
        Zend_Registry::get('logger')->debug('authentication backend: ' . $this->_backendType);
        $this->_backend = Tinebase_Auth_Factory::factory($this->_backendType, $authConfig);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Auth
     */
    private static $_instance = NULL;
    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Auth
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Auth;
        }
        
        return self::$_instance;
    }
    
    public function authenticate($_username, $_password)
    {
        $this->_backend->setIdentity($_username);
        $this->_backend->setCredential($_password);
        
        $result = Zend_Auth::getInstance()->authenticate($this->_backend);
                
        return $result;
    }
    
    public function isValidPassword($_username, $_password)
    {
        $this->_backend->setIdentity($_username);
        $this->_backend->setCredential($_password);
        
        $result = $this->_backend->authenticate();

        if ($result->isValid()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * sets the password
     *
     * @param string $_loginName the loginname of the account
     * @param string $_password1
     * @param string $_password2
     */
    public function setPassword($_loginName, $_password1, $_password2)
    {
        if($_password1 !== $_password2) {
            throw new Exception('$_password1 and $_password2 don not match');
        }
        
        $this->_backend->setPassword($_loginName, $_password1);
    }
}
