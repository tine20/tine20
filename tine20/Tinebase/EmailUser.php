<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add Ldap support?
 */

/**
 * class Tinebase_EmailUser
 * 
 * Email Account Managing
 * 
 * @package Tinebase
 * @subpackage Samba
 */
class Tinebase_EmailUser
{
    /**
     * dbmail backend const
     * 
     * @staticvar string
     */
    const DBMAIL    = 'Dbmail';

    /**
     * ldap backend const
     * 
     * @staticvar string
     */
    const LDAP      = 'Ldap';

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_EmailUser
     */
    private static $_instance = NULL;
    
    /**
     * backend object instances
     * 
     * @var array
     */
    private static $_backends = array();
    
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
     * @return Tinebase_EmailUser
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            $backendType = self::getConfiguredBackend();
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Email user backend: ' . $backendType);
            
            self::$_instance = self::factory($backendType);
        }
        
        return self::$_instance;
    }
    
    /**
     * return an instance of the current backend
     *
     * @param   string $_type name of the backend
     * @return  Tinebase_EmailUser_Abstract
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function factory($_type) 
    {
        switch($_type) {
            /*
            case self::LDAP:
                $ldapOptions = Tinebase_User::getBackendConfiguration();
                $sambaOptions = Tinebase_Core::getConfig()->samba->toArray();
                $options = array_merge($ldapOptions, $sambaOptions);
                
                $result = new Tinebase_EmailUser_Ldap($options);
                break;
            */
                
            case self::DBMAIL:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Tinebase_EmailUser_Dbmail();
                }
                $result = self::$_backends[$_type];
                
                break;
            
            default:
                throw new Tinebase_Exception_InvalidArgument("Backend type $_type not implemented.");
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
        if(isset(Tinebase_Core::getConfig()->dbmail)) {
            $backendType = Tinebase_Core::getConfig()->dbmail->get('backend', self::DBMAIL); 
            $backendType = ucfirst($backendType);
        } else {
            throw new Tinebase_Exception_NotFound("DBmail config not found.");
        }
    }
}
