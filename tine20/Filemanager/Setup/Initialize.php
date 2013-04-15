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
     * 
     * init folders
     */
    public function _initializeFolders(Tinebase_Model_Application $_application, $_options = null)
    {
        // initialize folders for installed apps
        foreach (Tinebase_Application::getInstance()->getApplications() as $app) {
            $reflectionClass = new ReflectionClass($app->name . '_Setup_Initialize');
            $methods = $reflectionClass->getMethods();
            foreach ($methods as $method) {
                $methodName = $method->name;
                if ($method->name == '_initializeFilemanagerFolder') {
                    Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Initializing filemanager folder for application: ' . $app->name);
                    $class = $reflectionClass->newInstance();
                    $class->_initializeFilemanagerFolder($app);
                }
            }
         }
    }
}
