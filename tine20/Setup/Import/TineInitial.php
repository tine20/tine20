<?php
/**
 * Tine 2.0
 * class for initial tine 2.0 
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $ $ 
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
       echo "Creating initial user(tine20admin) and groups...<br>";
        # or initialize the database ourself
        # add the admin group
        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);

        $adminGroup = new Tinebase_Group_Model_Group(array(
            'name'          => 'Administrators',
            'description'   => 'Group of administrative accounts'
        ));
        $adminGroup = $groupsBackend->addGroup($adminGroup);

        # add the user group
        $userGroup = new Tinebase_Group_Model_Group(array(
            'name'          => 'Users',
            'description'   => 'Group of user accounts'
        ));
        $userGroup = $groupsBackend->addGroup($userGroup);

        # add the admin account
        $accountsBackend = Tinebase_Account::factory(Tinebase_Account::SQL);

        $account = new Tinebase_Account_Model_FullAccount(array(
            'accountLoginName'      => 'tine20admin',
            'accountStatus'         => 'enabled',
            'accountPrimaryGroup'   => $userGroup->getId(),
            'accountLastName'       => 'Account',
            'accountDisplayName'    => 'Tine 2.0 Admin Account',
            'accountFirstName'      => 'Tine 2.0 Admin',
            'accountExpires'        => NULL,
            'accountEmailAddress'   => NULL,
        ));

        $accountsBackend->addAccount($account);

        Zend_Registry::set('currentAccount', $account);

        # set the password for the tine20admin account
        Tinebase_Auth::getInstance()->setPassword('tine20admin', 'lars', 'lars');

        # add the admin account to all groups
        Tinebase_Group::getInstance()->addGroupMember($adminGroup, $account);
        Tinebase_Group::getInstance()->addGroupMember($userGroup, $account);

        # enable the applications for the user group
        # give admin rights to the admin group for all applications
        $applications = Tinebase_Application::getInstance()->getApplications();
        foreach( $applications as $application) {
            
            if(strtolower($application->name) !== 'admin') {
                // run right for user group
                $right = new Tinebase_Acl_Model_Right(array(
                    'application_id'    => $application->getId(),
                    'account_id'        => $userGroup->getId(),
                    'account_type'      => 'group',
                    'right'             => Tinebase_Acl_Rights::RUN
                ));
                Tinebase_Acl_Rights::getInstance()->addRight($right);
                
                // run for admin group
                $right->account_id = $adminGroup->getId();            
                Tinebase_Acl_Rights::getInstance()->addRight($right);
                
                // admin for admin group
                $right->right = Tinebase_Acl_Rights::ADMIN;            
                Tinebase_Acl_Rights::getInstance()->addRight($right);
                
                // create shared tags for admin group in tinebase
                if ( strtolower($application->name) !== 'tinebase') {
                    $right->right = Tinebase_Acl_Rights::MANAGE_SHARED_TAGS;            
                    Tinebase_Acl_Rights::getInstance()->addRight($right);
                }            
            } else {
                
                /***** Admin application *****/
                
                $adminAppId = $application->getId();
                
                // run right for admin group
                $right = new Tinebase_Acl_Model_Right(array(
                    'application_id'    => $adminAppId,
                    'account_id'        => $adminGroup->getId(),
                    'account_type'      => 'group',
                    'right'             => Tinebase_Acl_Rights::RUN
                ));
                Tinebase_Acl_Rights::getInstance()->addRight($right);
                               
                $allAdminRights = Tinebase_Application::getInstance()->getAllRights($adminAppId);
                
                foreach ( $allAdminRights as $adminRight ) {
                    // don't add run right again
                    if ( $adminRight !== Tinebase_Acl_Rights::RUN ) {
                        $right->right = $adminRight;     
                        Tinebase_Acl_Rights::getInstance()->addRight($right);
                    }            
                }    
            }
        }

        # give Users group read rights to the internal addressbook
        # give Adminstrators group read/write rights to the internal addressbook
        $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Container::TYPE_INTERNAL);
        Tinebase_Container::getInstance()->addGrants($internalAddressbook, 'group', $userGroup, array(
            Tinebase_Container::GRANT_READ
        ), TRUE);
        Tinebase_Container::getInstance()->addGrants($internalAddressbook, 'group', $adminGroup, array(
            Tinebase_Container::GRANT_READ,
            Tinebase_Container::GRANT_ADD,
            Tinebase_Container::GRANT_EDIT,
            Tinebase_Container::GRANT_DELETE,
            Tinebase_Container::GRANT_ADMIN
        ), TRUE);
            
        echo "TINE 2.0 now ready to use - try <a href=\"./index.php\">TINE 2.0 Login</a>";
    }
}	