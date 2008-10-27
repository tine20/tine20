<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Events
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class to handle events between the applications
 *
 * @package     Tinebase
 * @subpackage  Events
 */
class Tinebase_Events
{
    /**
     * calls the handleEvents function in the controller of all enabled applications 
     *
     * @param Tinebase_Events_Object $_eventObject the event object
     */
    static public function fireEvent(Tinebase_Events_Abstract $_eventObject)
    {
        foreach(Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED) as $application) {
            try {
                $controller = Tinebase_Core::getApplicationInstance($application);
            } catch (Exception $e) {
                // application has no controller or is not useable at all
                continue;
            }
            if($controller instanceof Tinebase_Events_Interface) {
                Zend_Registry::get('logger')->debug(__METHOD__ . ' (' . __LINE__ . ') calling eventhandler of ' . (string) $application);
                try {
                    $controller->handleEvents($_eventObject);
                } catch (Exception $e) {
                    Zend_Registry::get('logger')->debug(__METHOD__ . ' (' . __LINE__ . ') ' . (string) $application . ' throwed an exception');
                }
            }
        }
    }
}