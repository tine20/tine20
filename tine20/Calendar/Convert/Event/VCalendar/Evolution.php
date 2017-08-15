<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christoph Elisabeth Hintermüller <christoph@out-world.com>
 * @copyright   Copyright (c) 2011-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a Evolution VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_Evolution extends Calendar_Convert_Event_VCalendar_Abstract
{
    // Evolution/3.18.5
    const HEADER_MATCH = '/Evolution\/(?P<version>.*)/';
        
    protected $_supportedFields = array(
        'seq',
        'dtend',
        'transp',
        'class',
        'description',
        #'geo',
        'location',
        #'priority',
        'summary',
        #'url',
        'alarms',
        'tags',
        'dtstart',
        'exdate',
        'rrule',
        # 'recurid',
        'is_all_day_event',
        'rrule_until',
        'originator_tz'
    );
}

