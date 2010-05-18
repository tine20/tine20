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
        #'Complete'          => 'completed',
        #'DateCompleted'     => 'datecompleted',
        #'DueDate'           => 'duedate',
        #'UtcDueDate'        => 'due',
        #'Importance'        => 'priority',
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
        #'Rtf'               => 'rtf'
        'Cc'                => 'cc',
        'DateReceived'      => 'received',
        'From'              => 'from',
        #'Sender'            => 'sender',
        'Subject'           => 'subject',
        'To'                => 'to'
    
    );
    
    protected $_folders = array(
        'INBOX' => array(
            'folderId'      => 'INBOX',
            'parentId'      => 0,
            'displayName'   => 'Inbox',
            'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_INBOX
        ),
        #'Sent' => array(
        #    'folderId'      => 'Sent',
        #    'parentId'      => 0,
        #    'displayName'   => 'Sent',
        #    'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_SENTMAIL
        #),
        #'Drafts' => array(
        #    'folderId'      => 'Drafts',
        #    'parentId'      => 0,
        #    'displayName'   => 'Drafts',
        #    'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_DRAFTS
        #),
        #'Trash' => array(
        #    'folderId'      => 'Trash',
        #    'parentId'      => 0,
        #    'displayName'   => 'Trash',
        #    'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_DELETEDITEMS
        #),
        #'Test' => array(
        #    'folderId'      => 'Test',
        #    'parentId'      => 0,
        #    'displayName'   => 'Test',
        #    'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_MAIL_USER_CREATED
        #),
    );
    
    /**
     * filter types
     */
    const FILTER_NOTHING        = 0;
    const FILTER_1_DAY_BACK     = 1;
    const FILTER_3_DAYS_BACK    = 2;
    const FILTER_1_WEEK_BACK    = 3;
    const FILTER_2_WEEKS_BACK   = 4;
    const FILTER_1_MONTH_BACK   = 5;
    
    /**
     * available filters
     * 
     * @var array
     */
    protected $_filterArray = array(
        self::FILTER_1_DAY_BACK,
        self::FILTER_3_DAYS_BACK,
        self::FILTER_1_WEEK_BACK,
        self::FILTER_2_WEEKS_BACK,
        self::FILTER_1_MONTH_BACK,
    );
    
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
     * type of the default folder
     *
     * @var int
     */
    protected $_defaultFolderType   = ActiveSync_Command_FolderSync::FOLDERTYPE_INBOX;
    
    /**
     * type of user created folders
     *
     * @var int
     */
    protected $_folderType          = ActiveSync_Command_FolderSync::FOLDERTYPE_MAIL_USER_CREATED;
    
    /**
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty = 'emailfilter_id';
            
    /**
     * field to sort search results by
     * 
     * @var string
     */
    protected $_sortField = 'received';
    
    /**
     * get all entries changed between to dates
     *
     * @param unknown_type $_field
     * @param unknown_type $_startTimeStamp
     * @param unknown_type $_endTimeStamp
     * @return array
     */
    public function getChanged($_field, $_startTimeStamp, $_endTimeStamp = null)
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
    public function appendXML(DOMElement $_xmlNode, $_folderId, $_serverId)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " serverId " . $_serverId);
        
        $data = $this->_contentController->getCompleteMessage($_serverId, TRUE, FALSE);
                
        foreach($this->_mapping as $key => $value) {
            if(!empty($data->$value) || $data->$value == 0) {
                $nodeContent = null;
                
                switch($value) {
                    case 'received':
                        $nodeContent = $data->$value->toString('yyyy-MM-ddTHH:mm:ss') . '.000Z';
                        break;
                        
                    default:
                        $nodeContent = $data->$value;
                        break;
                }
                
                // skip empty elements
                if($nodeContent === null || $nodeContent == '') {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Value for $key is empty. Skip element.");
                    continue;
                }
                
                // create a new DOMElement ...
                $node = new DOMElement($key, null, 'uri:Email');

                // ... append it to parent node aka append it to the document ...
                $_xmlNode->appendChild($node);
                
                // ... and now add the content (DomText takes care of special chars)
                $node->appendChild(new DOMText($nodeContent));
            }
        }
        
        #$_xmlNode->appendChild(new DOMElement('Body', 'Körper', 'uri:Email'));
        $_xmlNode->appendChild(new DOMElement('MessageClass', 'IPM.Note', 'uri:Email'));
        
        return;
        
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
    protected function _getServerEntries($_folderId, $_filterType)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " get server entries for folder " . $_folderId);
        
        $filter = new $this->_contentFilterClass();
        
        $this->_getContentFilter($filter, $_filterType);
        $this->_getContainerFilter($filter, $_folderId);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filter " . print_r($filter->toArray(), true));
        
        $messages = $this->_contentController->search($filter, null, false, true);
        
    	return $messages;    	
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
                #$this->_messageController->addFlags(1, $_collectionId, $_id, array(Zend_Mail_Storage::FLAG_SEEN));
            } else {
                #$this->_messageController->clearFlags(1, $_collectionId, $_id, array(Zend_Mail_Storage::FLAG_SEEN));
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
    protected function _toTineFilterArray(SimpleXMLElement $_data)
    {
        $xmlData = $_data->children('Email');
        
        $filterArray = array();
        
        foreach($this->_mapping as $fieldName => $value) {
            if(isset($xmlData->$fieldName)) {
                $filterArray[] = array(
                    'field'     => $value,
                    'operator'  => 'equals',
                    'value'     => (string)$xmlData->$fieldName
                );
            }
        }
        
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filterArray, true));
        
        return $filterArray;
    }
    
    /**
     * return list of supported folders for this backend
     *
     * @return array
     */
    public function getSupportedFolders()
    {
        $folderController = Felamimail_Controller_Folder::getInstance();
        
        $filter = new Felamimail_Model_FolderFilter(array(
            array(
                'field'     => 'account_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}
            )
        ));
        
        $folders = $folderController->search($filter);

        $result = array();
        
        foreach($folders as $folder) {
            #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " folder " . print_r($folder->toArray(), true));
            if(empty($folder['parent'])) {
                $result[$folder['id']] = array(
                    'folderId'      => $folder['id'],
                    'parentId'      => 0,
                    'displayName'   => $folder['localname'],
                    'type'          => $this->_getFolderType($folder['localname'])
                );
            }
        }
        
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " folder result " . print_r($result, true));
        
        return $result;
    }
    
    /**
     * set activesync foldertype
     * 
     * @param string $_folderName
     */
    protected function _getFolderType($_folderName)
    {
        if(strtoupper($_folderName) == 'INBOX') {
            return ActiveSync_Command_FolderSync::FOLDERTYPE_INBOX;
        } else {
            return ActiveSync_Command_FolderSync::FOLDERTYPE_MAIL_USER_CREATED;
        }
    }
    
    /**
     * get folder identified by $_folderId
     *
     * @param string $_folderId
     * @return string
     */
    public function getFolder($_folderId)
    {
        $folders = $this->getSupportedFolders();
        
        if(!array_key_exists($_folderId, $folders)) {
            throw new ActiveSync_Exception_FolderNotFound('folder not found. ' . $_folderId);
        }
        
        return $folders[$_folderId];
    }
    
    /**
     * return contentfilter object
     * 
     * @param $_filterType
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getContentFilter(Tinebase_Model_Filter_FilterGroup $_filter, $_filterType)
    {
        #$_filter->addFilter(new Tinebase_Model_Filter_Text('recurid', 'isnull', null));
        
        if(in_array($_filterType, $this->_filterArray)) {
            $today = Zend_Date::now()
                ->setHour(0)
                ->setMinute(0)
                ->setSecond(0);
                
            switch($_filterType) {
                case self::FILTER_1_DAY_BACK:
                    $received = $today->subDay(1);
                    break;
                case self::FILTER_3_DAYS_BACK:
                    $received = $today->subDay(3);
                    break;
                case self::FILTER_1_WEEK_BACK:
                    $received = $today->subWeek(1);
                    break;
                case self::FILTER_2_WEEKS_BACK:
                    $received = $today->subWeek(2);
                    break;
                case self::FILTER_1_MONTH_BACK:
                    $received = $today->subMonth(2);
                    break;
            }
            
            // add period filter
            $_filter->addFilter(new Tinebase_Model_Filter_DateTime('received', 'after', $received->get(Tinebase_Record_Abstract::ISO8601LONG)));
        }
    }
    
    protected function _getContainerFilter(Tinebase_Model_Filter_FilterGroup $_filter, $_containerId)
    {
        #$_filter->addFilter(
        $_filter->createFilter(
            'account_id', 
            'equals', 
            Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}
        );
        #);
        
        $_filter->addFilter($_filter->createFilter(
            'folder_id', 
            'equals', 
            $_containerId
        ));  

        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filter " . print_r($_filter->toArray(), true));
    }
}