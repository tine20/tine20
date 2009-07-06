<?php
/**
 * Tine 2.0
 * class for initial tine 2.0 data
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to handle data migration
 * 
 * @package     Setup
 */
class Setup_Import_TineInitial
{
    /**
     * import main function
     *
     */
    public function import()
    {
        /***************** initial config/preference settings ************************/
        
        $configSettings = $this->_setDefaultGroups('Users', 'Administrators');
        
        /***************** create initial user and groups ************************/
        
        list($userGroup, $adminGroup) = $this->_createInitialGroups($configSettings);
        $this->_createInitialAdminAccount('tine20admin', 'lars', 'Tine 2.0', 'Admin Account', $userGroup, $adminGroup);
        
        $this->initialLoad();
    }
     
    /**
     * fill the Database with default values and initialise admin account 
     *
     * @todo split this function in smaller subs
     * @todo add all new installed apps to roles (not only in initial install)
     */    
    public function initialLoad()
    {
        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);
        
        $adminGroup = $groupsBackend->getGroupByName(Tinebase_Config::getInstance()->getConfig(Tinebase_Config::DEFAULT_ADMIN_GROUP)->value);
        $userGroup  = $groupsBackend->getGroupByName(Tinebase_Config::getInstance()->getConfig(Tinebase_Config::DEFAULT_USER_GROUP)->value);
        
        // add roles and add the groups to the roles
        $adminRole = new Tinebase_Model_Role(array(
            'name'                  => 'admin role',
            'description'           => 'admin role for tine. this role has all rights per default.',
        ));
        $adminRole = Tinebase_Acl_Roles::getInstance()->createRole($adminRole);
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($adminRole->getId(), array(
            array(
                'account_id'    => $adminGroup->getId(),
                'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, 
            )
        ));
        
        $userRole = new Tinebase_Model_Role(array(
            'name'                  => 'user role',
            'description'           => 'userrole for tine. this role has only the run rights for all applications per default.',
        ));
        $userRole = Tinebase_Acl_Roles::getInstance()->createRole($userRole);
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($userRole->getId(), array(
            array(
                'account_id'    => $userGroup->getId(),
                'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, 
            )
        ));
        
        // enable the applications for the user group/role
        // give all rights to the admin group/role for all applications
        $applications = Tinebase_Application::getInstance()->getApplications();
        foreach ($applications as $application) {
            
            if ($application->name  !== 'Admin') {

                /***** All applications except Admin *****/
            
                // run right for user role
                Tinebase_Acl_Roles::getInstance()->addSingleRight(
                    $userRole->getId(), 
                    $application->getId(), 
                    Tinebase_Acl_Rights::RUN
                );
                
                // all rights for admin role
                $allRights = Tinebase_Application::getInstance()->getAllRights($application->getId());
                foreach ( $allRights as $right ) {
                    Tinebase_Acl_Roles::getInstance()->addSingleRight(
                        $adminRole->getId(), 
                        $application->getId(), 
                        $right
                    );
                }                                

            } else {

                /***** Admin application *****/

                // all rights for admin role
                $allRights = Tinebase_Application::getInstance()->getAllRights($application->getId());
                foreach ( $allRights as $right ) {
                    Tinebase_Acl_Roles::getInstance()->addSingleRight(
                        $adminRole->getId(), 
                        $application->getId(), 
                        $right
                    );
                }                                                
            }
            
            // more application specific rights
            if ($application->name  === 'Crm') { 
                // manage roles right for user role
                Tinebase_Acl_Roles::getInstance()->addSingleRight(
                    $userRole->getId(), 
                    $application->getId(), 
                    Crm_Acl_Rights::MANAGE_LEADS
                );                
            }
            
            // enable bug reporting and version check
            if ($application->name  === 'Tinebase') { 
                Tinebase_Acl_Roles::getInstance()->addSingleRight(
                    $userRole->getId(), 
                    $application->getId(), 
                    Tinebase_Acl_Rights::CHECK_VERSION
                );
                Tinebase_Acl_Roles::getInstance()->addSingleRight(
                    $userRole->getId(), 
                    $application->getId(), 
                    Tinebase_Acl_Rights::REPORT_BUGS
                );     
            }
           
            
        } // end foreach applications                               
        
