<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * json interface for calendar
 * @package     Calendar
 */
class Calendar_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    // todos :
    //
    // add fn deleteRecurSeries($_uid) cause we don't nessesaryly have the id of the series in the client 
    //
    // ensure rrule_until has dtstart timepart (fix for ext datepicker)
    //
    // add handling to compute recurset
    //      $candidates = $events->filter('rrule', "/^FREQ.*/", TRUE)
    //      foreach ($candidates as $candidate) {
    //          $exceptions = $events->filter('recurid', "/^{$candidate->uid}-.*/", TRUE);
    //          $recurSet = Calendar_Model_Rrule::computeRecuranceSet($canditae, $exceptions, $from, $until);
    //          $events->merge($recurSet);
    //      }
    //
    // transform whole day events into 00:00:00 to 23:59:59 (also ensure this in AS Frontend!)
    
    protected $_applicationName = 'Calendar';
    
    /**
     * Return a single event
     *
     * @param   string $id
     * @return  array record data
     */
    public function getEvent($id)
    {
        return $this->_get($id, Calendar_Controller_Event::getInstance());
    }
    
    /**
     * Search for events matching given arguments
     *
     * @param string $_filter json encoded
     * @param string $_paging json encoded
     * @return array
     */
    public function searchEvents($filter, $paging)
    {
        return $this->_search($filter, $paging, Calendar_Controller_Event::getInstance(), 'Calendar_Model_EventFilter');
    }
    
    /**
     * creates/updates a event
     *
     * @param   $recordData
     * @return  array created/updated event
     */
    public function saveEvent($recordData)
    {
        return $this->_save($recordData, Calendar_Controller_Event::getInstance(), 'Event');
    }
    
    /**
     * deletes existing events
     *
     * @param array $_ids 
     * @return string
     */
    public function deleteEvents($ids)
    {
        return $this->_delete($ids, Calendar_Controller_Event::getInstance());
    }
    
    /**
     * Returns registry data of the calendar.
     *
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $defaultCalendarId = Tinebase_Core::getPreference('Calendar')->getValue(Calendar_Preference::DEFAULTCALENDAR);
        $defaultCalendarArray = Tinebase_Container::getInstance()->getContainerById($defaultCalendarId)->toArray();
        $defaultCalendarArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultCalendarId)->toArray();
        
        return array(
            'defaultCalendar' => $defaultCalendarArray
        );
    }
    
    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        $this->_resolveAttendee($_record->attendee);
        return parent::_recordToJson($_record);
    }
    
    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Abstract
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records)
    {
        Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($_records);
        Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($_records);
        $this->_resolveAttendee($_records->attendee);
        
        return parent::_multipleRecordsToJson($_records);
    }
    
    /**
     * 
     *
     * @param array|Tinebase_Record_RecordSet $_attendee 
     * @param unknown_type $_idProperty
     * @param unknown_type $_typeProperty
     */
    protected function _resolveAttendee($_eventAttendee, $_idProperty='user_id', $_typeProperty='user_type') {
        $eventAttendee = $_eventAttendee instanceof Tinebase_Record_RecordSet ? array($_eventAttendee) : $_eventAttendee;
        
        // build type map 
        $typeMap = array();
        
        foreach ($eventAttendee as $attendee) {
            foreach ($attendee as $attender) {
                $type = $attender->$_typeProperty;
                if (! array_key_exists($type, $typeMap)) {
                    $typeMap[$type] = array();
                }
                $typeMap[$type][] = $attender->$_idProperty;
            }
        }
        
        // get all entries
        foreach ($typeMap as $type => $ids) {
            switch ($type) {
                case 'user':
                    //Tinebase_Core::getLogger()->debug(print_r(array_unique($ids), true));
                    $typeMap[$type] = Tinebase_User::getInstance()->getMultiple(array_unique($ids));
                    break;
                case 'group':
                //    Tinebase_Group::getInstance()->getM
                default:
                    throw new Exception("type $type not yet supported");
                    break;
            }
        }
        
        // sort entreis in
        foreach ($eventAttendee as $attendee) {
            foreach ($attendee as $attender) {
                $attendeeTypeSet = $typeMap[$attender->$_typeProperty];
                $attender->$_idProperty = $attendeeTypeSet[$attendeeTypeSet->getIndexById($attender->$_idProperty)];
            }
        }
    }
}