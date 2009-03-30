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
        
        return parent::create($_record);
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
        
        return parent::create($_record);
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
     */
    public function searchDirectEvents(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)
    {
        Calendar_Model_PeriodFilter::setType(Calendar_Model_PeriodFilter::TYPE_DIRECT);
        return parent::search($_filter, $_pagination, $_onlyIds);
    }
    
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
     */
    public function searchRecurBaseEvents(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)
    {
        Calendar_Model_PeriodFilter::setType(Calendar_Model_PeriodFilter::TYPE_RECURBASE);
        return parent::search($_filter, $_pagination, $_onlyIds);
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