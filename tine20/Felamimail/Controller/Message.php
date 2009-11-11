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
 * @todo        parse mail body and add <a> to telephone numbers?
 * @todo        check html purifier config (allow some tags/attributes?)
 * @todo        improve handling of BIG (rfc822) messages (don't read the whole content?) -> php://temp stream
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
     * 0 -> max size = memory limit
     * 2097152 = 2MB
     */
    //const MAX_ATTACHMENT_SIZE = 2097152;
    const MAX_ATTACHMENT_SIZE = 0;
    
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
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * holds the instance of the singleton
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
        
            if ($folder->globalname == $trashFolder) {
                
                // only delete if in Trash
                try {
                    $imapBackend->removeMessage($_record->messageuid);
                } catch (Zend_Mail_Storage_Exception $zmse) {
                    Tinebase_Core::getLogger()->warn(
                        __METHOD__ . '::' . __LINE__ 
                        . ' Could not delete message. Maybe it has already been deleted. Message: ' 
                        . $zmse->getMessage()
                    );
                }
                
            } else {
                try {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Moving message '" . $_record->messageuid . "' to $trashFolder.");
                    
                    // move to trash folder
                    $imapBackend->moveMessage($_record->messageuid, $trashFolder);
                    
                } catch (Zend_Mail_Storage_Exception $zmse) {
                    
                    if ($zmse->getMessage() == 'cannot copy message, does the folder exist?') {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                            . " Trash folder '$trashFolder' does not exist. " 
                            . " Deleting message instead."
                        );
                        try {
                            $imapBackend->removeMessage($_record->messageuid);
                        } catch (Zend_Mail_Storage_Exception $zmseRemove) {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . $zmseRemove->getMessage());
                        }
                    } else {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . $zmse->getMessage());
                    }
                }
            }
        }
    }
    
    /************************* other public funcs *************************/
    
    /**
     * get complete message by id
     *
     * @param string|Felamimail_Model_Message $_id
     * @param int $_containerId
     * @return Tinebase_Record_Interface
     */
    public function getCompleteMessage($_id, $_withAttachments = FALSE, $_setSeen = FALSE)
    {
        if ($_id instanceof Felamimail_Model_Message) {
            $message = $_id;
        } else {
            $message = parent::get($_id);
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Getting message ' . $message->messageuid . (($_withAttachments) ? ' (with attachments)' : ''));
        
        // increase timeout to 1 minute
        Tinebase_Core::setExecutionLifeTime(120);
        
        if ($imapBackend = $this->_getBackendAndSelectFolder($message->folder_id, $folder)) {
            
            try {
                $message->message = $imapBackend->getMessage($message->messageuid);
            } catch (Zend_Mail_Protocol_Exception $zmpe) {
                if ($zmpe->getMessage() == 'the single id was not found in response') {
                    throw new Felamimail_Exception('Message not found. Maybe it was deleted by another client.', 404);
                } else {
                    throw $zmpe;
                }
            }
            
            // get account
            $account = Felamimail_Controller_Account::getInstance()->get($folder->account_id);
            
            // add body
            if ($account->display_format == 'plain') {
                $message->content_type = 'text/plain';
                $replaceUriAndEmails = FALSE;
            } else {
                $replaceUriAndEmails = TRUE;
            }
            $message->body = $this->_getBody($message->message, $message->content_type, $replaceUriAndEmails);
            
            // add header
            $message->headers = $message->message->getHeaders();
            
            if ($_withAttachments) {
                // add attachments
                $message->attachments = $this->getAttachments($message->message, $message, $folder);
            }
            
            // set \Seen flag
            if ($_setSeen && preg_match('/\\Seen/', $message->flags) === 0) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Add \Seen flag to msg uid ' . $message->messageuid);
                $this->addFlags($message, array(Zend_Mail_Storage::FLAG_SEEN), $folder);
            }
        }

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($message->toArray(), true));
        
        return $message;
    }
    
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
            try {
                $imapBackend->addFlags($_message->messageuid, array_intersect($_flags, array_keys(self::$_allowedFlags)));
            } catch (Zend_Mail_Storage_Exception $zmse) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                    . ' Could not save flags: '
                    . $zmse->getMessage()
                );
            }                
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
            
            if (! $folder->is_selectable) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                    . ' Target folder ' . $folder->globalname . ' is not selectable.'
                );
                return $folder;
            }
            
            $messages = $this->_backend->getMultiple($_ids);
            if (count($messages) == 0) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 
                    ' Messages not found: ' . print_r($_ids, TRUE));
            } else {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . 
                    ' Moving ' . count($messages) . ' messages to folder ' . $folder->globalname);
            }
            
            $folderBackend  = new Felamimail_Backend_Folder();
            foreach ($messages as $message) {
                // select source folder
                if (! isset($sourceFolder) || $sourceFolder->getId() != $message->folder_id) {
                    $sourceFolder = $folderBackend->get($message->folder_id);
                    if($imapBackend->getCurrentFolder() != $sourceFolder->globalname) {
                        $imapBackend->selectFolder($sourceFolder->globalname);
                    }
                }
                
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
        $mailBodyText = $this->_removeHtml($_message->body);
        $mail->setBodyText($mailBodyText);
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
        
        $nonPrivateRecipients = array();
        
        // add recipients
        if (isset($_message->to)) {
            foreach ($_message->to as $to) {
                $mail->addTo($to, $to);
                $nonPrivateRecipients[] = $to;
            }
        }
        if (isset($_message->cc)) {
            foreach ($_message->cc as $cc) {
                $mail->addCc($cc, $cc);
                $nonPrivateRecipients[] = $cc;
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
        
        // add user agent
        $mail->addHeader('User-Agent', 'Tine 2.0 Email Client (version ' . TINE20_CODENAME . ' - ' . TINE20_PACKAGESTRING . ')');
        
        // set organization
        if (isset($account->organization) && ! empty($account->organization)) {
            $mail->addHeader('Organization', $account->organization);
        }
        
        // set transport + send mail
        $smtpConfig = $account->getSmtpConfig();
        if (! empty($smtpConfig)) {
            $transport = new Felamimail_Transport($smtpConfig['hostname'], $smtpConfig);
            
            // send message via smtp
            Tinebase_Smtp::getInstance()->sendMessage($mail, $transport);
            
            // add email notes to contacts (only to/cc)
            if ($_message->note) {
                $this->_addEmailNote($nonPrivateRecipients, $_message->subject, $mailBodyText);
            }
        
            // append mail to sent folder 
            $this->_saveInSent($transport, $account);
            
            // add reply/forward flags if set
            if (! empty($_message->flags) 
                && ($_message->flags == Zend_Mail_Storage::FLAG_ANSWERED || $_message->flags == Zend_Mail_Storage::FLAG_PASSED)
                && $originalMessage !== NULL
            ) {
                $this->addFlags($originalMessage, array($_message->flags));
            }
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message, no smtp config found.');
        }
        
        return $_message;
    }
    
    /**
     * get message part
     *
     * @param string $_id
     * @param string $_partId (the part id, can look like this: 1.3.2 -> returns the second part of third part of first part...)
     * @return Zend_Mail_Part|NULL
     */
    public function getMessagePart($_id, $_partId)
    {
        $result         = NULL;
        $message        = parent::get($_id);
        
        if ($imapBackend = $this->_getBackendAndSelectFolder($message->folder_id)) {
            $result = $imapBackend->getMessage($message->messageuid);
            
            $parts = explode('.', $_partId); 
            
            while (! empty($parts)) {
                $headers = $result->getHeaders();
                $partId = array_shift($parts);
                
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                    . ' getting part ' . $partId . ' of ' . $headers['content-type']
                    //. print_r($headers, true)
                );                
                
                if (preg_match('/message\/rfc822/', $headers['content-type'])) {
                    $content = $this->_decodePartContent($result, $headers);
                    $result = new Felamimail_Message(array('raw' => $content));                    
                } 

                $result = $result->getPart($partId);
            }
        }
        
        return $result;
    }
    
    /**
     * get attachments of message
     *
     * @param Felamimail_Message $_imapMessage
     * @param Felamimail_Model_Message $_message
     * @param Felamimail_Model_Folder $_folder
     * @param string $_partId [optional]
     * @return array
     * 
     * @todo    save images as tempfiles to show them inline the mail body?
     * @todo    check display_format setting from account (for rfc822 mails)
     * @todo    refactor this
     */
    public function getAttachments(Felamimail_Message $_imapMessage, Felamimail_Model_Message $_message, $_folder = NULL, $_partId = NULL)
    {
        if ($_folder === NULL) {
            $folderBackend = new Felamimail_Backend_Folder();
            $_folder = $folderBackend->get($_message->folder_id); 
        } 
            
        $accountId = $_folder->account_id;
        
        $attachments = array();
        $messageParts = $_imapMessage->countParts();
        $partNumber = 1;
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Message has ' . $messageParts . ' parts.');
        
        if ($_imapMessage->isMultipart()) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Get ' 
                . ($messageParts-1) . ' attachments.'
            );
            while ($partNumber <= $messageParts) {
                
                // init new part id
                if ($_partId !== NULL) {
                    $partId = $_partId . '.' . $partNumber;
                } else {
                    $partId = $partNumber;
                }
                
                // get next part and headers
                $part = $_imapMessage->getPart($partNumber);
                $partHeaders = $part->getHeaders();
                
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Attachment content-type: ' . $partHeaders['content-type']);
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($partHeaders, true));

                if (preg_match('/message\/rfc822/', $partHeaders['content-type'])) {
                    
                    /**************** split message/rfc822 attachment **********************/
                    
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Adding message/rfc822 attachment with part id ' . $partId);
                    
                    $rfcMessage = new Felamimail_Message(array('raw' => $this->_decodePartContent($part, $partHeaders)));
                    // add body to our message
                    $_message->body = $_message->body . '<br/><hr/><br/>' . $this->_getBody($rfcMessage, $partHeaders['content-type']);
                    
                    // add attachments
                    $attachments = array_merge($attachments, $this->getAttachments($rfcMessage, $_message, $_folder, $partId));
                                         
                } else if (preg_match('/multipart\/mixed/', $partHeaders['content-type'])) {
                    
                    /**************** split multipart/mixed again **************************/
                    
                    $messageSubParts = $part->countParts();
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Getting ' . $messageSubParts .' parts of multipart/mixed message.');
                    
                    $content = '';
                    for ($i = 1; $i <= $messageSubParts; $i++) {
                        
                        $subPart = $part->getPart($i);
                        $subPartHeaders = $subPart->getHeaders();
                        
                        // @todo add rfc822 attachments here
                        if (isset($subPartHeaders['content-disposition'])) {
                            $attachments[] = $this->_getAttachmentDataFromPart($subPart, $partId . '.' . $i, $accountId, $_message->getId(), $subPartHeaders);
                            
                        } else {
                            $content = $this->_decodePartContent($subPart, $subPartHeaders);
                            $_message->body = $_message->body . '<br/><hr/><br/>' . $this->_getBody($content, $subPartHeaders['content-type']);
                        }
                    }
                    
                } else if (isset($partHeaders['content-disposition']) || preg_match('/application\/pgp\-signature/', $partHeaders['content-type'])) {
                    
                    /**************** add attachment part ***********************************/
                    
                    $attachments[] = $this->_getAttachmentDataFromPart($part, $partId, $accountId, $_message->getId(), $partHeaders);
                   
                } else {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Don\'t get attachment of content-type: ' . $partHeaders['content-type']);
                }
                
                $partNumber++;
            } 
        }
        
        return $attachments;
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
        $result = array('folder_id' => '', 'flags' => '');
        
        $filters = $_filter->toArray();
        foreach($filters as $filter) {
            if (in_array($filter['field'], array_keys($result)) || ! empty($filter['value'])) {
                $result[$filter['field']] = $filter['value'];
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
     * @param Felamimail_Message|string $_message
     * @param string $_contentType
     * @param boolean $_replaceUriAndEmails
     * @return string
     * 
     * @todo check if we should replace email addresses in all cases (what if they are already in an anchor tag?)
     */
    protected function _getBody($_message, $_contentType, $_replaceUriAndEmails = TRUE)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Getting mail body with content type: ' . $_contentType);
        
        // get html body part if multipart/alternative
        if (! preg_match('/text\/plain/', $_contentType)) {
            // get html
            if ($_message instanceof Felamimail_Message) {
                $body = $_message->getBody(Zend_Mime::TYPE_HTML);
            } else {
                $body = $_message;
            }

            // purify
            $body = $this->_purifyBodyContent($body);
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $body);
        
        // get plain text if body is empty at this point
        if (! isset($body) || $body == 'no text part found') {
        
            // plain text
            if ($_message instanceof Felamimail_Message) {
                $body = $_message->getBody(Zend_Mime::TYPE_TEXT);
            } else {
                $body = $_message;
            }

            // add anchor tag to links
            if ($_replaceUriAndEmails) {
                $body = $this->_replaceUriAndSpaces($body);
            }
        }

        if ($_replaceUriAndEmails) {
            $body = $this->_replaceEmails($body);
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $body);
        
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
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' HTML purifier not found. Mail body could not be purified. Proceed at your own risk!');
            return $_content;
        }
        
        $config = Tinebase_Core::getConfig();
        $path = ($config->caching && $config->caching->active && $config->caching->path) 
            ? $config->caching->path : Tinebase_Core::getTempDir();

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Purifying html body. (cache path: ' . $path .')');
        
        require_once $purifierFilename;
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.DefinitionID', 'purify message body contents'); 
        $config->set('HTML.DefinitionRev', 1);
        $config->set('Cache.SerializerPath', $path);

        // add target="_blank" to anchors
        $def = $config->getHTMLDefinition(true);
        $a = $def->addBlankElement('a');
        $a->attr_transform_post[] = new Felamimail_HTMLPurifier_AttrTransform_AValidator();
        
        $purifier = new HTMLPurifier($config);
        $content = $purifier->purify($_content);
        
        return $content;
    }
    
    /**
     * add attachments to mail
     *
     * @param Tinebase_Mail $_mail
     * @param Felamimail_Model_Message $_message
     * @param Felamimail_Model_Message $_originalMessage
     * @throws Felamimail_Exception if max attachment size exceeded or no originalMessage available for forward
     * 
     * @todo use php://temp for BIG attachments / messages
     */
    protected function _addAttachments(Tinebase_Mail $_mail, Felamimail_Model_Message $_message, $_originalMessage = NULL)
    {
        $maxSize = (self::MAX_ATTACHMENT_SIZE == 0) ? convertToBytes(ini_get('upload_max_filesize')) : self::MAX_ATTACHMENT_SIZE;
        
        if (isset($_message->attachments)) {
            $size = 0;
            foreach ($_message->attachments as $attachment) {
                
                if ($attachment['type'] == Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
                    
                    if ($_originalMessage === NULL) {
                        throw new Felamimail_Exception('No original message available for forward!');
                    } else {
                        $originalMessage = $this->getCompleteMessage($_originalMessage);
                    }
                    
                    // add complete original message as attachment
                    $headers = '';
                    foreach ($originalMessage->message->getHeaders() as $key => $value) {
                        $headers .= "$key: $value" . Zend_Mime::LINEEND;
                    }
                    $rawContent = $headers . Zend_Mime::LINEEND . $originalMessage->message->getContent();
                    $part = new Zend_Mime_Part($rawContent);
                    
                    //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . $rawContent);
                    
                    $part->disposition = 'attachment; filename="' . $attachment['name'] . '"';
                    $part->encoding = Zend_Mime::ENCODING_7BIT;
                    
                } else {
                    // get contents from uploaded files
                    $part = new Zend_Mime_Part(file_get_contents($attachment['path']));
                    $part->filename = $attachment['name'];
                    $part->disposition = Zend_Mime::ENCODING_BASE64; // is needed for attachment filenames
                    $part->encoding = Zend_Mime::ENCODING_BASE64;
                }
                
                $part->type = $attachment['type'] . '; name="' . $attachment['name'] . '"';
                
                // check size
                $size += $attachment['size'];
                if ($size > $maxSize) {
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
        $result = htmlentities($_content, ENT_COMPAT, 'UTF-8');
        
        // uris
        $pattern = '@(http://|https://|ftp://|mailto:|news:)([^\s<>\)]+)@';
        $result = preg_replace($pattern, "<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>", $result);
        
        // spaces
        $result = preg_replace('/( {2,}|^ )/em', 'str_repeat("&nbsp;", strlen("\1"))', $result);
        
        return $result;
    }

    /**
     * replace emails with links
     *
     * @param string $_content
     * @return string
     * 
     * @todo try to skip email address that are already embedded in an url (such as unsubscription links with ?email=blabla@aha.com) 
     */
    protected function _replaceEmails($_content) 
    {
        // add anchor to email addresses (remove mailto hrefs first)
        $mailtoPattern = '/<a[="a-z\-0-9 ]*href="mailto:([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})"[^>]*>.*<\/a>/iU';
        $result = preg_replace($mailtoPattern, "\\1", $_content);
        
        //$emailPattern = '/(?<!mailto:)([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})/i';
        $emailPattern = '/([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})/i';
        $result = preg_replace($emailPattern, "<a href=\"#\" id=\"123:\\1\" class=\"tinebase-email-link\">\\1</a>", $result);
        
        return $result;
    }
        
    /**
     * remove all html entities
     *
     * @param string $_content
     * @return string
     */
    protected function _removeHtml($_content)
    {
        $result = strip_tags(preg_replace('/\<br(\s*)?\/?\>/i', "\n", $_content));
        $result = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $result);
        $result = html_entity_decode($result, ENT_COMPAT, 'UTF-8');
        
        return $result;
    }
    
    /**
     * decode mail part content
     *
     * @param Zend_Mail_Part $_part
     * @param array $_headers
     * @return string
     */
    protected function _decodePartContent(Zend_Mail_Part $_part, $_headers = NULL)
    {
        if ($_headers === NULL) {
            $_headers = $_part->getHeaders();
        }
        
        $result = $_part->getContent();
        if (isset($_headers['content-transfer-encoding'])) {
            switch (strtolower($_headers['content-transfer-encoding'])) {
                case Zend_Mime::ENCODING_QUOTEDPRINTABLE:
                    $result = quoted_printable_decode($result);
                    break;
                case Zend_Mime::ENCODING_BASE64:
                    $result = base64_decode($result);
                    break;
            }
        }
        
        return $result;
    }
    
    /**
     * get attachment data from mail part
     *
     * @param Zend_Mail_Part $_part
     * @param integer|string $_partId
     * @param string $_accountId
     * @param string $_messageId
     * @param array $_headers [optional]
     * @return array
     * 
     */
    protected function _getAttachmentDataFromPart(Zend_Mail_Part $_part, $_partId, $_accountId, $_messageId, $_headers = NULL)
    {
        if ($_headers === NULL) {
            $_headers = $_part->getHeaders();
        }
        
        $result = array();
        
        if (! isset($_headers['content-disposition'])) {
            $_headers['content-disposition'] = $_headers['content-type'];
        }
        preg_match(Felamimail_Model_Message::ATTACHMENT_FILENAME_REGEXP, $_headers['content-disposition'], $matches);

        $result = $_headers;
        $result['filename']     = (isset($matches[1])) ? $matches[1] : $_partId . '.txt';
        
        $result['partId']       = $_partId;
        $result['messageId']    = $_messageId;
        $result['accountId']    = $_accountId;
        $result['size']         = $_part->getSize();
                            
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding attachment: ' . print_r($result, true));
        
        return $result;
    }

    /**
     * add email notes to contacts with email addresses in $_recipients
     *
     * @param array $_recipients
     * @param string $_subject
     * 
     * @todo add email home (when we have OR filters)
     * @todo add link to message in sent folder?
     */
    protected function _addEmailNote($_recipients, $_subject, $_body)
    {
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'email', 'operator' => 'in', 'value' => $_recipients)
            // OR: array('field' => 'email_home', 'operator' => 'in', 'value' => $_recipients)
        ));
        $contacts = Addressbook_Controller_Contact::getInstance()->search($filter);
        
        if (count($contacts)) {
        
            $translate = Tinebase_Translation::getTranslation($this->_applicationName);
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Adding email notes to ' . count($contacts) . ' contacts.');
            
            $noteText = $translate->_('Subject') . ':' . $_subject . "\n\n" . $translate->_('Body') . ':' . substr($_body, 0, 4096);
            
            foreach ($contacts as $contact) {
                $note = new Tinebase_Model_Note(array(
                    'note_type_id'           => Tinebase_Notes::getInstance()->getNoteTypeByName('email')->getId(),
                    'note'                   => $noteText,
                    'record_id'              => $contact->getId(),
                    'record_model'           => 'Addressbook_Model_Contact',
                ));
                
                Tinebase_Notes::getInstance()->addNote($note);
            }
        }
    }
    
    /**
     * append mail to send folder
     * @param Felamimail_Transport $_transport
     * @param Felamimail_Model_Account $_account
     * @return void
     */
    protected function _saveInSent(Felamimail_Transport $_transport, Felamimail_Model_Account $_account)
    {
        try {
            $mailAsString = $_transport->getHeaders() . Zend_Mime::LINEEND . $_transport->getBody();
            
            $sentFolder = ($_account->sent_folder && ! empty($_account->sent_folder)) ? $_account->sent_folder : 'Sent';
            Felamimail_Backend_ImapFactory::factory($_account)->appendMessage($mailAsString, $sentFolder);
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Saved sent message in "' . $sentFolder . '".'
            );
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                . ' Could not save sent message in "' . $sentFolder . '".'
                . ' Please check if a folder with this name exists.'
                . '(' . $zmpe->getMessage() . ')'
            );
        } catch (Zend_Mail_Storage_Exception $zmse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                . ' Could not save sent message in "' . $sentFolder . '".'
                . ' Please check if a folder with this name exists.'
                . '(' . $zmse->getMessage() . ')'
            );
        }
    }
}
