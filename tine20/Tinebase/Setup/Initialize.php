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
        $authenticationData = empty($_options['authenticationData']) ? Setup_Controller::getInstance()->loadAuthenticationData() : $_options['authenticationData'];
        $defaultGroupNames = $this->_parseDefaultGroupNameOptions($_options);
        $authenticationData['accounts'][Tinebase_User::getConfiguredBackend()] = array_merge($authenticationData['accounts'][Tinebase_User::getConfiguredBackend()], $defaultGroupNames);
        Setup_Controller::getInstance()->saveAuthentication($authenticationData);       

        if (isset($_options['imap']) || isset($_options['smtp'])) {
            $data = $this->_parseEmailOptions($_options);
            Setup_Controller::getInstance()->saveEmailConfig($data);
        }
        
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
    
    /**
     * parse email options
     * 
     * @param array $_options
     * @return array
     * 
     * @todo generalize this to allow to add other options during cli setup
     */
    protected function _parseEmailOptions($_options)
    {
        $result = array('imap' => array(), 'smtp' => array());
        
        foreach (array_keys($result) as $group) {
            if (isset($_options[$group])) {
                $_options[$group] = preg_replace('/\s*/', '', $_options[$group]);
                $parts = explode(',', $_options[$group]);
                foreach ($parts as $part) {
                    if (preg_match('/_/', $part)) {
                        list($key, $sub) = explode('_', $part);
                        list($subKey, $value) = explode(':', $sub);
                        $result[$group][$key][$subKey] = $value;
                    } else {
                        list($key, $value) = explode(':', $part);
                        $result[$group][$key] = $value;
                    }
                }
                $result[$group]['active'] = 1;
            }
        }
        
        return $result;
    }
    
    /**
     * Extract default group name settings from {@param $_options}
     * 
     * @param array $_options
     * @return array
     */
    protected function _parseDefaultGroupNameOptions($_options)
    {
        $result = array();
        if (isset($_options['defaultAdminGroupName'])) {
          $result['defaultAdminGroupName'] = $_options['defaultAdminGroupName'];
        }
        
        if (isset($_options['defaultUserGroupName'])) {
          $result['defaultUserGroupName'] = $_options['defaultUserGroupName'];
        }
        
        return $result;
    }
}
