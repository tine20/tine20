<?php
/**
 * Tine 2.0
  * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Addressbook initialization
 * 
 * @todo move {@see _createInitialAdminAccount} to a better place (resolve dependency from addressbook)
 * 
 * @package Addressbook
 */
class Addressbook_Setup_Initialize extends Setup_Initialize
{
    /**
     * addressbook for internal contacts/groups
     * 
     * @var Tinebase_Model_Container
     */
    protected $_internalAddressbook = NULL;
    
    /**
     * Override method: Setup needs additional initialisation
     * 
     * @see tine20/Setup/Setup_Initialize#_initialize($_application)
     */
    protected function _initialize(Tinebase_Model_Application $_application, $_options = null)
    {
        parent::createInitialRights($_application);

        $initialAdminUserOptions = $this->_parseInitialAdminUserOptions($_options);

        $groupController = Tinebase_Group::getInstance();
        $userController = Tinebase_User::getInstance();
        $oldGroupValue = $groupController->modlogActive(false);
        $oldUserValue = $userController->modlogActive(false);

        $initialUserName = $initialAdminUserOptions['adminLoginName'];

        // make sure we have a setup user:
        $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
        if (! Tinebase_Core::getUser() instanceof Tinebase_Model_User) {
            Tinebase_Core::set(Tinebase_Core::USER, $setupUser);
        }

        // in case we have an ldap setup, we sync the users from the ldap before creating the initial accounts
        Tinebase_User::syncUsers([Tinebase_User::SYNC_WITH_CONFIG_OPTIONS => true]);
        Tinebase_User::createInitialAccounts($initialAdminUserOptions);
        $initialUser = $userController->getUserByProperty('accountLoginName', $initialUserName);
        
        Tinebase_Core::set(Tinebase_Core::USER, $initialUser);

        $groupController->modlogActive($oldGroupValue);
        $userController->modlogActive($oldUserValue);
        
        parent::_initialize($_application, $_options);
    }

    /**
     * create inital rights
     *
     * @param Tinebase_Application $application
     * @return void
     */
    public static function createInitialRights(Tinebase_Model_Application $_application)
    {
        // we do nothing to work our way through the jungle here
        // we call parent::createInitialRights at the time we like it in _initialize
    }

    /**
     * returns internal addressbook
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getInternalAddressbook()
    {
        if ($this->_internalAddressbook === NULL) {
            $this->_internalAddressbook = Tinebase_Container::getInstance()->getContainerById(
                Admin_Controller_User::getDefaultInternalAddressbook()
            );
        }
        
        return $this->_internalAddressbook;
    }
    
    /**
     * create group lists
     */
    protected function _initializeGroupLists()
    {
        Tinebase_Core::getCache()->clean();
        Tinebase_Group::getInstance()->resetClassCache();
        Addressbook_Controller_List::getInstance()->doContainerACLChecks(false);
        foreach (Tinebase_Group::getInstance()->getGroups() as $group) {
            $group->members = Tinebase_Group::getInstance()->getGroupMembers($group);
            $group->container_id = $this->_getInternalAddressbook()->getId();
            $group->visibility = Tinebase_Model_Group::VISIBILITY_DISPLAYED;
            Admin_Controller_Group::getInstance()->createOrUpdateList($group);

            Tinebase_Group::getInstance()->updateGroup($group);
        }
    }
    
