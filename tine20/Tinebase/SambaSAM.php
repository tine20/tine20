<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Samba
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * class Tinebase_SambaSAM
 * 
 * Samba Account Managing
 * 
 * @package Tinebase
 * @subpackage Samba
 */
class Tinebase_SambaSAM
{
    // const SQL = 'Sql';
    
    const LDAP = 'Ldap';

   
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_SambaSAM
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
        
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_SambaSAM
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            $backendType = self::getConfiguredBackend();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' samba sam backend: ' . $backendType);
            
            self::$_instance = self::factory($backendType);
        }
        
        return self::$_instance;
    }
    
    /**
     * return an instance of the current backend
     *
     * @param   string $_backendType name of the backend
     * @return  Tinebase_SambaSAM_Abstract
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function factory($_backendType) 
    {
        switch($_backendType) {
            case self::LDAP:
                $ldapOptions = Tinebase_User::getBackendConfiguration();
                $sambaOptions = Tinebase_Core::getConfig()->samba->toArray();
                $options = array_merge($ldapOptions, $sambaOptions);
                
                $result = new Tinebase_SambaSAM_Ldap($options);
                break;
                
            // case self::SQL:
            //     $result = Tinebase_SambaSAM_Sql::getInstance();
            //     break;
            
            default:
                throw new Tinebase_Exception_InvalidArgument("Backend type $_backendType not implemented.");
        }
        
        return $result;
    }
    
    /**
     * returns the configured backend
     * 
     * @return string
     */
    public static function getConfiguredBackend()
    {
        if(isset(Tinebase_Core::getConfig()->samba)) {
            $backendType = Tinebase_Core::getConfig()->samba->get('backend', self::LDAP);
            $backendType = ucfirst($backendType);
        } else {
            $backendType = self::LDAP;
        }
        return $backendType;
    }
}
