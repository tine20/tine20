<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Facade for Calendar_Controller_Event
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
 * 
 * @package     Calendar
 * @subpackage  Controller
 */
class Calendar_Controller_MSEventFacade implements Tinebase_Controller_Record_Interface
{
    /**
     * @var Calendar_Controller_Event
     */
    protected $_eventController = NULL;
    
    /**
     * @var Calendar_Controller_MSEventFacade
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
     * @return Calendar_Controller_MSEventFacade
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller_MSEventFacade();
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
        if ($event->rrule) {
            $event->exdate = $this->_eventController->getRecurExceptions($event, TRUE);
        }
        
        return $event;
    }
    
    /**
     * Returns a set of events identified by their id's
     * 
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet of Calendar_Model_Event
     */
    public function getMultiple($_ids)
    {
        $events = $this->_eventController->getMultiple($_ids);
        foreach ($events as $event) {
            try {
                if ($event->rrule) {
                    $event->exdate = $this->_eventController->getRecurExceptions($event, TRUE);
                }
            } catch (Tinebase_Exception_AccessDenied $ade) {
                // if we don't have permissions for the exdates, this is likely a freebusy info only -> remove from set
                $events->removeRecord($event);
            } catch (Exception $e) {
                $event->exdate = new Tinebase_Record_RecordSet('Calendar_Model_Event');
            }
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
            if ($event->rrule) {
                $event->exdate = $this->_eventController->getRecurExceptions($event, TRUE);
            }
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
        if (! $_filter) {
            $_filter = new Calendar_Model_EventFilter();
        }
        $_filter->addFilter(new Tinebase_Model_Filter_Text('recurid', 'isnull', null));
        
        $events = $this->_eventController->search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
        
        if (! $_onlyIds) {
            foreach($events as $event) {
                if ($event->rrule) {
                    $event->exdate = $this->_eventController->getRecurExceptions($event, TRUE);
                }
            }
        }
        
        return $events;
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
        if (! $_filter) {
            $_filter = new Calendar_Model_EventFilter();
        }
        $_filter->addFilter(new Tinebase_Model_Filter_Text('recurid', 'isnull', null));
        
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
    public function create(Tinebase_Record_Interface $_event)
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
        
        if ($savedEvent->rrule) {
            $savedEvent->exdate = $this->_eventController->getRecurExceptions($savedEvent, TRUE);
        }
        
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
    public function update(Tinebase_Record_Interface $_event, $_checkBusyConficts = FALSE)
    {
        if ($_event->recurid) {
            throw new Tinebase_Exception_UnexpectedValue('recur event instances must be saved as part of the base event');
        }
        
        $exceptions = $_event->exdate instanceof Tinebase_Record_RecordSet ? $_event->exdate : new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $_event->exdate = $exceptions->getOriginalDtStart();
        
        $currentPersistentExceptions = $_event->rrule ? $this->_eventController->getRecurExceptions($_event, FALSE) : new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $newPersistentExceptions = $exceptions->filter('is_deleted', 0);
        $this->_prepareException($_event, $newPersistentExceptions);
        
        $migration = $this->_getExceptionsMigration($currentPersistentExceptions, $newPersistentExceptions);
        
        $this->_eventController->delete($migration['toDelete']->getId());
        
        foreach($migration['toCreate'] as $exception) {
            $this->_eventController->createRecurException($exception, !!$exception->is_deleted);
        }
        
        foreach($migration['toUpdate'] as $exception) {
            $this->_eventController->update($exception, $_checkBusyConficts);
        }
        
        $updatedBaseEvent = $this->_eventController->update($_event, $_checkBusyConficts);
        
        if ($updatedBaseEvent->rrule) {
            $updatedBaseEvent->exdate = $this->_eventController->getRecurExceptions($updatedBaseEvent, TRUE);
        }
            
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
        $ids = array_unique((array)$_ids);
        $events = $this->getMultiple($ids);
        
        foreach($events as $event) {
            foreach ($event->exdate as $exception) {
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
     * filters given eventset for events with matching dtstart
     * 
     * @param Tinebase_Record_RecordSet $_events
     * @param array                     $_dtstarts
     */
    protected function _filterEventsByDTStarts($_events, $_dtstarts)
    {
        $filteredSet = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $allDTStarts = $_events->getOriginalDtStart();
        
        $existingIdxs = array_intersect($allDTStarts, $_dtstarts);
        
        foreach($existingIdxs as $idx => $dtstart) {
            $filteredSet->addRecord($_events[$idx]);
        }
        
        return $filteredSet;
    }

    /**
     * computes an returns the migration for event exceptions
     * 
     * @param Tinebase_Record_RecordSet $_currentPersistentExceptions
     * @param Tinebase_Record_RecordSet $_newPersistentExceptions
     */
    protected function _getExceptionsMigration($_currentPersistentExceptions, $_newPersistentExceptions)
    {
        $migration = array();
        
        // add indices and sort to speedup things
        $_currentPersistentExceptions->addIndices(array('dtstart'))->sort('dtstart');
        $_newPersistentExceptions->addIndices(array('dtstart'))->sort('dtstart');
        
        // get dtstarts
        $currDtStart = $_currentPersistentExceptions->getOriginalDtStart();
        $newDtStart = $_newPersistentExceptions->getOriginalDtStart();
        
        // compute migration in terms of dtstart
        $toDeleteDtStart = array_diff($currDtStart, $newDtStart);
        $toCreateDtStart = array_diff($newDtStart, $currDtStart);
        $toUpdateDtSTart = array_intersect($currDtStart, $newDtStart);
        
        $migration['toDelete'] = $this->_filterEventsByDTStarts($_currentPersistentExceptions, $toDeleteDtStart);
        $migration['toCreate'] = $this->_filterEventsByDTStarts($_newPersistentExceptions, $toCreateDtStart);
        $migration['toUpdate'] = $this->_filterEventsByDTStarts($_newPersistentExceptions, $toUpdateDtSTart);
        
        // get ids for toUpdate
        $idxIdMap = $this->_filterEventsByDTStarts($_currentPersistentExceptions, $toUpdateDtSTart)->getId();
        $migration['toUpdate']->setByIndices('id', $idxIdMap);
        
        return $migration;
    }
    
    /**
     * prepares an exception instance for persitence
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
        
        $_exception->uid = $_baseEvent->uid;
        $_exception->recurid = $_baseEvent->uid . '-' . $_exception->getOriginalDtStart()->format(Tinebase_Record_Abstract::ISO8601LONG);
    }
}
