<?php
/**
 * class to hold Account data
 * 
 * @package     Felamimail
 * @subpackage    Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        update account credentials if user password changed
 * @todo        use generic (JSON encoded) field for 'other' settings like folder names
 */

/**
 * class to hold Account data
 * 
 * @property  string    trash_folder
 * @property  string    sent_folder
 * @property  string    drafts_folder
 * @property  string    templates_folder
 * @property  string    sieve_vacation_active
 * @property  string    display_format
 * @property  string    delimiter
 * @property  string    type
 * @property  string    signature_position
 * @property  string    email
 * @package   Felamimail
 * @subpackage    Model
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
     * display format: plain
     *
     */
    const DISPLAY_PLAIN = 'plain';
    
    /**
     * display format: html
     *
     */
    const DISPLAY_HTML = 'html';
    
    /**
     * signature position above quote
     *
     */
    const SIGNATURE_ABOVE_QUOTE = 'above';
    
    /**
     * signature position above quote
     *
     */
    const SIGNATURE_BELOW_QUOTE = 'below';
    
    /**
     * display format: content type
     *
     * -> depending on content_type => text/plain show as plain text
     */
    const DISPLAY_CONTENT_TYPE = 'content_type';
    
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
            Zend_Filter_Input::DEFAULT_VALUE => self::TYPE_USER,
            array('InArray', array(self::TYPE_USER, self::TYPE_SYSTEM)),
        ),
    // imap server config
        'host'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'port'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 143),
        'ssl'                   => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => self::SECURE_TLS,
            array('InArray', array(self::SECURE_NONE, self::SECURE_SSL, self::SECURE_TLS)),
        ),
        'credentials_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'user'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'password'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // other settings (@todo add single JSON encoded field or keyfield for that?)
        'sent_folder'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'Sent'),
        'trash_folder'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'Trash'),
        'drafts_folder'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'Drafts'),
        'templates_folder'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'Templates'),
        'has_children_support'  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'delimiter'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => '/'),
        'display_format'        => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => self::DISPLAY_HTML,
            array('InArray', array(self::DISPLAY_HTML, self::DISPLAY_PLAIN, self::DISPLAY_CONTENT_TYPE)),
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
        'signature_position'    => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => self::SIGNATURE_BELOW_QUOTE,
            array('InArray', array(self::SIGNATURE_ABOVE_QUOTE, self::SIGNATURE_BELOW_QUOTE)),
        ),
        // smtp config
        'smtp_port'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 25),
        'smtp_hostname'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'smtp_auth'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'login'),
        'smtp_ssl'              => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => self::SECURE_TLS,
            array('InArray', array(self::SECURE_NONE, self::SECURE_SSL, self::SECURE_TLS)),
        ),
        'smtp_credentials_id'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'smtp_user'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'smtp_password'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // sieve config
        'sieve_port'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 2000),
        'sieve_hostname'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'sieve_ssl'=> array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => self::SECURE_TLS,
            array('InArray', array(self::SECURE_NONE, self::SECURE_TLS)),
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
        'seq'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
        $this->_filters['ssl']              = array(new Zend_Filter_Empty(self::SECURE_TLS), 'StringTrim', 'StringToLower');
        $this->_filters['smtp_ssl']         = array(new Zend_Filter_Empty(self::SECURE_TLS), 'StringTrim', 'StringToLower');
        $this->_filters['sieve_ssl']        = array(new Zend_Filter_Empty(self::SECURE_TLS), 'StringTrim', 'StringToLower');
        $this->_filters['display_format']   = array(new Zend_Filter_Empty(self::DISPLAY_HTML), 'StringTrim', 'StringToLower');
        $this->_filters['port']             = new Zend_Filter_Empty(NULL);
        $this->_filters['smtp_port']        = new Zend_Filter_Empty(NULL);
        $this->_filters['sieve_port']       = new Zend_Filter_Empty(NULL);
        
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
            if ($field === 'user') {
                $result[$field] = $this->getUsername();
            } else {
                $result[$field] = $this->{$field};
            }
        }
        
        if ($this->ssl && $this->ssl != 'none') {
            $result['ssl'] = strtoupper($this->ssl);
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
    public function getUsername($_username = NULL)
    {
        $result = ($_username !== NULL) ? $_username : $this->user;
        
        if ($this->type == self::TYPE_SYSTEM) {
            $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
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
            $systemAccountConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
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
            'username'  => $this->getUsername(),
            'password'  => $this->password,
        );
        
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
                
                try {
                    // NOTE: cache cleanup process might have removed the cache
                    $credentials = $credentialsBackend->get($this->{$credentialsField});
                    $credentials->key = substr($userCredentialCache->password, 0, 24);
                    $credentialsBackend->getCachedCredentials($credentials);
                } catch (Exception $e) {
                    if ($_throwException) {
                        throw $e;
                    } else {
                        return FALSE;
                    }
                }
            } else {
                // just use tine user credentials to connect to mailserver / or use credentials from config if set
                $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
                
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

    /**
     * returns TRUE if account has capability (i.e. QUOTA, CONDSTORE, ...)
     * 
     * @param $_capability
     * @return boolean
     */
    public function hasCapability($_capability)
    {
        $capabilities = Felamimail_Controller_Account::getInstance()->updateCapabilities($this);
        
        return (in_array($_capability, $capabilities['capabilities']));
    }
}
