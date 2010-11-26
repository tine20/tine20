<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Facade of Calendar_Controller_Event
 * 
 * hides the complexity of getting / storing recuring events exceptions which 
 * is conceptually different in the iCal world (tine 2.0 native) and the MS world.
 * 
 * In the MS world event exceptions are a property of an event. So with this facade
 * you also handle the event exceptions as a event property:
 * -> Tinebase_Record_RecordSet Calendar_Model_Event::exdate
 * 
 * deleted recur event instances (fall outs) have the property:
 * -> Calendar_Model_Event::is_deleted set to TRUE
 * 
 * when creating/updating events, make sure to have the original start time (ExceptionStartTime)
 * of recur event instances stored in the property:
 * -> Calendar_Model_Event::recurid
 */
class Calendar_Conroller_MSEventFacade implements Tinebase_Controller_Record_Interface
{
    /**
     * @var Calendar_Controller_Event
     */
    protected $_eventController = NULL;
    
    /**
     * @var Calendar_Conroller_MSEventFacade
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_eventController = Calendar_Controller_Event::getInstance();
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() 
    {
        
    }
    
    /**
     * singleton
     *
     * @return Calendar_Conroller_MSEventFacade
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Conroller_MSEventFacade();
        }
        return self::$_instance;
    }
    
    /**
     * get by id
     *
     * @param string $_id
     * @return Calendar_Model_Event
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function get($_id)
    {
        $event = $this->_eventController->get($_id);
        $event->exdate = $this->_eventController->getRecurExceptions($event, TRUE);
        
        return $event;
    }
    
    /**
     * Returns a set of leads identified by their id's
     * 
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet of Calendar_Model_Event
     */
    public function getMultiple($_ids)
    {
        $events = $this->_eventController->get($_ids);
        foreach ($events as $event) {
            $event->exdate = $this->_eventController->getRecurExceptions($event, TRUE);
        }
        
        return $events;
    }  
    
    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet of Calendar_Model_Event
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        $events = $this->_eventController->getAll($_orderBy, $_orderDirection);
        foreach ($events as $event) {
            $event->exdate = $this->_eventController->getRecurExceptions($event, TRUE);
        }
        
        return $events;
    }
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional    $_filter
     * @param Tinebase_Model_Pagination|optional            $_pagination
     * @param bool                                          $_getRelations
     * @param boolean                                       $_onlyIds
     * @param string                                        $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        //@todo add filter
        //@todo include excetptions on $_getRelations?
        return $this->_eventController->search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get') 
    {
        //@todo add filter
        return $this->_eventController->searchCount($_filter, $_action);
    }
    
    /*************** add / update / delete *****************/    

    /**
     * add one record
     *
     * @param   Calendar_Model_Event $_record
     * @return  Calendar_Model_Event
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Calendar_Model_Event $_event)
    {
        if ($_event->recurid) {
            throw new Tinebase_Exception_UnexpectedValue('recur event instances must be saved as part of the base event');
        }
        
        $exceptions = $_event->exdate;
        $_event->exdate = NULL;
        
        $savedEvent = $this->_eventController->create($_event);
        
        if ($exceptions instanceof Tinebase_Record_RecordSet) {
            foreach($exceptions as $exception) {
                $this->_prepareException($savedEvent, $exception);
                $this->_eventController->createRecurException($exception, !!$exception->is_deleted);
            }
        }
        
        $savedEvent->exdate = $this->_eventController->getRecurExceptions($savedEvent, TRUE);
        return $savedEvent;
    }
    
    /**
     * update one record
     *
     * @param   Calendar_Model_Event $_record
     * @param   bool                 $_checkBusyConficts
     * @return  Calendar_Model_Event
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Calendar_Model_Event $_event, $_checkBusyConficts = FALSE)
    {
        if ($_event->recurid) {
            throw new Tinebase_Exception_UnexpectedValue('recur event instances must be saved as part of the base event');
        }
        
        $exceptions = $_event->exdate instanceof Tinebase_Record_RecordSet ? $_event->exdate : new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $exceptions->addIndices(array('is_deleted', 'dtstart'));
        
        $_event->exdate = $exceptions->getOriginalDtStart();
        $updatedBaseEvent = $this->_eventController->update($_record, $_checkBusyConficts);
        
        $currentPersistentExceptions = $this->_eventController->getRecurExceptions($updatedBaseEvent, FALSE);
        $currentPersistentExceptions->addIndices(array('dtstart'));
        $newPersistentExceptions = $exceptions->filter('is_deleted', 0);
        $this->_prepareException($updatedBaseEvent, $newPersistentExceptions);
        
        $currDtStart = $currentPersistentExceptions->getOriginalDtStart();
        $newDtStart = $newPersistentExceptions->getOriginalDtStart();
        
        // compute migration
        $toDeleteDtStart = array_diff($currDtStart, $newDtStart);
        $toCreateDtStart = array_diff($newDtStart, $currDtStart);
        $toUpdateDtSTart = array_intersect($currDtStart, $newDtStart);
        
        //@todo get ids -> get events -> doAction
        
        $updatedBaseEvent->exdate = $this->_eventController->getRecurExceptions($updatedBaseEvent, TRUE);
        return $updatedBaseEvent;
    }
    
    /**
     * update multiple records
     * 
     * @param   Tinebase_Model_Filter_FilterGroup $_filter
     * @param   array $_data
     * @return  integer number of updated records
     */
    public function updateMultiple($_what, $_data)
    {
        throw new Tinebase_Exception_NotImplemented('Calendar_Conroller_MSEventFacade::updateMultiple not yet implemented');
    }
    
    /**
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet
     */
    public function delete($_ids)
    {
        $ids = array_unique($_ids);
        $events = $this->getMultiple($_ids);
        
        foreach($events as $event) {
            foreach ($event->rrule as $exception) {
                $exceptionId = $exception->getId();
                if ($exceptionId) {
                    $ids[] = $exceptionId;
                }
            }
        }
        
        $this->_eventController->delete($ids);
        return $events;
    }
    
    /**
     * prepares an exception instance for persitece
     * 
     * @param  Calendar_Model_Event $_baseEvent
     * @param  Calendar_Model_Event $_exception
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _prepareException($_baseEvent, $_exception)
    {
        if ($_exception instanceof Tinebase_Record_RecordSet) {
            foreach($_exception as $exception) {
                $this->_prepareException($_baseEvent, $exception);
            }
            
            return;
        }
        
        
        if (! $_baseEvent->uid) {
            throw new Tinebase_Exception_InvalidArgument('base event has no uid');
        }
        
        $_exception->recurid = $_baseEvent->uid . '-' . $_exception->getOriginalDtStart()->format(Tinebase_Record_Abstract::ISO8601LONG);
    }
}
