<?php
/**
 * @package     Admin
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Admin config class
 * 
 * @package     Admin
 * @subpackage  Config
 * 
 * @todo add config settings here
 */
class Admin_Config extends Tinebase_Config_Abstract
{
    const APP_NAME = 'Admin';

    /**
     * Default IMAP user settings
     * 
     * @var string
     */
    const DEFAULT_IMAP_USER_SETTINGS = 'defaultImapUserSettings';

    /**
     * FEATURE_PREVENT_SPECIAL_CHAR_LOGINNAME
     *
     * @var string
     */
    const FEATURE_PREVENT_SPECIAL_CHAR_LOGINNAME = 'featurePreventSpecialCharInLoginName';

    /**
     * FEATURE_FORCE_REYPE_PASSWORD
     *
     * @var string
     */
    const FEATURE_FORCE_REYPE_PASSWORD = 'featureForceRetypePassword';

    /**
     * FEATURE_EMAIL_ACCOUNTS
     *
     * @var string
     */
    const FEATURE_EMAIL_ACCOUNTS = 'featureEmailAccounts';

    /**
     * DEFAULT_PASSWORD_MUST_CHANGE
     * @var boolean
     */
    const DEFAULT_PASSWORD_MUST_CHANGE = 'defaultPasswordMustChange';

    /**
     * ALLOW_TOTAL_QUOTA_MANAGEMENT
     * @var boolean
     */
    const QUOTA_ALLOW_TOTALINMB_MANAGEMNET = 'quotaAllowTotalInMBManagement';


    /**
     * FEATURE_PREVENT_APPS_DISABLE
     *
     * @var string
     */
    const FEATURE_PREVENT_APPS_DISABLE = 'featurePreventAppsDisable';

    /**
     * QUOTA_APPS_TO_SHOW
     * @var array
     */
    const APPS_TO_SHOW = 'appsToShow';

    /**
     * MODULES_TO_SHOW
     * @var array
     */
    const MODULES_TO_SHOW = 'modulesToShow';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::APPS_TO_SHOW => [
            //_('Apps to show')
            self::LABEL                 => 'Apps to show',
            //_('Applications to show in quota, defaults null means all apps')
            self::DESCRIPTION           => 'Applications to show in quota management, default value null means all apps',
            self::TYPE                  => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => null,
        ],
        self::MODULES_TO_SHOW => [
            //_('Modules to show')
            self::LABEL                 => 'Modules to show',
            //_('Modules to show in admin app, default value null means all modules.
            // Module name is the same as dataPanelType in Admin/js/Admin.js')
            self::DESCRIPTION           => 'Modules to show in admin app, default value null means all modules. 
                                            Module name is the same as dataPanelType in Admin/js/Admin.js',
            self::TYPE                  => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => null,
        ],
        self::QUOTA_ALLOW_TOTALINMB_MANAGEMNET => [
            //_('Allow total quota in MB management')
            self::LABEL                 => 'Allow total quota in MB management',
            //_('Allow total quota in MB management')
            self::DESCRIPTION           => 'Allow total quota in MB management',
            self::TYPE                  => self::TYPE_BOOL,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => false,
        ],
        self::DEFAULT_PASSWORD_MUST_CHANGE => [
            //_('Default password must change for new user')
            self::LABEL                 => 'Default password must change for new user',
            //_('Default password must change for new user')
            self::DESCRIPTION           => 'Default password must change for new user',
            self::TYPE                  => self::TYPE_BOOL,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => true,
        ],
        self::DEFAULT_IMAP_USER_SETTINGS => array(
                                   //_('Default IMAP user settings')
            'label'                 => 'Default IMAP user settings',
                                   //_('Default IMAP user settings')
            'description'           => 'Default IMAP user settings',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => FALSE,
        ),
        self::ENABLED_FEATURES => array(
            //_('Enabled Features')
            'label' => 'Enabled Features',
            //_('Enabled Features in Admin Application.')
            'description' => 'Enabled Features in Admin Application.',
            'type' => 'object',
            'class' => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => true,
            'content' => array(
                self::FEATURE_PREVENT_SPECIAL_CHAR_LOGINNAME => array(
                    'label' => 'Prevent special chars in login name',
                    //_('Prevent special chars in login name')
                    'description' => 'Prevent special chars in login name',
                ),
                self::FEATURE_EMAIL_ACCOUNTS => array(
                    'label' => 'Manage all email accounts in admin area',
                    //_('Force retype of new password in user edit dialog')
                    'description' => 'Manage all email accounts in admin area',
                ),
                // maybe this can removed at some point if no one uses/misses it ... ;)
                self::FEATURE_FORCE_REYPE_PASSWORD => array(
                    'label' => 'Force retype of new password in user edit dialog',
                    //_('Force retype of new password in user edit dialog')
                    'description' => 'Force retype of new password in user edit dialog',
                ),
                self::FEATURE_PREVENT_APPS_DISABLE => [
                    //_('Prevent applications disable in front end')
                    self::LABEL              => 'Prevent applications disable',
                    //_('Prevent applications disable in front end')
                    self::DESCRIPTION        => 'Prevent applications disable in front end',
                ],
            ),
            'default' => array(
                self::FEATURE_PREVENT_SPECIAL_CHAR_LOGINNAME => false,
                self::FEATURE_EMAIL_ACCOUNTS => true,
                self::FEATURE_FORCE_REYPE_PASSWORD => false,
                self::FEATURE_PREVENT_APPS_DISABLE => false,
            ),
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Admin';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;
    
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
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    public static function unsetInstance()
    {
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
}
