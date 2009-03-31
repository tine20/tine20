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
 *  
 */
class Calendar_Backend_Sql extends Tinebase_Application_Backend_Sql_Abstract
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
            /* what */   array('exdate' => SQL_TABLE_PREFIX . 'cal_exdate'), 
            /* on   */   $this->_db->quoteIdentifier('exdate.cal_event_id') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.id'),
            /* select */ array('exdate' => 'GROUP_CONCAT(' . $this->_db->quoteIdentifier('exdate.exdate') . ')'));
        
        //$select->joinLeft(
        //    /* what */   array('attendee' => SQL_TABLE_PREFIX . 'cal_attendee'), 
        //    /* on   */   $this->_db->quoteIdentifier('attendee.cal_event_id') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.id'));
        
        $select->group(array_keys($this->_schema));
        
        return $select;
    }
    
    /**
     * saves exdates of an event
     *
     * @param Calendar_Model_Event $_event
     */
    protected function _saveExdates($_event)
    {
        $this->_db->delete(SQL_TABLE_PREFIX . 'cal_exdate', $this->_db->quoteInto($this->_db->quoteIdentifier('cal_event_id') . '= ?', $_event->getId()));
        foreach ((array)$_event->exdate as $exdate) {
            $this->_db->insert(SQL_TABLE_PREFIX . 'cal_exdate', array(
                'id'           => $_event->generateUID(),
                'cal_event_id' => $_event->getId(),
                'exdate'       => $exdate->get(Tinebase_Record_Abstract::ISO8601LONG)
            ));
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