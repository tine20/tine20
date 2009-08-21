<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: TineInitial.php 9535 2009-07-20 10:30:05Z p.schuele@metaways.de $
 *
 */

/**
 * class for Tinebase initialization
 * 
 * @package     Tinebase
 */
class Tinebase_Setup_Initialize extends Setup_Initialize
{
    
    /**
     * Override method: Tinebase needs additional initialisation
     * 
     * @see tine20/Setup/Setup_Initialize#_initialize($_application)
     */
    public function _initialize(Tinebase_Model_Application $_application, $_options = null)
    {
        if (isset($_options['authenticationData'])) {
            Setup_Controller::getInstance()->saveAuthentication($_options['authenticationData']);
            $accountsBackendType = $_options['authenticationData']['accounts']['backend'];
            $accountsBackendConfiguration = $_options['authenticationData']['accounts'][$accountsBackendType];
        } else {
            $accountsBackendType = Tinebase_User::getConfiguredBackend();
            $accountsBackendConfiguration = Tinebase_User::getBackendConfigurationDefaults($accountsBackendType);
        }

        Tinebase_User::setBackendType($accountsBackendType);
        Tinebase_User::setBackendConfiguration($accountsBackendConfiguration);
        Tinebase_User::saveBackendConfiguration();
        
		Tinebase_Group::getInstance()->importGroups(); //import groups(ldap)/create initial groups(sql)
		
        $this->_createInitialRoles();

    	parent::_initialize($_application, $_options);
    }
    
    /**
     * Override method because this app requires special rights
     * @see tine20/Setup/Setup_Initialize#_createInitialRights($_application)
     * 
     * @todo make hard coded role name ('user role') configurable
     */
    protected function _createInitialRights(Tinebase_Model_Application $_application)
    {
    	parent::_createInitialRights($_application);

    	$roles = Tinebase_Acl_Roles::getInstance();
        $userRole = $roles->getRoleByName('user role');
		$roles->addSingleRight(
		    $userRole->getId(), 
		    $_application->getId(), 
		    Tinebase_Acl_Rights::CHECK_VERSION
		);
		$roles->addSingleRight(
		    $userRole->getId(), 
		    $_application->getId(), 
		    Tinebase_Acl_Rights::REPORT_BUGS
		);
    }
    
    /**
     * @todo make hard coded role names ('user role' and 'admin role') configurable
     * 
     * @return void
     */
    protected function _createInitialRoles()
    {
        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);
        
        $adminGroup = $groupsBackend->getDefaultAdminGroup();
        $userGroup  = $groupsBackend->getDefaultGroup();
        
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
    }
}