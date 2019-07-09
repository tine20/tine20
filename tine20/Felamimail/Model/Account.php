<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        use generic (JSON encoded) field / xprops for 'other' settings like folder names
 * @todo        convert to MCV2
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
 * @property  string    user_id
 * @property  string    sieve_notification_email
 *
 * @package   Felamimail
 * @subpackage    Model
 */
class Felamimail_Model_Account extends Tinebase_EmailUser_Model_Account
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        # TODO switch to mcv2
        # self::VERSION => 25,
        'recordName' => 'Account',
        'recordsName' => 'Accounts', // ngettext('Account', 'Accounts', n)
        'containerName' => 'Email Accounts', // ngettext('Email Account', 'Email Accounts', n)
        'containersName' => 'Email Accounts',
        'hasRelations' => false,
        'copyRelations' => false,
        'hasCustomFields' => false,
        'hasSystemCustomFields' => false,
        'hasNotes' => false,
        'hasTags' => false,
        'modlogActive' => true,
        'hasAttachments' => false,
        'createModule' => false,
        'exposeHttpApi' => false,
        'exposeJsonApi' => true,
        'multipleEdit' => false,

        'titleProperty' => 'name',
        'appName' => 'Felamimail',
        'modelName' => 'Account',

        self::FIELDS => [
            'user_id' => [
                self::TYPE => self::TYPE_USER,
                self::LABEL => 'User', // _('User')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => null
                ],
                self::LENGTH => 40,
            ],
            'type' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 20,
                self::LABEL => 'Type', // _('Type')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::TYPE_USER,
                    ['InArray', [self::TYPE_USER, self::TYPE_SYSTEM, self::TYPE_ADB_LIST, self::TYPE_SHARED]]
                ],
                self::QUERY_FILTER              => true,
            ],
            'name' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Name', // _('Name')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'host' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'IMAP Host', // _('IMAP Host')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'port' => [
                self::TYPE => self::TYPE_INTEGER,
                self::NULLABLE => true,
                self::LABEL => 'IMAP Port', // _('IMAP Port')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 143
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => null,
                ],
            ],
            'ssl' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 32,
                self::LABEL => 'IMAP SSL', // _('IMAP SSL')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::SECURE_TLS,
                    ['InArray', [self::SECURE_NONE, self::SECURE_SSL, self::SECURE_TLS]]
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => self::SECURE_TLS,
                    Zend_Filter_StringTrim::class,
                    Zend_Filter_StringToLower::class
                ],
            ],
            'credentials_id' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 40,
                # self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => null,
                ],
                self::OMIT_MOD_LOG => true,
                self::NULLABLE                  => true,
            ],
            // imap username
            'user' => [
                self::TYPE => self::TYPE_STRING,
                self::SYSTEM => true, // ?
                self::IS_VIRTUAL => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            // imap pw
            'password' => [
                self::TYPE => self::TYPE_STRING,
                self::SYSTEM => true, // ?
                self::IS_VIRTUAL => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            'sent_folder' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Sent Folder', // _('Sent Folder')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'Sent'
                ],
            ],
            'trash_folder' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Trash Folder', // _('Trash Folder')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'Trash'
                ],
            ],
            'drafts_folder' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Drafts Folder', // _('Drafts Folder')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'Drafts'
                ],
            ],
            'templates_folder' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Templates Folder', // _('Templates Folder')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'Templates'
                ],
            ],
            'has_children_support' => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::SYSTEM => true,
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => true
                ],
            ],
            'delimiter' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 1,
                self::SYSTEM => true,
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => '/'
                ],
            ],
            'display_format' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 64,
                self::LABEL => 'Display Format', // _('Display Format')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::DISPLAY_HTML,
                    ['InArray', [self::DISPLAY_HTML, self::DISPLAY_PLAIN, self::DISPLAY_CONTENT_TYPE]]
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => self::DISPLAY_HTML,
                    Zend_Filter_StringTrim::class,
                    Zend_Filter_StringToLower::class
                ],
                self::NULLABLE                  => true,
            ],
            'compose_format' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 64,
                self::LABEL => 'Compose Format', // _('Compose Format')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::DISPLAY_HTML,
                    ['InArray', [self::DISPLAY_HTML, self::DISPLAY_PLAIN]]
                ],
                self::NULLABLE                  => true,
            ],
            'preserve_format' => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::LABEL => 'Preserve Format', // _('Preserve Format')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => false,
                ],
            ],
            'reply_to' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Reply-To', // _('Reply-To')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'ns_personal' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'ns_other' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'ns_shared' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'email' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'E-Mail', // _('E-Mail')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            // sql: from_email + from_name
            'from' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 512,
                self::NULLABLE => true,
                self::LABEL => 'From', // _('From')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'organization' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Organization', // _('Organization')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'signature' => [
                self::TYPE => self::TYPE_TEXT,
                self::LENGTH => 16777215,
                self::NULLABLE => true,
                self::LABEL => 'Signature', // _('Signature')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'signature_position' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 64,
                self::LABEL => 'Signature Position', // _('Signature Position')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::SIGNATURE_BELOW_QUOTE,
                    ['InArray', [self::SIGNATURE_ABOVE_QUOTE, self::SIGNATURE_BELOW_QUOTE]]
                ],
            ],
            'smtp_hostname' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'SMTP Host', // _('SMTP Host')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'smtp_port' => [
                self::TYPE => self::TYPE_INTEGER,
                self::NULLABLE => true,
                self::LABEL => 'SMTP Port', // _('SMTP Port')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 25
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => null,
                ],
            ],
            'smtp_ssl' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 32,
                self::LABEL => 'SMTP SSL', // _('SMTP SSL')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::SECURE_TLS,
                    ['InArray', [self::SECURE_NONE, self::SECURE_SSL, self::SECURE_TLS]]
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => self::SECURE_TLS,
                    Zend_Filter_StringTrim::class,
                    Zend_Filter_StringToLower::class
                ],
            ],
            'smtp_auth' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 32,
                self::LABEL => 'SMTP Authentication', // _('SMTP Authentication')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'login',
                    ['InArray', ['none', 'plain', 'login']]
                ],
                self::NULLABLE                  => true,
            ],
            'smtp_credentials_id' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 40,
                # self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => null,
                ],
                self::OMIT_MOD_LOG => true,
            ],
            'smtp_user' => [
                self::TYPE => self::TYPE_STRING,
                self::SYSTEM => true, // ?
                self::IS_VIRTUAL => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            'smtp_password' => [
                self::TYPE => self::TYPE_STRING,
                self::SYSTEM => true, // ?
                self::IS_VIRTUAL => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            'sieve_hostname' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Sieve Host', // _('Sieve Host')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'sieve_port' => [
                self::TYPE => self::TYPE_INTEGER,
                self::NULLABLE => true,
                self::LABEL => 'Sieve Port', // _('Sieve Port')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 2000
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => null,
                ],
            ],
            'sieve_ssl' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 32,
                self::LABEL => 'Sieve SSL', // _('Sieve SSL')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::SECURE_TLS,
                    ['InArray', [self::SECURE_NONE, self::SECURE_SSL, self::SECURE_TLS]]
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => self::SECURE_TLS,
                    Zend_Filter_StringTrim::class,
                    Zend_Filter_StringToLower::class
                ],
            ],
            'sieve_vacation_active' => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => false,
                ],
            ],
            'sieve_notification_email' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => null,
                ],
            ],
            'all_folders_fetched' => [
                self::TYPE => self::TYPE_BOOLEAN,
                // client only
                self::IS_VIRTUAL => true,
                self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => false,
                ],
            ],
            'imap_status' => [
                self::TYPE => self::TYPE_STRING,
                // client only
                self::IS_VIRTUAL => true,
                self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'success', // TODO an inArray validation with success|failure
                ],
            ],
        ]
    ];

    /**
     * get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->name;
    }

    /**
     * get imap config array
     * - decrypt pwd/user with user password
     *
     * @return array
     * @throws Felamimail_Exception
     * @throws Exception
     */
    public function getImapConfig()
    {
        $this->resolveCredentials(FALSE);
        
        $result = array();
        foreach (array('host', 'port', 'user', 'password') as $field) {
            $result[$field] = $this->{$field};
        }
        
        if ($this->ssl && $this->ssl !== Felamimail_Model_Account::SECURE_NONE) {
            $result['ssl'] = strtoupper($this->ssl);
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
        
        $result = array();
        
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
        
        if (isset($result['auth']) && $result['auth'] === 'none') {
            unset($result['username']);
            unset($result['password']);
            unset($result['auth']);
        }
        if ((isset($result['ssl']) || array_key_exists('ssl', $result)) && $result['ssl'] == 'none') {
            unset($result['ssl']);
        }
        
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
     * @throws Felamimail_Exception
     * @throws Exception
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
            $userCredentialCache = Tinebase_Core::getUserCredentialCache();
            
            if ($userCredentialCache !== NULL) {
                try {
                    $credentialsBackend->getCachedCredentials($userCredentialCache);
                } catch (Exception $e) {
                    return FALSE;
                }
            } else {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ 
                    . ' Something went wrong with the CredentialsCache');
                return FALSE;
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
                } catch (Tinebase_Exception_NotFound $tenf) {
                    // try to use imap credentials & reset smtp credentials if different
                    if ($_smtp) {
                        // TODO ask user for smtp creds if this fails
                        if ($this->smtp_credentials_id !== $this->credentials_id) {
                            $this->smtp_credentials_id = $this->credentials_id;
                            Felamimail_Controller_Account::getInstance()->update($this);
                            return $this->resolveCredentials($_onlyUsername, $_throwException, $_smtp);
                        }
                    }

                    if ($_throwException) {
                        throw $tenf;
                    } else {
                        return FALSE;
                    }
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
                
                // allow to set credentials in config
                if ((isset($imapConfig['user']) || array_key_exists('user', $imapConfig)) && (isset($imapConfig['password']) || array_key_exists('password', $imapConfig)) && ! empty($imapConfig['user'])) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                        ' Using credentials from config for system account.');
                    $credentials->username = $imapConfig['user'];
                    $credentials->password = $imapConfig['password'];
                }
                
                // allow to set pw suffix in config
                if ((isset($imapConfig['pwsuffix']) || array_key_exists('pwsuffix', $imapConfig)) && ! preg_match('/' . preg_quote($imapConfig['pwsuffix']) . '$/', $credentials->password)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                        ' Appending configured pwsuffix to system account password.');
                    $credentials->password .= $imapConfig['pwsuffix'];
                }

                if (isset($imapConfig['useEmailAsUsername']) && $imapConfig['useEmailAsUsername']) {
                    $credentials->username = $this->email;
                } else {
                    $credentials->username = $this->_appendDomainOrInstance($credentials->username, $imapConfig);
                }
            }

            if (! $this->{$userField}) {
                $this->{$userField} = $credentials->username;
            }

            $this->{$passwordField} = $credentials->password;
        }
        
        return TRUE;
    }

    protected function _appendDomainOrInstance($username, $config)
    {
        if (! empty($config['instanceName']) && strpos($username, $config['instanceName']) === false) {
            $user = Tinebase_Core::getUser();
            if ($username !== $user->getId()) {
                $username = $user->getId();
            }
            $username .= '@' . $config['instanceName'];
        } else if (! empty($config['domain']) && strpos($username, $config['domain']) === false) {
            $username .= '@' . $config['domain'];
        }

        return $username;
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
