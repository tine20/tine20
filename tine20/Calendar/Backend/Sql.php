<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * native tine 2.0 events sql backend
 *
 * Events consists of the properties of Calendar_Model_Evnet except Tags and Notes 
 * which are as always handles by their controllers/backends
 * 
 * @todo add handling for class_id
 * @package Calendar 
 */
class Calendar_Backend_Sql extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'cal_events';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Calendar_Model_Event';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
    
    /**
     * attendee backend
     * 
     * @var Calendar_Backend_Sql_Attendee
     */
    protected $_attendeeBackend = NULL;
    
    /**
     * the constructor
     *
     * @param Zend_Db_Adapter_Abstract $_db optional
     * @param string $_modelName
     * @param string $_tableName
     * @param string $_tablePrefix
     *
     */
    public function __construct ($_dbAdapter = NULL, $_modelName = NULL, $_tableName = NULL, $_tablePrefix = NULL)
    {
        parent::__construct($_dbAdapter, $_modelName, $_tableName, $_tablePrefix);
        
        $this->_attendeeBackend = new Calendar_Backend_Sql_Attendee($_dbAdapter);
    }
    
    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_UnexpectedValue
     * 
     * @todo    remove autoincremental ids later
     */
    public function create(Tinebase_Record_Interface $_record) 
    {
        $this->_setRruleUntil($_record);
        
        $event = parent::create($_record);
        $this->_saveExdates($_record);
        $this->_saveAttendee($_record);
        
        return $this->get($event->getId());
    }
    
    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record) 
    {
        $this->_setRruleUntil($_record);
        
        $event = parent::update($_record);
        $this->_saveExdates($_record);
        $this->_saveAttendee($_record);
        
        return $this->get($event->getId());
    }
    
    /**
     * Search for direct events matching given filter
     * 
     * Direct events are those, which duration (events dtstart -> dtend)
     *   reaches in the seached period.
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     *
    public function searchDirectEvents(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)
    {
        Calendar_Model_PeriodFilter::setType(Calendar_Model_PeriodFilter::TYPE_DIRECT);
        return parent::search($_filter, $_pagination, $_onlyIds);
    }
    */
    
    /**
     * Search for base events of recuring events matching given filter
     * 
     * Recur Base events are those recuring events which potentially could have
     *   recurances in the searched period
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     *
    public function searchRecurBaseEvents(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)
    {
        Calendar_Model_PeriodFilter::setType(Calendar_Model_PeriodFilter::TYPE_RECURBASE);
        return parent::search($_filter, $_pagination, $_onlyIds);
    }
    */
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        $select = parent::_getSelect($_cols, $_getDeleted);
        
        $select->joinLeft(
            /* table  */ array('exdate' => $this->_tablePrefix . 'cal_exdate'), 
            /* on     */ $this->_db->quoteIdentifier('exdate.cal_event_id') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.id'),
            /* select */ array('exdate' => 'GROUP_CONCAT(' . $this->_db->quoteIdentifier('exdate.exdate') . ')'));
        
        $select->group(array_keys($this->_schema));
        
        return $select;
    }
    
    /**
     * converts raw data from adapter into a single record
     *
     * @param  array $_data
     * @return Tinebase_Record_Abstract
     */
    protected function _rawDataToRecord($_rawData) {
        $event = parent::_rawDataToRecord($_rawData);
        
        $this->appendForeignRecordSetToRecord($event, 'attendee', 'id', Calendar_Backend_Sql_Attendee::FOREIGNKEY_EVENT, $this->_attendeeBackend);
        
        return $event;
    }
    
    /**
     * converts raw data from adapter into a set of records
     *
     * @param  array $_rawData of arrays
     * @return Tinebase_Record_RecordSet
     */
    protected function _rawDataToRecordSet(array $_rawData)
    {
        $events = new Tinebase_Record_RecordSet($this->_modelName);
        $events->addIndices(array('rrule', 'recurid'));
        
        foreach ($_rawData as $rawEvent) {
            $events->addRecord(new Calendar_Model_Event($rawEvent, true));
        }
        
        $this->appendForeignRecordSetToRecordSet($events, 'attendee', 'id', Calendar_Backend_Sql_Attendee::FOREIGNKEY_EVENT, $this->_attendeeBackend);
        
        return $events;
    }
    
    /**
     * saves exdates of an event
     *
     * @param Calendar_Model_Event $_event
     */
    protected function _saveExdates($_event)
    {
        $this->_db->delete($this->_tablePrefix . 'cal_exdate', $this->_db->quoteInto($this->_db->quoteIdentifier('cal_event_id') . '= ?', $_event->getId()));
        foreach ((array)$_event->exdate as $exdate) {
            $this->_db->insert($this->_tablePrefix . 'cal_exdate', array(
                'id'           => $_event->generateUID(),
                'cal_event_id' => $_event->getId(),
                'exdate'       => $exdate->get(Tinebase_Record_Abstract::ISO8601LONG)
            ));
        }
    }
    
    /**
     * saves attendee of given event
     * 
     * @param Calendar_Model_Evnet $_event
     */
    protected function _saveAttendee($_event)
    {
        $attendee = $_event->attendee instanceof Tinebase_Record_RecordSet ? 
            $_event->attendee : 
            new Tinebase_Record_RecordSet($this->_attendeeBackend->getModelName());
        $attendee->cal_event_id = $_event->getId();
            
        $currentAttendee = $this->_attendeeBackend->getMultipleByProperty($_event->getId(), Calendar_Backend_Sql_Attendee::FOREIGNKEY_EVENT);
        
        $diff = $currentAttendee->getMigration($attendee->getArrayOfIds());
        $this->_attendeeBackend->delete($diff['toDeleteIds']);
        
        foreach ($attendee as $attende) {
            $method = $attende->getId() ? 'update' : 'create';
            $this->_attendeeBackend->$method($attende);
        }
    }
    
    /**
     * sets rrule until field in event model
     *
     * @param  Calendar_Model_Event $_event
     * @return void
     */
    protected function _setRruleUntil(Calendar_Model_Event $_event)
    {
        if (empty($_event->rrule)) {
            $_event->rrule_until = NULL;
        } else {
            $rrule = $_event->rrule;
            if (! $_event->rrule instanceof Calendar_Model_Rrule) {
                $rrule = new Calendar_Model_Rrule(array());
                $rrule->setFromString($_event->rrule);
            }
            
            $_event->rrule_until = $rrule->until;
        }
    }
    
}