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
class Calendar_Convert_Event_VCalendar_Thunderbird extends Calendar_Convert_Event_VCalendar_Abstract
{
    // Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13
    const HEADER_MATCH = '/ Thunderbird\/(?P<version>.*)/';
    
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
