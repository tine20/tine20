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

class Syncroton_Model_EventException extends Syncroton_Model_AEntry
{    
    protected $_xmlBaseElement = 'Exception';
    
    protected $_dateTimeFormat = "Ymd\THis\Z";
    
    protected $_properties = array(
        'Calendar' => array(
            'allDayEvent'             => array('type' => 'number'),
            'appointmentReplyTime'    => array('type' => 'datetime'),
            'attendees'               => array('type' => 'container', 'childElement' => 'attendee', 'class' => 'Syncroton_Model_EventAttendee'),
            'busyStatus'              => array('type' => 'number'),
            'categories'              => array('type' => 'container', 'childElement' => 'category'),
            'deleted'                 => array('type' => 'number'),
            'dtStamp'                 => array('type' => 'datetime'),
            'endTime'                 => array('type' => 'datetime'),
            'exceptionStartTime'      => array('type' => 'datetime'),
            'location'                => array('type' => 'string'),
            'meetingStatus'           => array('type' => 'number'),
            'reminder'                => array('type' => 'number'),
            'responseType'            => array('type' => 'number'),
            'sensitivity'             => array('type' => 'number'),
            'startTime'               => array('type' => 'datetime'),
            'subject'                 => array('type' => 'string'),
        )
    );    
}