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
        'Cc'                => 'cc',
        'DateReceived'      => 'received',
        'From'              => 'from',
        #'Sender'            => 'sender',
        'Subject'           => 'subject',
        'To'                => 'to'
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
     * @var Felamimail_Controller_Message
     */
    protected $_contentController;
    
    /**
     * get all entries changed between to dates
     *
     * @param unknown_type $_field
     * @param unknown_type $_startTimeStamp
     * @param unknown_type $_endTimeStamp
     * @return array
     */
    public function getChanged($_folderId, $_startTimeStamp, $_endTimeStamp = NULL)
    {
        $filter = new $this->_contentFilterClass();
        
        $this->_getContentFilter($filter, 0);
        $this->_getContainerFilter($filter, $_folderId);

        $startTimeStamp = ($_startTimeStamp instanceof Zend_Date) ? $_startTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        $endTimeStamp = ($_endTimeStamp instanceof Zend_Date) ? $_endTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
        
        $filter->addFilter(new Tinebase_Model_Filter_DateTime(
            'timestamp',
            'after',
            $startTimeStamp
        ));
        
        if($endTimeStamp !== NULL) {
            $filter->addFilter(new Tinebase_Model_Filter_DateTime(
                'timestamp',
                'before',
                $endTimeStamp
            ));
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filter " . print_r($filter->toArray(), true));
        
        $result = $this->_contentController->search($filter, NULL, false, true, 'sync');
        
        return $result;
    }
    
    /**
     * append email data to xml element
     *
     * @param DOMElement  $_xmlNode   the parrent xml node
     * @param string      $_folderId  the local folder id
     * @param string      $_serverId  the local entry id
     * @param boolean     $_withBody  retrieve body of entry
     */
    public function appendXML(DOMElement $_xmlNode, $_folderId, $_serverId, array $_options = array(), $_neverTruncate = false)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " append email " . $_serverId/* . ' options ' . print_r($_options, true)*/);
        
        $data = $this->_contentController->get($_serverId);
                        
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
                    //Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Value for $key is empty. Skip element.");
                    continue;
                }
                
                // ... append it to parent node aka append it to the document ...
                $node = $_xmlNode->appendChild(new DOMElement($key, null, 'uri:Email'));
                
                // ... and now add the content (DomText takes care of special chars)
                $node->appendChild(new DOMText($nodeContent));
            }
        }
        
        // read flag
        if (in_array('\Seen', $data->flags)) {
            $_xmlNode->appendChild(new DOMElement('Read', 1, 'uri:Email'));                 
        } else {
            $_xmlNode->appendChild(new DOMElement('Read', 0, 'uri:Email'));
        }
        
        // get truncation
        $truncateAt = null;
        
        if ($_options['mimeSupport'] == 2 && (version_compare($this->_device->acsversion, '12.0', '<=') || isset($_options['bodyPreferences'][4]))) {
            if ($_neverTruncate === false && isset($_options['bodyPreferences'][4]) && isset($_options['bodyPreferences'][4]['truncationSize'])) {
                $truncateAt = $_options['bodyPreferences'][4]['truncationSize'];
            }
            $airSyncBaseType = 4;
        } elseif (isset($_options['bodyPreferences'][2])) {
            if ($_neverTruncate === false && isset($_options['bodyPreferences'][2]['truncationSize'])) {
                $truncateAt = $_options['bodyPreferences'][2]['truncationSize'];
            }
            $airSyncBaseType = 2;
        } else {
            if ($_neverTruncate === false && isset($_options['bodyPreferences'][1]) && isset($_options['bodyPreferences'][1]['truncationSize'])) {
                $truncateAt = $_options['bodyPreferences'][1]['truncationSize'];
            }
            $airSyncBaseType = 1;
        }
        
        if ($_neverTruncate === false) {
            if ($_options['mimeTruncation'] < 8) {
                switch($_options['mimeTruncation']) {
                    case 0:
                        $truncateAt = 0;
                        break;
                    case 1:
                        $truncateAt = 4096;
                        break;
                    case 2:
                        $truncateAt = 5120;
                        break;
                    case 3:
                        $truncateAt = 7168;
                        break;
                    case 4:
                        $truncateAt = 10240;
                        break;
                    case 5:
                        $truncateAt = 20480;
                        break;
                    case 6:
                        $truncateAt = 51200;
                        break;
                    case 7:
                        $truncateAt = 102400;
                        break;
                }
            }
        }
        
        if ($airSyncBaseType == 4) {
            // getMessagePart will return Zend_Mime_Part
            $messageBody = $this->_contentController->getMessagePart($_serverId);
            $messageBody = stream_get_contents($messageBody->getRawStream()); 
        } else {
            $messageBody = $this->_contentController->getMessageBody($_serverId, $airSyncBaseType = 2 ? Zend_Mime::TYPE_HTML : Zend_Mime::TYPE_TEXT, true);
        }
        
        if($truncateAt !== null && strlen($messageBody) > $truncateAt) {
            $messageBody  = substr($messageBody, 0, $truncateAt);
            // maybe the last character is no unicode character anymore
            $messageBody  = iconv('utf-8', 'utf-8//IGNORE', $messageBody);
            $isTruncacted = 1;
        } else {
            $isTruncacted = 0;
        }
        
        if (strlen($messageBody) > 0) {
            if (version_compare($this->_device->acsversion, '12.0', '>=')) {
                $body = $_xmlNode->appendChild(new DOMElement('Body', null, 'uri:AirSyncBase'));
                $body->appendChild(new DOMElement('Type', $airSyncBaseType, 'uri:AirSyncBase'));
                $body->appendChild(new DOMElement('Truncated', $isTruncacted, 'uri:AirSyncBase'));
                $body->appendChild(new DOMElement('EstimatedDataSize', $data->size, 'uri:AirSyncBase'));
                
                $dataTag = $body->appendChild(new DOMElement('Data', null, 'uri:AirSyncBase'));
                $dataTag->appendChild(new DOMText($messageBody));
                
                $_xmlNode->appendChild(new DOMElement('NativeBodyType', $airSyncBaseType, 'uri:AirSyncBase'));
            } else {
                if ($airSyncBaseType == 4) {
                    $_xmlNode->appendChild(new DOMElement('MIMETruncated', $isTruncacted, 'uri:Email'));
                    
                    $body = $_xmlNode->appendChild(new DOMElement('MIMEData', null, 'uri:Email'));
                    $body->appendChild(new DOMText($messageBody));
                    
                } else {
                    $_xmlNode->appendChild(new DOMElement('BodyTruncated', $isTruncacted, 'uri:Email'));
                    
                    $body = $_xmlNode->appendChild(new DOMElement('Body', null, 'uri:Email'));
                    $body->appendChild(new DOMText($messageBody));
                }
            }
        }
        
        if ($airSyncBaseType == 4) {
            $_xmlNode->appendChild(new DOMElement('MessageClass', 'IPM.Note.SMIME', 'uri:Email'));
        } else {
            $_xmlNode->appendChild(new DOMElement('MessageClass', 'IPM.Note', 'uri:Email'));
        }
        $_xmlNode->appendChild(new DOMElement('ContentClass', 'urn:content-classes:message', 'uri:Email'));
        // NativeBodyType
        
        return;
        /*
        foreach($message->getHeaders() as $headerName => $headerValue) {
            switch($headerName) {
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
            }
        }
        */
    }
        
    /**
     * delete entry
     *
     * @param string $_collectionId
     * @param string $_id
     */
    public function delete($_collectionId, $_id)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " ColectionId: $_collectionId Id: $_id");
        
        try {
            $deletedRecords = $this->_contentController->delete($_id);
        } catch (Zend_Mail_Storage_Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " " . $e->getMessage());
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " deleted entry id " . $_id);
    }
    
    /**
     * get id's of all contacts available on the server
     *
     * @return array
     */
    protected function _getServerEntries($_folderId, $_filterType)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " get server entries for folder " . $_folderId);
        
        $filter = new $this->_contentFilterClass();
        
        $this->_getContentFilter($filter, $_filterType);
        $this->_getContainerFilter($filter, $_folderId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filter " . print_r($filter->toArray(), true));
        
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
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId Id: $_id");
        
        $xmlData = $_data->children('uri:Email');
        
        if(isset($xmlData->Read)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId Id: $_id set read flag: $xmlData->Read");
            if((int)$xmlData->Read === 1) {
                $this->_contentController->addFlags($_id, Zend_Mail_Storage::FLAG_SEEN);
            } else {
                $this->_contentController->clearFlags($_id, Zend_Mail_Storage::FLAG_SEEN);
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " contactData " . print_r($task->toArray(), true));
        
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
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filterArray, true));
        
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
            if(empty($folder['parent'])) {
                $result[$folder['id']] = array(
                    'folderId'      => $folder['id'],
                    'parentId'      => 0,
                    'displayName'   => $folder['localname'],
                    'type'          => $this->_getFolderType($folder['localname'])
                );
            }
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " folder result " . print_r($result, true));
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/Controller/ActiveSync_Controller_Interface#moveItem()
     */
    public function moveItem($_srcFolder, $_srcItem, $_dstFolder)
    {
        $filter = new Felamimail_Model_MessageFilter(array(
            array(
                'field'     => 'id',
                'operator'  => 'equals',
                'value'     => $_srcItem
            )
        ));
        
        Felamimail_Controller_Message::getInstance()->moveMessages($filter, $_dstFolder);
        
        return $_srcItem;
    }
    
    /**
     * used by the mail backend only. Used to update the folder cache
     * 
     * @param  string  $_folderId
     */
    public function updateCache($_folderId)
    {
        Felamimail_Controller_Cache_Message::getInstance()->update($_folderId, 5);
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
        } elseif (strtoupper($_folderName) == 'TRASH') {
            return ActiveSync_Command_FolderSync::FOLDERTYPE_DELETEDITEMS;
        } elseif (strtoupper($_folderName) == 'SENT') {
            return ActiveSync_Command_FolderSync::FOLDERTYPE_SENTMAIL;
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

        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filter " . print_r($_filter->toArray(), true));
    }
}