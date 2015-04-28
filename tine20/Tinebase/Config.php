<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * the class provides functions to handle config options
 * 
 * @package     Tinebase
 * @subpackage  Config
 * 
 * @todo remove all deprecated stuff
 */
class Tinebase_Config extends Tinebase_Config_Abstract
{
    /**
     * imap conf name
     * 
     * @var string
     */
    const IMAP = 'imap';
    
    /**
     * smtp conf name
     * 
     * @var string
     */
    const SMTP = 'smtp';

    /**
     * sieve conf name
     * 
     * @var string
     */
    const SIEVE = 'sieve';

    /**
     * authentication backend config
     * 
     * @var string
     */
    const AUTHENTICATIONBACKEND = 'Tinebase_Authentication_BackendConfiguration';
    
    /**
     * authentication backend type config
     * 
     * @var string
     */
    const AUTHENTICATIONBACKENDTYPE = 'Tinebase_Authentication_BackendType';
    
    /**
     * save automatic alarms when creating new record
     * 
     * @var string
     */
    const AUTOMATICALARM = 'automaticalarm';
    
    /**
     * user backend config
     * 
     * @var string
     */
    const USERBACKEND = 'Tinebase_User_BackendConfiguration';
    
    /**
     * user backend type config
     * 
     * @var string
     */
    const USERBACKENDTYPE = 'Tinebase_User_BackendType';
    
    /**
     * cronjob user id
     * 
     * @var string
     */
    const CRONUSERID = 'cronuserid';
    
    /**
     * user defined page title postfix for browser page title
     * 
     * @var string
     */
    const PAGETITLEPOSTFIX = 'pagetitlepostfix';

    /**
     * logout redirect url
     * 
     * @var string
     */
    const REDIRECTURL = 'redirectUrl';
    
    /**
     * redirect always
     * 
     * @var string
     */
    const REDIRECTALWAYS = 'redirectAlways';
    
    /**
     * Config key for Setting "Redirect to referring site if exists?"
     * 
     * @var string
     */
    const REDIRECTTOREFERRER = 'redirectToReferrer';
    
    /**
     * Config key for configuring allowed origins of the json frontend
     *  
     * @var string
     */
    const ALLOWEDJSONORIGINS = 'allowedJsonOrigins';
    
    /**
     * Config key for acceptedTermsVersion
     * @var string
     */
    const ACCEPTEDTERMSVERSION = 'acceptedTermsVersion';
    
    /**
     * Config key for map panel in addressbook / include geoext code
     * @var string
     */
    const MAPPANEL = 'mapPanel';

    /**
     * disable ldap certificate check
     *
     * @var string
     */
    const LDAP_DISABLE_TLSREQCERT = 'ldapDisableTlsReqCert';
    
    /**
     * configure hook class for user sync
     *
     * @var string
     */
    const SYNC_USER_HOOK_CLASS = 'syncUserHookClass';
    
    /**
     * configure if user contact data should be synced from sync backend
     *
     * @var boolean
     */
    const SYNC_USER_CONTACT_DATA = 'syncUserContactData';
    
    /**
     * Config key for session ip validation -> if this is set to FALSE no Zend_Session_Validator_IpAddress is registered
     * 
     * @var string
     */
    const SESSIONIPVALIDATION = 'sessionIpValidation';
    
    /**
     * Config key for session user agent validation -> if this is set to FALSE no Zend_Session_Validator_HttpUserAgent is registered
     * 
     * @var string
     */
    const SESSIONUSERAGENTVALIDATION = 'sessionUserAgentValidation';
    
    /**
     * filestore directory
     * 
     * @var string
     */
    const FILESDIR = 'filesdir';
    
    /**
     * xls export config
     * 
     * @deprecated move to app config
     * @var string
     */
    const XLSEXPORTCONFIG = 'xlsexportconfig';
    
    /**
     * app defaults
     * 
     * @deprecated move to app and split
     * @var string
     */
    const APPDEFAULTS = 'appdefaults';
    
    /**
    * REUSEUSERNAME_SAVEUSERNAME
    *
    * @var string
    */
    const REUSEUSERNAME_SAVEUSERNAME = 'saveusername';
        
    /**
    * PASSWORD_CHANGE
    *
    * @var string
    */
    const PASSWORD_CHANGE = 'changepw';
    