        // give Users group read rights to the internal addressbook
        // give Adminstrators group read/edit/admin rights to the internal addressbook
        $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Model_Container::TYPE_INTERNAL);
        Tinebase_Container::getInstance()->addGrants($internalAddressbook, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $userGroup, array(
            Tinebase_Model_Container::GRANT_READ
        ), TRUE);
        Tinebase_Container::getInstance()->addGrants($internalAddressbook, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $adminGroup, array(
            Tinebase_Model_Container::GRANT_READ,
            Tinebase_Model_Container::GRANT_EDIT,
            Tinebase_Model_Container::GRANT_ADMIN
        ), TRUE);
        
        // @todo move that to erp application initial setup script
        // add shared container for erp contracts
        try {
            $sharedContracts = Tinebase_Container::getInstance()->getContainerByName('Erp', 'Shared Contracts', Tinebase_Model_Container::TYPE_SHARED);
            Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $userGroup, array(
                Tinebase_Model_Container::GRANT_READ,
                Tinebase_Model_Container::GRANT_EDIT
            ), TRUE);
            Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $adminGroup, array(
                Tinebase_Model_Container::GRANT_ADD,
                Tinebase_Model_Container::GRANT_READ,
                Tinebase_Model_Container::GRANT_EDIT,
                Tinebase_Model_Container::GRANT_ADMIN
            ), TRUE);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Erp application not found.');
        }
    }
    
    /**
     * set default group names in config
     *
     * @param string $_userGroup
     * @param string $_adminGroup
     * @return array initial config settings
     */
    protected function _setDefaultGroups($_userGroup, $_adminGroup)
    {
        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating initial config settings ...');
        $configSettings = array(
            Tinebase_Config::DEFAULT_USER_GROUP     => $_userGroup,              
            Tinebase_Config::DEFAULT_ADMIN_GROUP    => $_adminGroup,
        );
        
        $configBackend = Tinebase_Config::getInstance();
        $tinebaseAppId = Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId();
        
        foreach ($configSettings as $name => $value) {
            $config = new Tinebase_Model_Config(array(
                "application_id"    => $tinebaseAppId,
                "name"              => $name,
                "value"             => $value,              
            ));            
            $configBackend->setConfig($config);
        }
        
        return $configSettings;
    }
    
    /**
     * create initial groups
     *
     * @param array $_initialConfig
     * @return array with initial groups (user, admin)
     */
    protected function _createInitialGroups($_initialConfig)
    {
        // add the admin group
        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);

        $adminGroup = new Tinebase_Model_Group(array(
            'name'          => $_initialConfig[Tinebase_Config::DEFAULT_ADMIN_GROUP],
            'description'   => 'Group of administrative accounts'
        ));
        $adminGroup = $groupsBackend->addGroup($adminGroup);

        // add the user group
        $userGroup = new Tinebase_Model_Group(array(
            'name'          => $_initialConfig[Tinebase_Config::DEFAULT_USER_GROUP],
            'description'   => 'Group of user accounts'
        ));
        $userGroup = $groupsBackend->addGroup($userGroup);
        
        return array(
            $userGroup,
            $adminGroup,
        );
    }
    
    /**
     * create initial admin account
     *
     * @param string $_loginName
     * @param string $_password
     * @param string $_firstname
     * @param string $_lastname
     * @param Tinebase_Model_Group $_initialUserGroup
     * @param Tinebase_Model_Group $_initialAdminGroup
     */
    protected function _createInitialAdminAccount($_loginName, $_password, $_firstname, $_lastname, $_initialUserGroup, $_initialAdminGroup)
    {
        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating initial admin user(' . $_loginName . ')');

        // add the admin account
        $accountsBackend = Tinebase_User::factory(Tinebase_User::SQL);

        $account = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => $_loginName,
            'accountStatus'         => 'enabled',
            'accountPrimaryGroup'   => $_initialUserGroup->getId(),
            'accountLastName'       => $_lastname,
            'accountDisplayName'    => $_lastname . ', ' . $_firstname,
            'accountFirstName'      => $_firstname,
            'accountExpires'        => NULL,
            'accountEmailAddress'   => NULL,
        ));

        $accountsBackend->addUser($account);

        Tinebase_Core::set('currentAccount', $account);

        // set the password for the account
        Tinebase_User::getInstance()->setPassword($_loginName, $_password, $_password);

        // add the admin account to all groups
        Tinebase_Group::getInstance()->addGroupMember($_initialAdminGroup, $account);
        Tinebase_Group::getInstance()->addGroupMember($_initialUserGroup, $account);
    }
}
