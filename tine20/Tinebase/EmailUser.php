<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        think about splitting email user model in two (imap + smtp)
 */

/**
 * class Tinebase_EmailUser
 * 
 * Email Account Managing
 * 
 * @package Tinebase
 * @subpackage User
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
     * Dovecot imap backend const
     * 
     * @staticvar string
     */
    const DOVECOT_IMAP    = 'Dovecot_imap';
    
    /**
     * Dovecot imap combined backend const
     * 
     * @staticvar string
     */
    const DOVECOT_COMBINED    = 'Dovecotcombined';
    
    /**
     * postfix backend const
     * 
     * @staticvar string
     */
    const POSTFIX    = 'Postfix';
    
    /**
     * postfix backend const
     * 
     * @staticvar string
     */
    const POSTFIX_COMBINED    = 'Postfixcombined';
    
    /**
     * imap ldap backend const
     * 
     * @staticvar string
     */
    const LDAP_IMAP      = 'Ldap_imap';
    
    /**
     * smtp ldap backend const
     * 
     * @staticvar string
     */
    const LDAP_SMTP      = 'Ldapsmtp';
    
    /**
     * smtp ldap mail attribute backend const
     * 
     * @staticvar string
     */
    const LDAP_SMTP_MAIL      = 'Ldapsmtpmail';
    
    /**
     * smtp ldap backend const
     * 
     * @staticvar string
     */
    const LDAP_SMTP_QMAIL      = 'Ldapsmtpqmail';
    
    /**
     * cyrus backend const
     * 
     * @staticvar string
     */
    const CYRUS    = 'Cyrus';
    
    /**
     * imap standard backend const
     * 
     * @staticvar string
     */
    const IMAP_STANDARD      = 'Standard';
    
    /**
     * smtp standard backend const
     * 
     * @staticvar string
     */
    const SMTP_STANDARD      = 'Standard';
    
    /**
     * supported backends
     * 
     * @var array
     */
    protected static $_supportedBackends = array(
        self::CYRUS            => 'Tinebase_EmailUser_Imap_Cyrus',
        self::DBMAIL           => 'Tinebase_EmailUser_Imap_Dbmail',
        self::DOVECOT_IMAP     => 'Tinebase_EmailUser_Imap_Dovecot',
        self::DOVECOT_COMBINED => 'Tinebase_EmailUser_Imap_DovecotCombined',
        self::IMAP_STANDARD    => 'Tinebase_EmailUser_Imap_Standard',
        self::LDAP_IMAP        => 'Tinebase_EmailUser_Imap_LdapDbmailSchema',
        self::LDAP_SMTP        => 'Tinebase_EmailUser_Smtp_LdapDbmailSchema',
        self::LDAP_SMTP_MAIL   => 'Tinebase_EmailUser_Smtp_LdapMailSchema',
        self::LDAP_SMTP_QMAIL  => 'Tinebase_EmailUser_Smtp_LdapQmailSchema',
        self::POSTFIX          => 'Tinebase_EmailUser_Smtp_Postfix',
        self::POSTFIX_COMBINED => 'Tinebase_EmailUser_Smtp_PostfixCombined',
        self::SMTP_STANDARD    => 'Tinebase_EmailUser_Smtp_Standard',
    );
    
    /**
     * backend object instances
     * 
     * @var array
     */
    private static $_backends = array();
    
    /**
     * configs as static class var to minimize db queries
     *  
     * @var array
     */
    private static $_configs = array();
    
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
     * @param string $configType
     * @return Tinebase_User_Plugin_Abstract
     */
    public static function getInstance($configType = Tinebase_Config::IMAP) 
    {
        $type = self::getConfiguredBackend($configType);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Email user backend: ' . $type);
        
        if (!isset(self::$_backends[$type])) {
            self::$_backends[$type] = self::factory($type);
        }
        
        return self::$_backends[$type];
    }
    
    /**
     * return an instance of the defined backend
     *
     * @param   string $type name of the backend
     * @return  Tinebase_User_Plugin_Abstract
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function factory($type) 
    {
        if (!isset(self::$_supportedBackends[$type])) {
            throw new Tinebase_Exception_InvalidArgument("Backend type $type not implemented.");
        }
        
        $className = self::$_supportedBackends[$type];
        
        $backend = new $className();
        
        return $backend;
    }
    
    /**
     * returns the configured backend
     * 
     * @param string $configType
     * @return string
     * @throws Tinebase_Exception_NotFound
     */
    public static function getConfiguredBackend($configType = Tinebase_Config::IMAP)
    {
        $config = self::getConfig($configType);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($config, TRUE));
        
        if (!isset($config['backend'])) {
            throw new Tinebase_Exception_NotFound("No backend in config for type $configType found.");
        }
        
        $backend = ucfirst(strtolower($config['backend']));
        
        if (!isset(self::$_supportedBackends[$backend])) {
            throw new Tinebase_Exception_NotFound("Config for type $configType / $backend not found.");
        }
        
        return $backend;
    }
    
    /**
     * merge two email users
     * 
     * @param Tinebase_Model_EmailUser $_emailUserImap
     * @param Tinebase_Model_EmailUser $_emailUserSmtp
     * @return Tinebase_Model_EmailUser|NULL
     */
    public static function merge($_emailUserImap, $_emailUserSmtp)
    {
        $result = NULL;
        
        if ($_emailUserImap !== NULL && $_emailUserSmtp !== NULL) {
            // merge
            $_emailUserImap->emailAliases = $_emailUserSmtp->emailAliases;
            $_emailUserImap->emailForwards = $_emailUserSmtp->emailForwards;
            $_emailUserImap->emailForwardOnly = $_emailUserSmtp->emailForwardOnly;
            $_emailUserImap->emailAddress = $_emailUserSmtp->emailAddress;
            $result = $_emailUserImap;
            
        } else if ($_emailUserImap !== NULL) {
            $result =  $_emailUserImap;
            
        } else if ($_emailUserSmtp !== NULL) {
            $result =  $_emailUserSmtp;
        }
        
        return $result;
    }
    
    /**
     * check if email users are managed for backend/config type
     * 
     * @param string $_configType IMAP/SMTP
     * @return boolean
     */
    public static function manages($_configType)
    {
        $config = self::getConfig($_configType);
        
        $result = (!empty($config['backend']) && isset($config['active']) && $config['active'] == true);
        
        return $result;
    }
    
    /**
     * get config for type IMAP/SMTP
     * 
     * @param string $_configType
     * @return array
     */
    public static function getConfig($_configType)
    {
        if (!isset(self::$_configs[$_configType])) {
            self::$_configs[$_configType] = Tinebase_Config::getInstance()->get($_configType, new Tinebase_Config_Struct())->toArray();
        }
        
        return self::$_configs[$_configType];
    }
}