    /**
     * PASSWORD_POLICY_ACTIVE
     *
     * @var string
     */
    const PASSWORD_POLICY_ACTIVE = 'pwPolicyActive';
    
    /**
     * PASSWORD_POLICY_ONLYASCII
     *
     * @var string
     */
    const PASSWORD_POLICY_ONLYASCII = 'pwPolicyOnlyASCII';
    
    /**
     * PASSWORD_POLICY_MIN_LENGTH
     *
     * @var string
     */
    const PASSWORD_POLICY_MIN_LENGTH = 'pwPolicyMinLength';
    
    /**
     * PASSWORD_POLICY_MIN_WORD_CHARS
     *
     * @var string
     */
    const PASSWORD_POLICY_MIN_WORD_CHARS = 'pwPolicyMinWordChars';
    
    /**
     * PASSWORD_POLICY_MIN_UPPERCASE_CHARS
     *
     * @var string
     */
    const PASSWORD_POLICY_MIN_UPPERCASE_CHARS = 'pwPolicyMinUppercaseChars';
    
    /**
     * PASSWORD_POLICY_MIN_SPECIAL_CHARS
     *
     * @var string
     */
    const PASSWORD_POLICY_MIN_SPECIAL_CHARS = 'pwPolicyMinSpecialChars';
    
    /**
     * PASSWORD_POLICY_MIN_NUMBERS
     *
     * @var string
     */
    const PASSWORD_POLICY_MIN_NUMBERS = 'pwPolicyMinNumbers';
    
    /**
     * PASSWORD_POLICY_FORBID_USERNAME
     *
     * @var string
     */
    const PASSWORD_POLICY_FORBID_USERNAME = 'pwPolicyForbidUsername';
    
    /**
     * AUTOMATIC_BUGREPORTS
     *
     * @var string
     */
    const AUTOMATIC_BUGREPORTS = 'automaticBugreports';
    
    /**
     * LAST_SESSIONS_CLEANUP_RUN
     *
     * @var string
     */
    const LAST_SESSIONS_CLEANUP_RUN = 'lastSessionsCleanupRun';
    
    /**
     * MAX_LOGIN_FAILURES
     *
     * @var string
     */
    const MAX_LOGIN_FAILURES = 'maxLoginFailures';
     
    /**
     * ANYONE_ACCOUNT_DISABLED
     *
     * @var string
     */
    const ANYONE_ACCOUNT_DISABLED = 'anyoneAccountDisabled';
    
    /**
     * ALARMS_EACH_JOB
     *
     * @var string
     */
    const ALARMS_EACH_JOB = 'alarmsEachJob';
    
    /**
     * ACCOUNT_DEACTIVATION_NOTIFICATION
     *
     * @var string
     */
    const ACCOUNT_DEACTIVATION_NOTIFICATION = 'accountDeactivationNotification';
    
    /**
     * roleChangeAllowed
     *
     * @var string
     */
    const ROLE_CHANGE_ALLOWED = 'roleChangeAllowed';
    
    /**
     * max username length
     *
     * @var string
     */
    const MAX_USERNAME_LENGTH = 'max_username_length';

