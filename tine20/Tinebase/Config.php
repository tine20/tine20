<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * access log rotation in days
     *
     * @var string
     */
    const ACCESS_LOG_ROTATION_DAYS = 'accessLogRotationDays';

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
     * authentication second factor
     *
     * @var string
     */
    const AUTHENTICATIONSECONDFACTOR = 'Tinebase_Authentication_SecondFactor';

    /**
     * authentication second factor protection for apps
     *
     * @var string
     */
    const SECONDFACTORPROTECTEDAPPS = 'Tinebase_Authentication_SecondFactor_Protected_Apps';

    /**
     * save automatic alarms when creating new record
     *
     * @var string
     */
    const AUTOMATICALARM = 'automaticalarm';

    /**
     * availableLanguages
     *
     * @var string
     */
    const AVAILABLE_LANGUAGES = 'availableLanguages';

    /**
     * CACHE
     *
     * @var string
     */
    const CACHE = 'caching';

    /**
     * DEFAULT_LOCALE
     *
     * @var string
     */
    const DEFAULT_LOCALE = 'defaultLocale';

    /**
     * default user role
     */
    const DEFAULT_USER_ROLE_NAME = 'defaultUserRoleName';

    /**
     * default user role
     */
    const DEFAULT_ADMIN_ROLE_NAME = 'defaulAdminRoleName';

    /**
     * INTERNET_PROXY
     *
     * @var string
     */
    const INTERNET_PROXY = 'internetProxy';

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
     * user backend config
     * 
     * @var string
     */
    const USERBACKEND = 'Tinebase_User_BackendConfiguration';

    /**
     * sync options for user backend
     *
     * @var string
     */
    const SYNCOPTIONS = 'syncOptions';

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
     * setup user id
     *
     * @var string
     */
    const SETUPUSERID = 'setupuserid';

    /**
     * FEATURE_SHOW_ADVANCED_SEARCH
     *
     * @var string
     */
    const FEATURE_SHOW_ADVANCED_SEARCH = 'featureShowAdvancedSearch';

    /**
     * FEATURE_CONTAINER_CUSTOM_SORT
     *
     * @var string
     */
    const FEATURE_CONTAINER_CUSTOM_SORT = 'featureContainerCustomSort';

    /**
     * FEATURE_SHOW_ACCOUNT_EMAIL
     *
     * @var string
     */
    const FEATURE_SHOW_ACCOUNT_EMAIL = 'featureShowAccountEmail';

    /**
     * FEATURE_REMEMBER_POPUP_SIZE
     *
     * @var string
     */
    const FEATURE_REMEMBER_POPUP_SIZE = 'featureRememberPopupSize';

    /**
     * FEATURE_PATH
     *
     * @var string
     */
    const FEATURE_SEARCH_PATH = 'featureSearchPath';

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
     * overwritten ldap fields
     *
     * @var string
     */
    const LDAP_OVERWRITE_CONTACT_FIELDS = 'ldapOverwriteContactFields';

    /**
     * configure hook class for user sync
     *
     * @var string
     */
    const SYNC_USER_HOOK_CLASS = 'syncUserHookClass';
    
    /**
     * configure if user contact data should be synced from sync backend, default yes
     *
     * @var string
     */
    const SYNC_USER_CONTACT_DATA = 'syncUserContactData';

    /**
     * configure if user contact photo should be synced from sync backend, default yes
     *
     * @var string
     */
    const SYNC_USER_CONTACT_PHOTO = 'syncUserContactPhoto';

    /**
     * configure if deleted users from sync back should be deleted in sql backend, default yes
     *
     * @var string
     */
    const SYNC_DELETED_USER = 'syncDeletedUser';

    /**
     * configure when user should be removed from sql after it is removed from sync backend
     *
     * @var boolean
     */
    const SYNC_USER_DELETE_AFTER = 'syncUserDeleteAfter';

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
     * PASSWORD_POLICY_CHANGE_AFTER
     *
     * @var string
     */
    const PASSWORD_POLICY_CHANGE_AFTER = 'pwPolicyChangeAfter';

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
     * WARN_LOGIN_FAILURES
     *
     * @var string
     */
    const WARN_LOGIN_FAILURES = 'warnLoginFailures';
     
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
     * ACCOUNT_DELETION_EVENTCONFIGURATION
     *
     * @var string
     */
    const ACCOUNT_DELETION_EVENTCONFIGURATION = 'accountDeletionEventConfiguration';
    
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
     * maintenance mode
     *
     * @var string
     */
    const MAINTENANCE_MODE = 'maintenanceMode';
    const MAINTENANCE_MODE_OFF = 'off';
    const MAINTENANCE_MODE_NORMAL = 'normal';
    const MAINTENANCE_MODE_ALL = 'all';

    /**
     * @var string
     */
    const FAT_CLIENT_CUSTOM_JS = 'fatClientCustomJS';
    
    const BRANDING_LOGO = 'branding_logo';
    const BRANDING_FAVICON = 'branding_favicon';
    const BRANDING_TITLE = 'branding_title';
    const BRANDING_WEBURL = 'branding_weburl';
    const BRANDING_DESCRIPTION = 'branding_description';

    const CURRENCY_SYMBOL = 'currencySymbol';

    /**
     * @var string
     */
    const USE_LOGINNAME_AS_FOLDERNAME = 'useLoginnameAsFoldername';

    /**
     * @var string
     */
    const DENY_WEBDAV_CLIENT_LIST = 'denyWebDavClientList';

    /**
     * @var string
     */
    const VERSION_CHECK = 'versionCheck';

    /**
     * WEBDAV_SYNCTOKEN_ENABLED
     *
     * @var string
     */
    const WEBDAV_SYNCTOKEN_ENABLED = 'webdavSynctokenEnabled';

    /**
     * @var string
     */
    const REPLICATION_MASTER = 'replicationMaster';

    /**
     * @var string
     */
    const REPLICATION_SLAVE = 'replicationSlave';

    /**
     * @var string
     */
    const REPLICATION_USER_PASSWORD = 'replicationUserPassword';

    /**
     * @var string
     */
    const MASTER_URL = 'masterURL';

    /**
     * @var string
     */
    const MASTER_USERNAME = 'masterUsername';

    /**
     * @var string
     */
    const MASTER_PASSWORD = 'masterPassword';

    /**
     * @var string
     */
    const ERROR_NOTIFICATION_LIST = 'errorNotificationList';

    const FULLTEXT = 'fulltext';
    const FULLTEXT_BACKEND = 'backend';
    const FULLTEXT_JAVABIN = 'javaBin';
    const FULLTEXT_TIKAJAR = 'tikaJar';
    const FULLTEXT_QUERY_FILTER = 'queryFilter';

    const FILESYSTEM = 'filesystem';
    const FILESYSTEM_MODLOGACTIVE = 'modLogActive';
    const FILESYSTEM_NUMKEEPREVISIONS = 'numKeepRevisions';
    const FILESYSTEM_MONTHKEEPREVISIONS = 'monthKeepRevisions';
    const FILESYSTEM_INDEX_CONTENT = 'index_content';
    const FILESYSTEM_CREATE_PREVIEWS = 'createPreviews';
    const FILESYSTEM_PREVIEW_SERVICE_URL = 'previewServiceUrl';
    const FILESYSTEM_PREVIEW_SERVICE_CLASS = 'previewServiceClass';
    const FILESYSTEM_ENABLE_NOTIFICATIONS = 'enableNotifications';

    const ACTIONQUEUE = 'actionqueue';
    const ACTIONQUEUE_BACKEND = 'backend';
    const ACTIONQUEUE_ACTIVE = 'active';
    const ACTIONQUEUE_HOST = 'host';
    const ACTIONQUEUE_PORT = 'port';
    const ACTIONQUEUE_NAME = 'queueName';

    const QUOTA = 'quota';
    const QUOTA_SHOW_UI = 'showUI';
    const QUOTA_INCLUDE_REVISION = 'includeRevision';
    const QUOTA_TOTALINMB = 'totalInMB';
    const QUOTA_TOTALBYUSERINMB = 'totalByUserInMB';
    const QUOTA_SOFT_QUOTA = 'softQuota';
    const QUOTA_SQ_NOTIFICATION_ROLE = 'softQuotaNotificationRole';
    const QUOTA_SKIP_IMAP_QUOTA = 'skipImapQuota';

    const TINE20_URL = 'tine20URL';


    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::ACCESS_LOG_ROTATION_DAYS => [
            //_('Accesslog rotation in days')
            'label'                 => 'Accesslog rotation in days',
            //_('Accesslog rotation in days')
            'description'           => 'Accesslog rotation in days',
            'type'                  => self::TYPE_INT,
            'default'               => 7,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => TRUE,
        ],
        /**
         * possible values:
         *
         * $_deletePersonalContainers => delete personal containers
         * $_keepAsContact => keep "account" as contact in the addressbook
         * $_keepOrganizerEvents => keep accounts organizer events as external events in the calendar
         * $_keepAsContact => keep accounts calender event attendee as external attendee
         *
         * TODO add more options (like move to another container)
         */
        self::ACCOUNT_DELETION_EVENTCONFIGURATION => array(
            //_('Account Deletion Event')
            'label'                 => 'Account Deletion Event',
            //_('Configure what should happen to data of deleted users')
            'description'           => 'Configure what should happen to data of deleted users',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => TRUE,
        ),
        /**
         * for example: array('en', 'de')
         */
        self::AVAILABLE_LANGUAGES => array(
            //_('Available Languages')
            'label'                 => 'Available Languages',
            //_('Whitelist available languages that can be chosen in the GUI')
            'description'           => 'Whitelist available languages that can be chosen in the GUI',
            'type'                  => 'array',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => TRUE,
        ),
        /**
         * for example: 'de'
         */
        self::DEFAULT_LOCALE => array(
            //_('Default Locale')
            'label'                 => 'Default Locale',
            //_('Default locale for this installation.')
            'description'           => 'Default locale for this installation.',
            'type'                  => 'string',
            'default'               => 'en',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        /**
         * config keys (see Zend_Http_Client_Adapter_Proxy):
         *
         * 'proxy_host' => 'proxy.com',
         * 'proxy_port' => 3128,
         * 'proxy_user' => 'user',
         * 'proxy_pass' => 'pass'
         */
        self::INTERNET_PROXY => array(
            //_('Internet proxy config')
            'label'                 => 'Internet proxy config',
            'description'           => 'Internet proxy config',
            'type'                  => 'array',
            'default'               => array(),
            'clientRegistryInclude' => false,
            'setByAdminModule'      => true,
            'setBySetupModule'      => true,
        ),
        /**
         * config keys:
         *
         * useSystemAccount (bool)
         * domain (string)
         * instanceName (string)
         * useEmailAsUsername (bool)
         * host (string)
         * port (integer)
         * ssl (bool)
         * user (string) ?
         * backend (string) - see Tinebase_EmailUser::$_supportedBackends
         * verifyPeer (bool)
         */
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
        /**
         * example config:
         *
         * array(
         *      'active'                => true,
         *      'provider'              => 'PrivacyIdea',
         *      'url'                   => 'https://localhost/validate/check',
         *      'allow_self_signed'     => true,
         *      'ignorePeerName'        => true,
         *      'login'                 => true, // validate during login + show field on login screen
         *      'sessionLifetime'       => 15 // minutes
         * )
         */
        self::AUTHENTICATIONSECONDFACTOR => array(
            //_('Second Factor Authentication Configuration')
            'label'                 => 'Second Factor Authentication Configuration',
            'description'           => 'Second Factor Authentication Configuration',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::SECONDFACTORPROTECTEDAPPS => array(
            //_('Second Factor Protected Applications')
            'label'                 => 'Second Factor Protected Applications',
            //_('Second Factor Authentication is needed to access these applications')
            'description'           => 'Second Factor Authentication is needed to access these applications',
            'type'                  => 'array',
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'setBySetupModule'      => true,
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
        self::REPLICATION_MASTER => array(
            //_('Replication master configuration')
            'label'                 => 'Replication master configuration',
            //_('Replication master configuration.')
            'description'           => 'Replication master configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'content'               => array(
                self::REPLICATION_USER_PASSWORD     => array(
                    'type'                              => Tinebase_Config::TYPE_STRING
                )
            ),
        ),
        self::REPLICATION_SLAVE => array(
            //_('Replication slave configuration')
            'label'                 => 'Replication slave configuration',
            //_('Replication slave configuration.')
            'description'           => 'Replication slave configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'content'               => array(
                self::MASTER_URL                => array(
                    'type'                          => Tinebase_Config::TYPE_STRING,
                ),
                self::MASTER_USERNAME           => array(
                    'type'                          => Tinebase_Config::TYPE_STRING,
                ),
                self::MASTER_PASSWORD           => array(
                    'type'                          => Tinebase_Config::TYPE_STRING,
                ),
                self::ERROR_NOTIFICATION_LIST   => array(
                    'type'                          => Tinebase_Config::TYPE_ARRAY,
                )
            )
        ),
        self::FULLTEXT => array(
            //_('Full text configuration')
            'label'                 => 'Full text configuration',
            //_('Full text configuration.')
            'description'           => 'Full text configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'content'               => array(
                self::FULLTEXT_BACKEND          => array(
                    'type'                              => Tinebase_Config::TYPE_STRING,
                    'default'                           => 'Sql'
                ),
                self::FULLTEXT_JAVABIN          => array(
                    'type'                              => Tinebase_Config::TYPE_STRING,
                    'default'                           => 'java'
                ),
                self::FULLTEXT_TIKAJAR          => array(
                    'type'                              => Tinebase_Config::TYPE_STRING,
                ),
                // shall we include fulltext fields in the query filter?
                self::FULLTEXT_QUERY_FILTER     => array(
                    'type'                              => Tinebase_Config::TYPE_BOOL,
                    'default'                           => false
                ),
            ),
            'default'                           => array()
        ),
        self::ACTIONQUEUE => array(
            //_('Action queue configuration')
            'label'                 => 'Action queue configuration',
            //_('Action queue configuration.')
            'description'           => 'Action queue configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'content'               => array(
                self::ACTIONQUEUE_BACKEND       => array(
                    'type'                              => Tinebase_Config::TYPE_STRING,
                    'default'                           => 'Direct'
                ),
                self::ACTIONQUEUE_ACTIVE       => array(
                    'type'                              => Tinebase_Config::TYPE_BOOL,
                    'default'                           => false
                ),
                self::ACTIONQUEUE_HOST       => array(
                    'type'                              => Tinebase_Config::TYPE_STRING,
                    'default'                           => 'localhost'
                ),
                self::ACTIONQUEUE_PORT       => array(
                    'type'                              => Tinebase_Config::TYPE_INT,
                    'default'                           => 6379
                ),
                self::ACTIONQUEUE_NAME       => array(
                    'type'                              => Tinebase_Config::TYPE_STRING,
                    'default'                           => 'TinebaseQueue'
                ),
            ),
            'default'                           => array()
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
            'content'               => array(
                Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                Tinebase_User::DEFAULT_ADMIN_GROUP_NAME_KEY => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'host'                      => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'port'                      => array(
                    'type'                      => Tinebase_Config::TYPE_INT,
                ),
                'useSsl'                    => array(
                    'type'                      => Tinebase_Config::TYPE_BOOL,
                ),
                'username'                  => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'password'                  => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'bindRequiresDn'            => array(
                    'type'                      => Tinebase_Config::TYPE_BOOL,
                ),
                'baseDn'                    => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'accountCanonicalForm'      => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'accountDomainName'         => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'accountDomainNameShort'    => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'accountFilterFormat'       => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'allowEmptyPassword'        => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'useStartTls'               => array(
                    'type'                      => Tinebase_Config::TYPE_BOOL,
                ),
                'optReferrals'              => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'tryUsernameSplit'          => array(
                    'type'                      => Tinebase_Config::TYPE_BOOL,
                ),
                'groupUUIDAttribute'        => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'groupsDn'                  => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'useRfc2307bis'             => array(
                    'type'                      => Tinebase_Config::TYPE_BOOL,
                ),
                'userDn'                    => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'userFilter'                => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'userSearchScope'           => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'groupFilter'               => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'groupSearchScope'          => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'pwEncType'                 => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'minUserId'                 => array(
                    'type'                      => Tinebase_Config::TYPE_INT,
                ),
                'maxUserId'                 => array(
                    'type'                      => Tinebase_Config::TYPE_INT,
                ),
                'minGroupId'                => array(
                    'type'                      => Tinebase_Config::TYPE_INT,
                ),
                'maxGroupId'                => array(
                    'type'                      => Tinebase_Config::TYPE_INT,
                ),
                'userUUIDAttribute'         => array(
                    'type'                      => Tinebase_Config::TYPE_STRING,
                ),
                'readonly'                  => array(
                    'type'                      => Tinebase_Config::TYPE_BOOL,
                ),
                'useRfc2307'                => array(
                    'type'                      => Tinebase_Config::TYPE_BOOL,
                ),
                self::SYNCOPTIONS           => array(
                    'type'                      => 'object',
                    'class'                     => 'Tinebase_Config_Struct',
                    'content'                   => array(
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
                        self::SYNC_USER_CONTACT_PHOTO => array(
                            //_('Sync contact photo from sync backend')
                            'label'                 => 'Sync contact photo from sync backend',
                            //_('Sync user contact photo from sync backend')
                            'description'           => 'Sync user contact photo from sync backend',
                            'type'                  => 'bool',
                            'clientRegistryInclude' => FALSE,
                            'setByAdminModule'      => FALSE,
                            'setBySetupModule'      => FALSE,
                            'default'               => TRUE
                        ),
                        self::SYNC_DELETED_USER => array(
                            //_('Sync deleted users from sync backend')
                            'label'                 => 'Sync deleted users from sync backend',
                            //_('Sync deleted users from sync backend')
                            'description'           => 'Sync deleted users from sync backend',
                            'type'                  => 'bool',
                            'clientRegistryInclude' => FALSE,
                            'setByAdminModule'      => FALSE,
                            'setBySetupModule'      => FALSE,
                            'default'               => TRUE
                        ),
                    ),
                    'default'                   => array(),
                ),
            ),
        ),
        self::ENABLED_FEATURES => [
            //_('Enabled Features')
            self::LABEL                 => 'Enabled Features',
            self::DESCRIPTION           => 'Enabled Features',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [
                self::FEATURE_SHOW_ADVANCED_SEARCH  => array(
                    self::LABEL                         => 'Show Advanced Search', //_('Show Advanced Search')
                    self::DESCRIPTION                   =>
                        'Show toggle button to switch on or off the advanced search for the quickfilter',
                    //_('Show toggle button to switch on or off the advanced search for the quickfilter')
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => true,
                ),
                self::FEATURE_CONTAINER_CUSTOM_SORT => array(
                    self::LABEL                         => 'Container Custom Sort', //_('Container Custom Sort')
                    self::DESCRIPTION                   =>
                        'Allows to sort containers by setting the sort order in Admin/Container',
                    //_('Allows to sort containers by setting the sort order in Admin/Container')
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => true,
                ),
                self::FEATURE_SHOW_ACCOUNT_EMAIL    => array(
                    self::LABEL                         => 'Show Account Email Address',
                    //_('Show Account Email Address')
                    self::DESCRIPTION                   => 'Show email address in account picker and attendee grids',
                    //_('Show email address in account picker and attendee grids')
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => true,
                ),
                self::FEATURE_REMEMBER_POPUP_SIZE   => array(
                    self::LABEL                         => 'Remeber Popup Size', //_('Remeber Popup Size')
                    self::DESCRIPTION                   => 'Save edit dialog size in state',
                    //_('Save edit dialog size in state')
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => true,
                ),
                self::FEATURE_SEARCH_PATH           => array(
                    self::LABEL                         => 'Search Paths',
                    self::DESCRIPTION                   => 'Search Paths',
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => true,
                ),
            ],
            self::DEFAULT_STR => [],
        ],
        self::DEFAULT_ADMIN_ROLE_NAME => array(
            //_('Default Admin Role Name')
            'label'                 => 'Default Admin Role Name',
            'description'           => 'Default Admin Role Name',
            'type'                  => 'string',
            'clientRegistryInclude' => false,
            'setByAdminModule'      => false,
            'setBySetupModule'      => true,
            'default'               => 'admin role'
        ),
        self::DEFAULT_USER_ROLE_NAME => array(
            //_('Default User Role Name')
            'label'                 => 'Default User Role Name',
            'description'           => 'Default User Role Name',
            'type'                  => 'string',
            'clientRegistryInclude' => false,
            'setByAdminModule'      => false,
            'setBySetupModule'      => true,
            'default'               => 'user role'
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
            'clientRegistryInclude' => true,
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
            'clientRegistryInclude' => true,
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
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'setBySetupModule'      => true,
            'default'               => true,
        ),
        // TODO should this be added to LDAP config array/struct?
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
        // TODO should this be added to LDAP config array/struct?
        // TODO does this depend on LDAP readonly option?
        self::LDAP_OVERWRITE_CONTACT_FIELDS => array(
            //_('Contact fields overwritten by LDAP')
            'label'                 => 'Contact fields overwritten by LDAP',
            //_('These fields are overwritten during LDAP sync if empty')
            'description'           => 'These fields are overwritten during LDAP sync if empty',
            'type'                  => 'array',
            'clientRegistryInclude' => false,
            'setByAdminModule'      => false,
            'setBySetupModule'      => true,
            'default'               => array()
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
        self::SYNC_USER_DELETE_AFTER => array(
            //_('Sync user: delete after X months)
            'label'                 => 'Sync user: delete after X months',
            //_('Removed users should be deleted after X months')
            'description'           => 'Removed users should be deleted after X months',
            'type'                  => self::TYPE_INT,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
            'default'               => 12,
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
        // TODO move to FILESYSTEM
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
            'clientRegistryInclude' => TRUE,
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
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_POLICY_MIN_WORD_CHARS => array(
        //_('Minimum word chars')
            'label'                 => 'Minimum word chars',
        //_('Minimum word chars in password')
            'description'           => 'Minimum word chars in password',
            'type'                  => 'int',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_POLICY_MIN_UPPERCASE_CHARS => array(
        //_('Minimum uppercase chars')
            'label'                 => 'Minimum uppercase chars',
        //_('Minimum uppercase chars in password')
            'description'           => 'Minimum uppercase chars in password',
            'type'                  => 'int',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_POLICY_MIN_SPECIAL_CHARS => array(
        //_('Minimum special chars')
            'label'                 => 'Minimum special chars',
        //_('Minimum special chars in password')
            'description'           => 'Minimum special chars in password',
            'type'                  => 'int',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::PASSWORD_POLICY_MIN_NUMBERS => array(
        //_('Minimum numbers')
            'label'                 => 'Minimum numbers',
        //_('Minimum numbers in password')
            'description'           => 'Minimum numbers in password',
            'type'                  => 'int',
            'clientRegistryInclude' => TRUE,
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
        self::PASSWORD_POLICY_CHANGE_AFTER => array(
        //_('Change Password After ... Days')
            'label'                 => 'Change Password After ... Days',
        //_('Users need to change their passwords after defined number of days')
            'description'           => 'Users need to change their passwords after defined number of days',
            'type'                  => 'integer',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => 0,
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
        self::WARN_LOGIN_FAILURES => array(
            //_('Warn after X login failures')
            'label'                 => 'Warn after X login failures',
            //_('Maximum allowed login failures before writing warn log messages')
            'description'           => 'Maximum allowed login failures before writing warn log messages',
            'type'                  => 'int',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => 4
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
        self::MAINTENANCE_MODE => array(
            //_('Maintenance mode enabled')
            'label'                 => 'Maintenance mode enabled',
            //_('Set Tine 2.0 maintenance mode. Possible values: "off", "normal" (only users having the maintenance right can login) and "all"')
            'description'           => 'Set Tine 2.0 maintenance mode. Possible values: "off", "normal" (only users having the maintenance right can login) and "all"',
            'type'                  => 'string',
            'default'               => '',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => TRUE,
        ),
        self::VERSION_CHECK => array(
            //_('Version check enabled')
            'label'                 => 'Version check enabled',
            'description'           => 'Version check enabled',
            'type'                  => 'bool',
            'default'               => true,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'setBySetupModule'      => false,
        ),
        self::FAT_CLIENT_CUSTOM_JS => array(
            // NOTE: it's possible to deliver customjs vom vfs by using the tine20:// streamwrapper
            //       tine20://<applicationid>/folders/shared/<containerid>/custom.js
            //_('Custom Javascript includes for Fat-Client')
            'label'                 => 'Custom Javascript includes for Fat-Client',
            //_('An array of javascript files to include for the fat client. This files might be stored outside the docroot of the webserver.')
            'description'           => "An array of javascript files to include for the fat client. This files might be stored outside the docroot of the webserver.",
            'type'                  => 'array',
            'default'               => array(),
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
        ),
        self::BRANDING_DESCRIPTION => array(
                //_('custom description')
                'label'                 => 'custom description',
                //_('Custom description for branding.')
                'description'           => 'Custom description for branding.',
                'type'                  => 'string',
                'default'               => '',
                'clientRegistryInclude' => FALSE,
                'setByAdminModule'      => FALSE,
                'setBySetupModule'      => FALSE,
        ),
        self::BRANDING_WEBURL => array(
                //_('custom weburl')
                'label'                 => 'custom weburl',
                //_('Custom weburl for branding.')
                'description'           => 'Custom weburl for branding.',
                'type'                  => 'string',
                'default'               => '',
                'clientRegistryInclude' => FALSE,
                'setByAdminModule'      => FALSE,
                'setBySetupModule'      => FALSE,
        ),
        self::BRANDING_TITLE => array(
                //_('custom title')
                'label'                 => 'custom title',
                //_('Custom title for branding.')
                'description'           => 'Custom ltitle for branding.',
                'type'                  => 'string',
                'default'               => '',
                'clientRegistryInclude' => FALSE,
                'setByAdminModule'      => FALSE,
                'setBySetupModule'      => FALSE,
        ),
        self::BRANDING_LOGO => array(
                //_('custom logo path')
                'label'                 => 'custom logo path',
                //_('Path to custom logo.')
                'description'           => 'Path to custom logo.',
                'type'                  => 'string',
                'default'               => './images/tine_logo.png',
                'clientRegistryInclude' => FALSE,
                'setByAdminModule'      => FALSE,
                'setBySetupModule'      => FALSE,
        ),
        self::BRANDING_FAVICON => array(
                //_('custom favicon path')
                'label'                 => 'custom favicon path',
                //_('Path to custom favicon.')
                'description'           => 'Path to custom favicon.',
                'type'                  => 'string',
                'default'               => '',
                'clientRegistryInclude' => FALSE,
                'setByAdminModule'      => FALSE,
                'setBySetupModule'      => FALSE,
        ),
        self::USE_LOGINNAME_AS_FOLDERNAME => array(
        //_('Use login name instead of full name')
            'label'                 => 'Use login name instead of full name',
        //_('Use login name instead of full name for webdav.')
            'description'           => 'Use login name instead of full name for webdav.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
            'default'               => FALSE,
        ),
        self::DENY_WEBDAV_CLIENT_LIST  => array(
            //_('List of WebDav agent strings that will be denied')
            'label'                 => 'List of WebDav agent strings that will be denied',
            //_('List of WebDav agent strings that will be denied.')
            'description'           => 'List of WebDav agent strings that will be denied.',
            'type'                  => 'array',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
            'default'               => NULL,
        ),
        self::WEBDAV_SYNCTOKEN_ENABLED => array(
        //_('Enable SyncToken plugin')
            'label'                 => 'Enable SyncToken plugin',
        //_('Enable the use of the SyncToken plugin.')
            'description'           => 'Enable the use of the SyncToken plugin.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
            'default'               => TRUE,
        ),
        self::CURRENCY_SYMBOL => array(
            //_('currency symbol')
            'label' => 'urrency symbol',
            //_('Path to custom favicon.')
            'description' => 'Define currency symbol to be used.',
            'type' => 'string',
            'default' => '€',
            'clientRegistryInclude' => true,
            'setByAdminModule' => false,
            'setBySetupModule' => false,
        ),
        self::FILESYSTEM => array(
            //_('Filesystem settings')
            'label'                 => 'Filesystem settings',
            //_('Filesystem settings.')
            'description'           => 'Filesystem settings.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
            'content'               => array(
                self::FILESYSTEM_MODLOGACTIVE => array(
                    //_('Filesystem history')
                    'label'                 => 'Filesystem history',
                    //_('Filesystem keeps history, default is false.')
                    'description'           => 'Filesystem keeps history, default is false.',
                    'type'                  => 'bool',
                    'clientRegistryInclude' => TRUE,
                    'setByAdminModule'      => FALSE,
                    'setBySetupModule'      => FALSE,
                    'default'               => FALSE,
                ),
                self::FILESYSTEM_NUMKEEPREVISIONS => array(
                    //_('Filesystem number of revisions')
                    'label'                 => 'Filesystem number of revisions',
                    //_('Filesystem number of revisions being kept before they are automatically deleted.')
                    'description'           => 'Filesystem number of revisions being kept before they are automatically deleted.',
                    'type'                  => 'integer',
                    'clientRegistryInclude' => TRUE,
                    'setByAdminModule'      => FALSE,
                    'setBySetupModule'      => FALSE,
                    'default'               => 100,
                ),
                self::FILESYSTEM_MONTHKEEPREVISIONS => array(
                    //_('Filesystem months of revisions')
                    'label'                 => 'Filesystem months of revisions',
                    //_('Filesystem number of months revisions being kept before they are automatically deleted.')
                    'description'           => 'Filesystem number of months revisions being kept before they are automatically deleted.',
                    'type'                  => 'integer',
                    'clientRegistryInclude' => TRUE,
                    'setByAdminModule'      => FALSE,
                    'setBySetupModule'      => FALSE,
                    'default'               => 60,
                ),
                self::FILESYSTEM_INDEX_CONTENT => array(
                    //_('Filesystem index content')
                    'label'                 => 'Filesystem index content',
                    //_('Filesystem index content.')
                    'description'           => 'Filesystem index content.',
                    'type'                  => 'bool',
                    'clientRegistryInclude' => TRUE,
                    'setByAdminModule'      => FALSE,
                    'setBySetupModule'      => FALSE,
                    'default'               => FALSE,
                ),
                self::FILESYSTEM_ENABLE_NOTIFICATIONS => array(
                    //_('Filesystem enable notifications')
                    'label'                 => 'Filesystem enable notifications',
                    //_('Filesystem enable notifications.')
                    'description'           => 'Filesystem enable notifications.',
                    'type'                  => 'bool',
                    'clientRegistryInclude' => TRUE,
                    'setByAdminModule'      => FALSE,
                    'setBySetupModule'      => FALSE,
                    'default'               => FALSE,
                ),
                self::FILESYSTEM_CREATE_PREVIEWS => array(
                    //_('Filesystem create previews')
                    'label'                 => 'Filesystem create previews',
                    //_('Filesystem create previews.')
                    'description'           => 'Filesystem create previews.',
                    'type'                  => 'bool',
                    'clientRegistryInclude' => TRUE,
                    'setByAdminModule'      => FALSE,
                    'setBySetupModule'      => FALSE,
                    'default'               => FALSE,
                ),
                self::FILESYSTEM_PREVIEW_SERVICE_URL => array(
                    //_('URL of preview service')
                    'label'                 => 'URL of preview service',
                    //_('URL of preview service.')
                    'description'           => 'URL of preview service.',
                    'type'                  => 'string',
                    'clientRegistryInclude' => FALSE,
                    'setByAdminModule'      => FALSE,
                    'setBySetupModule'      => FALSE,
                    'default'               => NULL,
                ),
                self::FILESYSTEM_PREVIEW_SERVICE_CLASS => array(
                    //_('Class for preview service')
                    'label'                 => 'Class for preview service',
                    //_('Class to use, to connect to preview service.')
                    'description'           => 'Class to use, to connect to preview service.',
                    'type'                  => 'string',
                    'clientRegistryInclude' => FALSE,
                    'setByAdminModule'      => FALSE,
                    'setBySetupModule'      => FALSE,
                    'default'               => 'Tinebase_FileSystem_Preview_ServiceV1',
                ),
            ),
            'default'               => array(),
        ),
        self::QUOTA => array(
            //_('Quota settings')
            'label'                 => 'Quota settings',
            //_('Quota settings')
            'description'           => 'Quota settings',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'setBySetupModule'      => false,
            'content'               => array(
                self::QUOTA_SHOW_UI => array(
                    //_('Show UI')
                    'label'                 => 'Show UI',
                    //_('Should the quota UI elements be rendered or not.')
                    'description'           => 'Should the quota UI elements be rendered or not.',
                    'type'                  => 'bool',
                    'clientRegistryInclude' => true,
                    'setByAdminModule'      => true,
                    'setBySetupModule'      => true,
                    'default'               => true,
                ),
                self::QUOTA_INCLUDE_REVISION => array(
                    //_('Include revisions')
                    'label'                 => 'Include revisions',
                    //_('Should all revisions be used to calculate total usage?')
                    'description'           => 'Should all revisions be used to calculate total usage?',
                    'type'                  => 'bool',
                    'clientRegistryInclude' => true,
                    'setByAdminModule'      => true,
                    'setBySetupModule'      => false,
                    'default'               => false,
                ),
                self::QUOTA_TOTALINMB => array(
                    //_('Total quota in MB')
                    'label'                 => 'Total quota in MB',
                    //_('Total quota in MB (0 for unlimited)')
                    'description'           => 'Total quota in MB (0 for unlimited)',
                    'type'                  => 'integer',
                    'clientRegistryInclude' => true,
                    'setByAdminModule'      => true,
                    'setBySetupModule'      => false,
                    'default'               => 0,
                ),
                self::QUOTA_TOTALBYUSERINMB => array(
                    //_('Total quota by user in MB')
                    'label'                 => 'Total quota by user in MB',
                    //_('Total quota by user in MB (0 for unlimited)')
                    'description'           => 'Total quota by user in MB (0 for unlimited)',
                    'type'                  => 'integer',
                    'clientRegistryInclude' => true,
                    'setByAdminModule'      => true,
                    'setBySetupModule'      => false,
                    'default'               => 0,
                ),
                self::QUOTA_SOFT_QUOTA => array(
                    //_('Soft quota in %')
                    'label'                 => 'Soft quota in %',
                    //_('Soft quota in % (0-100, 0 means off)')
                    'description'           => 'Soft quota in % (0-100, 0 means off)',
                    'type'                  => 'integer',
                    'clientRegistryInclude' => true,
                    'setByAdminModule'      => true,
                    'setBySetupModule'      => false,
                    'default'               => 90,
                ),
                self::QUOTA_SQ_NOTIFICATION_ROLE => array(
                    //_('Soft quota notification role')
                    'label'                 => 'Soft quota notification role',
                    //_('Name of the role to notify if soft quote is exceeded')
                    'description'           => 'Name of the role to notify if soft quote is exceeded',
                    'type'                  => 'string',
                    'clientRegistryInclude' => true,
                    'setByAdminModule'      => true,
                    'setBySetupModule'      => false,
                    'default'               => 'soft quota notification',
                ),
                self::QUOTA_SKIP_IMAP_QUOTA => array(
                    //_('Skip Imap Quota Notfication')
                    'label'                 => 'Skip Imap Quota Notfication',
                    //_('NSkip Imap Quota Notfication')
                    'description'           => 'Skip Imap Quota Notfication',
                    'type'                  => 'bool',
                    'clientRegistryInclude' => true,
                    'setByAdminModule'      => true,
                    'setBySetupModule'      => false,
                    'default'               => false,
                ),
            ),
            'default'               => array(),
        ),
        self::TINE20_URL  => array(
            //_('Tine20 URL')
            'label' => 'Tine20 URL',
            //_('The full URL including scheme, hostname, optional port and optional uri part under which tine20 is reachable.')
            'description' => 'The full URL including scheme, hostname, optional port and optional uri part under which tine20 is reachable.',
            'type' => 'string',
            'default' => null,
            'clientRegistryInclude' => true,
            'setByAdminModule' => true,
            'setBySetupModule' => true,
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

    public static function destroyInstance()
    {
        static::_destroyBackend();
        self::$_instance = null;
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
        $userApplications = Tinebase_Core::getUser()->getApplications(TRUE);
        foreach ($userApplications as $application) {
            $config = Tinebase_Config_Abstract::factory($application->name);
            if ($config) {
                $clientProperties[$application->name] = new Tinebase_Config_Struct(array());
                $properties = $config->getProperties();
                foreach ((array) $properties as $name => $definition) {
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                        . ' ' . print_r($definition, TRUE));
                    
                    if (isset($definition['clientRegistryInclude']) && $definition['clientRegistryInclude'] === TRUE)
                    {
                        // add definition here till we have a better place
                        try {
                            $configRegistryItem = new Tinebase_Config_Struct(array(
                                'value' => $config->{$name},
                                'definition' => new Tinebase_Config_Struct($definition),
                            ), null, null, array(
                                'value' => array('type' => $definition['type']),
                                'definition' => array('type' => Tinebase_Config_Abstract::TYPE_ARRAY, 'class' => 'Tinebase_Config_Struct')
                            ));
                            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                                . ' ' . print_r($configRegistryItem->toArray(), TRUE));
                            $clientProperties[$application->name][$name] = $configRegistryItem;
                        } catch (Exception $e) {
                            Tinebase_Exception::log($e);
                        }
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
     * @return Tinebase_Config_Abstract  $configClass
     */
    public static function getAppConfig($applicationName)
    {
        $configClassName = $applicationName . '_Config';
        if (@class_exists($configClassName)) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $configClassName::getInstance();
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Application ' . $applicationName . ' has no config.');
            return NULL;
        }
    }
}
