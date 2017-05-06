<?php
/**
 * Tine 2.0
  * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Filemanager initialization
 * 
 * @package Filemanager
 */
class Filemanager_Setup_Initialize extends Setup_Initialize
{
    /**
     * initialize folders for installed apps
     */
    public function _initializeFoldersForOtherApps(Tinebase_Model_Application $_application, $_options = null)
    {
        foreach (Tinebase_Application::getInstance()->getApplications() as $app) {
            $initializeClass = $app->name . '_Setup_Initialize';
            if (class_exists($initializeClass)) {
                $reflectionClass = new ReflectionClass($initializeClass);
                $methods = $reflectionClass->getMethods();
                foreach ($methods as $method) {
                    $methodName = $method->name;
                    if ($methodName == '_initializeFilemanagerFolder') {
                        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Initializing filemanager folder for application: ' . $app->name);
                        $class = $reflectionClass->newInstance();
                        $class->_initializeFilemanagerFolder($app);
                    }
                }
            }
        }
    }

    /**
     * initialize folders for current users
     */
    public function _initializePersonalFolders(Tinebase_Model_Application $_application, $_options = null)
    {
        $users = Tinebase_User::getInstance()->getFullUsers();
        foreach ($users as $user) {
            if (in_array($user->accountLoginName, Tinebase_User::getSystemUsernames())) {
                continue;
            }

            Filemanager_Controller::getInstance()->createPersonalFolder($user);
        }
    }
}
