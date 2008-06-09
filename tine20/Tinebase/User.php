<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * User Class
 *
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User
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
     * @var Tinebase_User_Interface
     */
    private static $_instance = NULL;
    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_User_Abstract
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            $backendType = self::getConfiguredBackend();
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' acconts backend: ' . $backendType);
            
            self::$_instance = self::factory($backendType);
        }
        
        return self::$_instance;
    }
        
    /**
     * return an instance of the current rs backend
     *
     * @param string $_backendType name of the rs backend
     * @return Tinebase_User_Abstract
     */
    public static function factory($_backendType) 
    {
        switch($_backendType) {
            case self::LDAP:
                $options = Zend_Registry::get('configFile')->accounts->get('ldap')->toArray();
                unset($options['userDn']);
                unset($options['groupsDn']);
                
                $result = Tinebase_User_Ldap::getInstance($options);
                break;
                
            case self::SQL:
                $result = Tinebase_User_Sql::getInstance();
                break;
            
            default:
                throw new Exception("rs backend type $_backendType not implemented");
        }
        
        return $result;
    }
    
    /**
     * returns the configured rs backend
     * 
     * @return string
     */
    public static function getConfiguredBackend()
    {
        if(isset(Zend_Registry::get('configFile')->accounts)) {
            $backendType = Zend_Registry::get('configFile')->accounts->get('backend', self::SQL);
            $backendType = ucfirst($backendType);
        } else {
            $backendType = self::SQL;
        }
        return $backendType;
    }
}