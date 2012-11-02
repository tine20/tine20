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
 * class to handle ActiveSync email
 *
 * @package     Model
 * @property    array     attachments
 * @property    string    contentType
 * @property    array     flag
 * @property    Syncroton_Model_EmailBody    body
 * @property    array     cc
 * @property    array     to
 * @property    int       lastVerbExecuted
 * @property    DateTime  lastVerbExecutionTime
 * @property    int       read
 */
class Syncroton_Model_Email extends Syncroton_Model_AEntry
{
    const LASTVERB_UNKNOWN       = 0;
    const LASTVERB_REPLYTOSENDER = 1;
    const LASTVERB_REPLYTOALL    = 2;
    const LASTVERB_FORWARD       = 3;
    
    protected $_xmlBaseElement = 'ApplicationData';
    
    protected $_properties = array(
        'AirSyncBase' => array(
            'attachments'             => array('type' => 'container', 'childElement' => 'attachment', 'class' => 'Syncroton_Model_EmailAttachment'),
            'contentType'             => array('type' => 'string'),
            'body'                    => array('type' => 'container', 'class' => 'Syncroton_Model_EmailBody'),
            'nativeBodyType'          => array('type' => 'number'),
        ),
        'Email' => array(
            'busyStatus'              => array('type' => 'number'),
            'categories'              => array('type' => 'container', 'childElement' => 'category', 'supportedSince' => '14.0'),
            'cc'                      => array('type' => 'string'),
            'completeTime'            => array('type' => 'datetime'),
            'contentClass'            => array('type' => 'string'),
            'dateReceived'            => array('type' => 'datetime'),
            'disallowNewTimeProposal' => array('type' => 'number'),
            'displayTo'               => array('type' => 'string'),
            'dTStamp'                 => array('type' => 'datetime'),
            'endTime'                 => array('type' => 'datetime'),
            'flag'                    => array('type' => 'container', 'class' => 'Syncroton_Model_EmailFlag'),
            'from'                    => array('type' => 'string'),
            'globalObjId'             => array('type' => 'string'),
            'importance'              => array('type' => 'number'),
            'instanceType'            => array('type' => 'number'),
            'internetCPID'            => array('type' => 'string'),
            'location'                => array('type' => 'string'),
            'meetingRequest'          => array('type' => 'container', 'class' => 'Syncroton_Model_EmailMeetingRequest'),
            'messageClass'            => array('type' => 'string'),
            'organizer'               => array('type' => 'string'),
            'read'                    => array('type' => 'number'),
            'recurrences'             => array('type' => 'container'),
            'reminder'                => array('type' => 'number'),
            'replyTo'                 => array('type' => 'string'),
            'responseRequested'       => array('type' => 'number'),
            'sensitivity'             => array('type' => 'number'),
            'startTime'               => array('type' => 'datetime'),
            'status'                  => array('type' => 'number'),
            'subject'                 => array('type' => 'string'),
            'threadTopic'             => array('type' => 'string'),
            'timeZone'                => array('type' => 'timezone'),
            'to'                      => array('type' => 'string'),
        ),
        'Email2' => array(
            'accountId'             => array('type' => 'string', 'supportedSince' => '14.1'),
            'conversationId'        => array('type' => 'byteArray', 'supportedSince' => '14.0'),
            'conversationIndex'     => array('type' => 'byteArray', 'supportedSince' => '14.0'),
            'lastVerbExecuted'      => array('type' => 'number', 'supportedSince' => '14.0'),
            'lastVerbExecutionTime' => array('type' => 'datetime', 'supportedSince' => '14.0'),
            'meetingMessageType'    => array('type' => 'number', 'supportedSince' => '14.1'),
            'receivedAsBcc'         => array('type' => 'number', 'supportedSince' => '14.0'),
            'sender'                => array('type' => 'string', 'supportedSince' => '14.0'),
            'umCallerID'            => array('type' => 'string', 'supportedSince' => '14.0'),
            'umUserNotes'           => array('type' => 'string', 'supportedSince' => '14.0'),
        ),
    );
}
