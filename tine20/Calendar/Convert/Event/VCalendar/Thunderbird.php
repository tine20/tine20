<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a Thunderbird VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_Thunderbird extends Calendar_Convert_Event_VCalendar_Abstract
{
    // Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13
    // Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.24) Gecko/20111103 Lightning/1.0b2 Thunderbird/3.1.16
    // Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.16) Gecko/20111110 Iceowl/1.0b1 Icedove/3.0.11
    // Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:78.0) Gecko/20100101 Thunderbird/78.0
    const HEADER_MATCH = '/ (Lightning|Iceowl|Thunderbird)\/(?P<version>\S+)/';
    
    protected $_supportedFields = array(
        'seq',
        'dtend',
        'transp',
        'class',
        'description',
        #'geo',
        'location',
        'priority',
        'summary',
        'url',
        'alarms',
        #'tags',
        'dtstart',
        'exdate',
        'rrule',
        'recurid',
        'is_all_day_event',
        #'rrule_until',
        'originator_tz'
    );
    
    /**
     * (non-PHPdoc)
     * @see Calendar_Convert_Event_VCalendar_Abstract::_addEventAttendee()
     */
    protected function _addEventAttendee(\Sabre\VObject\Component\VEvent $vevent, Calendar_Model_Event $event)
    {
        if (version_compare($this->_version, '1.0b2' , '>')) {
            parent::_addEventAttendee($vevent, $event);
        } else {
            // special handling for Lightning <= 1.0b2
            // attendees get screwed up, if the CN contains commas
            Calendar_Model_Attender::resolveAttendee($event->attendee, FALSE, $event);
            
            foreach($event->attendee as $eventAttendee) {
                $attendeeEmail = $eventAttendee->getEmail();
                if ($attendeeEmail) {
                    $parameters = array(
                        'CN'       => str_replace(',', null, $eventAttendee->getName()),
                        'CUTYPE'   => Calendar_Convert_Event_VCalendar_Abstract::$cutypeMap[$eventAttendee->user_type],
                        'PARTSTAT' => $eventAttendee->status,
                        'ROLE'     => "{$eventAttendee->role}-PARTICIPANT",
                        'RSVP'     => 'FALSE'
                    );
                    if (strpos($attendeeEmail, '@') !== false) {
                        $parameters['EMAIL'] = $attendeeEmail;
                    }
                    $vevent->add('ATTENDEE', (strpos($attendeeEmail, '@') !== false ? 'mailto:' : 'urn:uuid:') . $attendeeEmail, $parameters);                    
                }
            }
        }
    }
}
