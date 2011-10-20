<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert a SOGO vcard to contact model and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_MacOSX extends Calendar_Convert_Event_VCalendar_Abstract
{
    // alendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)
    const HEADER_MATCH = '/^CalendarStore.*Mac OS X\/(?P<version>.*) /';
    
    protected $_supportedFields = array(
        'seq',
        'dtend',
        'transp',
        'class',
        'description',
        #'geo',
        'location',
        'organizer',
        'priority',
        'summary',
        'url',
        #'uid',
        'attendee',
        #'alarms',
        #'tags',
        'dtstart',
        #'exdate',
        #'rrule',
        'is_all_day_event',
        #'rrule_until',
        #'originator_tz'
    );
}
