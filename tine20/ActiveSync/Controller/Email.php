<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * controller email class
 *
 * @package     ActiveSync
 */
class ActiveSync_Controller_Email extends ActiveSync_Controller_Abstract 
{
    protected $_mapping = array(
        #'Body'              => 'body',
        #'BodySize'          => 'bodysize',
        #'BodyTruncated'     => 'bodytruncated',
        #'Categories'        => 'categories',
        #'Category'          => 'category',
        'Complete'          => 'completed',
        #'DateCompleted'     => 'datecompleted',
        #'DueDate'           => 'duedate',
        'UtcDueDate'        => 'due',
        'Importance'        => 'priority',
        #'Recurrence'        => 'recurrence',
        #'Type'              => 'type',
        #'Start'             => 'start',
        #'Until'             => 'until',
        #'Occurrences'       => 'occurrences',
        #'Interval'          => 'interval',
        #'DayOfWeek'         => 'dayofweek',
        #'DayOfMonth'        => 'dayofmonth',
        #'WeekOfMonth'       => 'weekofmonth',
        #'MonthOfYear'       => 'monthofyear',
        #'Regenerate'        => 'regenerate',
        #'DeadOccur'         => 'deadoccur',
        #'ReminderSet'       => 'reminderset',
        #'ReminderTime'      => 'remindertime',
        #'Sensitivity'       => 'sensitivity',
        #'StartDate'         => 'startdate',
        #'UtcStartDate'      => 'utcstartdate',
        'Subject'           => 'summary',
        #'Rtf'               => 'rtf'
    );
    
    protected $_folders = array(
        'INBOX' => array(
            'folderId'      => 'INBOX',
            'parentId'      => 0,
            'displayName'   => 'Inbox',
            'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_INBOX
        ),
        'Sent' => array(
            'folderId'      => 'Sent',
            'parentId'      => 0,
            'displayName'   => 'Sent',
            'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_SENTMAIL
        ),
        'Drafts' => array(
            'folderId'      => 'Drafts',
            'parentId'      => 0,
            'displayName'   => 'Drafts',
            'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_DRAFTS
        ),
        'Trash' => array(
            'folderId'      => 'Trash',
            'parentId'      => 0,
            'displayName'   => 'Trash',
            'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_DELETEDITEMS
        ),
        'Test' => array(
            'folderId'      => 'Test',
            'parentId'      => 0,
            'displayName'   => 'Test',
            'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_MAIL_USER_CREATED
        ),
    );
    
    /**
     * Enter description here...
     *
     * @var Felamimail_Backend_Imap
     */
    protected $_imapBackend;
    
    /**
     * felamimail message controller
     *
     * @var Felamimail_Controller_Message
     */
    protected $_messageController;
    
    /**
     * felamimail folder controller
     *
     * @var Felamimail_Controller_Folder
     */
    protected $_folderController;
    
    protected $_applicationName     = 'Felamimail';
    
    protected $_modelName           = 'Message';
        
    /**
     * the constructor
     *
     * @param Zend_Date $_syncTimeStamp
     */
    public function __construct(Zend_Date $_syncTimeStamp)
    {
        $this->_syncTimeStamp = $_syncTimeStamp;
        
        $this->_messageController = Felamimail_Controller_Message::getInstance();
        $this->_folderController = Felamimail_Controller_Folder::getInstance();                
    }
    
    /**
     * get estimate of add or changed entries
     *
     * @param Zend_Date $_startTimeStamp
     * @param Zend_Date $_endTimeStamp
     * @return int total count of changed items
     */
    public function getItemEstimate($_startTimeStamp = NULL, $_endTimeStamp = NULL)
    {
        return 1;
    }
    
    /**
     * get all entries changed between to dates
     *
     * @param unknown_type $_field
     * @param unknown_type $_startTimeStamp
     * @param unknown_type $_endTimeStamp
     * @return array
     */
    public function getChanged($_field, $_startTimeStamp, $_endTimeStamp)
    {
        return array();
    }
    
