<?php
/**
 * Tine 2.0
  * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $initialAdminUserOptions = $this->_parseInitialAdminUserOptions($_options);
        
        if(Tinebase_User::getInstance() instanceof Tinebase_User_Interface_SyncAble) {
            Tinebase_User::syncUsers(true);
        } else {
            Tinebase_User::createInitialAccounts($initialAdminUserOptions);
        }

        // set current user
        $initialUser = Tinebase_User::getInstance()->getUserByProperty('accountLoginName', $initialAdminUserOptions['adminLoginName']);
        Tinebase_Core::set(Tinebase_Core::USER, $initialUser);
        
        parent::_initialize($_application, $_options);
        
        // those should be called automatically in parent::_initialize
//         $this->_initializeUserContacts();
//         $this->_initializeGroupLists();
//         $this->_initializeConfig();
    }
    
    /**
    * init key fields
    */
    protected function _initializeKeyFields()
    {
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
    
        $keyfieldConfig = array(
            'name'    => Addressbook_Config::CONTACT_SALUTATION,
            'records' => array(
                array('id' => 'MR',      'value' => 'Mr', 	   'gender' => Addressbook_Model_Salutation::GENDER_MALE,   'image' => 'images/empty_photo_male.png',    'system' => true), //_('Mr')
                array('id' => 'MS',      'value' => 'Ms',      'gender' => Addressbook_Model_Salutation::GENDER_FEMALE, 'image' => 'images/empty_photo_female.png',  'system' => true), //_('Ms')
                array('id' => 'COMPANY', 'value' => 'Company', 'gender' => Addressbook_Model_Salutation::GENDER_OTHER,  'image' => 'images/empty_photo_company.png', 'system' => true), //_('Company')
            ),
        );
    
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => Addressbook_Config::CONTACT_SALUTATION,
            'value'             => json_encode($keyfieldConfig),
        )));
    }
    
    /**
     * init/create user contacts
     */
    protected function _initializeUserContacts()
    {
        foreach (Tinebase_User::getInstance()->getFullUsers() as $fullUser) {
            $fullUser->container_id = $this->_getInternalAddressbook()->getId();
            $contact = Admin_Controller_User::getInstance()->createOrUpdateContact($fullUser);
            
            $fullUser->contact_id = $contact->getId();
            Tinebase_User::getInstance()->updateUser($fullUser);
        }
    }
    
    /**
     * returns internal addressbook
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getInternalAddressbook()
    {
        if ($this->_internalAddressbook === NULL) {
            $this->_internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);
        } 
        
        return $this->_internalAddressbook;
    }
    
    /**
     * create group lists
     */
    protected function _initializeGroupLists()
    {
        foreach (Tinebase_Group::getInstance()->getGroups() as $group) {
            $group->members = Tinebase_Group::getInstance()->getGroupMembers($group);
            $group->container_id = $this->_getInternalAddressbook()->getId();
            $list = Admin_Controller_Group::getInstance()->createOrUpdateList($group);
            
            $group->list_id = $list->getId();
            Tinebase_Group::getInstance()->updateGroup($group);
        }
    }
    
    /**
     * create favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => 'Addressbook_Model_ContactFilter',
        );
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Addressbook_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All contacts I have read grants for", // _("All contacts I have read grants for")
            'filters'           => array(),
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
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
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
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
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
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
     * Override method because this app requires special rights
     * @see tine20/Setup/Setup_Initialize#_createInitialRights($_application)
     * 
     */
    protected function _createInitialRights(Tinebase_Model_Application $_application)
    {
        parent::_createInitialRights($_application);

        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);
        $adminGroup = $groupsBackend->getDefaultAdminGroup();
        
        // give anyone read rights to the internal addressbook
        // give Adminstrators group read/edit/admin rights to the internal addressbook
        Tinebase_Container::getInstance()->addGrants($this->_getInternalAddressbook(), Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE, '0', array(
            Tinebase_Model_Grants::GRANT_READ
        ), TRUE);
        Tinebase_Container::getInstance()->addGrants($this->_getInternalAddressbook(), Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $adminGroup, array(
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
        return $result;
    }
    
    /**
     * init config settings
     * - add internal addressbook config setting
     */
    protected function _initializeConfig()
    {
        Admin_Controller::getInstance()->saveConfigSettings(array(
            Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK => $this->_getInternalAddressbook()->getId()
        ));
    }
}
