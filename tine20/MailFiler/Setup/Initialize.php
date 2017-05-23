<?php
/**
 * Tine 2.0
  * 
 * @package     MailFiler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for MailFiler initialization
 * 
 * @package MailFiler
 */
class MailFiler_Setup_Initialize extends Setup_Initialize
{
    /**
     * init folders
     */
    public function _initializeFolders(Tinebase_Model_Application $_application, $_options = null)
    {
        // initialize folders for installed apps
        foreach (Tinebase_Application::getInstance()->getApplications() as $app) {
            $initializeClass = $app->name . '_Setup_Initialize';
            if (class_exists($initializeClass)) {
                $reflectionClass = new ReflectionClass($initializeClass);
                $methods = $reflectionClass->getMethods();
                foreach ($methods as $method) {
                    $methodName = $method->name;
                    if ($method->name == '_initializeMailFilerFolder') {
                        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Initializing filemanager folder for application: ' . $app->name);
                        $class = $reflectionClass->newInstance();
                        $class->_initializeMailFilerFolder($app);
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

            MailFiler_Controller::getInstance()->createPersonalFolder($user);
        }
    }
}
