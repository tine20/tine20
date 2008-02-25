<?php
/**
 * Tine 2.0
 *
 * @package     Egwbase
 * @subpackage  Events
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class to handle events between the applications
 *
 * @package     Egwbase
 * @subpackage  Events
 */
class Egwbase_Events
{
    /**
     * calls the handleEvents function in the controller of all enabled applications 
     *
     * @param Egwbase_Events_Object $_eventObject the event object
     */
    static public function fireEvent(Egwbase_Events_Abstract $_eventObject)
    {
        foreach(Egwbase_Application::getInstance()->getApplicationsByState(Egwbase_Application::ENABLED) as $application) {
            $controllerName = ucfirst($application->app_name) . '_Controller';
            
            if(class_exists($controllerName)) {
                try {
                    $controller = call_user_func(array($controllerName, 'getInstance'));
                } catch (Exception $e) {
                    // application has no controller or is not useable at all
                    continue;
                }
                if($controller instanceof Egwbase_Events_Interface) {
                    Zend_Registry::get('logger')->debug(__METHOD__ . ' (' . __LINE__ . ') ' . "calling eventhandler of $controllerName");
                    try {
                        $controller->handleEvents($_eventObject);
                    } catch (Exception $e) {
                        Zend_Registry::get('logger')->debug(__METHOD__ . ' (' . __LINE__ . ') ' . "$controllerName throwed an exception");
                    }
                }
            }
        }
    }
}