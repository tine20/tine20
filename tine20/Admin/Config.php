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
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
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
            ),
            'default' => array(
                self::FEATURE_PREVENT_SPECIAL_CHAR_LOGINNAME => false,
                self::FEATURE_EMAIL_ACCOUNTS => true,
                self::FEATURE_FORCE_REYPE_PASSWORD => false,
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
