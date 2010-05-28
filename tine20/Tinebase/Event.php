<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class to handle events between the applications
 *
 * @package     Tinebase
 * @subpackage  Event
 */
class Tinebase_Event
{
    /**
     * calls the handleEvents function in the controller of all enabled applications 
     *
     * @param Tinebase_Event_Object $_eventObject the event object
     */
    static public function fireEvent(Tinebase_Event_Abstract $_eventObject)
    {
        foreach(Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED) as $application) {
            try {
                $controller = Tinebase_Core::getApplicationInstance($application);
            } catch (Tinebase_Exception_NotFound $e) {
                // application has no controller or is not useable at all
                continue;
            }
            if($controller instanceof Tinebase_Event_Interface) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') calling eventhandler of ' . (string) $application);
                try {
                    $controller->handleEvents($_eventObject);
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') ' 
                        . (string) $application . ' threw an exception: '
                        . $e->getMessage()
                    );
                }
            }
        }
        
        // try custom user defined listeners
        try { 
            if (@class_exists('CustomEventHooks')) {
                $methods = get_class_methods('CustomEventHooks');
                if (in_array('handleEvents', (array)$methods)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . ' (' . __LINE__ . ') ' . ' about to process user defined event hook for '. get_class($_eventObject));
                    CustomEventHooks::handleEvents($_eventObject);
                }
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->info(__METHOD__ . ' (' . __LINE__ . ') ' . ' failed to process user defined event hook with message: ' . $e);
        }
    }
}