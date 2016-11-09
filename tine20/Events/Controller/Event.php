<?php
/**
 * Event controller for Events application
 * 
 * @package     Events
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Event controller class for Events application
 * 
 * @package     Events
 * @subpackage  Controller
 */
class Events_Controller_Event extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'Events';
        $this->_backend = new Events_Backend_Event();
        $this->_modelName = 'Events_Model_Event';
        $this->_purgeRecords = false;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = true;
        $this->_resolveCustomFields = true;

        //set this to true to create/update related records
        $this->_inspectRelatedRecords  = true;

        // remove all related calendar events
        $this->setRelatedObjectsToDelete(array('Calendar_Model_Event'));
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Events_Controller_Event
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Events_Controller_Event
     */
    public static function getInstance()
    {
        if (static::$_instance === NULL) {
            static::$_instance = new self();
        }
        
        return static::$_instance;
    }

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_checkBusyConflicts
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     *
     * TODO do we need this for update(), too?
     * TODO revert to previous state after create()?
     */
    public function create(Tinebase_Record_Interface $_record, $_checkBusyConflicts = false)
    {
        // set $_checkBusyConflicts for related cal events
        $this->_doRelatedCreateUpdateCheck = $_checkBusyConflicts;

        return parent::create($_record);
    }

    /**
     * returns the default Events
     *
     * @return Tinebase_Model_Container
     */
    public function getDefaultEvents()
    {
        return Tinebase_Container::getInstance()->getDefaultContainer($this->_applicationName, NULL, Events_Preference::DEFAULT_EVENTS_CONTAINER);
    }
    
    /**
     * get (create if it does not exist) calendar container for events
     *
     * @return Tinebase_Model_Container|NULL
     */
    public static function getDefaultEventsCalendar()
    {
        try {
            $eventsCalendarId = Events_Config::getInstance()->get(Events_Config::DEFAULT_EVENTS_CALENDAR);
            $eventsCalendar = Tinebase_Container::getInstance()->get($eventsCalendarId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $eventsCalendar = Tinebase_Container::getInstance()->createSystemContainer('Calendar', 'Events');
            Events_Config::getInstance()->set(Events_Config::DEFAULT_EVENTS_CALENDAR, $eventsCalendar->getId());
        }
    
        return $eventsCalendar;
    }
}
