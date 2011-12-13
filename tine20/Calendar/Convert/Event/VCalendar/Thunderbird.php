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
    // Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.24) Gecko/20111103 Lightning/1.0b2 Thunderbird/3.1.16
    // Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.16) Gecko/20111110 Iceowl/1.0b1 Icedove/3.0.11
    const HEADER_MATCH = '/ (Lightning|Iceowl)\/(?P<version>\S+)/';
    
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
