<?php
/**
 * class to hold Account data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        update account credentials if user password changed
 * @todo        use generic (JSON encoded) field for 'other' settings like folder names
 * @todo        don't use enum fields (ssl, smtp_ssl, display_format)
 */

/**
 * class to hold Account data
 * 
 * @package     Felamimail
 */
class Felamimail_Model_Account extends Tinebase_Record_Abstract
{  
    /**
     * default account id
     *
     */
    const DEFAULT_ACCOUNT_ID = 'default';
    
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
        'intelligent_folders'    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'has_children_support'  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'sort_folders'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
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
     * name of fields that should be ommited from modlog
     *
     * @var array list of modlog ommit fields
     */
    protected $_modlogOmmitFields = array(
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
        // set ssl/smtp_ssl/display_format to default if not set
        $this->_filters['ssl']              = new Zend_Filter_Empty('tls');
        $this->_filters['smtp_ssl']         = new Zend_Filter_Empty('tls');
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
        if ($this->getId() !== self::DEFAULT_ACCOUNT_ID) {        
            $this->resolveCredentials(FALSE);
        }
        
        $imapConfigFields = array('host', 'port', 'user', 'password');
        $result = array();
        foreach ($imapConfigFields as $field) {
            $result[$field] = $this->{$field};
        }
        
        if ($this->ssl && $this->ssl != 'none') {
            $result['ssl'] = strtoupper($this->ssl);
        }
        
        // add domain
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        if (isset($imapConfig['domain']) && ! empty($imapConfig['domain'])) {
            $result['user'] .= '@' . $imapConfig['domain'];
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
        if ($this->getId() !== self::DEFAULT_ACCOUNT_ID) {        
            $this->resolveCredentials(FALSE, TRUE, TRUE);
        }
        
        // add values from config to empty fields
        $result = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::SMTP);
        
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
            if ( $this->smtp_auth == 'none') {
                unset($result['username']);
                unset($result['password']);
                unset($result['auth']);
            } else {
                $result['auth'] = $this->smtp_auth;
            }
        }
        
        if ($this->smtp_ssl) {
            if ($this->smtp_ssl == 'none') {
                unset($result['ssl']);
            } else {
                $result['ssl'] = $this->smtp_ssl;
            } 
        }

        if ($this->smtp_port) {
            $result['port'] = $this->smtp_port; 
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result, true));
        
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
                $credentialsBackend->getCachedCredentials($userCredentialCache);
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
                // just use tine user credentials to connect to mailserver
                $credentials = $userCredentialCache;
            }
            
            $this->{$userField} = $credentials->username;
            $this->{$passwordField} = $credentials->password;
        }
        
        return TRUE;
    }
}
