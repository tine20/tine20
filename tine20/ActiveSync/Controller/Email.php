<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Controller
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * controller email class
 *
 * @package     ActiveSync
 * @subpackage  Controller
 */
class ActiveSync_Controller_Email extends ActiveSync_Controller_Abstract 
{
    protected $_mapping = array(
        #'Body'              => 'body',
        'Cc'                => 'cc',
        'DateReceived'      => 'received',
        'From'              => 'from_email',
        #'Sender'            => 'sender',
        'Subject'           => 'subject',
        'To'                => 'to'
    );
    
    /**
     * available filters
     * 
     * @var array
     */
    protected $_filterArray = array(
        Syncope_Command_Sync::FILTER_1_DAY_BACK,
        Syncope_Command_Sync::FILTER_3_DAYS_BACK,
        Syncope_Command_Sync::FILTER_1_WEEK_BACK,
        Syncope_Command_Sync::FILTER_2_WEEKS_BACK,
        Syncope_Command_Sync::FILTER_1_MONTH_BACK,
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
    protected $_defaultFolderType   = Syncope_Command_FolderSync::FOLDERTYPE_INBOX;
    
    /**
     * type of user created folders
     *
     * @var int
     */
    protected $_folderType          = Syncope_Command_FolderSync::FOLDERTYPE_MAIL_USER_CREATED;
    
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
    public function getChangedEntries($_folderId, DateTime $_startTimeStamp, DateTime $_endTimeStamp = NULL)
    {
        $filter = $this->_getContentFilter(0);
        $this->_addContainerFilter($filter, $_folderId);

        $startTimeStamp = ($_startTimeStamp instanceof DateTime) ? $_startTimeStamp->format(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        $endTimeStamp = ($_endTimeStamp instanceof DateTime) ? $_endTimeStamp->format(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
        
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
     * @param DOMElement  $_domParrent   the parrent xml node
     * @param string      $_folderId  the local folder id
     */
    public function appendFileReference(DOMElement $_domParrent, $_fileReference)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " append fileReference " . $_fileReference/* . ' options ' . print_r($_collectionData, true)*/);
        
        list($messageId, $partId) = explode('-', $_fileReference, 2);
        
        $file = $this->_contentController->getMessagePart($messageId, $partId);
        
        $_domParrent->appendChild(new DOMElement('ContentType', $file->type, 'uri:AirSyncBase'));
        $_domParrent->appendChild(new DOMElement('Data', base64_encode($file->getDecodedContent()), 'uri:ItemOperations'));  
    }
    
    /**
     * append email data to xml element
     *
     * @param DOMElement  $_domParrent   the parrent xml node
     * @param string      $_folderId  the local folder id
     * @param string      $_serverId  the local entry id
     * @param boolean     $_withBody  retrieve body of entry
     */
    public function appendXML(DOMElement $_domParrent, $_collectionData, $_serverId)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " append email " . $_serverId/* . ' options ' . print_r($_collectionData, true)*/);
        
        $data = $_serverId instanceof Tinebase_Record_Abstract ? $_serverId : $this->_contentController->get($_serverId);
        
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Email'       , 'uri:Email');
        
        foreach ($this->_mapping as $key => $value) {
            if(!empty($data->$value) || $data->$value == '0') {
                $nodeContent = null;
                
                switch($value) {
                    case 'received':
                        if($data->$value instanceof DateTime) {
                            $nodeContent = $data->$value->toString('Y-m-d\TH:i:s') . '.000Z';
                        }
                        break;
                        
                    case 'from_email':
                        $nodeContent = $this->_createEmailAddress($data->from_name, $data->from_email); 
                        break;
                        
                    case 'to':
                    case 'cc':
                        $nodeContent = implode(', ', $data->$value);
                        
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
                
                // strip off any non printable control characters
                if (!ctype_print($nodeContent)) {
                    $nodeContent = $this->removeControlChars($nodeContent);
                }
                
                $node = $_domParrent->ownerDocument->createElementNS('uri:Email', $key);
                $node->appendChild($_domParrent->ownerDocument->createTextNode($nodeContent));
                
                $_domParrent->appendChild($node);
            }
        }
        
        // read flag
        if (in_array('\Seen', $data->flags)) {
            $_domParrent->appendChild(new DOMElement('Read', 1, 'uri:Email'));                 
        } else {
            $_domParrent->appendChild(new DOMElement('Read', 0, 'uri:Email'));
        }
        
        // attachments?
        if ($data->has_attachment == true) {
            $attachments = $this->_contentController->getAttachments($data);
            
            if (count($attachments) > 0) {
                $tagAttachments = $_domParrent->appendChild(new DOMElement('Attachments', null, 'uri:AirSyncBase'));
                
                foreach ($attachments as $attachment) {
                    $tagAttachment = $tagAttachments->appendChild(new DOMElement('Attachment', null, 'uri:AirSyncBase'));
                    $filenameNode = $tagAttachment->appendChild(new DOMElement('DisplayName', null, 'uri:AirSyncBase'));
                    $filenameNode->appendChild(new DOMText($this->removeControlChars(trim($attachment['filename']))));
                    
                    $tagAttachment->appendChild(new DOMElement('FileReference', $data->getId() . '-' . $attachment['partId'], 'uri:AirSyncBase'));
                    $tagAttachment->appendChild(new DOMElement('Method', 1, 'uri:AirSyncBase'));
                    $tagAttachment->appendChild(new DOMElement('EstimatedDataSize', $this->removeControlChars($attachment['size']), 'uri:AirSyncBase'));
                }
            }
        }
        
        // get truncation
        $truncateAt = null;
        
        if (isset($_collectionData['mimeSupport']) && $_collectionData['mimeSupport'] == 2 && (version_compare($this->_device->acsversion, '12.0', '<=') || isset($_collectionData['bodyPreferences'][4]))) {
            if (isset($_collectionData['bodyPreferences'][4]) && isset($_collectionData['bodyPreferences'][4]['truncationSize'])) {
                $truncateAt = $_collectionData['bodyPreferences'][4]['truncationSize'];
            }
            $airSyncBaseType = 4;
        } elseif (isset($_collectionData['bodyPreferences'][2])) {
            if (isset($_collectionData['bodyPreferences'][2]['truncationSize'])) {
                $truncateAt = $_collectionData['bodyPreferences'][2]['truncationSize'];
            }
            $airSyncBaseType = 2;
        } else {
            if (isset($_collectionData['bodyPreferences'][1]) && isset($_collectionData['bodyPreferences'][1]['truncationSize'])) {
                $truncateAt = $_collectionData['bodyPreferences'][1]['truncationSize'];
            }
            $airSyncBaseType = 1;
        }
        
        if (isset($_collectionData['mimeTruncation']) && $_collectionData['mimeTruncation'] < 8) {
            switch($_collectionData['mimeTruncation']) {
                case Syncope_Command_Sync::TRUNCATE_ALL:
                    $truncateAt = 0;
                    break;
                case Syncope_Command_Sync::TRUNCATE_4096:
                    $truncateAt = 4096;
                    break;
                case Syncope_Command_Sync::TRUNCATE_5120:
                    $truncateAt = 5120;
                    break;
                case Syncope_Command_Sync::TRUNCATE_7168:
                    $truncateAt = 7168;
                    break;
                case Syncope_Command_Sync::TRUNCATE_10240:
                    $truncateAt = 10240;
                    break;
                case Syncope_Command_Sync::TRUNCATE_20480:
                    $truncateAt = 20480;
                    break;
                case Syncope_Command_Sync::TRUNCATE_51200:
                    $truncateAt = 51200;
                    break;
                case Syncope_Command_Sync::TRUNCATE_102400:
                    $truncateAt = 102400;
                    break;
            }
        }
        
        if ($airSyncBaseType == 4) {
            // getMessagePart will return Zend_Mime_Part
            $messageBody = $this->_contentController->getMessagePart($_serverId);
            $messageBody = stream_get_contents($messageBody->getRawStream()); 
            
            if (version_compare($this->_device->acsversion, '12.0', '<')) {
                // if the email contains non 7bit ascii characters we can't transfer them via MIMEData xml and we need to fall back to plain text
                if (preg_match('/(?:[^\x00-\x7F])/', $messageBody)) {
                    $airSyncBaseType = 1;
                    $messageBody     = $this->_contentController->getMessageBody($_serverId, null, Zend_Mime::TYPE_TEXT, NULL, true);
                }
            }
        } else {
            $messageBody = $this->_contentController->getMessageBody($_serverId, null, $airSyncBaseType == 2 ? Zend_Mime::TYPE_HTML : Zend_Mime::TYPE_TEXT, NULL, true);
        }
        
        
        if($truncateAt !== null && strlen($messageBody) > $truncateAt) {
            $messageBody  = substr($messageBody, 0, $truncateAt);
            $isTruncacted = 1;
        } else {
            $isTruncacted = 0;
        }
        
        if (strlen($messageBody) > 0) {
            // remove control chars
            $messageBody = $this->removeControlChars($messageBody);
            
            // strip out any non utf-8 characters
            $messageBody  = @iconv('utf-8', 'utf-8//IGNORE', $messageBody);
            
            if (version_compare($this->_device->acsversion, '12.0', '>=')) {
                $body = $_domParrent->appendChild(new DOMElement('Body', null, 'uri:AirSyncBase'));
                $body->appendChild(new DOMElement('Type', $airSyncBaseType, 'uri:AirSyncBase'));
                $body->appendChild(new DOMElement('Truncated', $isTruncacted, 'uri:AirSyncBase'));
                $body->appendChild(new DOMElement('EstimatedDataSize', $data->size, 'uri:AirSyncBase'));
                
                $dataTag = $body->appendChild(new DOMElement('Data', null, 'uri:AirSyncBase'));
                $dataTag->appendChild(new DOMText($messageBody));
                
                $_domParrent->appendChild(new DOMElement('NativeBodyType', $airSyncBaseType, 'uri:AirSyncBase'));
            } else {
                if ($airSyncBaseType == 4) {
                    $_domParrent->appendChild(new DOMElement('MIMETruncated', $isTruncacted, 'uri:Email'));
                    
                    $body = $_domParrent->appendChild(new DOMElement('MIMEData', null, 'uri:Email'));
                    $body->appendChild(new DOMText($messageBody));
                    
                } else {
                    $_domParrent->appendChild(new DOMElement('BodyTruncated', $isTruncacted, 'uri:Email'));
                    
                    $body = $_domParrent->appendChild(new DOMElement('Body', null, 'uri:Email'));
                    $body->appendChild(new DOMText($messageBody));
                }
            }
        }
        
        if ($airSyncBaseType == 4) {
            $_domParrent->appendChild(new DOMElement('MessageClass', 'IPM.Note.SMIME', 'uri:Email'));
        } else {
            $_domParrent->appendChild(new DOMElement('MessageClass', 'IPM.Note', 'uri:Email'));
        }
        $_domParrent->appendChild(new DOMElement('ContentClass', 'urn:content-classes:message', 'uri:Email'));
        
        return;
        /*
        foreach($message->getHeaders() as $headerName => $headerValue) {
            switch($headerName) {
                case 'importance':
                    switch (strtolower($headerValue)) {
                        case 'low':
                            $_domParrent->appendChild($_xmlDocument->createElementNS('uri:Email', 'Importance', 0));
                            break;
                    	
                        case 'high':
                            $_domParrent->appendChild($_xmlDocument->createElementNS('uri:Email', 'Importance', 2));
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
     * @param  string  $_folderId
     * @param  string  $_serverId
     * @param  array   $_collectionData
     */
    public function deleteEntry($_folderId, $_serverId, $_collectionData)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " delete ColectionId: $_folderId Id: $_serverId");
        
        $folder  = Felamimail_Controller_Folder::getInstance()->get($_folderId);
        $account = Felamimail_Controller_Account::getInstance()->get($folder->account_id);
        
        if ($_collectionData['deletesAsMoves'] === true && !empty($account->trash_folder)) {
            // move message to trash folder
            $trashFolder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($account, $account->trash_folder);
            Felamimail_Controller_Message_Move::getInstance()->moveMessages($_serverId, $trashFolder);
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " moved entry $_serverId to trash folder");
        } else {
            // set delete flag
            Felamimail_Controller_Message_Flags::getInstance()->addFlags($_serverId, Zend_Mail_Storage::FLAG_DELETED);
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " deleted entry " . $_serverId);
        }
    }
    
    /**
     * update existing entry
     *
     * @param  string  $_folderId
     * @param  string  $_serverId
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function updateEntry($_folderId, $_serverId, SimpleXMLElement $_entry)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " CollectionId: $_folderId Id: $_serverId");
        
        $xmlData = $_entry->children('uri:Email');
        
        if(isset($xmlData->Read)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_folderId Id: $_serverId set read flag: $xmlData->Read");
            if((int)$xmlData->Read === 1) {
                Felamimail_Controller_Message_Flags::getInstance()->addFlags($_serverId, Zend_Mail_Storage::FLAG_SEEN);
            } else {
                Felamimail_Controller_Message_Flags::getInstance()->clearFlags($_serverId, Zend_Mail_Storage::FLAG_SEEN);
            }
            
            $message = $this->_contentController->get($_serverId);
            $message->timestamp = $this->_syncTimeStamp;
            $this->_contentController->update($message);
        }
        
        return;
    }
    
    /**
     * convert email from xml to Felamimail_Model_Message
     *
     * @param  SimpleXMLElement  $_data
     * @param  mixed             $_entry
     */
    public function toTineModel(SimpleXMLElement $_data, $_entry = null)
    {
        // does nothing => you can't add emails via ActiveSync
    }
    
    /**
     * create rfc email address 
     * 
     * @param  string  $_realName
     * @param  string  $_address
     * @return string
     */
    protected function _createEmailAddress($_realName, $_address)
    {
        return !empty($_realName) ? sprintf('"%s" <%s>', str_replace('"', '\\"', $_realName), $_address) : $_address; 
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
    public function getAllFolders()
    {
        if (!Tinebase_Core::getUser()->hasRight('Felamimail', Tinebase_Acl_Rights::RUN)) {
            // no folders
            return array();
        }
        
        $defaultAccountId = Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT};
        
        if (empty($defaultAccountId)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " no default account set. Can't sync any folders.");
            return array();            
        }
        
        try {
            $account = Felamimail_Controller_Account::getInstance()->get($defaultAccountId);
        } catch (Tinebase_Exception_NotFound $ten) {
            // no folders
            return array();
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " accountData " . print_r($account->toArray(), true));
        
        // update folder cache
        Felamimail_Controller_Cache_Folder::getInstance()->update($account);
        
        // get folders
        $folderController = Felamimail_Controller_Folder::getInstance();
        $folders = $folderController->getSubfolders($account->getId(), '');

        $result = array();
        
        foreach ($folders as $folder) {
            if (! empty($folder->parent)) {
                try {
                    $parent   = $folderController->getByBackendAndGlobalName($folder->account_id, $folder->parent);
                    $parentId = $parent->getId();
                } catch (Tinebase_Exception_NotFound $ten) {
                    continue;
                }
            } else {
                $parentId = 0;
            }
            
            $result[$folder->getId()] = array(
                'folderId'      => $folder->getId(),
                'parentId'      => $parentId,
                'displayName'   => $folder->localname,
                'type'          => $this->_getFolderType($folder->localname)
            );
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " folder result " . print_r($result, true));
        
        return $result;
    }
    
    public function moveItem($_srcFolderId, $_serverId, $_dstFolderId)
    {
        $filter = new Felamimail_Model_MessageFilter(array(
            array(
                'field'     => 'id',
                'operator'  => 'equals',
                'value'     => $_serverId
            )
        ));
        
        Felamimail_Controller_Message_Move::getInstance()->moveMessages($filter, $_dstFolderId);
        
        return $_serverId;
    }
    
    /**
     * used by the mail backend only. Used to update the folder cache
     * 
     * @param  string  $_folderId
     */
    public function updateCache($_folderId)
    {
        try {
            Felamimail_Controller_Cache_Message::getInstance()->updateCache($_folderId, 5);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " catched exception " . get_class($e));
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " " . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $e->getTraceAsString());
        }
    }
    
    /**
     * set activesync foldertype
     * 
     * @param string $_folderName
     */
    protected function _getFolderType($_folderName)
    {
        if(strtoupper($_folderName) == 'INBOX') {
            return Syncope_Command_FolderSync::FOLDERTYPE_INBOX;
        } elseif (strtoupper($_folderName) == 'TRASH') {
            return Syncope_Command_FolderSync::FOLDERTYPE_DELETEDITEMS;
        } elseif (strtoupper($_folderName) == 'SENT') {
            return Syncope_Command_FolderSync::FOLDERTYPE_SENTMAIL;
        } else {
            return Syncope_Command_FolderSync::FOLDERTYPE_MAIL_USER_CREATED;
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
     * return contentfilter array
     * 
     * @param  int $_filterType
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getContentFilter($_filterType)
    {
        $filter = parent::_getContentFilter($_filterType);
        
        if(in_array($_filterType, $this->_filterArray)) {
            $today = Tinebase_DateTime::now()->setTime(0,0,0);
                
            switch($_filterType) {
                case Syncope_Command_Sync::FILTER_1_DAY_BACK:
                    $received = $today->subDay(1);
                    break;
                case Syncope_Command_Sync::FILTER_3_DAYS_BACK:
                    $received = $today->subDay(3);
                    break;
                case Syncope_Command_Sync::FILTER_1_WEEK_BACK:
                    $received = $today->subWeek(1);
                    break;
                case Syncope_Command_Sync::FILTER_2_WEEKS_BACK:
                    $received = $today->subWeek(2);
                    break;
                case Syncope_Command_Sync::FILTER_1_MONTH_BACK:
                    $received = $today->subMonth(2);
                    break;
            }
            
            // add period filter
            $filter->addFilter(new Tinebase_Model_Filter_DateTime('received', 'after', $received->get(Tinebase_Record_Abstract::ISO8601LONG)));
        }
        
        return $filter;
    }
    
    protected function _addContainerFilter(Tinebase_Model_Filter_FilterGroup $_filter, $_containerId)
    {
        // custom filter gets added when created
        $_filter->createFilter(
            'account_id', 
            'equals', 
            Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}
        );
        
        $_filter->addFilter($_filter->createFilter(
            'folder_id', 
            'equals', 
            $_containerId
        ));  

        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filter " . print_r($_filter->toArray(), true));
    }
}