    /**
     * create favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => 'Addressbook_Model_ContactFilter',
        );
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Addressbook_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All contacts I have read grants for", // _("All contacts I have read grants for")
            'filters'           => array(),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My company", // _("My company")
            'description'       => "All coworkers in my company", // _("All coworkers in my company")
            'filters'           => array(array(
                'field'     => 'container_id',
                'operator'  => 'in',
                'value'     => array(
                    'id'    => $this->_getInternalAddressbook()->getId(),
                    // @todo use placeholder here (as this can change later)?
                    'path'  => '/shared/' . $this->_getInternalAddressbook()->getId(),
                )
            )),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My contacts", // _("My contacts")
            'description'       => "All contacts in my Addressbooks", // _("All contacts in my Addressbooks")
            'filters'           => array(array(
                'field'     => 'container_id',
                'operator'  => 'in',
                'value'     => array(
                    'id'    => 'personal',
                    'path'  => '/personal/' . Tinebase_Model_User::CURRENTACCOUNT,
                )
            )),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Last modified by me", // _("Last modified by me")
            'description'       => "All contacts that I have last modified", // _("All contacts that I have last modified")
            'filters'           => array(array(
                'field'     => 'last_modified_by',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            )),
        ))));
    }

    /**
     * init grants of internal addressbook
     *
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _initializeInternalAddressbook()
    {
        $internalAddressbook = $this->_getInternalAddressbook();
        self::setGrantsForInternalAddressbook($internalAddressbook);
    }

    /**
     * give anyone read rights to the internal addressbook
     * give Administrators group read/edit/admin rights to the internal addressbook
     *
     * @param $internalAddressbook
     */
    public static function setGrantsForInternalAddressbook($internalAddressbook)
    {
        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);
        $adminGroup = $groupsBackend->getDefaultAdminGroup();

        Tinebase_Container::getInstance()->addGrants($internalAddressbook, Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE, '0', array(
            Tinebase_Model_Grants::GRANT_READ
        ), TRUE);
        Tinebase_Container::getInstance()->addGrants($internalAddressbook, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $adminGroup, array(
            Tinebase_Model_Grants::GRANT_READ,
            Tinebase_Model_Grants::GRANT_EDIT,
            Tinebase_Model_Grants::GRANT_ADMIN
        ), TRUE);
    }
    
    /**
     * Extract default group name settings from {@param $_options}
     * 
     * @todo the initial admin user options get set for the sql backend only. They should be set independed of the backend selected
     * @param array $_options
     * @return array
     */
    protected function _parseInitialAdminUserOptions($_options)
    {
        $result = array();
        $accounts = isset($_options['authenticationData']['authentication'][Tinebase_User::SQL]) ? $_options['authenticationData']['authentication'][Tinebase_User::SQL] : array();
        $keys = array('adminLoginName', 'adminPassword', 'adminEmailAddress');
        foreach ($keys as $key) {
            if (isset($_options[$key])) {
                $result[$key] = $_options[$key];
            } elseif (isset($accounts[$key])) {
                $result[$key] = $accounts[$key];
            }
        }
        
        if (! isset($result['adminLoginName']) || ! isset($result['adminPassword'])) {
            $loginConfig = Tinebase_Config::getInstance()->get('login');
            if ($loginConfig) {
                $result = array(
                    'adminLoginName' => $loginConfig->username,
                    'adminPassword' => $loginConfig->password,
                );
            } else {
                throw new Setup_Exception('Inital admin username and password are required');
            }
        }
        
        return $result;
    }
    
    /**
     * init config settings
     * - add internal addressbook config setting
     */
    protected function _initializeConfig()
    {
        self::setDefaultInternalAddressbook($this->_getInternalAddressbook());
    }
    
    /**
     * set default internal addressbook
     * 
     * @param Tinebase_Model_Container $internalAddressbook
     * @return Tinebase_Model_Container
     *
     * @todo translate 'Internal Contacts'
     */
    public static function setDefaultInternalAddressbook($internalAddressbook = NULL)
    {
        if ($internalAddressbook === NULL) {
            try {
                $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName(
                    Addressbook_Model_Contact::class,
                    'Internal Contacts',
                    Tinebase_Model_Container::TYPE_SHARED);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // create new internal adb
                $internalAddressbook = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
                    'name'              =>'Internal Contacts',
                    'type'              => Tinebase_Model_Container::TYPE_SHARED,
                    'backend'           => 'Sql',
                    'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
                    'model'             => 'Addressbook_Model_Contact'
                )), null, true);
                self::setGrantsForInternalAddressbook($internalAddressbook);
            }
        }
        
        Admin_Config::getInstance()->set(
            Tinebase_Config::APPDEFAULTS,
            array(
                Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK => $internalAddressbook->getId()
            )
        );
        
        return $internalAddressbook;
    }
}
