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
 * @property    array    Attachments
 * @property    string   ContentType
 * @property    Syncroton_Model_EmailFlag  Flag
 * @property    Syncroton_Model_EmailBody  Body
 * @property    array    Cc
 * @property    array    To
 * @property    int      Read
 */
class Syncroton_Model_Email extends Syncroton_Model_AEntry
{
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
            'categories'              => array('type' => 'container', 'childElement' => 'category'),
            'cc'                      => array('type' => 'string'),
            'completeTime'            => array('type' => 'datetime'),
            'contentClass'            => array('type' => 'string'),
            'dateReceived'            => array('type' => 'datetime'),
            'disallowNewTimeProposal' => array('type' => 'number'),
            'displayTo'               => array('type' => 'string'),
            'dTStamp'                 => array('type' => 'datetime'),
            'endTime'                 => array('type' => 'datetime'),
            'flag'                    => array('type' => 'container'),
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
            'accountId'             => array('type' => 'string'),
            'conversationId'        => array('type' => 'byteArray'), // @todo handle this
            'conversationIndex'     => array('type' => 'byteArray'), // @todo handle this
            'lastVerbExecuted'      => array('type' => 'number'),
            'lastVerbExecutionTime' => array('type' => 'datetime'),
            'meetingMessageType'    => array('type' => 'number'),
            'receivedAsBcc'         => array('type' => 'number'),
            'sender'                => array('type' => 'string'),
            'umCallerID'            => array('type' => 'string'),
            'umUserNotes'           => array('type' => 'string'),
        ),
    );
}
