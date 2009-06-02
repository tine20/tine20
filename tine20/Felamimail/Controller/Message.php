<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add acl
 */

/**
 * message controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message extends Tinebase_Controller_Record_Abstract
{
    /**
     * imap flags to constants translation
     * @var array
     */
    protected static $_allowedFlags = array('\Answered' => Zend_Mail_Storage::FLAG_ANSWERED,
                                            '\Seen'     => Zend_Mail_Storage::FLAG_SEEN,
                                            '\Deleted'  => Zend_Mail_Storage::FLAG_DELETED,
                                            '\Draft'    => Zend_Mail_Storage::FLAG_DRAFT,
                                            '\Flagged'  => Zend_Mail_Storage::FLAG_FLAGGED);
    
    /**
     * totalcount of messages in folder
     *
     * @var integer
     */
    protected $_totalcount = 0;
    
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * holdes the instance of the singleton
     *
     * @var Felamimail_Controller_Message
     */
    private static $_instance = NULL;
    
    /**
     * cache controller
     *
     * @var Felamimail_Controller_Cache
     */
    protected $_cacheController = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_modelName = 'Felamimail_Model_Message';
        $this->_doContainerACLChecks = FALSE;
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();
        
        $this->_currentAccount = Tinebase_Core::getUser();
        
        $this->_cacheController = Felamimail_Controller_Cache::getInstance();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Message
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {            
            self::$_instance = new Felamimail_Controller_Message();
        }
        
        return self::$_instance;
    }
    
    /************************* overwritten funcs *************************/
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @return Tinebase_Record_RecordSet
     * 
     * @todo add support for multiple folders
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        // get folder_id from filter (has to be set)
        $filterValues = $this->_extractFilter($_filter);
        $folderId = $filterValues['folder_id'];
        
        if (empty($folderId) || $folderId == '/') {
            $result = new Tinebase_Record_RecordSet('Felamimail_Model_Message');
        } else {
            // update cache -> set totalcount > 0 only if cache is incomplete
            $this->_totalcount = $this->_cacheController->update($folderId);
        
            $result = parent::search($_filter, $_pagination);
        }
        
        return $result;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        // get folder_id from filter (has to be set)
        $filterValues = $this->_extractFilter($_filter);
        
        if (empty($filterValues['folder_id'])) {
            $result = 0;
        } elseif (! empty($this->_totalcount)) {
            // cache is incomplete but we want to show the total number of messages in mailbox folder
            $result = $this->_totalcount;
        } else {
            $result = parent::searchCount($_filter);
        }
            
        return $result;
    }
    
    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @return Tinebase_Record_Interface
     */
    public function get($_id, $_containerId = NULL)
    {
        $message = parent::get($_id);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting message ' . $message->subject);
        
        if ($imapBackend = $this->_getBackendAndSelectFolder($message->folder_id, $folder)) {
            
            $imapMessage = $imapBackend->getMessage($message->messageuid);
            
            /********* add body ****************/
            
            $message->body = $imapMessage->getBody(Zend_Mime::TYPE_TEXT);
            
            /********* add header **************/
            
            $message->headers = $imapMessage->getHeaders();
            
            /********* add attachments *********/
            
            $attachments   = array();
            $messageParts = $imapMessage->countParts();
            if ( $messageParts > 1) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Get ' 
                    . $messageParts-1 . ' attachments.'
                );
                $partNumber = 2;
                while ($partNumber <= $messageParts) {
                    $part = $imapMessage->getPart($partNumber);

                    $attachment = $part->getHeaders();
                    if (isset($attachment['content-disposition'])) {
                        preg_match("/filename=\"([a-zA-Z0-9\-\._]+)\"/", $attachment['content-disposition'], $matches);
                        
                        $attachment['filename']     = $matches[1];
                        $attachment['partId']       = $partNumber;
                        $attachment['messageId']    = $message->getId();
                        $attachment['accountId']    = $folder->account_id;
                        $attachment['size']         = $part->getSize();
                                            
                        $attachments[] = $attachment; 
                    }
                    
                    $partNumber++;
                } 
            }
            $message->attachments = $attachments;
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($message->toArray(), true));
            
            // set \Seen flag
            if (preg_match('/\\Seen/', $message->flags) === 0) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Add \Seen flag to msg uid ' . $message->messageuid);
                $this->addFlags($message, array(Zend_Mail_Storage::FLAG_SEEN), $folder);
            }
        }
        
        return $message;
    }
    
    /**
     * delete one record
     *
     * @param Tinebase_Record_Interface $_record
     * 
     * @todo allow to configure Trash folder name (as account option) and if messages should be moved there
     * @todo always assume that a trash folder exists?
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        // remove from cache db table
        parent::_deleteRecord($_record);
        
        if ($imapBackend = $this->_getBackendAndSelectFolder($_record->folder_id, $folder)) {
            // remove from server
            if ($folder->globalname == 'Trash') {
                // only delete if in Trash
                $imapBackend->removeMessage($_record->messageuid);
            } else {
                // move to trash
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Moving message '" . $_record->subject . "' to Trash.");
                $imapBackend->moveMessage($_record->messageuid, 'Trash');
            }
        }
    }
    
    /************************* other public funcs *************************/
    
    /**
     * add flags to message
     *
     * @param Felamimail_Model_Message  $_message
     * @param array                     $_flags
     * @param Felamimail_Model_Folder   $_folder [optional]
     */
    public function addFlags($_message, $_flags, $_folder = NULL)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Add flags: ' . print_r($_flags, TRUE));
        
        // save each flag in backend, cache db and message record
        if ($imapBackend = $this->_getBackendAndSelectFolder($_message->folder_id, $_folder)) {
            $imapBackend->addFlags($_message->messageuid, array_intersect($_flags, array_keys(self::$_allowedFlags)));
            foreach ($_flags as $flag) {
                $_message->flags .= ' ' . $flag;
                $this->_backend->addFlag($_message, $flag);
            }
        }
    }
    
    /**
     * clear message flag(s)
     *
     * @param Felamimail_Model_Message  $_message
     * @param array                     $_flags
     * @param Felamimail_Model_Folder   $_folder [optional]
     */
    public function clearFlags($_message, $_flags, $_folder = NULL)
    {
        // remove flag in imap backend, cache db and message record
        if ($imapBackend = $this->_getBackendAndSelectFolder($_message->folder_id, $_folder)) {
            $imapBackend->clearFlags($_message->messageuid, $_flags);
            foreach ($_flags as $flag) {
                $this->_backend->clearFlag($_message->getId(), $flag);
            }
        }
    }
    
    /**
     * move messages to folder
     *
     * @param array $_ids
     * @param string $_folderId
     * @return boolean success
     */
    public function moveMessages($_ids, $_folderId)
    {
        if ($imapBackend = $this->_getBackendAndSelectFolder($_folderId, $folder, FALSE)) {
            $messages = $this->_backend->getMultiple($_ids);
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' Moving ' . count($messages) . ' messages to folder ' . $folder->globalname);
            
            // select source folder
            $firstMessage = $messages->getFirstRecord();
            $sourceFolder = $folderBackend->get($firstMessage->folder_id);
            if($imapBackend->getCurrentFolder() != $sourceFolder->globalname) {
                $imapBackend->selectFolder($sourceFolder->globalname);
            }
            
            foreach ($messages as $message) {
                $imapBackend->moveMessage($message->messageuid, $folder->globalname);
            }
            
            // remove from cache db table
            $this->_backend->delete($_ids);
        }
                
        return TRUE;
    }
    
    /**
     * send one message through smtp
     * 
     * @param Felamimail_Model_Message $_message
     * 
     * @todo set In-Reply-To header for replies (which message id?)
     * @todo add smtp host from account settings
     * @todo add name for to/cc/bcc
     * @todo add max attachment size check?
     */
    public function sendMessage(Felamimail_Model_Message $_message)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Sending message with subject ' . $_message->subject . ' to ' . print_r($_message->to, TRUE));

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($_message->toArray(), TRUE));
                
        // get account
        $account = Felamimail_Controller_Account::getInstance()->get($_message->from);
        
        // create new mail to send
        $mail = new Tinebase_Mail();
        
        // build mail content
        $mail->setBodyText(strip_tags(preg_replace('/\<br(\s*)?\/?\>/i', "\n", $_message->body)), 'UTF-8');
        $mail->setBodyHtml($this->_addHtmlMarkup($_message->body), 'UTF-8');
        
        // set from
        $from = (isset($account->from) && ! empty($account->from)) 
            ? $account->from 
            : substr($account->email, 0, strpos($account->email, '@'));
        $mail->setFrom($account->email, $from);
        
        // add recipients
        if (isset($_message->to)) {
            foreach ($_message->to as $to) {
                $mail->addTo($to, $to);
            }
        }
        if (isset($_message->cc)) {
            foreach ($_message->cc as $cc) {
                $mail->addCc($cc, $cc);
            }
        }
        if (isset($_message->bcc)) {
            foreach ($_message->bcc as $bcc) {
                $mail->addBcc($bcc, $bcc);
            }
        }
        
        // set subject
        $mail->setSubject($_message->subject);
        
        // add attachments
        if (isset($_message->attachments)) {
            foreach ($_message->attachments as $attachment) {
                $part = new Zend_Mime_Part(file_get_contents($attachment['path']));
                $part->type = $attachment['type'];
                $part->filename = $attachment['name'];
                $part->encoding = Zend_Mime::ENCODING_BASE64;
                $part->disposition = Zend_Mime::ENCODING_BASE64; //?
                
                $mail->addAttachment($part);
            }
        }
        
        // set transport + send mail
        if (isset(Tinebase_Core::getConfig()->imap->smtp)) {
            $smtpConfig = Tinebase_Core::getConfig()->imap->smtp->toArray();
            $transport = new Felamimail_Transport($smtpConfig['hostname'], $smtpConfig);
            
            // send message via smtp
            Tinebase_Smtp::getInstance()->sendMessage($mail, $transport);

            // save in sent folder (account id is in from property)
            $mailAsString = $transport->getHeaders() . Zend_Mime::LINEEND . $transport->getBody();
            Felamimail_Backend_ImapFactory::factory($_message->from)->appendMessage($mailAsString, 'Sent');
            
            // add reply/forward flags if set
            if (! empty($_message->flags) && 
                ! empty($_message->id) &&
                ($_message->flags == Zend_Mail_Storage::FLAG_ANSWERED || $_message->flags == Zend_Mail_Storage::FLAG_PASSED)
            ) {
                $message = $this->get($_message->id);
                $this->addFlags($message, array($_message->flags));
            }
        }
        
        return $_message;
    }
    
    /**
     * get content of a message part
     *
     * @param string $_id
     * @param integer $_partId
     * @return Zend_Mail_Part|NULL
     */
    public function getMessagePart($_id, $_partId)
    {
        $result         = NULL;
        $message        = parent::get($_id);
        
        if ($imapBackend = $this->_getBackendAndSelectFolder($message->folder_id)) {
            $imapMessage            = $imapBackend->getMessage($message->messageuid);
            $result                 = $imapMessage->getPart($_partId);
        }
        
        return $result;
    }
    
    /************************* protected funcs *************************/
    
    /**
     * get imap backend and folder (and select folder)
     *
     * @param string                    $_folderId
     * @param Felamimail_Backend_Folder &$_folder
     * @param boolean                   $_select
     * @return NULL|Felamimail_Backend_Imap
     */
    protected function _getBackendAndSelectFolder($_folderId = NULL, &$_folder = NULL, $_select = TRUE)
    {
        $imapBackend = NULL;
        
        if ($_folder === NULL || empty($_folder)) {
            $folderBackend  = new Felamimail_Backend_Folder();
            $_folder = $folderBackend->get($_folderId);
        }
        
        try {
            $imapBackend = Felamimail_Backend_ImapFactory::factory($_folder->account_id);
            if ($_select && $imapBackend->getCurrentFolder() != $_folder->globalname) {
                $backendFolderValues = $imapBackend->selectFolder($_folder->globalname);
            }
            
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            // no imap connection
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
        }
        
        return $imapBackend;
    }
    
    /**
     * extract values from folder filter
     *
     * @param Felamimail_Model_MessageFilter $_filter
     * @return array (assoc) with filter values
     */
    protected function _extractFilter(Felamimail_Model_MessageFilter $_filter)
    {
        //$result = array('accountId' => 'default', 'folder' => '');
        $result = array('folder_id' => '');
        
        $filters = $_filter->getFilterObjects();
        foreach($filters as $filter) {
            if (in_array($filter->getField(), array_keys($result))) {
                $result[$filter->getField()] = $filter->getValue();
            }
        }
        
        return $result;
    }

    /**
     * add html markup to message body
     *
     * @param string $_body
     * @return string
     * 
     * @todo put this somewhere else (views?)?
     */
    protected function _addHtmlMarkup($_body)
    {
        $result = '<html>'
            . '<head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
            . '<title></title>'
            . '<style type="text/css">'
                . '.felamimail-body-blockquote {'
                    . 'margin: 5px 10px 0 3px;'
                    . 'padding-left: 10px;'
                    . 'border-left: 2px solid #000088;'
                . '} '
                . '.felamimail-body-signature {'
                    . 'font-size: 9px;'
                    . 'color: #bbbbbb;'
                . '} '
            . '</style>'
            . '</head>'
            . '<body>'
            . $_body
            . '</body></html>';
            
        return $result;
    }
}
