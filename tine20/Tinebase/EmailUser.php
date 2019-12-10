<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * Imap Cyrus backend const
     * 
     * @staticvar string
     */
    const IMAP_CYRUS            = 'Imap_Cyrus';
    
    /**
     * Imap standard backend const
     * 
     * @staticvar string
     */
    const IMAP_STANDARD         = 'Imap_Standard';
    
    /**
     * Imap DBmail backend const
     * 
     * @staticvar string
     */
    const IMAP_DBMAIL           = 'Imap_Dbmail';
    
    /**
     * Imap Dovecot backend const
     * 
     * @staticvar string
     */
    const IMAP_DOVECOT          = 'Imap_Dovecot_imap';
    
    /**
     * Imap Dovecot combined backend const
     * 
     * @staticvar string
     */
    const IMAP_DOVECOT_COMBINED = 'Imap_Dovecotcombined';
    
    /**
     * Smtp Postfix backend const
     * 
     * @staticvar string
     */
    const SMTP_POSTFIX          = 'Smtp_Postfix';

    /**
     * Smtp Postfix multi instance backend const
     *
     * @staticvar string
     */
    const SMTP_POSTFIXMULTIINSTANCE          = 'Smtp_Postfixmultiinstance';

    /**
     * Smtp Postfix backend const
     * 
     * @staticvar string
     */
    const SMTP_POSTFIX_COMBINED = 'Smtp_Postfixcombined';
    
    /**
     * Imap ldap backend const
     * 
     * @staticvar string
     */
    const IMAP_LDAP             = 'Imap_Ldap_imap';
    
    /**
     * Smtp Ldap backend const
     * 
     * @staticvar string
     */
    const SMTP_LDAP             = 'Smtp_Ldapsmtp';
    
    /**
     * Smtp Ldap mail attribute backend const
     * 
     * @staticvar string
     */
    const SMTP_LDAP_MAIL        = 'Smtp_Ldapsmtpmail';
    
    /**
     * Smtp Ldap backend const
     * 
     * @staticvar string
     */
    const SMTP_LDAP_QMAIL       = 'Smtp_Ldapsmtpqmail';
    
    /**
     * univention smtp ldap backend const
     * 
     * @staticvar string
     */
    const SMTP_LDAP_UNIVENTION  = 'Smtp_Ldap_univention';

    /**
     * univention imap ldap backend const
     * 
     * @staticvar string
     */
    const IMAP_LDAP_UNIVENTION  = 'Imap_Ldap_univention';

    /**
     * simpleMail smtp ldap backend const
     *
     * @staticvar string
     */
    const SMTP_LDAP_SIMPLEMAIL  = 'Smtp_Ldap_simplemail';

    /**
     * Smtp standard backend const
     * 
     * @staticvar string
     */
    const SMTP_STANDARD         = 'Smtp_Standard';
    
    /**
     * supported backends
     * 
     * @var array
     */
    protected static $_supportedBackends = array(
        self::IMAP_CYRUS            => 'Tinebase_EmailUser_Imap_Cyrus',
        self::IMAP_DBMAIL           => 'Tinebase_EmailUser_Imap_Dbmail',
        self::IMAP_DOVECOT          => 'Tinebase_EmailUser_Imap_Dovecot',
        self::IMAP_DOVECOT_COMBINED => 'Tinebase_EmailUser_Imap_DovecotCombined',
        self::IMAP_STANDARD         => 'Tinebase_EmailUser_Imap_Standard',
        self::IMAP_LDAP             => 'Tinebase_EmailUser_Imap_LdapDbmailSchema',
        self::IMAP_LDAP_UNIVENTION  => 'Tinebase_EmailUser_Imap_LdapUniventionMailSchema',
        self::SMTP_LDAP             => 'Tinebase_EmailUser_Smtp_LdapDbmailSchema',
        self::SMTP_LDAP_MAIL        => 'Tinebase_EmailUser_Smtp_LdapMailSchema',
        self::SMTP_LDAP_QMAIL       => 'Tinebase_EmailUser_Smtp_LdapQmailSchema',
        self::SMTP_LDAP_UNIVENTION  => 'Tinebase_EmailUser_Smtp_LdapUniventionMailSchema',
        self::SMTP_LDAP_SIMPLEMAIL  => 'Tinebase_EmailUser_Smtp_LdapSimpleMailSchema',
        self::SMTP_POSTFIX          => 'Tinebase_EmailUser_Smtp_Postfix',
        self::SMTP_POSTFIXMULTIINSTANCE => 'Tinebase_EmailUser_Smtp_PostfixMultiInstance',
        self::SMTP_POSTFIX_COMBINED => 'Tinebase_EmailUser_Smtp_PostfixCombined',
        self::SMTP_STANDARD         => 'Tinebase_EmailUser_Smtp_Standard',
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

    private static $_configuredBackends = [];
    
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

    public static function clearCaches()
    {
        self::$_configuredBackends = [];
        self::$_configs = [];
        self::$_backends = [];
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
        if (isset(self::$_configuredBackends[$configType])) {
            return self::$_configuredBackends[$configType];
        }

        $config = self::getConfig($configType);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($config, TRUE));
        
        if (!isset($config['backend'])) {
            throw new Tinebase_Exception_NotFound("No backend in config for type $configType found.");
        }
        
        $backend = ucfirst(strtolower($configType)) . '_' . ucfirst(strtolower($config['backend']));
        
        if (!isset(self::$_supportedBackends[$backend])) {
            throw new Tinebase_Exception_NotFound("Config for type $configType / $backend not found.");
        }

        self::$_configuredBackends[$configType] = $backend;

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
            
            /*
             * If LDAP-Url is given (instead of comma separated domains) add secondary domains from LDAP
             * e.g. ldap://localhost/ou=domains,ou=mailConfig,dc=example,dc=com?dc?sub?objectclass=mailDomain
             */
            if (($_configType == Tinebase_Config::SMTP) && (array_key_exists('secondarydomains', self::$_configs[Tinebase_Config::SMTP])) &&
                    preg_match("~^ldaps?://~i", self::$_configs[Tinebase_Config::SMTP]['secondarydomains']))
            {
                $ldap_url = parse_url(self::$_configs[Tinebase_Config::SMTP]['secondarydomains']);
                $ldap_url['path'] = substr($ldap_url['path'], 1);
                $query = explode('?', $ldap_url['query']);
                (count($query) > 0) ? $ldap_url['attributes'] = explode(',', $query[0]) : $ldap_url['attributes'] = array();
                $ldap_url['scope'] = Zend_Ldap::SEARCH_SCOPE_BASE;
                if (count($query) > 1)
                {
                    switch ($query[1]) {
                        case 'subtree':
                        case 'sub':
                            $ldap_url['scope'] = Zend_Ldap::SEARCH_SCOPE_SUB;
                            break;
                        case 'one':
                            $ldap_url['scope'] = Zend_Ldap::SEARCH_SCOPE_ONE;
                            break;

                    }
                }
                (count($query) > 2) ? $ldap_url['filter'] = $query[2] : $ldap_url['filter'] = 'objectClass=*';
                // By now your options are limited to configured server
                $ldap = new Tinebase_Ldap(Tinebase_User::getBackendConfiguration());
                $ldap->connect()->bind();
                $secondarydomains = $ldap->searchEntries(
                        $ldap_url['filter'],
                        $ldap_url['path'],
                        $ldap_url['scope'],
                        $ldap_url['attributes']
                );
                self::$_configs[Tinebase_Config::SMTP]['secondarydomains'] = '';
                foreach ($secondarydomains as $dn) 
                {
                    foreach ($ldap_url['attributes'] as $attr) 
                    {
                        if (array_key_exists($attr, $dn)) foreach ($dn[$attr] as $domain) 
                        {
                            self::$_configs[Tinebase_Config::SMTP]['secondarydomains'] != '' ? $domain = ','.$domain : $domain;
                            self::$_configs[Tinebase_Config::SMTP]['secondarydomains'] .= $domain;                            
                        }

                    }
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .' Secondarydomains: ' . print_r(self::$_configs[Tinebase_Config::SMTP]['secondarydomains'], true));
            }
        }
        
        return self::$_configs[$_configType];
    }

    /**
     * @param array|null $config
     * @param boolean $_includeAdditional
     * @return array
     */
    public static function getAllowedDomains($config = null, $_includeAdditional = false)
    {
        if ($config === null) {
            $config = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP)->toArray();
        }

        $allowedDomains = array();
        if (! empty($config['primarydomain'])) {
            $allowedDomains = array($config['primarydomain']);
            if (! empty($config['secondarydomains'])) {
                // merge primary and secondary domains and split secondary domains + trim whitespaces
                $allowedDomains = array_merge($allowedDomains, preg_split('/\s*,\s*/', $config['secondarydomains']));
            }
            if ($_includeAdditional) {
                $allowedDomains = array_merge($allowedDomains, self::getAdditionalDomains($config));
            }
        }
        return $allowedDomains;
    }

    public static function getAdditionalDomains($config = null)
    {
        if ($config === null) {
            $config = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP)->toArray();
        }

        $result = [];
        if (! empty($config['additionaldomains'])) {
            $result = preg_split('/\s*,\s*/', $config['additionaldomains']);
        }
        return $result;
    }

    /**
     * check if email address is in allowed domains
     *
     * @param string $_email
     * @param boolean $_throwException
     * @param array $_allowedDomains
     * @param boolean $_includeAdditional
     * @return boolean
     * @throws Tinebas_Exception_SystemGeneric
     * @throws Tinebase_Exception_EmailInAddionalDomains
     */
    public static function checkDomain($_email, $_throwException = false, $_allowedDomains = null, $_includeAdditional = false)
    {
        $result = true;
        $allowedDomains = $_allowedDomains ? $_allowedDomains : self::getAllowedDomains(null, $_includeAdditional);

        if (! empty($_email) && ! empty($allowedDomains)) {

            if (! preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $_email)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                    __METHOD__ . '::' . __LINE__ . ' No valid email address: ' . $_email);
                $domain = null;
            } else {
                list($user, $domain) = explode('@', $_email, 2);
            }

            if (! in_array($domain, $allowedDomains)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                    __METHOD__ . '::' . __LINE__ . ' Email address ' . $_email . ' not in allowed domains!');

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' Allowed domains: ' . print_r($allowedDomains, TRUE));

                if ($_throwException) {
                    if (! $_includeAdditional && in_array($domain, self::getAdditionalDomains())) {
                        throw new Tinebase_Exception_EmailInAdditionalDomains();
                    } else {
                        $translation = Tinebase_Translation::getTranslation('Tinebase');
                        throw new Tinebase_Exception_SystemGeneric(str_replace(
                            ['{0}', '{1}'],
                            [$_email, implode(',', $allowedDomains)],
                            $translation->_('Email address {0} not in allowed domains [{1}] or invalid')
                        ));
                    }
                } else {
                    $result = false;
                }
            }
        }

        return $result;
    }
}
