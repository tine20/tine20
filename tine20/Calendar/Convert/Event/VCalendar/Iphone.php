<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert an iOS VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_Iphone extends Calendar_Convert_Event_VCalendar_MacOSX
{
    // DAVKit/4.0 (728.4); iCalendar/1 (42.1); iPhone/3.1.3 7E18
    // DAVKit/5.0 (767); iCalendar/5.0 (79); iPhone/4.2.1 8C148
    // iOS/5.0.1 (9A405) dataaccessd/1.0
    // iOS/7.1.2 (11D257) dataaccessd/1.0
    // iOS/8.2 (12D508) dataaccessd/1.0
    const HEADER_MATCH = '/(iPhone|iOS)\/(?P<version>\S+)/';
}
