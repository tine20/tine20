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
class Calendar_Model_ContactAttendeeFilter extends Tinebase_Model_Filter_ForeignId 
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Calendar_Model_ContactAttendeeFilter';
    
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
     * set options 
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        if (! array_key_exists('controller', $_options)) {
            $_options['controller'] = 'Calendar_Controller_Event';
        }
        if (! array_key_exists('filtergroup', $_options)) {
            $_options['filtergroup'] = 'Calendar_Model_EventFilter';
        }
        
        parent::_setOptions($_options);
    }
    
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
        if (! is_array($this->_foreignIds)) {
            $this->_getFilterData();
            $events = $this->_getController()->search($this->_filterGroup);
            Calendar_Model_Rrule::mergeAndRemoveNonMatchingRecurrences($events, $this->_filterGroup);
            $this->_getForeignIds($events);
        }
        
        // this is supposed to run in ContactFilter context
        $contactIdFilter = new Addressbook_Model_ContactIdFilter('id', 'in', $this->_foreignIds);
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
     */
    protected function _getForeignIds($_events)
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
        
        $this->_foreignIds = array_unique($contactIds);
    }
    
    /**
     * check if record field matches filter
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
