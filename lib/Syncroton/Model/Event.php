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

class Syncroton_Model_Event extends Syncroton_Model_AEntry
{
    /**
     * busy status constants
     */
    const BUSY_STATUS_FREE      = 0;
    const BUSY_STATUS_TENATTIVE = 1;
    const BUSY_STATUS_BUSY      = 2;
    
    protected $_dateTimeFormat = "Ymd\THis\Z";
    
    protected $_xmlBaseElement = 'ApplicationData';
    
    protected $_properties = array(
        'AirSyncBase' => array(
            'body'                      => array('type' => 'container', 'class' => 'Syncroton_Model_EmailBody')
        ),
        'Calendar' => array(
            'allDayEvent'               => array('type' => 'number'),
            'appointmentReplyTime'      => array('type' => 'datetime'),
            'attendees'                 => array('type' => 'container', 'childElement' => 'attendee', 'class' => 'Syncroton_Model_EventAttendee'),
            'busyStatus'                => array('type' => 'number'),
            'categories'                => array('type' => 'container', 'childElement' => 'category'),
            'disallowNewTimeProposal'   => array('type' => 'number'),
            'dtStamp'                   => array('type' => 'datetime'),
            'endTime'                   => array('type' => 'datetime'),
            'exceptions'                => array('type' => 'container', 'childElement' => 'exception', 'class' => 'Syncroton_Model_EventException'),
            'location'                  => array('type' => 'string'),
            'meetingStatus'             => array('type' => 'number'),
            'onlineMeetingConfLink'     => array('type' => 'string'),
            'onlineMeetingExternalLink' => array('type' => 'string'),
            'organizerEmail'            => array('type' => 'string'),
            'organizerName'             => array('type' => 'string'),
            'recurrence'                => array('type' => 'container'),
            'reminder'                  => array('type' => 'number'),
            'responseRequested'         => array('type' => 'number'),
            'responseType'              => array('type' => 'number'),
            'sensitivity'               => array('type' => 'number'),
            'startTime'                 => array('type' => 'datetime'),
            'subject'                   => array('type' => 'string'),
            'timezone'                  => array('type' => 'timezone'),
            'uID'                       => array('type' => 'string'),
        )
    );
}