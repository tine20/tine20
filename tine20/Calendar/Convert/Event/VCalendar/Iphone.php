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
 * class to convert a iphone vcalendar to event model and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_Iphone extends Calendar_Convert_Event_VCalendar_Abstract
{
    // DAVKit/4.0 (728.4); iCalendar/1 (42.1); iPhone/3.1.3 7E18
    const HEADER_MATCH = '/iCalendar\/1 \((?P<version>\S+)\)\; iPhone/';
    
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
        'uid',
        'attendee',
        #'alarms',
        #'tags',
        'dtstart',
        'exdate',
        'rrule',
        'recurid',
    	'is_all_day_event',
        #'rrule_until',
        'originator_tz'
    );
}
