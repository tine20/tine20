<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
        
        $this->_setConfigOptions($_options);
        
        // import groups(ldap)/create initial groups(sql)
        if(Tinebase_User::getInstance() instanceof Tinebase_User_Interface_SyncAble) {
            Tinebase_Group::syncGroups();
        } else {
            Tinebase_Group::createInitialGroups();
        }
        
        Tinebase_Acl_Roles::getInstance()->createInitialRoles();
        
    	parent::_initialize($_application, $_options);
    }
    
    /**
     * set config options (accounts/authentication/email/...)
     * 
     * @param array $_options
     */
    protected function _setConfigOptions($_options)
    {
        $emailConfigKeys = Setup_Controller::getInstance()->getEmailConfigKeys();
        $configsToSet = array_merge($emailConfigKeys, array('authentication', 'accounts', 'redirectSettings'));
        
        $parsedOptions = array();
        foreach ($configsToSet as $group) {
            if (isset($_options[$group])) {
                $parsedOptions[$group] = (is_string($_options[$group])) ? Setup_Frontend_Cli::parseConfigValue($_options[$group]) : $_options[$group];
            }
        }
        
        Setup_Controller::getInstance()->saveEmailConfig($parsedOptions);
        Setup_Controller::getInstance()->saveAuthentication($parsedOptions);
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
        $roles->addSingleRight(
            $userRole->getId(), 
            $_application->getId(), 
            Tinebase_Acl_Rights::MANAGE_OWN_STATE
        );
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
    
    /**
     * init scheduler tasks
     */
    protected function _initializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Tinebase_Scheduler_Task::addAlarmTask($scheduler);
        Tinebase_Scheduler_Task::addCacheCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addCredentialCacheCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addTempFileCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addDeletedFileCleanupTask($scheduler);
    }
}
