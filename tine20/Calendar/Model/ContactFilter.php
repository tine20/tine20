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
     */
    public function appendFilterSql($_select, $_backend)
    {
        // @todo get role and status from filter and remove all non-matching attendee
        
        $eventFilter = new Calendar_Model_EventFilter($this->_value);
        $events = Calendar_Controller_Event::getInstance()->search($eventFilter);
        Calendar_Model_Rrule::mergeAndRemoveNonMatchingRecurrences($events, $eventFilter);
        
        $contactIds = array();
        foreach ($events as $event) {
            foreach ($event->attendee as $attender) {
                if ($attender->user_type === Calendar_Model_Attender::USERTYPE_GROUPMEMBER || $attender->user_type === Calendar_Model_Attender::USERTYPE_USER) {
                    $contactIds[] = $attender->user_id;
                }
            }
        }
        $contactIds = array_unique($contactIds);
        
        // this is supposed to run in ContactFilter context
        $contactIdFilter = new Addressbook_Model_ContactIdFilter('id', 'in', $contactIds);
        $contactIdFilter->appendFilterSql($_select, $_backend);
    }
}
