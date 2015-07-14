<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <kontakt@michaelspahn.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a DAVdroid to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_DavDroid extends Calendar_Convert_Event_VCalendar_Abstract
{
    // DAVdroid/0.7.3
    const HEADER_MATCH = '/DAVdroid\/(?P<version>.*)/';
    
    protected $_supportedFields = array(
        'seq',
        'dtend',
        'transp',
        'class',
        'description',
        'location',
        'priority',
        'summary',
        'url',
        'alarms',
        'dtstart',
        'exdate',
        'rrule',
        'recurid',
        'is_all_day_event',
        'originator_tz'
    );
}
