<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ingo Ratsdorf <ingo@envirology.co.nz>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

 /*
BEGIN:VEVENT
CATEGORIES:Blue Category
CLASS:PUBLIC
DESCRIPTION:  \n
DTEND;TZID=New Zealand Standard Time:20160208T163000
DTSTAMP:20160207T214052Z
DTSTART;TZID=New Zealand Standard Time:20160208T160000
LOCATION:testlocation
ORGANIZER;CN=Ingo Ratsdorf;SCHEDULE-AGENT=CLIENT:MAILTO:ingo@envirology.co.
 nz
PRIORITY:5
SEQUENCE:3
SUMMARY:test
TRANSP:OPAQUE
UID:96b7244d-f185-43c7-a0df-be6b76117421
X-SOGO-SEND-APPOINTMENT-NOTIFICATIONS:NO
BEGIN:VALARM
ACTION:DISPLAY
DESCRIPTION:This is an event reminder
TRIGGER:-PT15M
END:VALARM
END:VEVENT
*/

/**
 * class to convert a CalDavSynchronizer VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 * TODO: Delete old alarms that have been deleted within Outlook (acknowledged)
 */
class Calendar_Convert_Event_VCalendar_CalDAVSynchronizer extends Calendar_Convert_Event_VCalendar_Abstract
{
    // "CalDavSynchronizer/1.15"
    const HEADER_MATCH = '/CalDavSynchronizer\/(?P<version>\S+)/';
    
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
        'tags',
        'dtstart',
        'exdate',
        'rrule',
        'recurid',
        'is_all_day_event',
        'rrule_until',
        'originator_tz'
    );

}
