<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * send message controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message_Send extends Felamimail_Controller_Message
{
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Message_Send
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() 
    {
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();
        $this->_currentAccount = Tinebase_Core::getUser();
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
     * @return Felamimail_Controller_Message_Send
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {            
            self::$_instance = new Felamimail_Controller_Message_Send();
        }
        
        return self::$_instance;
    }
    
    /**
     * send one message through smtp
     * 
     * @param Felamimail_Model_Message $_message
     * @return Felamimail_Model_Message
     */
    public function sendMessage(Felamimail_Model_Message $_message)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Sending message with subject ' . $_message->subject . ' to ' . print_r($_message->to, TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_message->toArray(), TRUE));
        
        // increase execution time (sending message with attachments can take a long time)
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        $account = Felamimail_Controller_Account::getInstance()->get($_message->account_id);
        $this->_resolveOriginalMessage($_message);

        $mail = $this->createMailForSending($_message, $account, $nonPrivateRecipients);
        $this->_sendMailViaTransport($mail, $account, $_message, true, $nonPrivateRecipients);
        
        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
        
        return $_message;
    }
    
    /**
     * places a Felamimail_Model_Message in original_id field of given message (if it had an original_id set)
     * 
     * @param Felamimail_Model_Message $_message
     */
    protected function _resolveOriginalMessage(Felamimail_Model_Message $_message)
    {
        if (! $_message->original_id || $_message->original_id instanceof Felamimail_Model_Message) {
            return;
        }
        
        $originalMessageId = $_message->original_id;
        if (strpos($originalMessageId, '_') !== FALSE ) {
            list($originalMessageId, $partId) = explode('_', $originalMessageId);
        } else {
            $partId = NULL;
        }
        
        try {
            $originalMessage = ($originalMessageId) ? $this->get($originalMessageId) : NULL;
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Did not find original message (' . $originalMessageId . ')');
            $originalMessage = NULL;
        }
        
        $_message->original_id      = $originalMessage;
        $_message->original_part_id = $partId;
    }
    
    /**
     * save message in folder (target folder can be within a different account)
     * 
     * @param string|Felamimail_Model_Folder $_folder globalname or folder record
     * @param Felamimail_Model_Message $_message
     * @return Felamimail_Model_Message
     */
    public function saveMessageInFolder($_folder, $_message)
    {
        $sourceAccount = Felamimail_Controller_Account::getInstance()->get($_message->account_id);
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($_message->account_id, $_folder);
        $targetAccount = ($_message->account_id == $folder->account_id) ? $sourceAccount : Felamimail_Controller_Account::getInstance()->get($folder->account_id);
        
        $mailToAppend = $this->createMailForSending($_message, $sourceAccount);
        
        $transport = new Felamimail_Transport();
        $mailAsString = $transport->getRawMessage($mailToAppend);
        $flags = ($folder->globalname === $targetAccount->drafts_folder) ? array(Zend_Mail_Storage::FLAG_DRAFT) : null;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Appending message ' . $_message->subject . ' to folder ' . $folder->globalname . ' in account ' . $targetAccount->name);
        Felamimail_Backend_ImapFactory::factory($targetAccount)->appendMessage($mailAsString, $folder->globalname, $flags);
        
        return $_message;
    }

    /**
     * create new mail for sending via SMTP
     * 
     * @param Felamimail_Model_Message $_message
     * @param Felamimail_Model_Account $_account
     * @param array $_nonPrivateRecipients
     * @return Tinebase_Mail
     */
    public function createMailForSending(Felamimail_Model_Message $_message, Felamimail_Model_Account $_account, &$_nonPrivateRecipients = array())
    {
        // create new mail to send
        $mail = new Tinebase_Mail('UTF-8');
        $mail->setSubject($_message->subject);
        
        $this->_setMailBody($mail, $_message);
        $this->_setMailFrom($mail, $_account, $_message);
        $_nonPrivateRecipients = $this->_setMailRecipients($mail, $_message);
        $this->_setMailHeaders($mail, $_account, $_message);
        
        $this->_addAttachments($mail, $_message);
        
        return $mail;
    }
    
    /**
     * send mail via transport (smtp)
     * 
     * @param Zend_Mail $_mail
     * @param Felamimail_Model_Account $_account
     * @param boolean $_saveInSent
     * @param Felamimail_Model_Message $_message
     * @param array $_nonPrivateRecipients
     */
    protected function _sendMailViaTransport(Zend_Mail $_mail, Felamimail_Model_Account $_account, Felamimail_Model_Message $_message = NULL, $_saveInSent = false, $_nonPrivateRecipients = array())
    {
        $smtpConfig = $_account->getSmtpConfig();
        if (! empty($smtpConfig) && array_key_exists('hostname', $smtpConfig)) {
            $transport = new Felamimail_Transport($smtpConfig['hostname'], $smtpConfig);
            
            // send message via smtp
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' About to send message via SMTP ...');
            Tinebase_Smtp::getInstance()->sendMessage($_mail, $transport);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' successful.');
            
            // append mail to sent folder
            if ($_saveInSent) {
                $this->_saveInSent($transport, $_account, ($_message !== NULL) ? array('Bcc' => $_message->bcc) : array());
            }
            
            if ($_message !== NULL) {
                // add reply/forward flags if set
                if (! empty($_message->flags) 
                    && ($_message->flags == Zend_Mail_Storage::FLAG_ANSWERED || $_message->flags == Zend_Mail_Storage::FLAG_PASSED)
                    && $_message->original_id instanceof Felamimail_Model_Message
                ) {
                    Felamimail_Controller_Message_Flags::getInstance()->addFlags($_message->original_id, array($_message->flags));
                }
    
                // add email notes to contacts (only to/cc)
                if ($_message->note) {
                    $this->_addEmailNote($_nonPrivateRecipients, $_message->subject, $_message->getPlainTextBody());
                }
            }
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message, no smtp config found.');
        }
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
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_recipients, TRUE));
        
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
        } else {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Found no contacts to add notes to.');
        }
    }
    
    /**
     * append mail to send folder
     * 
     * @param Felamimail_Transport $_transport
     * @param Felamimail_Model_Account $_account
     * @param array $_additionalHeaders
     * @return void
     */
    protected function _saveInSent(Felamimail_Transport $_transport, Felamimail_Model_Account $_account, $_additionalHeaders = array())
    {
        try {
            $mailAsString = $_transport->getRawMessage(NULL, $_additionalHeaders);
            $sentFolder = Felamimail_Controller_Account::getInstance()->getSystemFolder($_account, Felamimail_Model_Folder::FOLDER_SENT);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' About to save message in sent folder (' . $sentFolder->globalname . ') ...');
            Felamimail_Backend_ImapFactory::factory($_account)->appendMessage($mailAsString, $sentFolder->globalname);
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Saved sent message in "' . $sentFolder->globalname . '".'
            );
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                . ' Could not save sent message in "' . $sentFolder->globalname . '".'
                . ' Please check if a folder with this name exists.'
                . '(' . $zmpe->getMessage() . ')'
            );
        } catch (Zend_Mail_Storage_Exception $zmse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                . ' Could not save sent message in "' . $sentFolder->globalname . '".'
                . ' Please check if a folder with this name exists.'
                . '(' . $zmse->getMessage() . ')'
            );
        }
    }
    
    /**
     * send Zend_Mail message via smtp
     * 
     * @param  mixed      $_accountId
     * @param  Zend_Mail  $_message
     * @param  bool       $_saveInSent
     * @return Zend_Mail
     */
    public function sendZendMail($_accountId, Zend_Mail $_mail, $_saveInSent = false)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Sending message with subject ' . $_mail->getSubject() 
        );

        // increase execution time (sending message with attachments can take a long time)
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        // get account
        $account = ($_accountId instanceof Felamimail_Model_Account) ? $_accountId : Felamimail_Controller_Account::getInstance()->get($_accountId);
        
        $this->_setMailFrom($_mail, $account);
        $this->_setMailHeaders($_mail, $account);
        $this->_sendMailViaTransport($_mail, $account, NULL, $_saveInSent);
        
        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
        
        return $_mail;
    }
    
    /**
     * set mail body
     * 
     * @param Tinebase_Mail $_mail
     * @param Felamimail_Model_Message $_message
     */
    protected function _setMailBody(Tinebase_Mail $_mail, Felamimail_Model_Message $_message)
    {
        if ($_message->content_type == Felamimail_Model_Message::CONTENT_TYPE_HTML) {
            $_mail->setBodyHtml(Felamimail_Message::addHtmlMarkup($_message->body));
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $_mail->getBodyHtml(TRUE));
        }
        
        $plainBodyText = $_message->getPlainTextBody();
        $_mail->setBodyText($plainBodyText);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $_mail->getBodyText(TRUE));
    }
    
    /**
     * set from in mail to be sent
     * 
     * @param Tinebase_Mail $_mail
     * @param Felamimail_Model_Account $_account
     * @param Felamimail_Model_Message $_message
     */
    protected function _setMailFrom(Zend_Mail $_mail, Felamimail_Model_Account $_account, Felamimail_Model_Message $_message = NULL)
    {
        $_mail->clearFrom();
        
        $from = (isset($_account->from) && ! empty($_account->from)) 
            ? $_account->from 
            : Tinebase_Core::getUser()->accountFullName;
        
        $email = ($_message !== NULL && ! empty($_message->from_email)) ? $_message->from_email : $_account->email;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set from for mail: ' . $email . ' / ' . $from);
        
        $_mail->setFrom($email, $from);
    }
    
    /**
     * set mail recipients
     * 
     * @param Tinebase_Mail $_mail
     * @param Felamimail_Model_Message $_message
     * @return array
     */
    protected function _setMailRecipients(Zend_Mail $_mail, Felamimail_Model_Message $_message)
    {
        $nonPrivateRecipients = array();
        $punycodeConverter = $this->getPunycodeConverter();
        
        foreach (array('to', 'cc', 'bcc') as $type) {
            if (isset($_message->{$type})) {
                foreach((array) $_message->{$type} as $address) {
                    
                    $address = $punycodeConverter->encode($address);
                    
                    switch($type) {
                        case 'to':
                            $_mail->addTo($address);
                            $nonPrivateRecipients[] = $address;
                            break;
                        case 'cc':
                            $_mail->addCc($address);
                            $nonPrivateRecipients[] = $address;
                            break;
                        case 'bcc':
                            $_mail->addBcc($address);
                            break;
                    }
                }
            }
        }
        
        return $nonPrivateRecipients;
    }
    
    /**
     * set headers in mail to be sent
     * 
     * @param Tinebase_Mail $_mail
     * @param Felamimail_Model_Account $_account
     * @param Felamimail_Model_Message $_message
     * 
     * @todo what has to be set in the 'In-Reply-To' header?
     */
    protected function _setMailHeaders(Zend_Mail $_mail, Felamimail_Model_Account $_account, Felamimail_Model_Message $_message = NULL)
    {
        // add user agent
        $_mail->addHeader('User-Agent', 'Tine 2.0 Email Client (version ' . TINE20_CODENAME . ' - ' . TINE20_PACKAGESTRING . ')');
        
        // set organization
        if (isset($_account->organization) && ! empty($_account->organization)) {
            $_mail->addHeader('Organization', $_account->organization);
        }
        
        if ($_message !== NULL) {
            // set in reply to
            if ($_message->flags && $_message->flags == Zend_Mail_Storage::FLAG_ANSWERED && $_message->original_id instanceof Felamimail_Model_Message) {
                $_mail->addHeader('In-Reply-To', $_message->original_id->messageuid);
            }
        
            // add other headers
            if (! empty($_message->headers) && is_array($_message->headers)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding custom headers: ' . print_r($_message->headers, TRUE));
                foreach ($_message->headers as $key => $value) {
                    $_mail->addHeader($key, $value);
                }
            }
        }
    }
    
    /**
     * add attachments to mail
     *
     * @param Tinebase_Mail $_mail
     * @param Felamimail_Model_Message $_message
     */
    protected function _addAttachments(Tinebase_Mail $_mail, Felamimail_Model_Message $_message)
    {
        if (isset($_message->attachments)) {
            $size = 0;
            $tempFileBackend = Tinebase_TempFile::getInstance();
            foreach ($_message->attachments as $attachment) {
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding attachment: ' . print_r($attachment, TRUE));
                
                if ($attachment['type'] == Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822 && $_message->original_id instanceof Felamimail_Model_Message) {
                    $part = $this->getMessagePart($_message->original_id, ($_message->original_part_id) ? $_message->original_part_id : NULL);
                    $part->decodeContent();
                    
                    $name = $attachment['name'] . '.eml';
                    $type = $attachment['type'];
                    
                } else {
                    $tempFile = ($attachment instanceof Tinebase_Model_TempFile) 
                        ? $attachment 
                        : ((array_key_exists('tempFile', $attachment)) ? $tempFileBackend->get($attachment['tempFile']['id']) : NULL);
                    
                    if ($tempFile === NULL) {
                        continue;
                    }
                    
                    if (! $tempFile->path) {
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not find attachment.');
                        continue;
                    }
                    
                    // get contents from uploaded file
                    $stream = fopen($tempFile->path, 'r');
                    $part = new Zend_Mime_Part($stream);
                    
                    // RFC822 attachments are not encoded, set all others to ENCODING_BASE64
                    $part->encoding = ($tempFile->type == Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) ? null : Zend_Mime::ENCODING_BASE64;
                    
                    $name = $tempFile->name;
                    $type = $tempFile->type;
                }
                
                $part->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                $part->filename = $name;
                $part->type = $type . '; name="' . $name . '"';
                
                $_mail->addAttachment($part);
            }
        }
    }
}
