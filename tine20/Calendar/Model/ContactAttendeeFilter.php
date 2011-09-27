<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filters for contacts that are event attendee
 * 
 * @package     Calendar
 * @subpackage  Model
 */
class Calendar_Model_ContactAttendeeFilter extends Tinebase_Model_Filter_Abstract 
{
    /**
     * filter fields for role and status
     * 
     * @var array
     */
    protected $_filterFields = array('attender_status', 'attender_role');
    
    /**
     * filter data
     * 
     * @var array
     */
    protected $_filterData = array();
    
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'definedBy',
    );

    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * 
     * @todo allow multiple role/status filters?
     */
    public function appendFilterSql($_select, $_backend)
    {
        $this->_getFilterData();
        
        $eventFilter = new Calendar_Model_EventFilter($this->_value);
        $events = Calendar_Controller_Event::getInstance()->search($eventFilter);
        Calendar_Model_Rrule::mergeAndRemoveNonMatchingRecurrences($events, $eventFilter);
        
        $contactIds = $this->_getContactIds($events);
        
        // this is supposed to run in ContactFilter context
        $contactIdFilter = new Addressbook_Model_ContactIdFilter('id', 'in', $contactIds);
        $contactIdFilter->appendFilterSql($_select, $_backend);
    }
    
    /**
     * get filter data from value
     */
    protected function _getFilterData()
    {
        foreach ($this->_value as $filterData) {
            if (in_array($filterData['field'], $this->_filterFields)) {
                $this->_filterData[$filterData['field']] = $filterData;
            }
        }
    }
    
    /**
     * extract contact ids
     * 
     * @param Tinebase_Record_RecordSet $_events
     * @return array
     */
    protected function _getContactIds($_events)
    {
        $contactIds = array();
        
        foreach ($_events as $event) {
            foreach ($event->attendee as $attender) {
                if (   $attender->user_type === Calendar_Model_Attender::USERTYPE_GROUPMEMBER 
                    || $attender->user_type === Calendar_Model_Attender::USERTYPE_USER
                ) {
                    if ($this->_matchFilter($attender, 'attender_role', 'role') && $this->_matchFilter($attender, 'attender_status', 'status')) {
                        $contactIds[] = $attender->user_id;
                    }
                }
            }
        }
        
        return array_unique($contactIds);
    }
    
    /**
     * check if attender matches role/status filter
     * 
     * @param Calendar_Model_Attender $_attender
     * @param string $_filterField
     * @param string $_recordField
     * @return boolean
     */
    protected function _matchFilter($_record, $_filterField, $_recordField)
    {
        if (! array_key_exists($_filterField, $this->_filterData)) {
            return TRUE;
        }
        
        switch ($this->_filterData[$_filterField]['operator']) {
            case 'equals':
                $result = ($_record->{$_recordField} === $this->_filterData[$_filterField]['value']);
                break;
            case 'not':
                $result = ($_record->{$_recordField} !== $this->_filterData[$_filterField]['value']);
                break;
            case 'in':
                $result = in_array($_record->{$_recordField}, $this->_filterData[$_filterField]['value']);
                break;
            case 'notin':
                $result = ! in_array($_record->{$_recordField}, $this->_filterData[$_filterField]['value']);
                break;
            default:
                $result = FALSE;
        }
        
        return $result;
    }
}
