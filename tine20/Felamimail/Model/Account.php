<?php
/**
 * class to hold Account data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        update account credentials if user password changed
 * @todo        use generic (JSON encoded) field for 'other' settings like folder names
 * @todo        don't use enum fields (ssl, smtp_ssl, display_format)
 */

/**
 * class to hold Account data
 * 
 * @property  string  trash_folder
 * @property  string  sieve_vacation_active
 * @package     Felamimail
 */
class Felamimail_Model_Account extends Tinebase_Record_Abstract
{  
    /**
     * secure connection setting for no secure connection
     *
     */
    const SECURE_NONE = 'none';

    /**
     * secure connection setting for tls
     *
     */
    const SECURE_TLS = 'tls';

    /**
     * secure connection setting for ssl
     *
     */
    const SECURE_SSL = 'ssl';
    
    /**
     * system account
     *
     */
    const TYPE_SYSTEM = 'system';
    
    /**
     * user defined account
     *
     */
    const TYPE_USER = 'user';
    
    /**
     * key in $_validators/$_properties array for the field which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';    
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Felamimail';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'user_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // account type (system/user defined)
        'type'        => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => 'user',
            'InArray' => array(self::TYPE_USER, self::TYPE_SYSTEM)
        ),
    // imap server config
        'host'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'port'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 143),
        'ssl'                   => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => 'tls',
            'InArray' => array(self::SECURE_NONE, self::SECURE_SSL, self::SECURE_TLS)
        ),
        'credentials_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'user'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'password'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // other settings (add single JSON encoded field for that?)
        'sent_folder'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'Sent'),
        'trash_folder'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'Trash'),
        'intelligent_folders'   => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'has_children_support'  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'delimiter'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => '/'),
        'display_format'        => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => 'html',
            'InArray' => array('html', 'plain')
        ),
    // namespaces
        'ns_personal'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'ns_other'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'ns_shared'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // user data
        'email'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'from'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => ''),
        'organization'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => ''),
        'signature'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // smtp config
        'smtp_port'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 25),
        'smtp_hostname'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'smtp_auth'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'login'),
        'smtp_ssl'=> array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => 'tls',
            'InArray' => array(self::SECURE_NONE, self::SECURE_SSL, self::SECURE_TLS)
        ),
        'smtp_credentials_id'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'smtp_user'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'smtp_password'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // sieve config
        'sieve_port'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 2000),
        'sieve_hostname'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'sieve_ssl'=> array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => 'tls',
            'InArray' => array(self::SECURE_NONE, self::SECURE_TLS)
        ),
        'sieve_vacation_active' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        //'sieve_credentials_id'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        //'sieve_user'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        //'sieve_password'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time'
    );
    
    /**
     * name of fields that should be omited from modlog
     *
     * @var array list of modlog omit fields
     */
    protected $_modlogOmitFields = array(
        'user',
        'password',
        'smtp_user',
        'smtp_password',
        'credentials_id',
        'smtp_credentials_id'
    );
    
    /**
     * overwrite constructor to add more filters
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     * @return void
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        // set some fields to default if not set
        $this->_filters['ssl']              = new Zend_Filter_Empty('tls');
        $this->_filters['smtp_ssl']         = new Zend_Filter_Empty('tls');
        $this->_filters['smtp_port']        = new Zend_Filter_Empty(NULL);
        $this->_filters['sieve_ssl']        = new Zend_Filter_Empty(NULL);
        $this->_filters['sieve_port']       = new Zend_Filter_Empty(NULL);
        $this->_filters['display_format']   = new Zend_Filter_Empty('html');
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
        
    /**
     * get imap config array
     * - decrypt pwd/user with user password
     *
     * @return array
     */
    public function getImapConfig()
    {
        $this->resolveCredentials(FALSE);
        
        $imapConfigFields = array('host', 'port', 'user', 'password');
        $result = array();
        foreach ($imapConfigFields as $field) {
            $result[$field] = $this->{$field};
        }
        
        if ($this->ssl && $this->ssl != 'none') {
            $result['ssl'] = strtoupper($this->ssl);
        }
        
        $result['user'] = $this->_addDomainToUsername($result['user']);
        
        // overwrite settings with config.inc.php values if set
        if (Tinebase_Core::getConfig()->imap) {
            $imapConfig = Tinebase_Core::getConfig()->imap->toArray();
            $imapConfigOverwriteFields = array('host', 'port', 'secure_connection');
            foreach ($imapConfigOverwriteFields as $field) {
                if (array_key_exists($field, $imapConfig)) {
                    if ($field == 'secure_connection' && in_array($imapConfig[$field], array('ssl', 'tls'))) {
                        $result['ssl'] = strtoupper($imapConfig[$field]);
                    } else {
                        $result[$field] = $imapConfig[$field];
                    }
                }
            }
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result, true));
        
