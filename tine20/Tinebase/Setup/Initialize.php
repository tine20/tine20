<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
        
        // import groups(ldap)/create initial groups(sql)
        if(Tinebase_User::getInstance() instanceof Tinebase_User_Interface_SyncAble) {
            Tinebase_Group::syncGroups();
        } else {
            Tinebase_Group::createInitialGroups();
        }
        
        Tinebase_Acl_Roles::getInstance()->createInitialRoles();
        $this->_initTinebaseScheduler();
        
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
     * parse email options
     * 
     * @param array $_options
     * @return array
     * 
     * @todo generalize this to allow to add other options during cli setup
     */
    protected function _parseEmailOptions($_options)
    {
        $result = array('imap' => array(), 'smtp' => array(), 'sieve' => array());
        
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
    
    /**
     * init scheduler
     */
    protected function _initTinebaseScheduler()
    {
        $scheduler = Tinebase_Core::getScheduler();
        $this->_addAlarmTask($scheduler);
    }
    
    /**
     * add alarm task to scheduler
     * 
     * @param Zend_Scheduler $_scheduler
     */
    protected function _addAlarmTask($_scheduler)
    {
        $request = new Zend_Controller_Request_Simple(); 
        $request->setControllerName('Tinebase_Alarm');
        $request->setActionName('sendPendingAlarms');
        $request->setParam('eventName', 'Tinebase_Event_Async_Minutely');
        
        $task = Tinebase_Scheduler_Task::getTask();
        $task->setRequest($request);
        
        $_scheduler->addTask('Tinebase_Alarm', $task);
        $_scheduler->saveTask();
    }
}
