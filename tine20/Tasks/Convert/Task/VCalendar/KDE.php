<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert a kde vcalendar to event model and back again
 *
 * @package     Tasks
 * @subpackage  Convert
 */
class Tasks_Convert_Task_VCalendar_KDE extends Tasks_Convert_Task_VCalendar_Abstract
{
    // Mozilla/5.0 (X11; Linux i686) KHTML/4.7.3 (like Gecko) Konqueror/4.7
    const HEADER_MATCH = '/Konqueror\/(?P<version>.*)/';
        
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
