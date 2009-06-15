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
        $defaultCalendarArray = Tinebase_Container::getContainerById($defaultCalendarId)->toArray();
        $defaultCalendarArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultCalendarId)->toArray();
        
        return array(
            'defaultCalendar' => $defaultCalendarArray
        );
    }
}