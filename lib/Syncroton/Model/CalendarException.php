<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync event
 *
 * @package     Model
 * @property    string  class
 * @property    string  collectionId
 * @property    bool    deletesAsMoves
 * @property    bool    getChanges
 * @property    string  syncKey
 * @property    int     windowSize
 */

class Syncroton_Model_CalendarException extends Syncroton_Model_CalendarEvent
{    
    protected $_xmlBaseElement = 'Exception';
    
    // @todo handle body
    protected $_properties = array(
        'Calendar' => array(
            'AllDayEvent'             => array('type' => 'number'),
            'AppointmentReplyTime'    => array('type' => 'datetime'),
            'Attendees'               => array('type' => 'container'),
            'BusyStatus'              => array('type' => 'number'),
            'Categories'              => array('type' => 'container'),
            'Deleted'                 => array('type' => 'number'),
            'DtStamp'                 => array('type' => 'datetime'),
            'EndTime'                 => array('type' => 'datetime'),
            'ExceptionStartTime'      => array('type' => 'datetime'),
            'Location'                => array('type' => 'string'),
            'MeetingStatus'           => array('type' => 'number'),
            'Reminder'                => array('type' => 'number'),
            'ResponseType'            => array('type' => 'number'),
            'Sensitivity'             => array('type' => 'number'),
            'StartTime'               => array('type' => 'datetime'),
            'Subject'                 => array('type' => 'string'),
        )
    );    
}