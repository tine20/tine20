<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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
     * keeps a list of currently processed events
     * 
     * @var array
     */
    static public $events = array();
    
    /**
     * calls the handleEvent function in the controller of all enabled applications 
     *
     * @param  Tinebase_Event_Object  $_eventObject  the event object
     */
    static public function fireEvent(Tinebase_Event_Abstract $_eventObject)
    {
        self::$events[get_class($_eventObject)][$_eventObject->getId()] = $_eventObject;
        
        if (self::isDuplicateEvent($_eventObject)) {
            // do nothing
            return;
        }
        
        foreach(Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED) as $application) {
            try {
                $controller = Tinebase_Core::getApplicationInstance($application, NULL, TRUE);
            } catch (Tinebase_Exception_NotFound $e) {
                // application has no controller or is not useable at all
                continue;
            }
            if($controller instanceof Tinebase_Event_Interface) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') calling eventhandler for event ' . get_class($_eventObject) . ' of application ' . (string) $application);
                try {
                    $controller->handleEvent($_eventObject);
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' (' . __LINE__ . ') ' 
                        . (string) $application . ' threw an exception: '
                        . $e->getMessage()
                    );
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') ' . $e->getTraceAsString());
                }
            }
        }
        
        // try custom user defined listeners
        try {
            if (@class_exists('CustomEventHooks')) {
                $methods = get_class_methods('CustomEventHooks');
                if (in_array('handleEvent', (array)$methods)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . ' (' . __LINE__ . ') ' . ' about to process user defined event hook for '. get_class($_eventObject));
                    CustomEventHooks::handleEvent($_eventObject);
                }
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->info(__METHOD__ . ' (' . __LINE__ . ') ' . ' failed to process user defined event hook with message: ' . $e);
        }
        
        unset(self::$events[get_class($_eventObject)][$_eventObject->getId()]);
    }
    
    /**
     * checks if an event is duplicate
     * 
     * @todo   implement logic
     * @param  Tinebase_Event_Abstract  $_eventObject  the event object
     * @return boolean
     */
    static public function isDuplicateEvent(Tinebase_Event_Abstract $_eventObject)
    {
        return false;
    }
}
