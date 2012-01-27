<?php
/**
 * Tine 2.0
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:AirSync.php 4968 2008-10-17 09:09:33Z l.kneschke@metaways.de $
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 */
 
class Wbxml_Dtd_ActiveSync_CodePage2 extends Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 2;
    
    protected $_codePageName    = 'Email';
        
    protected $_tags = array(     
        'Attachment'            => 0x05,
        'Attachments'           => 0x06,
        'AttName'               => 0x07,
        'AttSize'               => 0x08,
        'Att0Id'                => 0x09,
        'AttMethod'             => 0x0a,
        'AttRemoved'            => 0x0b,
        'Body'                  => 0x0c,
        'BodySize'              => 0x0d,
        'BodyTruncated'         => 0x0e,
        'DateReceived'          => 0x0f,
        'DisplayName'           => 0x10,
        'DisplayTo'             => 0x11,
        'Importance'            => 0x12,
        'MessageClass'          => 0x13,
        'Subject'               => 0x14,
        'Read'                  => 0x15,
        'To'                    => 0x16,
        'Cc'                    => 0x17,
        'From'                  => 0x18,
        'ReplyTo'               => 0x19,
        'AllDayEvent'           => 0x1a,
        'Categories'            => 0x1b,
        'Category'              => 0x1c,
        'DTStamp'               => 0x1d,
        'EndTime'               => 0x1e,
        'InstanceType'          => 0x1f,
        'IntDBusyStatus'        => 0x20,
        'Location'              => 0x21,
        'MeetingRequest'        => 0x22,
        'Organizer'             => 0x23,
        'RecurrenceId'          => 0x24,
        'Reminder'              => 0x25,
        'ResponseRequested'     => 0x26,
        'Recurrences'           => 0x27,
        'Recurrence'            => 0x28,
        'Recurrence_Type'       => 0x29,
        'Recurrence_Until'      => 0x2a,
        'Recurrence_Occurrences' => 0x2b,
        'Recurrence_Interval'   => 0x2c,
        'Recurrence_DayOfWeek'  => 0x2d,
        'Recurrence_DayOfMonth' => 0x2e,
        'Recurrence_WeekOfMonth' => 0x2f,
        'Recurrence_MonthOfYear' => 0x30,
        'StartTime'             => 0x31,
        'Sensitivity'           => 0x32,
        'TimeZone'              => 0x33,
        'GlobalObjId'           => 0x34,
        'ThreadTopic'           => 0x35,
        'MIMEData'              => 0x36,
        'MIMETruncated'         => 0x37,
        'MIMESize'              => 0x38,
        'InternetCPID'          => 0x39,
        'Flag'                  => 0x3a,
        'FlagStatus'            => 0x3b,
        'ContentClass'          => 0x3c,
        'FlagType'              => 0x3d,
        'CompleteTime'          => 0x3e
    );
}