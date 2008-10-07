<?php
/**
 * Tine 2.0
 * class for initial tine 2.0 
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $this->initialLoad();
    }
     
    /**
     * fill the Database with default values and initialise admin account 
     *
     */    
    public function initialLoad()
    {
        
        /***************** initial config settings ************************/
        
        echo "Creating initial config settings ...<br>";
        $configSettings = array(
            "Default User Group" => "Users",              
            "Default Admin Group" => "Administrators",
            "Locale" => "auto",              
            "Timezone" => "Europe/Berlin",              
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
        
        /***************** admin account, groups and roles ************************/
        
        echo "Creating initial user(tine20admin), groups and roles ...<br>";
        
        // or initialize the database ourself
        // add the admin group
        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);

        $adminGroup = new Tinebase_Model_Group(array(
            'name'          => $configSettings["Default Admin Group"],
            'description'   => 'Group of administrative accounts'
        ));
        $adminGroup = $groupsBackend->addGroup($adminGroup);

        // add the user group
        $userGroup = new Tinebase_Model_Group(array(
            'name'          => $configSettings["Default User Group"],
            'description'   => 'Group of user accounts'
        ));
        $userGroup = $groupsBackend->addGroup($userGroup);

        // add the admin account
        $accountsBackend = Tinebase_User::factory(Tinebase_User::SQL);

        $account = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'tine20admin',
            'accountStatus'         => 'enabled',
            'accountPrimaryGroup'   => $userGroup->getId(),
            'accountLastName'       => 'Account',
            'accountDisplayName'    => 'Tine 2.0 Admin Account',
            'accountFirstName'      => 'Tine 2.0 Admin',
            'accountExpires'        => NULL,
            'accountEmailAddress'   => NULL,
        ));

        $accountsBackend->addUser($account);

        Zend_Registry::set('currentAccount', $account);

        // set the password for the tine20admin account
        Tinebase_Auth::getInstance()->setPassword('tine20admin', 'lars', 'lars');

        // add the admin account to all groups
        Tinebase_Group::getInstance()->addGroupMember($adminGroup, $account);
        Tinebase_Group::getInstance()->addGroupMember($userGroup, $account);
        
        // add roles and add the groups to the roles
        $adminRole = new Tinebase_Model_Role(array(
            'name'                  => 'admin role',
            'description'           => 'admin role for tine. this role has all rights per default.',
        ));
        $adminRole = Tinebase_Acl_Roles::getInstance()->createRole($adminRole);
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($adminRole->getId(), array(
            array(
                'account_id'    => $adminGroup->getId(),
                'account_type'  => 'group', 
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
                'account_type'  => 'group', 
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
        Tinebase_Container::getInstance()->addGrants($internalAddressbook, 'group', $userGroup, array(
            Tinebase_Model_Container::GRANT_READ
        ), TRUE);
        Tinebase_Container::getInstance()->addGrants($internalAddressbook, 'group', $adminGroup, array(
            Tinebase_Model_Container::GRANT_READ,
            Tinebase_Model_Container::GRANT_EDIT,
            Tinebase_Model_Container::GRANT_ADMIN
        ), TRUE);
        
        echo "TINE 2.0 now ready to use - try <a href=\"./index.php\">TINE 2.0 Login</a>";
    }
}