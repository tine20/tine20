<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Account Class
 *
 * @package     Tinebase
 * @subpackage  Account
 */
class Tinebase_Account
{
    const SQL = 'Sql';
    
    const LDAP = 'Ldap';

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Account_Interface
     */
    private static $_instance = NULL;
    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Account_Abstract
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            if(isset(Zend_Registry::get('configFile')->accounts)) {
                $backendType = Zend_Registry::get('configFile')->accounts->get('backend', self::SQL);
                $backendType = ucfirst($backendType);
            } else {
                $backendType = self::SQL;
            }
            
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' acconts backend: ' . $backendType);
            
            self::$_instance = self::factory($backendType);
        }
        
        return self::$_instance;
    }
        
    /**
     * return an instance of the current accounts backend
     *
     * @param string $_backendType name of the accounts backend
     * @return Tinebase_Account_Abstract
     */
    public static function factory($_backendType) 
    {
        switch($_backendType) {
            case self::LDAP:
                $options = Zend_Registry::get('configFile')->accounts->get('ldap')->toArray();
                unset($options['userDn']);
                unset($options['groupsDn']);
                
                $result = Tinebase_Account_Ldap::getInstance($options);
                break;
                
            case self::SQL:
                $result = Tinebase_Account_Sql::getInstance();
                break;
            
            default:
                throw new Exception("accounts backend type $_backendType not implemented");
        }
        
        return $result;
    }
}