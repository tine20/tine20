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
class Calendar_Model_ContactFilter extends Tinebase_Model_Filter_Abstract 
{
    /**
     * filter fields for role and status
     * 
     * @var string
     */
    const STATUS_FIELD = 'attender_status';
    const ROLE_FIELD   = 'attender_role';
    
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
        // get role and status from filter to remove all non-matching attendee
        $roleFilter = NULL;
        $statusFilter = NULL;
        foreach ($this->_value as $filterData) {
            if ($filterData['field'] === self::STATUS_FIELD) {
                $statusFilter = $filterData;
            } else if ($filterData['field'] === self::ROLE_FIELD) {
                $roleFilter = $filterData;
            }
        }
        
        $eventFilter = new Calendar_Model_EventFilter($this->_value);
        $events = Calendar_Controller_Event::getInstance()->search($eventFilter);
        Calendar_Model_Rrule::mergeAndRemoveNonMatchingRecurrences($events, $eventFilter);
        
        $contactIds = array();
        foreach ($events as $event) {
            foreach ($event->attendee as $attender) {
                if (   $attender->user_type === Calendar_Model_Attender::USERTYPE_GROUPMEMBER 
                    || $attender->user_type === Calendar_Model_Attender::USERTYPE_USER
                ) {
                    if ($this->_matchFilter($attender, $roleFilter) && $this->_matchFilter($attender, $statusFilter)) {
                        $contactIds[] = $attender->user_id;
                    }
                }
            }
        }
        $contactIds = array_unique($contactIds);
        
        // this is supposed to run in ContactFilter context
        $contactIdFilter = new Addressbook_Model_ContactIdFilter('id', 'in', $contactIds);
        $contactIdFilter->appendFilterSql($_select, $_backend);
    }
    
    /**
     * check if attender matches role/status filter
     * 
     * @param Calendar_Model_Attender $_attender
     * @param array $_filterData
     * @return boolean
     */
    protected function _matchFilter(Calendar_Model_Attender $_attender, $_filterData = NULL)
    {
        if ($_filterData === NULL) {
            return TRUE;
        }
        
        $attenderField = ($_filterData['field'] === self::STATUS_FIELD) ? 'status' : 'role';
        
        switch ($_filterData['operator']) {
            case 'equals':
                $result = ($_attender->{$attenderField} === $_filterData['value']);
                break;
            case 'not':
                $result = ($_attender->{$attenderField} !== $_filterData['value']);
                break;
            case 'in':
                $result = in_array($_attender->{$attenderField}, $_filterData['value']);
                break;
            case 'notin':
                $result = ! in_array($_attender->{$attenderField}, $_filterData['value']);
                break;
            default:
                $result = FALSE;
        }
        
        return $result;
    }
}
