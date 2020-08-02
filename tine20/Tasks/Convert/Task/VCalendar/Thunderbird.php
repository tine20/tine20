<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert a SOGO vcard to contact model and back again
 *
 * @package     Tasks
 * @subpackage  Convert
 */
class Tasks_Convert_Task_VCalendar_Thunderbird extends Tasks_Convert_Task_VCalendar_Abstract
{
    // Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13
    // Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.24) Gecko/20111103 Lightning/1.0b2 Thunderbird/3.1.16
    // Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.16) Gecko/20111110 Iceowl/1.0b1 Icedove/3.0.11
    const HEADER_MATCH = '/ (Thunderbird|Lightning|Iceowl)\/(?P<version>\S+)/';
    
    protected $_supportedFields = array(
        'seq',
        'dtstart',
        #'transp',
        'class',
        'description',
        'geo',
        'location',
        'priority',
        'summary',
        'url',
        'alarms',
        'tags',
        'status',
        'due',
        'percent',
        'completed',
        #'exdate',
        #'rrule',
        #'recurid',
        #'is_all_day_event',
        #'rrule_until',
        'originator_tz'
    );
    
    protected function _addEventAttendee(Sabre_VObject_Component $_vevent, Calendar_Model_Event $_event)
    {
        if (version_compare($this->_version, '1.0b2' , '>')) {
            parent::_addEventAttendee($_vevent, $_event);
        } else {
            // special handling for Lightning <= 1.0b2
            // attendees get screwed up, if the CN contains commas
            Calendar_Model_Attender::resolveAttendee($_event->attendee, FALSE, $_event);
            
            foreach($_event->attendee as $eventAttendee) {
                $attendeeEmail = $eventAttendee->getEmail();
                if ($attendeeEmail) {
                    $attendee = new Sabre_VObject_Property('ATTENDEE', (strpos($attendeeEmail, '@') !== false ? 'mailto:' : 'urn:uuid:') . $attendeeEmail);
                    $attendee->add('CN',       str_replace(',', null, $eventAttendee->getName()));
                    $attendee->add('CUTYPE',   Calendar_Convert_Event_VCalendar_Abstract::$cutypeMap[$eventAttendee->user_type]);
                    $attendee->add('PARTSTAT', $eventAttendee->status);
                    $attendee->add('ROLE',     "{$eventAttendee->role}-PARTICIPANT");
                    $attendee->add('RSVP',     'FALSE');
                    if (strpos($attendeeEmail, '@') !== false) {
                        $attendee->add('EMAIL',    $attendeeEmail);
                    }
    
                    $_vevent->add($attendee);
                }
            }
        }
    }
}
