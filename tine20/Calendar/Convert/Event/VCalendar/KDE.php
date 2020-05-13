<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a KDE VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 *
 * NOTE: write access for KDE clients has been removed because sometimes it changes/deletes events at random
 */
class Calendar_Convert_Event_VCalendar_KDE extends Calendar_Convert_Event_VCalendar_Abstract
{
    // Mozilla/5.0 (X11; Linux i686) KHTML/4.7.3 (like Gecko) Konqueror/4.7
    // Mozilla/5.0 (X11; Linux i686) AppleWebKit/534.34 (KHTML, like Gecko) akonadi_davgroupware_resource_59/4.13.0 Safari/534.34
    const HEADER_MATCH = '/(?J)((Konqueror\/(?P<version>.*))|(akonadi_davgroupware_resource_.*\/(?P<version>\S+)))/';
        
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
}
