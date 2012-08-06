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
            'Attachments'             => array('type' => 'container'),
            'ContentType'             => array('type' => 'string'),
            'Body'                    => array('type' => 'container'),
            'NativeBodyType'          => array('type' => 'number'),
        ),
        'Email' => array(
            'BusyStatus'              => array('type' => 'number'),
            'Categories'              => array('type' => 'container'),
            'Category'                => array('type' => 'string'),
            'Cc'                      => array('type' => 'string'),
            'CompleteTime'            => array('type' => 'datetime'),
            'ContentClass'            => array('type' => 'string'),
            'DateReceived'            => array('type' => 'datetime'),
            'DayOfMonth'              => array('type' => 'number'),
            'DayOfWeek'               => array('type' => 'number'),
            'DisallowNewTimeProposal' => array('type' => 'number'),
            'DisplayTo'               => array('type' => 'string'),
            'DTStamp'                 => array('type' => 'datetime'),
            'EndTime'                 => array('type' => 'datetime'),
            'Flag'                    => array('type' => 'container'),
            'FlagType'                => array('type' => 'string'),
            'From'                    => array('type' => 'string'),
            'GlobalObjId'             => array('type' => 'string'),
            'Importance'              => array('type' => 'number'),
            'InstanceType'            => array('type' => 'number'),
            'InternetCPID'            => array('type' => 'string'),
            'Interval'                => array('type' => 'number'),
            'Location'                => array('type' => 'string'),
            'MeetingRequest'          => array('type' => 'container'),
            'MessageClass'            => array('type' => 'string'),
            'MonthOfYear'             => array('type' => 'number'),
            'Occurrences'             => array('type' => 'number'),
            'Organizer'               => array('type' => 'string'),
            'Read'                    => array('type' => 'number'),
            'Recurrence'              => array('type' => 'container'),
            'RecurrenceId'            => array('type' => 'datetime'),
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
            'Type'                    => array('type' => 'number'),
            'Until'                   => array('type' => 'datetime'),
            'WeekOfMonth'             => array('type' => 'number'),
                
            //'AttName'               => 0x07,
            //'AttSize'               => 0x08,
            //'Att0Id'                => 0x09,
            //'AttMethod'             => 0x0a,
            //'AttRemoved'            => 0x0b,
            //'Body'                  => 0x0c,
            //'BodySize'              => 0x0d,
            //'BodyTruncated'         => 0x0e,
            //'MIMEData'                => 0x36,
            //'MIMETruncated'           => 0x37,
            //'MIMESize'                => 0x38,
        ),
        'Email2' => array(
            'AccountId'             => array('type' => 'string'),
            'CalendarType'          => array('type' => 'number'),
            'ConversationId'        => array('type' => 'byteArray'), // @todo handle this
            'ConversationIndex'     => array('type' => 'byteArray'), // @todo handle this
            'FirstDayOfWeek'        => array('type' => 'number'),
            'IsLeapMonth'           => array('type' => 'number'),
            'LastVerbExecuted'      => array('type' => 'number'),
            'LastVerbExecutionTime' => array('type' => 'datetime'),
            'MeetingMessageType'    => array('type' => 'number'),
            'ReceivedAsBcc'         => array('type' => 'number'),
            'Sender'                => array('type' => 'string'),
            'UmCallerID'            => array('type' => 'string'),
            'UmUserNotes'           => array('type' => 'string'),
        ),
    );
    
    public function appendXML(DOMElement $_domParrent)
    {
        $this->_addXMLNamespaces($_domParrent);
        
        foreach($this->_elements as $elementName => $value) {
            // skip empty values
            if($value === null || $value === '' || (is_array($value) && empty($value))) {
                continue;
            }
            
            list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);
            
            $nameSpace = 'uri:' . $nameSpace;
            
            // strip off any non printable control characters
            if (!ctype_print($value)) {
                #$value = $this->removeControlChars($value);
            }
            
            switch($elementName) {
                case 'Attachments':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    foreach($value as $attachment) {
                        $attachmentElement = $_domParrent->ownerDocument->createElementNS($nameSpace, 'Attachment');
                        $attachment->appendXML($attachmentElement);
                        
                        $element->appendChild($attachmentElement);
                    }
                    
                    $_domParrent->appendChild($element);
                    
                    break;
                    
                case 'Body':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    $value->appendXML($element);
                    
                    $_domParrent->appendChild($element);
                    break;
                    
                case 'Categories':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                
                    foreach($value as $category) {
                        $categoryElement = $_domParrent->ownerDocument->createElementNS($nameSpace, 'Category');
                        $categoryElement->appendChild($_domParrent->ownerDocument->createTextNode($category));
                
                        $element->appendChild($categoryElement);
                    }
                
                    $_domParrent->appendChild($element);
                
                    break;
                    
                case 'Recurrence':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    $value->appendXML($element);
                    
                    $_domParrent->appendChild($element);
                    
                    break;
                    
                default:
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    if ($value instanceof DateTime) {
                        $value = $value->format("Y-m-d\TH:i:s\Z");
                    }
                    $element->appendChild($_domParrent->ownerDocument->createTextNode($value));
                    
                    $_domParrent->appendChild($element);
            }
        }
        
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Model_AEntry::setFromSimpleXMLElement()
     */
    public function setFromSimpleXMLElement(SimpleXMLElement $properties)
    {
        // email sending is not handled via this class
        
        return;
    }
}