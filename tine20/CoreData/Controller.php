<?php
/**
 * Tine 2.0
 * 
 * @package     CoreData
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * CoreData Controller (composite)
 * 
 * The CoreData 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package CoreData
 * @subpackage  Controller
 */
class CoreData_Controller extends Tinebase_Controller_Event implements Tinebase_Application_Container_Interface
{
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'CoreData_Model_CoreData';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'CoreData';
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * holds self
     * @var CoreData_Controller
     */
    private static $_instance = NULL;
    
    /**
     * singleton
     *
     * @return CoreData_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new CoreData_Controller();
        }
        return self::$_instance;
    }

    /**
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the account object
     * @return Tinebase_Record_RecordSet  of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_accountId)
    {
        // not needed
    }

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        // not needed atm
    }

    /**
     * get core data for all applications
     *
     * @return Tinebase_Record_RecordSet
     */
    public function getCoreData()
    {
        $result = new Tinebase_Record_RecordSet('CoreData_Model_CoreData');

        // loop all installed apps and collect CoreData
        foreach (Tinebase_Core::getUser()->getApplications() as $application) {
            $appControllerName = $application->name . '_Controller';
            if (class_exists($appControllerName)) {
                $appController = call_user_func($appControllerName . '::getInstance');
                if (method_exists($appController, 'getCoreDataForApplication')) {
                    $coreDataOfApplication = $appController->getCoreDataForApplication();
                    $result->merge($coreDataOfApplication);
                }
            }

        }

        return $result;
    }
}
