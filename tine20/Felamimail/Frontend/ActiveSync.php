<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * ActiveSync frontend class
 *
 * @package     Felamimail
 * @subpackage  Frontend
 */
class Felamimail_Frontend_ActiveSync extends ActiveSync_Frontend_Abstract implements Syncroton_Data_IDataEmail, Syncroton_Data_IDataSearch
{
    protected $_mapping = array(
        'body'              => 'body',
        'cc'                => 'cc',
        'dateReceived'      => 'received',
        'from'              => 'from_email',
        #'Sender'            => 'sender',
        'subject'           => 'subject',
        'to'                => 'to'
    );
    
    protected $_debugEmail = false;
    
    /**
     * available filters
     * 
     * @var array
     */
    protected $_filterArray = array(
        Syncroton_Command_Sync::FILTER_1_DAY_BACK,
        Syncroton_Command_Sync::FILTER_3_DAYS_BACK,
        Syncroton_Command_Sync::FILTER_1_WEEK_BACK,
        Syncroton_Command_Sync::FILTER_2_WEEKS_BACK,
        Syncroton_Command_Sync::FILTER_1_MONTH_BACK,
    );
    
    /**
     * felamimail message controller
     *
     * @var Felamimail_Controller_Message
     */
    protected $_messageController;
    
    /**
     * felamimail account
     * 
     * @var Felamimail_Model_Account
     */
    protected $_account;
    
    /**
     * app name (required by abstract class)
     * 
     * @var string
     */
    protected $_applicationName     = 'Felamimail';
    
    /**
     * model name (required by abstract class)
     * 
     * @var string
     */
    protected $_modelName           = 'Message';
    
    /**
     * type of the default folder
     *
     * @var int
     */
    protected $_defaultFolderType   = Syncroton_Command_FolderSync::FOLDERTYPE_INBOX;
    
    /**
     * type of user created folders
     *
     * @var int
     */
    protected $_folderType          = Syncroton_Command_FolderSync::FOLDERTYPE_MAIL_USER_CREATED;
    
    /**
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty = 'emailfilterId';
    
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
     * prefix for faked folders
     * 
     * @var string
     */
    protected $_fakePrefix = 'fake-';
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataEmail::forwardEmail()
     */
    public function forwardEmail($source, $inputStream, $saveInSent, $replaceMime)
    {
        $account = $this->_getAccount();
        
        if (!$account) {
            throw new Syncroton_Exception('no email account configured');
        }
        
        if (empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            throw new Syncroton_Exception('no email address set for current user');
        }
        
        if (! is_resource($inputStream)) {
            $stream = fopen("php://temp", 'r+');
            fwrite($stream, $inputStream);
            $inputStream = $stream;
            rewind($inputStream);
        }
        
         if ($this->_debugEmail == true) {
             $debugStream = fopen("php://temp", 'r+');
             stream_copy_to_stream($inputStream, $debugStream);
             rewind($debugStream);
             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                 __METHOD__ . '::' . __LINE__ . " email to send:" . stream_get_contents($debugStream));
        
             // replace original stream with debug stream, as php://input can't be rewinded
             $inputStream = $debugStream;
             rewind($inputStream);
         }
        
        $incomingMessage = new Zend_Mail_Message(
            array(
                'file' => $inputStream
            )
        );
        
