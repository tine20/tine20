<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * this class handles the rights for the admin application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * to add a new right you have to do these 3 steps:
 * - add a constant for the right
 * - add the constant to the $addRights in getAllApplicationRights() function
 * . add getText identifier in getTranslatedRightDescriptions() function
 * 
 * @package     Tinebase
 * @subpackage  Acl
 */
class Admin_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to manage roles
     * @staticvar string
     */
    const MANAGE_ACCESS_LOG = 'manage_access_log';
    
    /**
     * the right to manage accounts
     * @staticvar string
     */
    const MANAGE_ACCOUNTS = 'manage_accounts';
    
   /**
     * the right to manage applications
     * @staticvar string
     */
    const MANAGE_APPS = 'manage_apps';

    /**
     * the right to manage e-mail accounts
     * @staticvar string
     */
    const MANAGE_EMAILACCOUNTS = 'manage_emailaccounts';

    /**
     * the right to manage shared tags
     * @staticvar string
     */
    const MANAGE_SHARED_TAGS = 'manage_shared_tags';
   
    /**
     * the right to manage roles
     * @staticvar string
     */
    const MANAGE_ROLES = 'manage_roles';
    
    /**
     * the right to manage computers
     * @staticvar string
     */
    const MANAGE_COMPUTERS = 'manage_computers';
    
    /**
     * the right to manage computers
     * @staticvar string
     */
    const MANAGE_CONTAINERS = 'manage_containers';
    
    /**
     * the right to manage customfields
     * @staticvar string
     */
    const MANAGE_CUSTOMFIELDS = 'manage_customfields';

    /**
     * the right to view roles
     * @staticvar string
     */
    const VIEW_ACCESS_LOG = 'view_access_log';
    
    /**
     * the right to view accounts
     * @staticvar string
     */
    const VIEW_ACCOUNTS = 'view_accounts';

    /**
     * the right to view shared tags
     * @staticvar string
     */
    const VIEW_SHARED_TAGS = 'viewshared_tags';

    /**
     * the right to view e-mail accounts
     * @staticvar string
     */
    const VIEW_EMAILACCOUNTS = 'view_emailaccounts';

    /**
     * the right to view applications
     * @staticvar string
     */
    const VIEW_APPS = 'view_apps';
    
    /**
     * the right to view roles
     * @staticvar string
     */
    const VIEW_ROLES = 'view_roles';
    
    /**
     * the right to manage computers
     * @staticvar string
     */
    const VIEW_COMPUTERS = 'view_computers';
    
    /**
     * the right to manage containers
     * @staticvar string
     */
    const VIEW_CONTAINERS = 'view_containers';
    
    /**
     * the right to customfields
     * @staticvar string
     */
    const VIEW_CUSTOMFIELDS = 'view_customfields';
        
    /**
     * MOD: added right
     * 
     * the right to manage serverinfo
     * @staticvar string
     */
    const VIEW_SERVERINFO = 'view_serverinfo';

    /**
     * the right to view quota usage
     * @staticvar string
     */
    const VIEW_QUOTA_USAGE = 'view_quota_usage';
        
    /**
     * holds the instance of the singleton
     *
     * @var Admin_Acl_Rights
     */
    private static $_instance = NULL;
    
    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone() 
    {
    }
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
    }    
    
    /**
     * the singleton pattern
     *
     * @return Admin_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Acl_Rights;
        }
        
        return self::$_instance;
    }
    
    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        $allRights = parent::getAllApplicationRights();
        
        $addRights = array (
            self::MANAGE_ACCESS_LOG, 
            self::MANAGE_ACCOUNTS,
            self::MANAGE_EMAILACCOUNTS,
            self::MANAGE_APPS,
            self::MANAGE_SHARED_TAGS,
            self::MANAGE_ROLES,
            self::MANAGE_COMPUTERS,
            self::MANAGE_CONTAINERS,
            self::MANAGE_CUSTOMFIELDS,
            self::VIEW_ACCESS_LOG,
            self::VIEW_ACCOUNTS,
            self::VIEW_EMAILACCOUNTS,
            self::VIEW_APPS,
            self::VIEW_SHARED_TAGS,
            self::VIEW_ROLES,
            self::VIEW_COMPUTERS,
            self::VIEW_CONTAINERS,
            self::VIEW_CUSTOMFIELDS,
            self::VIEW_SERVERINFO,
            self::VIEW_QUOTA_USAGE,
        );
        $allRights = array_merge($allRights, $addRights);
        
        return $allRights;
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Admin');
        
        $rightDescriptions = array(
            self::MANAGE_ACCESS_LOG   => array(
                'text'          => $translate->_('Manage access log'),
                'description'   => $translate->_('Delete access log entries.'),
            ),
            self::MANAGE_ACCOUNTS   => array(
                'text'          => $translate->_('Manage accounts'),
                'description'   => $translate->_('Add and edit users and groups, add group members, change user passwords.'),
            ),
            self::MANAGE_EMAILACCOUNTS   => array(
                'text'          => $translate->_('Manage e-mail accounts'),
                'description'   => $translate->_('Add and edit shared and personal e-mail accounts.'),
            ),
            self::MANAGE_APPS   => array(
                'text'          => $translate->_('Manage applications'),
                'description'   => $translate->_('Enable and disable applications, edit application settings.'),
            ),
            self::MANAGE_ROLES  => array(
                'text'          => $translate->_('Manage roles'),
                'description'   => $translate->_('Add and edit roles, add new members to roles, add application rights to roles.'),
            ),
            self::MANAGE_SHARED_TAGS    => array(
                'text'          => $translate->_('Manage shared tags'),
                'description'   => $translate->_('Add, delete and edit shared tags.'),
            ),
            self::MANAGE_COMPUTERS => array(
                'text'          => $translate->_('Manage computers'),
                'description'   => $translate->_('Add, delete and edit (samba) computers.'),
            ),
            self::MANAGE_CONTAINERS => array(
                'text'          => $translate->_('Manage containers'),
                'description'   => $translate->_('Add, delete and edit containers and manage container grants.'),
            ),
            self::MANAGE_CUSTOMFIELDS   => array(
                'text'          => $translate->_('Manage customfields'),
                'description'   => $translate->_('Add and edit customfields.'),
            ),            
            self::VIEW_ACCESS_LOG   => array(
                'text'          => $translate->_('View access log'),
                'description'   => $translate->_('View access log list.'),
            ),
            self::VIEW_ACCOUNTS   => array(
                'text'          => $translate->_('View accounts'),
                'description'   => $translate->_('View accounts list and details.'),
            ),
            self::VIEW_EMAILACCOUNTS   => array(
                'text'          => $translate->_('View e-mail accounts'),
                'description'   => $translate->_('View shared and personal e-mail accounts.'),
            ),
            self::VIEW_APPS   => array(
                'text'          => $translate->_('View applications'),
                'description'   => $translate->_('View applications list and details.'),
            ),
            self::VIEW_ROLES  => array(
                'text'          => $translate->_('View roles'),
                'description'   => $translate->_('View roles list and details.'),
            ),
            self::VIEW_SHARED_TAGS    => array(
                'text'          => $translate->_('View shared tags'),
                'description'   => $translate->_('View shared tags list and details.'),
            ),
            self::VIEW_COMPUTERS  => array(
                'text'          => $translate->_('View computers'),
                'description'   => $translate->_('View computers list and details.'),
            ),
            self::VIEW_CONTAINERS  => array(
                'text'          => $translate->_('View containers'),
                'description'   => $translate->_('View personal and shared containers.'),
            ),
            self::VIEW_CUSTOMFIELDS   => array(
                'text'          => $translate->_('View customfields'),
                'description'   => $translate->_('View customfields list.'),
            ),
            self::VIEW_SERVERINFO   => array(
                'text'          => $translate->_('View serverinfo'),
                'description'   => $translate->_('View serverinfo list.'),
            ),
            self::VIEW_QUOTA_USAGE => array(
                'text'          => $translate->_('View quota usage'),
                'description'   => $translate->_('View quota usage.'),
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        unset($rightDescriptions[self::USE_PERSONAL_TAGS]);
        
        return $rightDescriptions;
    }
}
