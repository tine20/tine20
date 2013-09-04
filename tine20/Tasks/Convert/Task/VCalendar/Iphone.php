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
 * class to convert a iphone vcalendar to event model and back again
 *
 * @package     Tasks
 * @subpackage  Convert
 */
class Tasks_Convert_Task_VCalendar_Iphone extends Tasks_Convert_Task_VCalendar_Abstract
{
    // DAVKit/4.0 (728.4); iCalendar/1 (42.1); iPhone/3.1.3 7E18
    // DAVKit/5.0 (767); iCalendar/5.0 (79); iPhone/4.2.1 8C148
    // iOS/5.0.1 (9A405) dataaccessd/1.0
    const HEADER_MATCH = '/(iPhone|iOS)\/(?P<version>\S+)/';
    
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
}