        return $result;
    }
    
    /**
     * add domain from imap settings to username
     * 
     * @param string $_username
     * @return string
     */
    protected function _addDomainToUsername($_username)
    {
        $result = $_username;
        
        if ($this->type == self::TYPE_SYSTEM) {
            $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
            if (isset($imapConfig['domain']) && ! empty($imapConfig['domain'])) {
                $result .= '@' . $imapConfig['domain'];
            }
        }
        
        return $result;
    }
    
    /**
     * get smtp config
     *
     * @return array
     */
    public function getSmtpConfig()
    {
        $this->resolveCredentials(FALSE, TRUE, TRUE);
        
        // get values from account
        if ($this->smtp_hostname) {
            $result['hostname'] = $this->smtp_hostname; 
        }
        if ($this->smtp_user) {
            $result['username'] = $this->smtp_user; 
        }
        if ($this->smtp_password) {
            $result['password'] = $this->smtp_password; 
        }
        if ($this->smtp_auth) {
            $result['auth'] = $this->smtp_auth;
        }
        if ($this->smtp_ssl) {
            $result['ssl'] = $this->smtp_ssl;
        }
        if ($this->smtp_port) {
            $result['port'] = $this->smtp_port; 
        }
        
        // system account: overwriting with values from config if set
        if ($this->type == self::TYPE_SYSTEM) {
            $systemAccountConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::SMTP);
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($systemAccountConfig, true));
            // we don't need username/pass from system config (those are the notification service credentials)
            // @todo think about renaming config keys (to something like notification_email/pass)
            unset($systemAccountConfig['username']);
            unset($systemAccountConfig['password']);
            $result = array_merge($result, $systemAccountConfig);
        }
        
        // sanitizing some values
        if (isset($result['primarydomain']) && ! empty($result['primarydomain'])) {            
            $result['username'] .= '@' . $result['primarydomain'];        
        }        
        if (array_key_exists('auth', $result) && $result['auth'] == 'none') {
            unset($result['username']);
            unset($result['password']);
            unset($result['auth']);
        }
        if (array_key_exists('ssl', $result) && $result['ssl'] == 'none') {
            unset($result['ssl']);
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result, true));
        
        return $result;
    }

    /**
     * get sieve config array
     *
     * @return array
     * 
     * @todo add sieve credentials? this uses imap credentials atm.
     */
    public function getSieveConfig()
    {
        $this->resolveCredentials(FALSE);
        
        $result = array(
            'host'      => $this->sieve_hostname,
            'port'      => $this->sieve_port, 
            'ssl'       => ($this->sieve_ssl && $this->sieve_ssl !== self::SECURE_NONE) ? $this->sieve_ssl : FALSE,
            'username'  => $this->user,
            'password'  => $this->password,
        );
        
        $result['username'] = $this->_addDomainToUsername($result['username']);
        
        return $result;
    }
    
    /**
     * to array
     *
     * @param boolean $_recursive
     */
    public function toArray($_recursive = TRUE)
    {
        $result = parent::toArray($_recursive);

        // don't show password
        unset($result['password']);
        unset($result['smtp_password']);
        
        return $result;
    }

    /**
     * resolve imap or smtp credentials
     *
     * @param boolean $_onlyUsername
     * @param boolean $_throwException
     * @param boolean $_smtp
     * @return boolean
     */
    public function resolveCredentials($_onlyUsername = TRUE, $_throwException = FALSE, $_smtp = FALSE)
    {
        if ($_smtp) {
            $passwordField      = 'smtp_password';
            $userField          = 'smtp_user';
            $credentialsField   = 'smtp_credentials_id';
        } else {
            $passwordField      = 'password';
            $userField          = 'user';
            $credentialsField   = 'credentials_id';
        }
        
        if (! $this->{$userField} || ! ($this->{$passwordField} && ! $_onlyUsername)) {
            
            $credentialsBackend = Tinebase_Auth_CredentialCache::getInstance();
            $userCredentialCache = Tinebase_Core::get(Tinebase_Core::USERCREDENTIALCACHE);
            
            if ($userCredentialCache !== NULL) {
                try {
                    $credentialsBackend->getCachedCredentials($userCredentialCache);
                } catch (Exception $e) {
                    return FALSE;
                }
            } else {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ 
                    . ' Something went wrong with the CredentialsCache / use given imap username/password instead.'
                );
                $userCredentialCache = new Tinebase_Model_CredentialCache(array(
                    'username' => $this->user,
                    'password' => $this->password,
                ));
            }
            
            if ($this->type == self::TYPE_USER) {
                if (! $this->{$credentialsField}) {
                    if ($_throwException) {
                        throw new Felamimail_Exception('Could not get credentials, no ' . $credentialsField . ' given.');
                    } else {
                        return FALSE;
                    }
                }
    
                $credentials = $credentialsBackend->get($this->{$credentialsField});
                $credentials->key = substr($userCredentialCache->password, 0, 24);
                $credentialsBackend->getCachedCredentials($credentials);
            } else {
                // just use tine user credentials to connect to mailserver / or use credentials from config if set
                $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
                
                $credentials = $userCredentialCache;
                if (array_key_exists('user', $imapConfig) && array_key_exists('password', $imapConfig) && ! empty($imapConfig['user'])) {
                    $credentials->username = $imapConfig['user'];
                    $credentials->password = $imapConfig['password'];
                }
            }
            
            $this->{$userField} = $credentials->username;
            $this->{$passwordField} = $credentials->password;
        }
        
        return TRUE;
    }
}
