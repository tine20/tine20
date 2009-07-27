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
        $this->_setDefaultGroups('Users', 'Administrators');
        
		switch(Tinebase_Core::getAuthType()) {
			case Tinebase_Auth_Factory::SQL:
			    $this->_createInitialGroups();
			    break;
			case Tinebase_Auth_Factory::LDAP:
			    Tinebase_Group::getInstance()->importGroups();
			    Tinebase_User::getInstance()->importUsers();
			    Tinebase_Group::getInstance()->importGroupMembers();
			    break;
			//$import = new Setup_Import_Egw14();
		}
		
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
     * set default group names in config
     *
     * @param string $_userGroup
     * @param string $_adminGroup
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
    }
    
    /**
     * create initial groups
     *
     */
    protected function _createInitialGroups()
    {
        // add the admin group
        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);

        $adminGroup = new Tinebase_Model_Group(array(
            'name'          => Tinebase_Config::getInstance()->getConfig(Tinebase_Config::DEFAULT_ADMIN_GROUP)->value,
            'description'   => 'Group of administrative accounts'
        ));
        $adminGroup = $groupsBackend->addGroup($adminGroup);

        // add the user group
        $userGroup = new Tinebase_Model_Group(array(
            'name'          => Tinebase_Config::getInstance()->getConfig(Tinebase_Config::DEFAULT_USER_GROUP)->value,
            'description'   => 'Group of user accounts'
        ));
        $userGroup = $groupsBackend->addGroup($userGroup);
    }
    
    /**
     * @todo make hard coded role names ('user role' and 'admin role') configurable
     * 
     * @return void
     */
    protected function _createInitialRoles()
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
    }
}