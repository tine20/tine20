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
 * @todo        use enum/array for tls (and more fields?)
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
    // imap server config
        'host'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'port'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 143),
        'secure_connection'     => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'tls'),
        'credentials_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'user'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'password'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // user data
        'email'                 => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'from'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => ''),
        'signature'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // smtp config
        'smtp_port'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 25),
        'smtp_hostname'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'smtp_auth'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'login'),
        'smtp_secure_connection'=> array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'tls'),
        'smtp_credentials_id'   => array(Zend_Filter_Input::ALLOW_EMPTY => false),
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
     * get imap config array
     * - decrypt pwd/user with user password
     *
     * @return array
     */
    public function getImapConfig()
    {
        $this->resolveCredentials(FALSE, TRUE);
        
        $imapConfigFields = array('host', 'port', 'user', 'password');
        $result = array();
        foreach ($imapConfigFields as $field) {
            $result[$field] = $this->{$field};
        }
        return $result;
    }
    
    /**
     * get smtp config
     *
     * @return array
     * 
     * @todo add values from config to empty fields ?
     */
    public function getSmtpConfig()
    {
        $this->resolveCredentials(FALSE, TRUE, TRUE);
        
        // add values from config to empty fields
        /*
        if (isset(Tinebase_Core::getConfig()->imap->smtp)) {
            $smtpConfig = Tinebase_Core::getConfig()->imap->smtp;
        }
        */
        
        $result = array(
            'hostname'  => $this->smtp_hostname,
            'username'  => $this->smtp_user,
            'password'  => $this->smtp_password,
            'ssl'       => $this->smtp_secure_connection,
            'port'      => $this->smtp_port,
            'auth'      => $this->smtp_auth,        
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
        
        return $result;
    }

    /**
     * resolve credentials
     *
     */
    public function resolveCredentials($_onlyUsername = TRUE, $_throwException = FALSE, $_smtp = FALSE)
    {
        if (! $this->user || (! $this->password && ! $_onlyUsername)) {
            
            $fieldname = ($_smtp) ? smtp_credentials_id : credentials_id;
            
            if (! $this->{$fieldname}) {
                if ($_throwException) {
                    throw new Felamimail_Exception('Could not get credentials, no ' . $fieldname . ' given.');
                } else {
                    return;
                }
            }

            $credentialsBackend = Tinebase_Auth_CredentialCache::getInstance();
            $userCredentialCache = Tinebase_Core::get(Tinebase_Core::USERCREDENTIALCACHE);
            $credentialsBackend->getCachedCredentials($userCredentialCache);
            
            $credentials = $credentialsBackend->get($this->{$fieldname});
            $credentials->key = substr($userCredentialCache->password, 0, 24);
            $credentialsBackend->getCachedCredentials($credentials);
            
            if ($_smtp) {
                $this->smtp_user = $credentials->username;
                $this->smtp_password = $credentials->password;
            } else {
                $this->user = $credentials->username;
                $this->password = $credentials->password;
            }
        }
    }
}