        $messageId = is_array($source) ? $source['itemId'] : $source;
        $fmailMessage = Felamimail_Controller_Message::getInstance()->get($messageId);
        $fmailMessage->flags = Zend_Mail_Storage::FLAG_PASSED;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . " source: " . $messageId . "saveInSent: " . $saveInSent);
        
        if ($replaceMime === FALSE) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                 __METHOD__ . '::' . __LINE__ . " Adding RFC822 attachment and appending body to forward message.");
            
            $rfc822 = Felamimail_Controller_Message::getInstance()->getMessagePart($fmailMessage);
            $rfc822->type = Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822;
            $rfc822->filename = 'forwarded_email.eml';
            $rfc822->encoding = Zend_Mime::ENCODING_7BIT;
            $replyBody = Felamimail_Controller_Message::getInstance()->getMessageBody($fmailMessage, NULL, 'text/plain');
        } else {
            $rfc822 = NULL;
            $replyBody = NULL;
        }
        
        $mail = Tinebase_Mail::createFromZMM($incomingMessage, $replyBody);
        if ($rfc822) {
            $mail->addAttachment($rfc822);
        }
        
        Felamimail_Controller_Message_Send::getInstance()->sendZendMail($account, $mail, $saveInSent, $fmailMessage);
    }
    
    /**
     * get all entries changed between to dates
     *
     * @param unknown_type $_field
     * @param unknown_type $_startTimeStamp
     * @param unknown_type $_endTimeStamp
     * @return array
     */
    public function getChangedEntries($folderId, DateTime $_startTimeStamp, DateTime $_endTimeStamp = NULL, $filterType = NULL)
    {
        if (strpos($folderId, $this->_fakePrefix) === 0) {
            return array();
        }
        
        $filter = $this->_getContentFilter(0);
        
        $this->_addContainerFilter($filter, $folderId);

        $startTimeStamp = ($_startTimeStamp instanceof DateTime) ? $_startTimeStamp->format(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        $endTimeStamp = ($_endTimeStamp instanceof DateTime) ? $_endTimeStamp->format(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
        
        // @todo filter also for create_timestamp??
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
        
        $result = $this->_contentController->search($filter, NULL, false, true, 'sync');
        
        return $result;
    }
    
    /**
     * retrieve folders which were modified since last sync
     * 
     * @param  DateTime $startTimeStamp
     * @param  DateTime $endTimeStamp
     * @return array
     * 
     * @todo implement
     * 
     * @see 0007786: changed email folder names do not sync to device
     */
    public function getChangedFolders(DateTime $startTimeStamp, DateTime $endTimeStamp)
    {
        $syncrotonFolders = array();
        
        // @todo calculate changed folders
        
        return $syncrotonFolders;
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::getFileReference()
     */
    public function getFileReference($fileReference)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . " fileReference " . $fileReference);
        
        list($messageId, $partId) = explode(ActiveSync_Frontend_Abstract::LONGID_DELIMITER, $fileReference, 2);
        
        $part = $this->_contentController->getMessagePart($messageId, $partId);
        
        $syncrotonFileReference = new Syncroton_Model_FileReference(array(
            'contentType' => $part->type,
            'data'        => $part->getDecodedStream()
        ));
        
        return $syncrotonFileReference;
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataEmail::replyEmail()
     */
    public function replyEmail($source, $inputStream, $saveInSent, $replaceMime)
    {
        $account = $this->_getAccount();
        
        if (!$account) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . " no email account configured");
            
            throw new Syncroton_Exception('no email account configured');
        }
        
        if (empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            throw new Syncroton_Exception('no email address set for current user');
        }
        
        if (! is_resource($inputStream)) {
            $stream = fopen("php://temp", 'r+');
            fwrite($stream, $inputStream);
            $inputStream = $stream;
            rewind($inputStream);
        }
        
        if ($this->_debugEmail == true) {
             $debugStream = fopen("php://temp", 'r+');
             stream_copy_to_stream($inputStream, $debugStream);
             rewind($debugStream);
             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                 __METHOD__ . '::' . __LINE__ . " email to send:" . stream_get_contents($debugStream));
        
             //replace original stream wirh debug stream, as php://input can't be rewinded
             $inputStream = $debugStream;
             rewind($inputStream);
        }
        
        $incomingMessage = new Zend_Mail_Message(
            array(
                'file' => $inputStream
            )
        );
        
        $messageId = is_array($source) ? $source['itemId'] : $source;
        $fmailMessage = Felamimail_Controller_Message::getInstance()->get($messageId);
        $fmailMessage->flags = Zend_Mail_Storage::FLAG_ANSWERED;
        
        if ($replaceMime === false) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . " attach source: " . $messageId . " saveInSent: " . $saveInSent);
            
            $replyBody = Felamimail_Controller_Message::getInstance()->getMessageBody($fmailMessage, null, 'text/plain');
        } else {
            $replyBody = null;
        }
        
        $mail = Tinebase_Mail::createFromZMM($incomingMessage, $replyBody);
        
        Felamimail_Controller_Message_Send::getInstance()->sendZendMail($account, $mail, (bool)$saveInSent, $fmailMessage);
    }
    
    /**
     * send email
     * 
     * @param resource $inputStream
     * @param boolean $saveInSent
     * @throws Syncroton_Exception
     */
    public function sendEmail($inputStream, $saveInSent)
    {
        $account = $this->_getAccount();
        
        if (!$account) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . " no email account configured");
            
            throw new Syncroton_Exception('no email account configured');
        }
        
        if (empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            throw new Syncroton_Exception('no email address set for current user');
        }
        
        if (! is_resource($inputStream)) {
            $stream = fopen("php://temp", 'r+');
            fwrite($stream, $inputStream);
            $inputStream = $stream;
            rewind($inputStream);
        }
        
        if ($this->_debugEmail == true) {
             $debugStream = fopen("php://temp", 'r+');
             stream_copy_to_stream($inputStream, $debugStream);
             rewind($debugStream);
             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                 __METHOD__ . '::' . __LINE__ . " email to send:" . stream_get_contents($debugStream));
        
             //replace original stream wirh debug stream, as php://input can't be rewinded
             $inputStream = $debugStream;
             rewind($inputStream);
        }
        
        $incomingMessage = new Zend_Mail_Message(
            array(
                'file' => $inputStream
            )
        );
        
        $subject = $incomingMessage->headerExists('subject') ? $incomingMessage->getHeader('subject') : null;
        
        if (Tinebase_Mail::isiMIPMail($incomingMessage)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Do not send iMIP message with subject "' . $subject . '". The server should handle those.');
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . " Send Message with subject " . $subject . " (saveInSent: " . $saveInSent . ")");
            
            $mail = Tinebase_Mail::createFromZMM($incomingMessage);
        
            Felamimail_Controller_Message_Send::getInstance()->sendZendMail($account, $mail, (bool)$saveInSent);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::toSyncrotonModel()
     */
    public function toSyncrotonModel($entry, array $options = array())
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . " email data " . print_r($entry->toArray(), true));
        
        if (! $this->_isMemoryLeft($entry->size)) {
            throw new Syncroton_Exception_MemoryExhausted('not enough memory left for: ' . $entry->getId() . ' Needed: ' . $entry->size);
        }
        
        $syncrotonEmail = new Syncroton_Model_Email();
        
        foreach ($this->_mapping as $syncrotonProperty => $tine20Property) {
            if ($this->_isEmptyValue($entry->$tine20Property)) {
                // skip empty values
                continue;
            }

            switch($tine20Property) {
                case 'from_email':
                    $syncrotonEmail->$syncrotonProperty = $this->_createEmailAddress($entry->from_name, $entry->from_email);
                    
                    break;
                    
                case 'to':
                case 'cc':
                    $syncrotonEmail->$syncrotonProperty = implode(', ', $entry->$tine20Property);
                    
                    break;
                    
                default:
                    $syncrotonEmail->$syncrotonProperty = $entry->$tine20Property;
                    break;
            }
        }
        
        $syncrotonEmail->body = $this->_getSyncrotonBody($entry, $options);
        if ($syncrotonEmail->body->type < 4) {
            $syncrotonEmail->nativeBodyType = $syncrotonEmail->body->type;
        }
        
        if ($syncrotonEmail->body->type == Syncroton_Command_Sync::BODY_TYPE_MIME) {
            $syncrotonEmail->messageClass = 'IPM.Note.SMIME';
        } else {
            $syncrotonEmail->messageClass = 'IPM.Note';
        }
        
        $syncrotonEmail->contentClass = 'urn:content-classes:message';
        
        // read flag
        $syncrotonEmail->read = in_array(Zend_Mail_Storage::FLAG_SEEN, $entry->flags) ? 1 : 0;
        
        if (in_array(Zend_Mail_Storage::FLAG_ANSWERED, $entry->flags)) {
            $syncrotonEmail->lastVerbExecuted = Syncroton_Model_Email::LASTVERB_REPLYTOSENDER;
            $syncrotonEmail->lastVerbExecutionTime = new DateTime('now', new DateTimeZone('utc'));
        #} elseif (in_array('\Forwarded', $entry->flags)) {
        #    $syncrotonEmail->lastVerbExecuted = Syncroton_Model_Email::LASTVERB_FORWARD;
        #    $syncrotonEmail->lastVerbExecutionTime = new DateTime('now', new DateTimeZone('utc'));
        }
        
        $syncrotonEmail->flag = in_array('\Flagged', $entry->flags) ? 
            new Syncroton_Model_EmailFlag(array(
                'status'       => Syncroton_Model_EmailFlag::STATUS_ACTIVE,
                'flagType'     => 'FollowUp',
                'reminderSet'  => 0,
                'startDate'    => Tinebase_DateTime::now(),
                'utcStartDate' => Tinebase_DateTime::now(),
                'dueDate'    => Tinebase_DateTime::now()->addWeek(1),
                'utcDueDate' => Tinebase_DateTime::now()->addWeek(1),
            )) : 
            new Syncroton_Model_EmailFlag(array(
                'status' => Syncroton_Model_EmailFlag::STATUS_CLEARED
            ));
        
        // attachments?
        if ($entry->has_attachment == true) {
            $syncrotonAttachments = array();
            
            $attachments = $this->_contentController->getAttachments($entry);

            if (count($attachments) > 0) {
                foreach ($attachments as $attachment) {
                    $syncrotonAttachment = new Syncroton_Model_EmailAttachment(array(
                        'displayName'       => trim($attachment['filename']),
                        'fileReference'     => $entry->getId() . ActiveSync_Frontend_Abstract::LONGID_DELIMITER . $attachment['partId'],
                        'method'            => 1,
                        'estimatedDataSize' => $attachment['size']
                    ));
                    
                    $syncrotonAttachments[] = $syncrotonAttachment;
                }
            }
            
            $syncrotonEmail->attachments = $syncrotonAttachments;
        }
        
        
        #$syncrotonEmail->categories = array('Test');
        $syncrotonEmail->conversationId    = $entry->getId();
        $syncrotonEmail->conversationIndex = "\x00\x01\x02\x03\x04";
        
        return $syncrotonEmail;
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataSearch::search()
     */
    public function search(Syncroton_Model_StoreRequest $store)
    {
        $storeResponse = new Syncroton_Model_StoreResponse();
        
        if (!isset($store->query['and']) || !isset($store->query['and']['freeText'])) {
            $storeResponse->total = 0;
            return $storeResponse;
        }
        
        $filter = new $this->_contentFilterClass(array(array(
            'field'     => 'query',
            'operator'  => 'contains',
            'value'     => $store->query['and']['freeText']
        )));
        
        if (isset($store->query['and']['collections'])) {
            // @todo search for multiple folders
            $folderId = $store->query['and']['collections'][0];
        } else {
            $folderId = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName(
                Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT},
                'INBOX'
            )->getId();
        }
        $this->_addContainerFilter($filter, $folderId);
        
        if (isset($store->options['range'])) {
            $pagination = new Tinebase_Model_Pagination(array(
                'start' => $store->options['range'][0],
                'limit' => $store->options['range'][1] - $store->options['range'][0],
                'sort' => $this->_sortField,
                'dir' => 'DESC',
            ));
        } else {
            $pagination = new Tinebase_Model_Pagination(array(
                'sort' => $this->_sortField,
                'dir' => 'DESC',
            ));
        }
        
        $serverIds = $this->_contentController->search($filter, $pagination, false, true, 'sync');
        $totalCount = $this->_contentController->searchCount($filter, 'sync');
        
        foreach ($serverIds as $serverId) {
            $email = $this->getEntry(
                new Syncroton_Model_SyncCollection(array(
                    'collectionId' => $folderId,
                    'options'      => $store->options
                )), 
                $serverId
            );
    
            $storeResponse->result[] = new Syncroton_Model_StoreResponseResult(array(
                'class'        => 'Email',
                'longId'       => $folderId . ActiveSync_Frontend_Abstract::LONGID_DELIMITER . $serverId,
                'collectionId' => $folderId,
                'properties'   => $email
            ));
        }
        
        $storeResponse->total = $totalCount;
        if (is_array($storeResponse->result) && count($storeResponse->result) > 0) {
            $storeResponse->range = array($store->options['range'][0], $store->options['range'][1]);
        }
        
        return $storeResponse;
    }
    
    /**
     * 
     * @param Felamimail_Model_Message $entry
     * @param array $options
     * @return void|Syncroton_Model_EmailBody
     */
    protected function _getSyncrotonBody(Felamimail_Model_Message $entry, $options)
    {
        //var_dump($options);
        
        // get truncation
        $truncateAt  = null;
        $previewSize = null;
        
        if ($options['mimeSupport'] == Syncroton_Command_Sync::MIMESUPPORT_SEND_MIME) {
            $airSyncBaseType = Syncroton_Command_Sync::BODY_TYPE_MIME;
            
            if (isset($options['bodyPreferences'][Syncroton_Command_Sync::BODY_TYPE_MIME]['truncationSize'])) {
                $truncateAt = $options['bodyPreferences'][Syncroton_Command_Sync::BODY_TYPE_MIME]['truncationSize'];
            } elseif (isset($options['mimeTruncation']) && $options['mimeTruncation'] < Syncroton_Command_Sync::TRUNCATE_NOTHING) {
                switch($options['mimeTruncation']) {
                    case Syncroton_Command_Sync::TRUNCATE_ALL:
                        $truncateAt = 0;
                        break;
                    case Syncroton_Command_Sync::TRUNCATE_4096:
                        $truncateAt = 4096;
                        break;
                    case Syncroton_Command_Sync::TRUNCATE_5120:
                        $truncateAt = 5120;
                        break;
                    case Syncroton_Command_Sync::TRUNCATE_7168:
                        $truncateAt = 7168;
                        break;
                    case Syncroton_Command_Sync::TRUNCATE_10240:
                        $truncateAt = 10240;
                        break;
                    case Syncroton_Command_Sync::TRUNCATE_20480:
                        $truncateAt = 20480;
                        break;
                    case Syncroton_Command_Sync::TRUNCATE_51200:
                        $truncateAt = 51200;
                        break;
                    case Syncroton_Command_Sync::TRUNCATE_102400:
                        $truncateAt = 102400;
                        break;
                }
            }
            
        } elseif (isset($options['bodyPreferences'][Syncroton_Command_Sync::BODY_TYPE_HTML])) {
            $airSyncBaseType = Syncroton_Command_Sync::BODY_TYPE_HTML;
            
            if (isset($options['bodyPreferences'][Syncroton_Command_Sync::BODY_TYPE_HTML]['truncationSize'])) {
                $truncateAt = $options['bodyPreferences'][Syncroton_Command_Sync::BODY_TYPE_HTML]['truncationSize'];
            }
            if (isset($options['bodyPreferences'][Syncroton_Command_Sync::BODY_TYPE_HTML]['preview'])) {
                $previewSize = $options['bodyPreferences'][Syncroton_Command_Sync::BODY_TYPE_HTML]['preview'];
            }
            
        } else {
            $airSyncBaseType = Syncroton_Command_Sync::BODY_TYPE_PLAIN_TEXT;
            
            if (isset($options['bodyPreferences'][Syncroton_Command_Sync::BODY_TYPE_PLAIN_TEXT]['truncationSize'])) {
                $truncateAt = $options['bodyPreferences'][Syncroton_Command_Sync::BODY_TYPE_PLAIN_TEXT]['truncationSize'];
            }
            if (isset($options['bodyPreferences'][Syncroton_Command_Sync::BODY_TYPE_PLAIN_TEXT]['preview'])) {
                $previewSize = $options['bodyPreferences'][Syncroton_Command_Sync::BODY_TYPE_PLAIN_TEXT]['preview'];
            }
        }
        
        if ($airSyncBaseType == Syncroton_Command_Sync::BODY_TYPE_MIME) {
            // getMessagePart will return Zend_Mime_Part
            $messageBody = $this->_contentController->getMessagePart($entry);
            $messageBody = stream_get_contents($messageBody->getRawStream());
            
        } else {
            $messageBody = $this->_contentController->getMessageBody($entry, null, $airSyncBaseType == 2 ? Zend_Mime::TYPE_HTML : Zend_Mime::TYPE_TEXT, NULL, true);
        }
        
        if($previewSize !== null) {
            $preview = substr($this->_contentController->getMessageBody($entry, null, Zend_Mime::TYPE_TEXT, NULL, true), 0, $previewSize);
            
            // strip out any non utf-8 characters
            $preview = @iconv('utf-8', 'utf-8//IGNORE', $preview);
        }
        
        if($truncateAt !== null && strlen($messageBody) > $truncateAt) {
            $messageBody  = substr($messageBody, 0, $truncateAt);
            $isTruncacted = 1;
        } else {
            $isTruncacted = 0;
        }
        
        // strip out any non utf-8 characters
        $messageBody  = @iconv('utf-8', 'utf-8//IGNORE', $messageBody);
        
        $synrotonBody = new Syncroton_Model_EmailBody(array(
            'type'              => $airSyncBaseType,
            'estimatedDataSize' => $entry->size,
        ));
        
        if (strlen($messageBody) > 0) {
            $synrotonBody->data = $messageBody;
        }
        
        if (isset($preview) && strlen($preview) > 0) {
            $synrotonBody->preview = $preview;
        }
        
        if ($isTruncacted === 1) {
            $synrotonBody->truncated = 1;
        } else {
            $synrotonBody->truncated = 0;
        }
        
        return $synrotonBody;
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::_inspectGetCountOfChanges()
     */
    protected function _inspectGetCountOfChanges(Syncroton_Backend_IContent $contentBackend, Syncroton_Model_IFolder $folder, Syncroton_Model_ISyncState $syncState)
    {
        if (strpos($folder->serverId, $this->_fakePrefix) === false) {
            try {
                Felamimail_Controller_Cache_Message::getInstance()->updateCache($folder->serverId, 10);
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                    __METHOD__ . '::' . __LINE__ . " Could not update folder cache: " . $tenf);
            }
        }
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
        
        if ($_collectionData->deletesAsMoves === true && !empty($account->trash_folder)) {
            // move message to trash folder
            $trashFolder = Felamimail_Controller_Account::getInstance()->getSystemFolder($account->getId(),
                Felamimail_Model_Folder::FOLDER_TRASH);
            Felamimail_Controller_Message_Move::getInstance()->moveMessages($_serverId, $trashFolder);
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " moved entry $_serverId to trash folder");
        } else {
            // set delete flag
            Felamimail_Controller_Message_Flags::getInstance()->addFlags($_serverId, Zend_Mail_Storage::FLAG_DELETED);
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " deleted entry " . $_serverId);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::updateEntry()
     */
    public function updateEntry($folderId, $serverId, Syncroton_Model_IEntry $entry)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . " CollectionId: $folderId Id: $serverId");
        
        try {
            $message = $this->_contentController->get($serverId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' ' . $tenf);
            throw new Syncroton_Exception_NotFound($tenf->getMessage());
        }
        
        if(isset($entry->read)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . " CollectionId: $folderId Id: $serverId set read flag: $entry->read");
            
            if($entry->read == 1) {
                Felamimail_Controller_Message_Flags::getInstance()->addFlags($serverId, Zend_Mail_Storage::FLAG_SEEN);
            } else {
                Felamimail_Controller_Message_Flags::getInstance()->clearFlags($serverId, Zend_Mail_Storage::FLAG_SEEN);
            }
        }
        
        if(isset($entry->flag)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . " CollectionId: $folderId Id: $serverId set flagged flag: {$entry->flag->status}");
            
            if($entry->flag->status == Syncroton_Model_EmailFlag::STATUS_ACTIVE) {
                Felamimail_Controller_Message_Flags::getInstance()->addFlags($serverId, Zend_Mail_Storage::FLAG_FLAGGED);
            } else {
                Felamimail_Controller_Message_Flags::getInstance()->clearFlags($serverId, Zend_Mail_Storage::FLAG_FLAGGED);
            }
        }
        
        $message->timestamp = $this->_syncTimeStamp;
        $this->_contentController->update($message);
        
        return;
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::updateFolder()
     */
    public function updateFolder(Syncroton_Model_IFolder $folder)
    {
        if (strpos($folder->serverId, $this->_fakePrefix) === 0) {
            return $folder;
        }
        
        $fmailFolder = Felamimail_Controller_Folder::getInstance()->get($folder->serverId);
        Felamimail_Controller_Folder::getInstance()->rename($fmailFolder->account_id, $folder->displayName, $fmailFolder->globalname);
        return $folder;
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::toTineModel()
     */
    public function toTineModel(Syncroton_Model_IEntry $data, $entry = null)
    {
        // does nothing => you can't add emails via ActiveSync
        throw new Syncroton_Exception_Status(Syncroton_Exception_Status::COMMAND_NOT_SUPPORTED);
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
        
        $this->_updateFolderCache();
        $result = $this->_getFolders();
        
        $this->_addFakeFolders($result);
        
        return $result;
    }
    
    /**
     * get felamimail account
     * 
     * @return Felamimail_Model_Account|NULL
     */
    protected function _getAccount()
    {
        if ($this->_account === NULL) {
            $defaultAccountId = Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT};
            
            if (empty($defaultAccountId)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . " no default account set. Can't sync any folders.");
                return NULL;
            }
            
            try {
                $this->_account = Felamimail_Controller_Account::getInstance()->get($defaultAccountId);
            } catch (Tinebase_Exception_NotFound $ten) {
                return NULL;
            }
        }
        
        return $this->_account;
    }
    
    /**
     * update the folder cache
     * 
     * @throws Syncroton_Exception_Status_FolderSync
     */
    protected function _updateFolderCache()
    {
        $account = $this->_getAccount();
        if (! $account) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " accountData " . print_r($account->toArray(), true));
        
        try {
            Felamimail_Controller_Cache_Folder::getInstance()->update($account);
            
        } catch (Felamimail_Exception_IMAPServiceUnavailable $feisu) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . " Could not update folder cache: " . $feisu);
            throw new Syncroton_Exception_Status_FolderSync(Syncroton_Exception_Status_FolderSync::FOLDER_SERVER_ERROR);
            
        } catch (Felamimail_Exception_IMAPInvalidCredentials $feiic) {
            Tinebase_Exception::log($feiic, null, array(
                'accountname' => $account->name
            ));
        }
    }
    
    /**
     * get Syncroton_Model_Folder folders recursively by parentFolder
     * 
     * @param Felamimail_Model_Folder $parentFolder
     * @param array $result
     * @return array of Syncroton_Model_Folder
     */
    protected function _getFolders($parentFolder = NULL, &$result = array())
    {
        $globalname = ($parentFolder) ? $parentFolder->globalname : '';
        $account = $this->_getAccount();
        if (! $account) {
            return array();
        }
        
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'globalname', 'operator' => 'startswith',  'value' => $globalname),
            array('field' => 'account_id', 'operator' => 'equals',      'value' => $account->getId()),
        ));
        
        try {
            $folders = Felamimail_Controller_Folder::getInstance()->search($filter);
        } catch (Felamimail_Exception_IMAPInvalidCredentials $feiic) {
            Tinebase_Exception::log($feiic, null, array(
                'accountname' => $account->name
            ));
            return array();
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Found " . count($folders) . ' subfolders of folder "' . $globalname . '"');
        
        foreach ($folders as $folder) {
            $result[$folder->getId()] = new Syncroton_Model_Folder(array(
                'serverId'    => $folder->getId(),
                'parentId'    => ($parentFolder) ? $parentFolder->getId() : 0,
                'displayName' => $folder->localname,
                'type'        => $this->_getFolderType($folder)
            ));
            
            if ($folder->has_children) {
                $this->_getFolders($folder, $result);
            }
        }
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::moveItem()
     */
    public function moveItem($srcFolderId, $serverId, $dstFolderId)
    {
        $filter = new Felamimail_Model_MessageFilter(array(
            array(
                'field'     => 'id',
                'operator'  => 'equals',
                'value'     => $serverId
            )
        ));
        
        Felamimail_Controller_Message_Move::getInstance()->moveMessages($filter, $dstFolderId);
        
        return $serverId;
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
     * @param Felamimail_Model_Folder $folder
     */
    protected function _getFolderType(Felamimail_Model_Folder $folder)
    {
        $personalNameSpaceSuffix = null;

        $account = $this->_getAccount();
        
        // first lookup folder type by account settings ...
        if ($account) { 
            if (!empty($account->ns_personal)) {
                $personalNameSpaceSuffix = $account->ns_personal . $account->delimiter;
            }
             
            if ($account->trash_folder === $folder->globalname)  {
                return Syncroton_Command_FolderSync::FOLDERTYPE_DELETEDITEMS;
            }
            if ($account->sent_folder === $folder->globalname) {
                return Syncroton_Command_FolderSync::FOLDERTYPE_SENTMAIL;
            }
            if ($account->drafts_folder === $folder->globalname) {
                return Syncroton_Command_FolderSync::FOLDERTYPE_DRAFTS;
            }
        }
        
        switch (strtoupper($folder->localname)) {
            case 'INBOX':
                if (($personalNameSpaceSuffix . 'INBOX' === $folder->globalname) ||
                    (substr($personalNameSpaceSuffix, 0, 5) === 'INBOX' && $folder->globalname === 'INBOX') // Cyrus Prvate Namespace == 'INBOX.'
                ) {
                    return Syncroton_Command_FolderSync::FOLDERTYPE_INBOX;
                }
                
                break;
                
            case 'TRASH':
                // detect by name if not configured
                if (($account && $account->trash_folder === $personalNameSpaceSuffix) &&
                    ($personalNameSpaceSuffix . $folder->localname === $folder->globalname)
                ) {
                    return Syncroton_Command_FolderSync::FOLDERTYPE_DELETEDITEMS;
                }
                
                break;
                
            case 'SENT':
                // detect by name if not configured
                if (($account && $account->sent_folder === $personalNameSpaceSuffix) &&
                    ($personalNameSpaceSuffix . $folder->localname === $folder->globalname)
                ) {
                    return Syncroton_Command_FolderSync::FOLDERTYPE_SENTMAIL;
                }
                
                break;
                
            case 'DRAFTS':
                // detect by name if not configured
                if (($account && $account->drafts_folder === $personalNameSpaceSuffix) &&
                    ($personalNameSpaceSuffix . $folder->localname === $folder->globalname)
                ) {
                    return Syncroton_Command_FolderSync::FOLDERTYPE_DRAFTS;
                }
                
                break;
                
            case 'OUTBOX':
                // detect by name
                if ($personalNameSpaceSuffix . $folder->localname === $folder->globalname) {
                    return Syncroton_Command_FolderSync::FOLDERTYPE_OUTBOX;
                }
                
                break;
        }
        
        return Syncroton_Command_FolderSync::FOLDERTYPE_MAIL_USER_CREATED;
    }
    
    /**
     * get folder identified by $_folderId
     *
     * @param string $_folderId
     * @return string
     */
    private function getFolder($_folderId)
    {
        $folders = $this->getSupportedFolders();
        
        if(!(isset($folders[$_folderId]) || array_key_exists($_folderId, $folders))) {
            throw new ActiveSync_Exception_FolderNotFound('folder not found. ' . $_folderId);
        }
        
        return $folders[$_folderId];
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::_getContentFilter()
     */
    protected function _getContentFilter($_filterType)
    {
        $filter = parent::_getContentFilter($_filterType);
        
        if(in_array($_filterType, $this->_filterArray)) {
            $today = Tinebase_DateTime::now()->setTime(0,0,0);
                
            switch($_filterType) {
                case Syncroton_Command_Sync::FILTER_1_DAY_BACK:
                    $received = $today->subDay(1);
                    break;
                case Syncroton_Command_Sync::FILTER_3_DAYS_BACK:
                    $received = $today->subDay(3);
                    break;
                case Syncroton_Command_Sync::FILTER_1_WEEK_BACK:
                    $received = $today->subWeek(1);
                    break;
                case Syncroton_Command_Sync::FILTER_2_WEEKS_BACK:
                    $received = $today->subWeek(2);
                    break;
                case Syncroton_Command_Sync::FILTER_1_MONTH_BACK:
                    $received = $today->subMonth(1);
                    break;
            }
            
            // add period filter
            $filter->addFilter(new Tinebase_Model_Filter_DateTime('received', 'after', $received->get(Tinebase_Record_Abstract::ISO8601LONG)));
        }
        
        return $filter;
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::_addContainerFilter()
     */
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
    }
    
    /**
     * It creates faked folders that is required by Android 5 (Lollipop),
     * when it doesn't exists in IMAP
     *
     * @param array $folders
     */
    protected function _addFakeFolders(&$folders)
    {
        $requiredFolderTypes = array(
            Syncroton_Command_FolderSync::FOLDERTYPE_DRAFTS       => 'Drafts',
            Syncroton_Command_FolderSync::FOLDERTYPE_DELETEDITEMS => 'Trash',
            Syncroton_Command_FolderSync::FOLDERTYPE_SENTMAIL     => 'Sent',
            Syncroton_Command_FolderSync::FOLDERTYPE_OUTBOX       => 'Outbox'
        );
        
        $foundFolderTypes = array();
        
        foreach ($folders as $folder) {
            if ($folder->type >= Syncroton_Command_FolderSync::FOLDERTYPE_DRAFTS && $folder->type <= Syncroton_Command_FolderSync::FOLDERTYPE_OUTBOX) {
                $foundFolderTypes[] = $folder->type;
            }
        }
        
        $missingFolderTypes = array_diff(array_keys($requiredFolderTypes), $foundFolderTypes);
        
        foreach ($missingFolderTypes as $folderType) {
           $fakedServerId = $this->_fakePrefix . $folderType;
           
           $folders[$fakedServerId] = new Syncroton_Model_Folder(array(
                'serverId'      => $fakedServerId,
                'parentId'      => 0,
                'displayName'   => $requiredFolderTypes[$folderType],
                'type'          => $folderType
            ));
        }
    }
    
    /**
     * 
     * @return int     Syncroton_Command_Sync::FILTER...
     */
    public function getMaxFilterType()
    {
        return ActiveSync_Config::getInstance()->get(ActiveSync_Config::MAX_FILTER_TYPE_EMAIL);
    }
}