    /**
     * conf.d folder name
     *
     * @var string
     */
    const CONFD_FOLDER = 'confdfolder';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::IMAP => array(
                                   //_('System IMAP')
            'label'                 => 'System IMAP',
                                   //_('System IMAP server configuration.')
            'description'           => 'System IMAP server configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::SMTP => array(
                                   //_('System SMTP')
            'label'                 => 'System SMTP',
                                   //_('System SMTP server configuration.')
            'description'           => 'System SMTP server configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::SIEVE => array(
                                   //_('System SIEVE')
            'label'                 => 'System SIEVE',
                                   //_('System SIEVE server configuration.')
            'description'           => 'System SIEVE server configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::AUTHENTICATIONBACKENDTYPE => array(
                                   //_('Authentication Backend')
            'label'                 => 'Authentication Backend',
                                   //_('Backend adapter for user authentication.')
            'description'           => 'Backend adapter for user authentication.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::AUTHENTICATIONBACKEND => array(
                                   //_('Authentication Configuration')
            'label'                 => 'Authentication Configuration',
                                   //_('Authentication backend configuration.')
            'description'           => 'Authentication backend configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::USERBACKENDTYPE => array(
                                   //_('User Backend')
            'label'                 => 'User Backend',
                                   //_('Backend adapter for user data.')
            'description'           => 'Backend adapter for user data.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::USERBACKEND => array(
                                   //_('User Configuration')
            'label'                 => 'User Configuration',
                                   //_('User backend configuration.')
            'description'           => 'User backend configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::CRONUSERID => array(
                                   //_('Cronuser ID')
            'label'                 => 'Cronuser ID',
                                   //_('User ID of the cron user.')
            'description'           => 'User ID of the cron user.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => TRUE,
        ),
        self::PAGETITLEPOSTFIX => array(
                                   //_('Title Postfix')
            'label'                 => 'Title Postfix',
                                   //_('Postfix string appended to the title of this installation.')
            'description'           => 'Postfix string appended to the title of this installation.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => TRUE,
        ),
        self::REDIRECTURL => array(
                                   //_('Redirect URL')
            'label'                 => 'Redirect URL',
                                   //_('Redirect to this URL after logout.')
            'description'           => 'Redirect to this URL after logout.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::REDIRECTTOREFERRER => array(
                                   //_('Redirect to Referrer')
            'label'                 => 'Redirect to Referrer',
                                   //_('Redirect to referrer after logout.')
            'description'           => 'Redirect to referrer after logout.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::REDIRECTALWAYS => array(
                                   //_('Redirect Always')
            'label'                 => 'Redirect Always',
                                   //_('Redirect to configured redirect URL also for login.')
            'description'           => 'Redirect to configured redirect URL also for login.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::ALLOWEDJSONORIGINS => array(
                                   //_('Allowed Origins')
            'label'                 => 'Allowed Origins',
                                   //_('Allowed Origins for the JSON API.')
            'description'           => 'Allowed Origins for the JSON API.',
            'type'                  => 'array',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
        ),
        self::ACCEPTEDTERMSVERSION => array(
                                   //_('Accepted Terms Version')
            'label'                 => 'Accepted Terms Version',
                                   //_('Accepted version number of the terms and conditions document.')
            'description'           => 'Accepted version number of the terms and conditions document.',
            'type'                  => 'int',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
        ),
        self::MAPPANEL => array(
                                   //_('Use Geolocation Services')
            'label'                 => 'Use Geolocation Services',
                                   //_('Use of external Geolocation services is allowed.')
            'description'           => 'Use of external Geolocation services is allowed.',
            'type'                  => 'bool',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::LDAP_DISABLE_TLSREQCERT => array(
                                   //_('Disable LDAP TLS Certificate Check')
            'label'                 => 'Disable LDAP TLS Certificate Check',
                                   //_('LDAP TLS Certificate should not be checked')
            'description'           => 'LDAP TLS Certificate should not be checked',
            'type'                  => 'bool',
            'clientRegistryInclude' => false,
            'setByAdminModule'      => false,
            'setBySetupModule'      => true,
            'default'               => false
        ),
        self::SYNC_USER_HOOK_CLASS => array(
                                   //_('Configure hook class for user sync')
            'label'                 => 'Configure hook class for user sync',
                                   //_('Allows to change data after fetching user from sync backend')
            'description'           => 'Allows to change data after fetching user from sync backend',
            'type'                  => 'string',
            'clientRegistryInclude' => false,
            'setByAdminModule'      => false,
            'setBySetupModule'      => true,
        ),
        self::SYNC_USER_CONTACT_DATA => array(
                //_('Sync contact data from sync backend')
                'label'                 => 'Sync contact data from sync backend',
                //_('Sync user contact data from sync backend')
                'description'           => 'Sync user contact data from sync backend',
                'type'                  => 'bool',
                'clientRegistryInclude' => FALSE,
                'setByAdminModule'      => FALSE,
                'setBySetupModule'      => FALSE,
                'default'               => TRUE
        ),
        self::SESSIONIPVALIDATION => array(
                                   //_('IP Session Validator')
            'label'                 => 'IP Session Validator',
                                   //_('Destroy session if the users IP changes.')
            'description'           => 'Destroy session if the users IP changes.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::SESSIONUSERAGENTVALIDATION => array(
                                   //_('UA Session Validator')
            'label'                 => 'UA Session Validator',
                                   //_('Destroy session if the users user agent string changes.')
            'description'           => 'Destroy session if the users user agent string changes.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::FILESDIR => array(
                                   //_('Files Directory')
            'label'                 => 'Files Directory',
                                   //_('Directory with web server write access for user files.')
            'description'           => 'Directory with web server write access for user files.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::REUSEUSERNAME_SAVEUSERNAME => array(
            //_('Reuse last username logged')
            'label'                 => 'Reuse last username logged',
            //_('Reuse last username logged')            
            'description'           => 'Reuse last username logged',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_CHANGE => array(
        //_('User may change password')
            'label'                 => 'User may change password',
        //_('User may change password')
            'description'           => 'User may change password',
            'type'                  => 'bool',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => TRUE
        ),
        self::PASSWORD_POLICY_ACTIVE => array(
        //_('Enable password policy')
            'label'                 => 'Enable password policy',
        //_('Enable password policy')
            'description'           => 'Enable password policy',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_POLICY_ONLYASCII => array(
        //_('Only ASCII')
            'label'                 => 'Only ASCII',
        //_('Only ASCII characters are allowed in passwords.')
            'description'           => 'Only ASCII characters are allowed in passwords.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_POLICY_MIN_LENGTH => array(
        //_('Minimum length')
            'label'                 => 'Minimum length',
        //_('Minimum password length')
            'description'           => 'Minimum password length.',
            'type'                  => 'int',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_POLICY_MIN_WORD_CHARS => array(
        //_('Minimum word chars')
            'label'                 => 'Minimum word chars',
        //_('Minimum word chars in password')
            'description'           => 'Minimum word chars in password',
            'type'                  => 'int',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_POLICY_MIN_UPPERCASE_CHARS => array(
        //_('Minimum uppercase chars')
            'label'                 => 'Minimum uppercase chars',
        //_('Minimum uppercase chars in password')
            'description'           => 'Minimum uppercase chars in password',
            'type'                  => 'int',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_POLICY_MIN_SPECIAL_CHARS => array(
        //_('Minimum special chars')
            'label'                 => 'Minimum special chars',
        //_('Minimum special chars in password')
            'description'           => 'Minimum special chars in password',
            'type'                  => 'int',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_POLICY_MIN_NUMBERS => array(
        //_('Minimum numbers')
            'label'                 => 'Minimum numbers',
        //_('Minimum numbers in password')
            'description'           => 'Minimum numbers in password',
            'type'                  => 'int',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_POLICY_FORBID_USERNAME => array(
        //_('Forbid part of username')
            'label'                 => 'Forbid part of username',
        //_('Forbid part of username in password')
            'description'           => 'Forbid part of username in password',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::AUTOMATIC_BUGREPORTS => array(
                                   //_('Automatic bugreports')
            'label'                 => 'Automatic bugreports',
                                   //_('Always send bugreports, even on timeouts and other exceptions / failures.')
            'description'           => 'Always send bugreports, even on timeouts and other exceptions / failures.',
            'type'                  => 'bool',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::LAST_SESSIONS_CLEANUP_RUN => array(
                                   //_('Last sessions cleanup run')
            'label'                 => 'Last sessions cleanup run',
                                   //_('Stores the timestamp of the last sessions cleanup task run.')
            'description'           => 'Stores the timestamp of the last sessions cleanup task run.',
            'type'                  => self::TYPE_DATETIME,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
        ),
        self::MAX_LOGIN_FAILURES => array(
        //_('Maximum login failures')
            'label'                 => 'Maximum login failures',
        //_('Maximum allowed login failures before blocking account')
            'description'           => 'Maximum allowed login failures before blocking account',
            'type'                  => 'int',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::ANYONE_ACCOUNT_DISABLED => array(
                                   //_('Disable Anyone Account')
            'label'                 => 'Disable Anyone Account',
                                   //_('Disallow anyone account in grant configurations')
            'description'           => 'Disallow anyone account in grant configurations',
            'type'                  => 'bool',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::ALARMS_EACH_JOB => array(
                                   //_('Alarms sent each job')
            'label'                 => 'Alarms sent each job',
                                   //_('Allows to configure the maximum number of alarm notifications in each run of sendPendingAlarms (0 = no limit)')
            'description'           => 'Allows to configure the maximum number of alarm notifications in each run of sendPendingAlarms (0 = no limit)',
            'type'                  => 'integer',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::ACCOUNT_DEACTIVATION_NOTIFICATION => array(
            //_('Account deactivation notfication')
            'label'                 => 'Account deactivation notfication',
            //_('Send E-Mail to user if the account is deactivated or the user tries to login with deactivated account')
            'description'           => 'Send E-Mail to User if the account is deactivated or the user tries to login with deactivated account',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::ROLE_CHANGE_ALLOWED => array(
                                   //_('Role change allowed')
            'label'                 => 'Role change allowed',
                                   //_('Allows to configure which user is allowed to switch to another users account')
            'description'           => 'Allows to configure which user is allowed to switch to another users account',
            'type'                  => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::MAX_USERNAME_LENGTH => array(
                //_('Max username length')
                'label'                 => 'Max username length',
                //_('Max username length')
                'description'           => 'Max username length',
                'type'                  => 'int',
                'default'               => NULL,
                'clientRegistryInclude' => FALSE,
        ),
        self::CONFD_FOLDER => array(
            //_('conf.d folder name')
            'label'                 => 'conf.d folder name',
            //_('Folder for additional config files (conf.d) - NOTE: this is only used if set in config.inc.php!')
            'description'           => 'Folder for additional config files (conf.d) - NOTE: this is only used if set in config.inc.php!',
            'type'                  => 'string',
            'default'               => '',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Tinebase';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;

    /**
     * server classes
     *
     * @var array
     */
    protected static $_serverPlugins = array(
        'Tinebase_Server_Plugin_Json'   => 80,
        'Tinebase_Server_Plugin_WebDAV' => 80,
        'Tinebase_Server_Plugin_Cli'    => 90,
        'Tinebase_Server_Plugin_Http'   => 100
    );

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __clone() {}
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Config();
        }
        
        return self::$_instance;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
    
    /**
     * get config for client registry
     * 
     * @return Tinebase_Config_Struct
     */
    public function getClientRegistryConfig()
    {
        // get all config names to be included in registry
        $clientProperties = new Tinebase_Config_Struct(array());
        $filters = array();
        $userApplications = Tinebase_Core::getUser()->getApplications(TRUE);
        foreach ($userApplications as $application) {
            $config = Tinebase_Config_Abstract::factory($application->name);
            if ($config) {
                $clientProperties[$application->name] = new Tinebase_Config_Struct(array());
                $properties = $config->getProperties();
                foreach ((array) $properties as $name => $definition) {
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                        . ' ' . print_r($definition, TRUE));
                    
                    if ((isset($definition['clientRegistryInclude']) || array_key_exists('clientRegistryInclude', $definition)) && $definition['clientRegistryInclude'] === TRUE) {
                        // add definition here till we have a better place
                        $configRegistryItem = new Tinebase_Config_Struct(array(
                            'value'         => $config->{$name},
                            'definition'    => new Tinebase_Config_Struct($definition),
                        ));
                        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                            . ' ' . print_r($configRegistryItem->toArray(), TRUE));
                        $clientProperties[$application->name][$name] = $configRegistryItem;
                    }
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Got ' . count($clientProperties[$application->name]) . ' config items for ' . $application->name . '.');
            }
        }
        
        return $clientProperties;
    }
    
    /**
     * get application config
     *
     * @param  string  $applicationName Application name
     * @return string  $configClassName
     * 
     * @todo shouldn't this return a config object??
     */
    public static function getAppConfig($applicationName)
    {
        $configClassName = $applicationName . '_Config';
        if (@class_exists($configClassName)) {
            return $configClassName;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Application ' . $applicationName . ' has no config.');
            return NULL;
        }
    }
    
    /**
     * get option setting string
     * 
     * @deprecated
     * @param Tinebase_Record_Interface $_record
     * @param string $_id
     * @param string $_label
     * @return string
     */
    public static function getOptionString($_record, $_label)
    {
        $controller = Tinebase_Core::getApplicationInstance($_record->getApplication());
        $settings = $controller->getConfigSettings();
        $idField = $_label . '_id';
        
        $option = $settings->getOptionById($_record->{$idField}, $_label . 's');
        
        $result = (isset($option[$_label])) ? $option[$_label] : '';
        
        return $result;
    }
}
