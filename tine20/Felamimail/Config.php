<?php
/**
 * @package     Felamimail
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Felamimail config class
 * 
 * @package     Felamimail
 * @subpackage  Config
 */
class Felamimail_Config extends Tinebase_Config_Abstract
{
    /**
     * is email body cached
     * 
     * @var string
     */
    const CACHE_EMAIL_BODY = 'cacheEmailBody';

    /**
     * delete archived mail
     *
     * @var string
     */
    const DELETE_ARCHIVED_MAIL = 'deleteArchivedMail';

    /**
     * Tine 2.0 flag feature
     *
     * @var string
     * @see 0010576: show a tine20 icon on each message which was written in tine20
     */
    const FEATURE_TINE20_FLAG = 'tine20Flag';

    /**
     * Tine 2.0 filter message uris (only allow <a> uris)
     *
     * @var string
     */
    const FILTER_EMAIL_URIS = 'filterEmailUris';

    /**
     * system account special folders
     *
     * @var string
     */
    const SYSTEM_ACCOUNT_FOLDER_DEFAULTS = 'systemAccountFolderDefaults';

    /**
     * id of (filsystem) container for vacation templates
     *
     * @var string
     */
    const VACATION_TEMPLATES_CONTAINER_ID = 'vacationTemplatesContainerId';

    /**
     * id of (filsystem) container for email notification templates
     *
     * @var string
     */
    const EMAIL_NOTIFICATION_TEMPLATES_CONTAINER_ID = 'emailNotificationTemplatesContainerId';

    /**
     * user can set custom vacation message
     *
     * @var string
     */
    const VACATION_CUSTOM_MESSAGE_ALLOWED = 'vacationMessageCustomAllowed';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::VACATION_TEMPLATES_CONTAINER_ID => array(
        //_('Vacation Templates Node ID')
            'label'                 => 'Vacation Templates Node ID',
            'description'           => 'Vacation Templates Node ID',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
        ),
        self::EMAIL_NOTIFICATION_TEMPLATES_CONTAINER_ID => array(
            //_('Email Notification Templates Node ID')
            'label'                 => 'Email Notification Templates Node ID',
            'description'           => 'Email Notification Templates Node ID',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
        ),
        self::VACATION_CUSTOM_MESSAGE_ALLOWED => array(
        //_('Custom Vacation Message')
            'label'                 => 'Custom Vacation Message',
        // _('User is allowed to set custom vacation message for system account')
            'description'           => 'User is allowed to set custom vacation message for system account',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
            'default'               => 1,
        ),
        self::CACHE_EMAIL_BODY => array(
        //_('Cache email body')
            'label'                 => 'Cache email body',
        // _('Should the email body be cached (recommended for slow IMAP server connections)')
            'description'           => 'Should the email body be cached (recommended for slow IMAP server connections)',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => 1,
        ),
        self::DELETE_ARCHIVED_MAIL => array(
            //_('Delete Archived Mail')
            'label'                 => 'Delete Archived Mail',
            'description'           => 'Delete Archived Mail',
            'type'                  => Tinebase_Config_Abstract::TYPE_BOOL,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => false,
        ),
        self::ENABLED_FEATURES => array(
            //_('Enabled Features')
            'label'                 => 'Enabled Features',
            //_('Enabled features in Felamimail application.')
            'description'           => 'Enabled features in Felamimail application.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => TRUE,
            'content'               => array(
                self::FEATURE_TINE20_FLAG => array(
                    'label'         => 'Tine 2.0 Flag', //_('Tine 2.0 Flag')
                    'description'   => 'Add a Tine 2.0 flag to sent messages', //_('Add a Tine 2.0 flag to sent messages')
                ),
            ),
            'default'               => array(
                self::FEATURE_TINE20_FLAG => true,
            ),
        ),
        self::FILTER_EMAIL_URIS => array(
            //_('Filter E-Mail URIs')
            'label'                 => 'Filter E-Mail URIs',
            // _('Should the email body uris be filtered. Only anchors with URIs are allowed if this is turned on.)')
            'description'           => 'Should the email body uris be filtered. Only anchors with URIs are allowed if this is turned on',
            'type'                  => Tinebase_Config_Abstract::TYPE_BOOL,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => true,
        ),
        /**
         * possible keys/values::
         *
         * 'sent_folder'       => 'Sent',
         * 'trash_folder'      => 'Trash',
         * 'drafts_folder'     => 'Drafts',
         * 'templates_folder'  => 'Templates',
         */
        self::SYSTEM_ACCOUNT_FOLDER_DEFAULTS => array(
            //_('System Account Folder Defaults')
            'label'                 => 'System Account Folder Defaults',
            // _('Paths of the special folders (like Sent, Trash, ...)')
            'description'           => 'Paths of the special folders (like Sent, Trash, ...)',
            'type'                  => Tinebase_Config_Abstract::TYPE_ARRAY,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => TRUE,
            'default'               => null,
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Felamimail';
    
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
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
