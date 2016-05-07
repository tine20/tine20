<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Johannes Nohl <lab@nohl.eu>
 * @copyright   Copyright (c) 2012-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert an EMClient 7 (beta) VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_EMClient7 extends Calendar_Convert_Event_VCalendar_Abstract
{
    // eM Client 7 (beta) user agent is "MailClient/7.0.25432.0"
    const HEADER_MATCH = '/MailClient\/(?P<version>.*)/';

}

