<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add support for message/rfc822 attachments
 * @todo        parse mail body and add <a> to telephone numbers and email addresses?
 * @todo        check html purifier config (allow some tags/attributes?)
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
     * maximum file upload size (in bytes)
     * 
     * 2097152 = 2MB
     */
    const MAX_ATTACHMENT_SIZE = '2097152';
    
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
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        // get folder_id from filter (has to be set)
        $filterValues = $this->_extractFilter($_filter);
        $folderId = $filterValues['folder_id'];
        
        if (empty($folderId) || $folderId == '/') {
            $result = new Tinebase_Record_RecordSet('Felamimail_Model_Message');
        } else {
            // update cache -> set totalcount > 0 (only if cache is incomplete?)
            $folder = $this->_cacheController->update($folderId);
            if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE
                || $folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING
            ) {
                $this->_totalcount = $folder->totalcount;
            }
        
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
            
            // add body
            $message->body = $this->_getBody($imapMessage, $message->content_type);
            
            // add header
            $message->headers = $imapMessage->getHeaders();
            
            // add attachments
            $message->attachments = $this->_getAttachments($imapMessage, $folder->account_id, $message->getId());
            
            // set \Seen flag
            if (preg_match('/\\Seen/', $message->flags) === 0) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Add \Seen flag to msg uid ' . $message->messageuid);
                $this->addFlags($message, array(Zend_Mail_Storage::FLAG_SEEN), $folder);
            }
            
            // add the complete imap message object
            $message->message = $imapMessage;
        }

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($message->toArray(), true));
        
        return $message;
    }
    
    /**
     * delete one record
     *
     * @param Tinebase_Record_Interface $_record
     * 
     * @todo allow to configure if messages should be moved to trash
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        // remove from cache db table
        parent::_deleteRecord($_record);
        
        if ($imapBackend = $this->_getBackendAndSelectFolder($_record->folder_id, $folder)) {
            // get account and trash folder name
            $account = Felamimail_Controller_Account::getInstance()->get($folder->account_id);
            $trashFolder = ($account->trash_folder && ! empty($account->trash_folder)) ? $account->trash_folder : 'Trash';
        
            // remove from server
            if ($folder->globalname == $trashFolder) {
                // only delete if in Trash
                $imapBackend->removeMessage($_record->messageuid);
            } else {
                // move to trash
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Moving message '" . $_record->subject . "' to $trashFolder.");
                try {
                    $imapBackend->moveMessage($_record->messageuid, $trashFolder);
                } catch (Zend_Mail_Storage_Exception $zmse) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                        . " Trash folder '$trashFolder' does not exist."
                        . " Deleting message."
                    );
                    $imapBackend->removeMessage($_record->messageuid);
                }
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
            $folderBackend  = new Felamimail_Backend_Folder();
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
     * @todo what has to be set in the 'In-Reply-To' header?
     * @todo set organization header (add setting to accounts)
     * @todo add name for to/cc/bcc
     */
    public function sendMessage(Felamimail_Model_Message $_message)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Sending message with subject ' . $_message->subject . ' to ' . print_r($_message->to, TRUE));

        // get account
        $account = Felamimail_Controller_Account::getInstance()->get($_message->from);
        
        // get original message
        $originalMessage = ($_message->original_id) ? $this->get($_message->original_id) : NULL;

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($_message->toArray(), TRUE));
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($account->toArray(), TRUE));
        
        // create new mail to send
        $mail = new Tinebase_Mail('UTF-8');
        
        // build mail content
        $mail->setBodyText(strip_tags(preg_replace('/\<br(\s*)?\/?\>/i', "\n", $_message->body)));
        $mail->setBodyHtml($this->_addHtmlMarkup($_message->body));
        
        // set from
        $from = (isset($account->from) && ! empty($account->from)) 
            ? $account->from 
            : substr($account->email, 0, strpos($account->email, '@'));
        $mail->setFrom($account->email, $from);
        
        // set in reply to
        if ($_message->flags && $_message->flags == Zend_Mail_Storage::FLAG_ANSWERED && $originalMessage !== NULL) {
            $mail->addHeader('In-Reply-To', $originalMessage->messageuid);
        }
        
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
        $this->_addAttachments($mail, $_message, $originalMessage);
        
        /*
        if (isset($_message->attachments)) {
            $size = 0;
            foreach ($_message->attachments as $attachment) {
                
                if ($attachment['type'] == Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
                    // add complete original message as attachment
                    $part = new Zend_Mime_Part($originalMessage->message->getContent());
                    
                    $part->filename = $attachment['name']; // ?
                    
                    //$part->disposition = Zend_Mime::ENCODING_BASE64; // is needed for attachment filenames
                    
                } else {
                    // get contents from uploaded files
                    $part = new Zend_Mime_Part(file_get_contents($attachment['path']));
                    $part->filename = $attachment['name'];
                    $part->disposition = Zend_Mime::ENCODING_BASE64; // is needed for attachment filenames
                }

                $part->encoding = Zend_Mime::ENCODING_BASE64;
                $part->type = $attachment['type'];
                
                // check size
                $size += $attachment['size'];
                if ($size > self::MAX_ATTACHMENT_SIZE) {
                    throw new Felamimail_Exception('Allowed attachment size exceeded! Tried to attach ' . $size . ' bytes.');
                }
                
                $mail->addAttachment($part);
            }
        }
        */
        
        // add user agent
        $mail->addHeader('User-Agent', 'Tine 2.0 Email Client (version ' . TINE20_CODENAME . ' - ' . TINE20_PACKAGESTRING);
        
        // set transport + send mail
        $smtpConfig = $account->getSmtpConfig();
        if (! empty($smtpConfig)) {
            $transport = new Felamimail_Transport($smtpConfig['hostname'], $smtpConfig);
            
            // send message via smtp
            Tinebase_Smtp::getInstance()->sendMessage($mail, $transport);

            // save in sent folder (account id is in from property)
            try {
                $mailAsString = $transport->getHeaders() . Zend_Mime::LINEEND . $transport->getBody();
                $sentFolder = ($account->sent_folder && ! empty($account->sent_folder)) ? $account->sent_folder : 'Sent';
                Felamimail_Backend_ImapFactory::factory($_message->from)->appendMessage($mailAsString, $sentFolder);
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Saved sent message in "' . $sentFolder . '".'
                );
            } catch (Zend_Mail_Protocol_Exception $zmpe) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                    . ' Could not save sent message in "' . $sentFolder . '".'
                    . ' Please check if a folder with this name exists.'
                );
            }
            
            // add reply/forward flags if set
            if (! empty($_message->flags) 
                && ($_message->flags == Zend_Mail_Storage::FLAG_ANSWERED || $_message->flags == Zend_Mail_Storage::FLAG_PASSED)
                && $originalMessage !== NULL
            ) {
                $this->addFlags($originalMessage, array($_message->flags));
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
    
    /**
     * get message body
     *
     * @param Felamimail_Message $_imapMessage
     * @param string $_contentType
     * @return string
     * 
     * @todo check if we should replace email addresses in all cases (what if they are already in an anchor tag?)
     */
    public function _getBody(Felamimail_Message $_imapMessage, $_contentType)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting mail body with content type: ' . $_contentType);
        
        // get html body part if multipart/alternative
        $body = '';
        if (! preg_match('/text\/plain/', $_contentType)) {
            // get html
            $body = $_imapMessage->getBody(Zend_Mime::TYPE_HTML);
        } 
        
        // get plain text if body is empty at this point
        if (empty($body) || $body == 'no text part found') {
        
            // plain text
            $body = $_imapMessage->getBody(Zend_Mime::TYPE_TEXT);

            // add anchor tag to links
            $body = $this->_replaceUriAndSpaces($body);
        }

        // purify
        $body = $this->_purifyBodyContent($body);
        
        // add anchor to email addresses (remove mailto hrefs first)
        $mailtoPattern = '/<a href="mailto:([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})"[^>]*>[^<]*<\/a>/i';
        $body = preg_replace($mailtoPattern, "\\1", $body);
        //$emailPattern = '/(?<!mailto:)([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})/i';
        $emailPattern = '/([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})/i';
        $body = preg_replace($emailPattern, "<a href=\"#\" id=\"123:\\1\" class=\"tinebase-email-link\">\\1</a>", $body);
        
        return $body;
    }
    
    /**
     * use html purifier to remove 'bad' tags/attributes from html body
     *
     * @param string $_content
     * @return string
     */
    protected function _purifyBodyContent($_content)
    {
        $purifierFilename = 'HTMLPurifier' . DIRECTORY_SEPARATOR . 'HTMLPurifier.auto.php'; 
        if (! file_exists(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $purifierFilename) ) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' HTML purifier not found. Mail body could not be purified. Proceed at your own risk!');
            return $_content;
        }
        
        $config = Tinebase_Core::getConfig();
        $path = ($config->caching && $config->caching->active && $config->caching->path) 
            ? $config->caching->path : session_save_path();

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Purifying html body. (cache path: ' . $path .')');
        
        require_once $purifierFilename;
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML', 'DefinitionID', 'purify message body contents'); 
        $config->set('HTML', 'DefinitionRev', 1);
        $config->set('Cache', 'SerializerPath', $path);

        // add target="_blank" to anchors
        $def = $config->getHTMLDefinition(true);
        $a = $def->addBlankElement('a');
        $a->attr_transform_post[] = new Felamimail_HTMLPurifier_AttrTransform_AValidator();
        
        $purifier = new HTMLPurifier($config);
        $content = $purifier->purify($_content);
        
        return $content;
    }
    
    /**
     * get attachments of message
     *
     * @param Zend_Mail_Message $_imapMessage
     * @param string $_accountId
     * @param string $_messageId
     * @return array
     * 
     * @todo make it possible to add message/rfc822 attachments
     * @todo save images as tempfiles to show them inline the mail body
     */
    protected function _getAttachments($_imapMessage, $_accountId, $_messageId)
    {
        $attachments = array();
        $messageParts = $_imapMessage->countParts();
        if ($messageParts > 1) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Get ' 
                . ($messageParts-1) . ' attachments.'
            );
            $partNumber = 2;
            while ($partNumber <= $messageParts) {
                $part = $_imapMessage->getPart($partNumber);

                $attachment = $part->getHeaders();
                if (isset($attachment['content-disposition'])) {
                    
                    if (preg_match('/message\/rfc822/', $attachment['content-type'])) {
                        // not supported yet
                        $partNumber++;
                        continue;
                    } else {
                        preg_match("/filename=\"*([a-zA-Z0-9\-\._]+)\"*/", $attachment['content-disposition'], $matches);
                        $attachment['filename']     = $matches[1];
                    }
                    
                    $attachment['partId']       = $partNumber;
                    $attachment['messageId']    = $_messageId;
                    $attachment['accountId']    = $_accountId;
                    $attachment['size']         = $part->getSize();
                                        
                    $attachments[] = $attachment; 
                    
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' adding attachment: ' . print_r($attachment, true));
                }
                
                $partNumber++;
            } 
        }
        
        return $attachments;
    }
    
    /**
     * add attachments to mail
     *
     * @param Tinebase_Mail $_mail
     * @param Felamimail_Model_Message $_message
     * @throws Felamimail_Exception if max attachment size exceeded or no originalMessage available for forward
     */
    protected function _addAttachments(Tinebase_Mail $_mail, Felamimail_Model_Message $_message, $_originalMessage = NULL)
    {
        if (isset($_message->attachments)) {
            $size = 0;
            foreach ($_message->attachments as $attachment) {
                
                if ($attachment['type'] == Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
                    
                    if ($_originalMessage === NULL) {
                        throw new Felamimail_Exception('No original message available for forward!');
                    }
                    
                    // add complete original message as attachment
                    $part = new Zend_Mime_Part($_originalMessage->message->getContent());
                    
                    $part->filename = $attachment['name']; // ?
                    
                    //$part->disposition = Zend_Mime::ENCODING_BASE64; // is needed for attachment filenames
                    
                } else {
                    // get contents from uploaded files
                    $part = new Zend_Mime_Part(file_get_contents($attachment['path']));
                    $part->filename = $attachment['name'];
                    $part->disposition = Zend_Mime::ENCODING_BASE64; // is needed for attachment filenames
                }

                $part->encoding = Zend_Mime::ENCODING_BASE64;
                $part->type = $attachment['type'];
                
                // check size
                $size += $attachment['size'];
                if ($size > self::MAX_ATTACHMENT_SIZE) {
                    throw new Felamimail_Exception('Allowed attachment size exceeded! Tried to attach ' . $size . ' bytes.');
                }
                
                $_mail->addAttachment($part);
            }
        }
    }

    /**
     * replace uris with links and more than one space with &nbsp;
     *
     * @param string $_content
     * @return string
     */
    protected function _replaceUriAndSpaces($_content) 
    {
        // uris
        $pattern = '@(http://|https://|ftp://|mailto:|news:)([^\s<>]+)@';
        $result = preg_replace($pattern, "<a href=\"\\1\\2\">\\1\\2</a>", $_content);
        
        // spaces
        $result = preg_replace('/( {2,}|^ )/em', 'str_repeat("&nbsp;", strlen("\1"))', $result);
        
        return $result;
    }
}