    /**
     *
     * @todo proper entity handling
     * 
     * $node = $_xmlDocument->createElementNS('uri:Contacts', $key);
     * $node->appendChild(new DOMText($nodeContent));
     *         
     * $_xmlNode->appendChild($node);
     *
     * (non-PHPdoc)
     * @see ActiveSync/Controller/ActiveSync_Controller_Abstract#appendXML($_xmlDocument, $_xmlNode, $_folderId, $_serverId)
     */
    public function appendXML(DOMDocument $_xmlDocument, DOMElement $_xmlNode, $_folderId, $_serverId)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " serverId " . $_serverId);
        
        $message = $this->_messageController->getMessage($_folderId, $_serverId);
        
        foreach($message->getHeaders() as $headerName => $headerValue) {
            switch($headerName) {
                case 'cc':
                    $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Email', 'CC', htmlspecialchars($headerValue, ENT_NOQUOTES, 'utf-8')));
                    break;
                    
                case 'date':
                    // strip of timezone information for example: (CEST)
                    $dateString = preg_replace('/( [+-]{1}\d{4}) \(.*\)$/', '${1}', $headerValue);
                    
                    // append dummy weekday if missing
                    if(preg_match('/^(\d{1,2})\s(\w{3})\s(\d{4})\s(\d{2}):(\d{2}):{0,1}(\d{0,2})\s([+-]{1}\d{4})$/', $dateString)) {
                        $dateString = 'xxx, ' . $dateString;
                    }
                    
                    try {
                        # Fri,  6 Mar 2009 20:00:36 +0100
                        $date = new Zend_Date($dateString, Zend_Date::RFC_2822, 'en_US');
                    } catch (Zend_Date_Exception $e) {
                        # Fri,  6 Mar 2009 20:00:36 CET
                        $date = new Zend_Date($dateString, 'EEE, d MMM YYYY hh:mm:ss zzz', 'en_US');
                        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " date header $headerValue => $dateString => $date => " . $date->get(Zend_Date::ISO_8601));
                    }
                    $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Email', 'DateReceived', $date->get(Zend_Date::ISO_8601)));
                    break;
                    
                case 'from':
                    $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Email', 'From', htmlspecialchars($headerValue, ENT_NOQUOTES, 'utf-8')));
                    break;
                    
                case 'to':
                    $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Email', 'To', htmlspecialchars($headerValue, ENT_NOQUOTES, 'utf-8')));
                    break;
                    
                case 'subject':
                    $subject = $headerValue;
                    if(preg_match('/=?[\d,\w,-]*?[q,Q,b,B]?.*?=/', $subject)) {
                        $subject = preg_replace('/(=[1-9,a-f]{2})/e', "strtoupper('\\1')", $subject);
                        $subject = iconv_mime_decode($subject, 2);
                    }
                    $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Email', 'Subject', htmlspecialchars($subject, ENT_NOQUOTES, 'utf-8')));
                    break;
                    
                case 'importance':
                    switch (strtolower($headerValue)) {
                        case 'low':
                            $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Email', 'Importance', 0));
                            break;
                    	
                        case 'high':
                            $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Email', 'Importance', 2));
                            break;
                            
                    }
                    
                    break;
                    
                default:
                    #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " header $headerName => $headerValue");
                    break;
                    # Body
                    # Flag
                    
                    # body
                    ## Type
                    ## (EstimatedDataSize)
                    ## (Truncated)
                    ## Data
            }
        }
        
        $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Email', 'Read', (int) $message->hasFlag(Zend_Mail_Storage::FLAG_SEEN)));
        
        $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Email', 'Body', htmlspecialchars($message->getBody(Zend_Mime::TYPE_TEXT), ENT_NOQUOTES, 'utf-8')));
        #$body = $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:AirSyncBase', 'Body'));
        #$body->appendChild($_xmlDocument->createElementNS('uri:AirSyncBase', 'Type', 2));
        #$body->appendChild($_xmlDocument->createElementNS('uri:AirSyncBase', 'Data', htmlspecialchars('Hallo <b>Lars</b>!', ENT_NOQUOTES, 'utf-8')));
        
        $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Email', 'MessageClass', 'IPM.Note'));
        #$_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Email', 'ContentClass', 'urn:content-classes:message'));
        
        # 1 Text
        # 2 HTML
        #$_xmlNode->appendChild($_xmlDocument->createElementNS('uri:AirSyncBase', 'NativeBodyType', 2));   
    }
        
    /**
     * delete entry
     *
     * @param string $_collectionId
     * @param string $_id
     */
    public function delete($_collectionId, $_id)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " ColectionId: $_collectionId Id: $_id");
        
        try {
            $this->_messageController->deleteMessage(1, $_collectionId, $_id);
        } catch (Zend_Mail_Storage_Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " " . $e->getMessage());
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " deleted entry id " . $_id);
    }
    
    /**
     * get id's of all contacts available on the server
     *
     * @return array
     */
    public function getServerEntries($_folderId, $_filterType)
    {
        $foundEntries = $this->_messageController->getUid($_folderId, 1, INF);
                
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found " . count($foundEntries) . ' entries');
            
        return $foundEntries;
    }
    
    /**
     * update existing entry
     *
     * @param unknown_type $_collectionId
     * @param string $_id
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function change($_collectionId, $_id, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId Id: $_id");
        
        $xmlData = $_data->children('uri:Email');
        
        if(isset($xmlData->Read)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId Id: $_id set read flag: $xmlData->Read");
            if((int)$xmlData->Read === 1) {
                $this->_messageController->addFlags(1, $_collectionId, $_id, array(Zend_Mail_Storage::FLAG_SEEN));
            } else {
                $this->_messageController->clearFlags(1, $_collectionId, $_id, array(Zend_Mail_Storage::FLAG_SEEN));
            }
        }
        
        return;
    }
    
    /**
     * convert contact from xml to Addressbook_Model_Contact
     *
     * @todo handle images
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_Contact
     */
    protected function _toTineModel(SimpleXMLElement $_data, $_entry = null)
    {
        if($_entry instanceof Tasks_Model_Task) {
            $task = $_entry;
        } else {
            $task = new Tasks_Model_Task(null, true);
        }
        
        $xmlData = $_data->children('uri:Tasks');

        foreach($this->_mapping as $fieldName => $value) {
            switch($value) {
                case 'completed':
                    if((int)$_data->$fieldName === 1) {
                        $task->status_id = 2;
                        $task->completed = (string)$_data->DateCompleted;
                    } else {
                        $task->status_id = 3; 
                        $task->completed = NULL;
                    }
                    break;
                default:
                    if(isset($xmlData->$fieldName)) {
                        $task->$value = (string)$xmlData->$fieldName;
                    } else {
                        $task->$value = null;
                    }
                    break;
            }
        }
        
        // contact should be valid now
        $task->isValid();
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " contactData " . print_r($task->toArray(), true));
        
        return $task;
    }
    
    /**
     * convert contact from xml to Addressbook_Model_ContactFilter
     *
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_ContactFilter
     */
    protected function _toTineFilter(SimpleXMLElement $_data)
    {
        $xmlData = $_data->children('Tasks');
        
        $filter = new Tasks_Model_TaskFilter(array(
            array(
                'field'     => 'containerType',
                'operator'  => 'equals',
                'value'     => 'all'
            )
        )); 
            
        foreach($this->_mapping as $fieldName => $value) {
            if($filter->has($value)) {
                $filter->$value = array(
                    'operator'  => 'equals',
                    'value'     => (string)$xmlData->$fieldName
                );
            }
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filter, true));
        
        return $filter;
    }
    
    /**
     * return list of supported folders for this backend
     *
     * @return array
     */
    public function getSupportedFolders()
    {
        return $this->_folders;
    }
    
    /**
     * get folder identified by $_folderId
     *
     * @param string $_folderId
     * @return string
     */
    public function getFolder($_folderId)
    {
        foreach($this->_folders as $folder) {
            if($folder['folderId'] == $_folderId) {
                return $folder;
            }
        }
        
        throw new ActiveSync_Exception_FolderNotFound('folder not found. ' . $_folderId);
    }
}