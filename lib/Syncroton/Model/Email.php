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
            'Attachments'             => array('type' => 'container', 'childName' => 'Attachment'),
            'ContentType'             => array('type' => 'string'),
            'Body'                    => array('type' => 'container'),
            'NativeBodyType'          => array('type' => 'number'),
        ),
        'Email' => array(
            'BusyStatus'              => array('type' => 'number'),
            'Categories'              => array('type' => 'container', 'childName' => 'Category'),
            'Cc'                      => array('type' => 'string'),
            'CompleteTime'            => array('type' => 'datetime'),
            'ContentClass'            => array('type' => 'string'),
            'DateReceived'            => array('type' => 'datetime'),
            #'DayOfMonth'              => array('type' => 'number'),
            #'DayOfWeek'               => array('type' => 'number'),
            'DisallowNewTimeProposal' => array('type' => 'number'),
            'DisplayTo'               => array('type' => 'string'),
            'DTStamp'                 => array('type' => 'datetime'),
            'EndTime'                 => array('type' => 'datetime'),
            'Flag'                    => array('type' => 'container'),
            'From'                    => array('type' => 'string'),
            'GlobalObjId'             => array('type' => 'string'),
            'Importance'              => array('type' => 'number'),
            'InstanceType'            => array('type' => 'number'),
            'InternetCPID'            => array('type' => 'string'),
            #'Interval'                => array('type' => 'number'),
            'Location'                => array('type' => 'string'),
            'MeetingRequest'          => array('type' => 'container'),
            'MessageClass'            => array('type' => 'string'),
            #'MonthOfYear'             => array('type' => 'number'),
            #'Occurrences'             => array('type' => 'number'),
            'Organizer'               => array('type' => 'string'),
            'Read'                    => array('type' => 'number'),
            #'Recurrence'              => array('type' => 'container'),
            #'RecurrenceId'            => array('type' => 'datetime'),
            'Recurrences'             => array('type' => 'container'),
            'Reminder'                => array('type' => 'number'),
            'ReplyTo'                 => array('type' => 'string'),
            'ResponseRequested'       => array('type' => 'number'),
            'Sensitivity'             => array('type' => 'number'),
            'StartTime'               => array('type' => 'datetime'),
            'Status'                  => array('type' => 'number'),
            'Subject'                 => array('type' => 'string'),
            'ThreadTopic'             => array('type' => 'string'),
            'TimeZone'                => array('type' => 'timezone'),
            'To'                      => array('type' => 'string'),
            #'Type'                    => array('type' => 'number'),
            #'Until'                   => array('type' => 'datetime'),
            #'WeekOfMonth'             => array('type' => 'number'),
        ),
        'Email2' => array(
            'AccountId'             => array('type' => 'string'),
            #'CalendarType'          => array('type' => 'number'),
            'ConversationId'        => array('type' => 'byteArray'), // @todo handle this
            'ConversationIndex'     => array('type' => 'byteArray'), // @todo handle this
            #'FirstDayOfWeek'        => array('type' => 'number'),
            #'IsLeapMonth'           => array('type' => 'number'),
            'LastVerbExecuted'      => array('type' => 'number'),
            'LastVerbExecutionTime' => array('type' => 'datetime'),
            'MeetingMessageType'    => array('type' => 'number'),
            'ReceivedAsBcc'         => array('type' => 'number'),
            'Sender'                => array('type' => 'string'),
            'UmCallerID'            => array('type' => 'string'),
            'UmUserNotes'           => array('type' => 'string'),
        ),
    );
    
    protected function _parseAirSyncBaseNamespace(SimpleXMLElement $properties)
    {
        // fetch data from AirSyncBase namespace
        $children = $properties->children('uri:AirSyncBase');
    
        foreach ($children as $elementName => $xmlElement) {
    
            switch ($elementName) {
                case 'Body':
                    $this->$elementName = new Syncroton_Model_EmailBody($xmlElement);
    
                    break;
    
                default:
                    list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);
    
                    switch ($properties['type']) {
                        case 'datetime':
                            $this->$elementName = new DateTime((string) $xmlElement, new DateTimeZone('UTC'));
    
                            break;
    
                        case 'number':
                            $this->$elementName = (int) $xmlElement;
    
                            break;
                        default:
                            $this->$elementName = (string) $xmlElement;
    
                            break;
                    }
            }
        }
    }
    
    protected function _parseEmailNamespace(SimpleXMLElement $properties)
    {
        // fetch data from AirSyncBase namespace
        $children = $properties->children('uri:Email');
    
        foreach ($children as $elementName => $xmlElement) {
    
            switch ($elementName) {
                case 'Body':
                    $this->$elementName = new Syncroton_Model_EmailBody($xmlElement);
    
                    break;

                case 'Flag':
                    $this->$elementName = new Syncroton_Model_EmailFlag($xmlElement);
                    break;

                default:
                    list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);
    
                    switch ($properties['type']) {
                        case 'datetime':
                            $this->$elementName = new DateTime((string) $xmlElement, new DateTimeZone('UTC'));
    
                            break;
    
                        case 'number':
                            $this->$elementName = (int) $xmlElement;
    
                            break;
                        default:
                            $this->$elementName = (string) $xmlElement;
    
                            break;
                    }
            }
        }
    }
    
    protected function _parseEmail2Namespace(SimpleXMLElement $properties)
    {
        // fetch data from AirSyncBase namespace
        $children = $properties->children('uri:Email2');
    
        foreach ($children as $elementName => $xmlElement) {
    
            switch ($elementName) {
                case 'Body':
                    $this->$elementName = new Syncroton_Model_EmailBody($xmlElement);
    
                    break;
    
                default:
                    list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);
    
                    switch ($properties['type']) {
                        case 'datetime':
                            $this->$elementName = new DateTime((string) $xmlElement, new DateTimeZone('UTC'));
    
                            break;
    
                        case 'number':
                            $this->$elementName = (int) $xmlElement;
    
                            break;
                        default:
                            $this->$elementName = (string) $xmlElement;
    
                            break;
                    }
            }
        }
    }
}