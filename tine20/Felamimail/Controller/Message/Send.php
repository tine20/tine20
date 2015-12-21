<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function sendMessage(Felamimail_Model_Message $_message)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Sending message with subject ' . $_message->subject . ' to ' . print_r($_message->to, TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_message->toArray(), TRUE));
        
        // increase execution time (sending message with attachments can take a long time)
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        $account = Felamimail_Controller_Account::getInstance()->get($_message->account_id);
        try {
            $this->_resolveOriginalMessage($_message);
            $mail = $this->createMailForSending($_message, $account, $nonPrivateRecipients);
            $this->_sendMailViaTransport($mail, $account, $_message, true, $nonPrivateRecipients);
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message: ' . $e);
            $translation = Tinebase_Translation::getTranslation('Felamimail');
            $message = sprintf($translation->_('Error: %s'), $e->getMessage());
            $tesg = new Tinebase_Exception_SystemGeneric($message);
            $tesg->setTitle($translation->_('Could not send message'));
            throw $tesg;
        }
        
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
        if (is_string($originalMessageId) && strpos($originalMessageId, '_') !== FALSE ) {
            list($originalMessageId, $partId) = explode('_', $originalMessageId);
        } else if (is_array($originalMessageId)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Something strange happened. original_id is an array: ' . print_r($originalMessageId, true));
            return;
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
        
        if (is_string($_folder) && ($_folder === $sourceAccount->templates_folder || $_folder === $sourceAccount->drafts_folder)) {
            // make sure that system folder exists
            $systemFolder = $_folder === $sourceAccount->templates_folder ? Felamimail_Model_Folder::FOLDER_TEMPLATES : Felamimail_Model_Folder::FOLDER_DRAFTS;
            $folder = Felamimail_Controller_Account::getInstance()->getSystemFolder($sourceAccount, $systemFolder);
        } else if ($_folder instanceof Felamimail_Model_Folder) {
            $folder = $_folder;
        } else {
            $folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($_message->account_id, $_folder);
        }
        
        $targetAccount = ($_message->account_id == $folder->account_id) ? $sourceAccount : Felamimail_Controller_Account::getInstance()->get($folder->account_id);
        
        $mailToAppend = $this->createMailForSending($_message, $sourceAccount);
        
        $transport = new Felamimail_Transport();
        $mailAsString = $transport->getRawMessage($mailToAppend, $this->_getAdditionalHeaders($_message));
        $flags = ($folder->globalname === $targetAccount->drafts_folder) ? array(Zend_Mail_Storage::FLAG_DRAFT) : null;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Appending message ' . $_message->subject . ' to folder ' . $folder->globalname . ' in account ' . $targetAccount->name);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . 
            ' ' . $mailAsString);
        
        Felamimail_Backend_ImapFactory::factory($targetAccount)->appendMessage(
            $mailAsString,
            Felamimail_Model_Folder::encodeFolderName($folder->globalname),
            $flags
        );
        
        return $_message;
    }
    
    /**
     * Bcc recipients need to be added separately because they are removed by default
     * 
     * @param Felamimail_Model_Message $message
     * @return array
     */
    protected function _getAdditionalHeaders($message)
    {
        $additionalHeaders = ($message && ! empty($message->bcc)) ? array('Bcc' => $message->bcc) : array();
        return $additionalHeaders;
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
    protected function _sendMailViaTransport(Zend_Mail $_mail, Felamimail_Model_Account $_account, Felamimail_Model_Message $_message = null, $_saveInSent = false, $_nonPrivateRecipients = array())
    {
        $smtpConfig = $_account->getSmtpConfig();
        if (! empty($smtpConfig) && (isset($smtpConfig['hostname']) || array_key_exists('hostname', $smtpConfig))) {
            $transport = new Felamimail_Transport($smtpConfig['hostname'], $smtpConfig);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                $debugConfig = $smtpConfig;
                $whiteList = array('hostname', 'username', 'port', 'auth', 'ssl');
                foreach ($debugConfig as $key => $value) {
                    if (! in_array($key, $whiteList)) {
                        unset($debugConfig[$key]);
                    }
                }
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' About to send message via SMTP with the following config: ' . print_r($debugConfig, true));
            }
            
            Tinebase_Smtp::getInstance()->sendMessage($_mail, $transport);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' successful.');
            
            // append mail to sent folder
            if ($_saveInSent) {
                $this->_saveInSent($transport, $_account, $this->_getAdditionalHeaders($_message));
            }
            
            if ($_message !== null) {
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
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'email', 'operator' => 'in', 'value' => $_recipients)
            // OR: array('field' => 'email_home', 'operator' => 'in', 'value' => $_recipients)
        ));
        $contacts = Addressbook_Controller_Contact::getInstance()->search($filter);
        
        if (count($contacts)) {
        
            $translate = Tinebase_Translation::getTranslation($this->_applicationName);
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Adding email notes to ' . count($contacts) . ' contacts.');
            
            $truncatedBody = (extension_loaded('mbstring')) ? mb_substr($_body, 0, 4096, 'UTF-8') : substr($_body, 0, 4096);
            $noteText = $translate->_('Subject') . ':' . $_subject . "\n\n" . $translate->_('Body') . ': ' . $truncatedBody;
            
            try {
                foreach ($contacts as $contact) {
                    $note = new Tinebase_Model_Note(array(
                        'note_type_id'           => Tinebase_Notes::getInstance()->getNoteTypeByName('email')->getId(),
                        'note'                   => $noteText,
                        'record_id'              => $contact->getId(),
                        'record_model'           => 'Addressbook_Model_Contact',
                    ));
                    
                    Tinebase_Notes::getInstance()->addNote($note);
                }
            } catch (Zend_Db_Statement_Exception $zdse) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Saving note failed: ' . $noteText);
                Tinebase_Exception::log($zdse);
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
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' About to save message in sent folder (' . $sentFolder->globalname . ') ...');
            
            Felamimail_Backend_ImapFactory::factory($_account)->appendMessage(
                $mailAsString,
                Felamimail_Model_Folder::encodeFolderName($sentFolder->globalname)
            );
            
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
     * @param  mixed      $accountId
     * @param  Zend_Mail  $mail
     * @param  boolean    $saveInSent
     * @param  Felamimail_Model_Message $originalMessage
     * @return Zend_Mail
     */
    public function sendZendMail($accountId, Zend_Mail $mail, $saveInSent = false, $originalMessage = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Sending message with subject ' . $mail->getSubject() 
        );
        if ($originalMessage !== NULL) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' Original Message subject: ' . $originalMessage->subject . ' / Flag to set: ' . var_export($originalMessage->flags, TRUE)
            );
            
            // this is required for adding the reply/forward flag in _sendMailViaTransport()
            $originalMessage->original_id = $originalMessage;
        }
        
        // increase execution time (sending message with attachments can take a long time)
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        // get account
        $account = ($accountId instanceof Felamimail_Model_Account) ? $accountId : Felamimail_Controller_Account::getInstance()->get($accountId);
        
        $this->_setMailFrom($mail, $account);
        $this->_setMailHeaders($mail, $account);
        $this->_sendMailViaTransport($mail, $account, $originalMessage, $saveInSent);
        
        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
        
        return $mail;
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
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Add ' . $type . ' address: ' . $address);
                    
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
     */
    protected function _setMailHeaders(Zend_Mail $_mail, Felamimail_Model_Account $_account, Felamimail_Model_Message $_message = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting mail headers');
        
        // add user agent
        $_mail->addHeader('User-Agent', Tinebase_Core::getTineUserAgent('Email Client'));
        
        // set organization
        if (isset($_account->organization) && ! empty($_account->organization)) {
            $_mail->addHeader('Organization', $_account->organization);
        }
        
        // set message-id (we could use Zend_Mail::createMessageId() here)
        if ($_mail->getMessageId() === NULL) {
            $domainPart = substr($_account->email, strpos($_account->email, '@'));
            $uid = Tinebase_Record_Abstract::generateUID();
            $_mail->setMessageId('<' . $uid . $domainPart . '>');
        }
        
        if ($_message !== NULL) {
            if ($_message->flags && $_message->flags == Zend_Mail_Storage::FLAG_ANSWERED && $_message->original_id instanceof Felamimail_Model_Message) {
                $this->_addReplyHeaders($_message);
            }
            
            // set the header request response
            if ($_message->reading_conf) {
                $_mail->addHeader('Disposition-Notification-To', $_message->from_email);
            }
            
            // add other headers
            if (! empty($_message->headers) && is_array($_message->headers)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Adding custom headers: ' . print_r($_message->headers, TRUE));
                foreach ($_message->headers as $key => $value) {
                    $value = $this->_trimHeader($key, $value);
                    $_mail->addHeader($key, $value);
                }
            }
        }
    }
    
    /**
     * trim message headers (Zend_Mail only supports < 998 chars)
     * 
     * @param string $value
     * @return string
     */
    protected function _trimHeader($key, $value)
    {
        if (strlen($value) + strlen($key) > 998) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Trimming header ' . $key);
            
            $value = substr(trim($value), 0, (995 - strlen($key)));

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . $value);
        }
        
        return $value;
    }
    
    /**
     * set In-Reply-To and References headers
     * 
     * @param Felamimail_Model_Message $message
     * 
     * @see http://www.faqs.org/rfcs/rfc2822.html / Section 3.6.4.
     */
    protected function _addReplyHeaders(Felamimail_Model_Message $message)
    {
        $originalHeaders = Felamimail_Controller_Message::getInstance()->getMessageHeaders($message->original_id);
        if (! isset($originalHeaders['message-id'])) {
            // no message-id -> skip this
            return;
        }

        $messageHeaders = is_array($message->headers) ? $message->headers : array();
        $messageHeaders['In-Reply-To'] = $originalHeaders['message-id'];
        
        $references = '';
        if (isset($originalHeaders['references'])) {
            $references = $originalHeaders['references'] . ' ';
        } else if (isset($originalHeaders['in-reply-to'])) {
            $references = $originalHeaders['in-reply-to'] . ' ';
        }
        $references .= $originalHeaders['message-id'];
        $messageHeaders['References'] = $references;
        
        $message->headers = $messageHeaders;
    }
    
    /**
     * add attachments to mail
     *
     * @param Tinebase_Mail $_mail
     * @param Felamimail_Model_Message $_message
     * @throws Felamimail_Exception_IMAP
     */
    protected function _addAttachments(Tinebase_Mail $_mail, Felamimail_Model_Message $_message)
    {
        if (! isset($_message->attachments) || empty($_message->attachments)) {
            return;
        }

        $maxAttachmentSize = $this->_getMaxAttachmentSize();
        $totalSize = 0;

        foreach ($_message->attachments as $attachment) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Adding attachment: ' . (is_object($attachment) ? print_r($attachment->toArray(), TRUE) : print_r($attachment, TRUE)));

            if (isset($attachment['type'])
                && $attachment['type'] == Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822
                && $_message->original_id instanceof Felamimail_Model_Message
            ) {
                $part = $this->_getRfc822Attachment($attachment, $_message);

            } else if ($attachment instanceof Tinebase_Model_TempFile || isset($attachment['tempFile'])) {
                $part = $this->_getTempFileAttachment($attachment);

            } else {
                $part = $this->_getMessagePartAttachment($attachment);
            }

            if (! $part || empty($attachment['type'])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Skipping attachment ' . print_r($attachment, true));
                continue;
            }
            
            $part->setTypeAndDispositionForAttachment($attachment['type'], $attachment['name']);

            if (! empty($attachment['size'])) {
                $totalSize += $attachment['size'];
            }
            
            if ($totalSize > $maxAttachmentSize) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Current attachment size: ' . Tinebase_Helper::convertToMegabytes($totalSize) . ' MB / allowed size: '
                    . Tinebase_Helper::convertToMegabytes($maxAttachmentSize) . ' MB');
                throw new Felamimail_Exception_IMAP('Maximum attachment size exceeded. Please remove one or more attachments.');
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Adding attachment ' . $part->type);
            
            $_mail->addAttachment($part);
        }
    }

    /**
     * get attachment of type CONTENT_TYPE_MESSAGE_RFC822
     *
     * @param $attachment
     * @param $message
     * @return Zend_Mime_Part
     */
    protected function _getRfc822Attachment(&$attachment, $message)
    {
        $part = $this->getMessagePart($message->original_id, ($message->original_part_id) ? $message->original_part_id : NULL);
        $part->decodeContent();

        $attachment['name'] = $attachment['name'] . '.eml';

        return $part;
    }

    /**
     * get attachment defined by temp file
     *
     * @param $attachment
     * @return null|Zend_Mime_Part
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getTempFileAttachment(&$attachment)
    {
        $tempFileBackend = Tinebase_TempFile::getInstance();
        $tempFile = ($attachment instanceof Tinebase_Model_TempFile)
            ? $attachment
            : (((isset($attachment['tempFile']) || array_key_exists('tempFile', $attachment))) ? $tempFileBackend->get($attachment['tempFile']['id']) : NULL);

        if ($tempFile === null) {
            return null;
        }

        if (! $tempFile->path) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not find attachment.');
            return null;
        }

        // get contents from uploaded file
        $stream = fopen($tempFile->path, 'r');
        $part = new Zend_Mime_Part($stream);

        // RFC822 attachments are not encoded, set all others to ENCODING_BASE64
        $part->encoding = ($tempFile->type == Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) ? null : Zend_Mime::ENCODING_BASE64;

        $attachment['name'] = $tempFile->name;
        $attachment['type'] = $tempFile->type;

        if (! empty($tempFile->size)) {
            $attachment['size'] = $tempFile->size;
        }

        return $part;
    }

    /**
     * get attachment part defined by message id + part id
     *
     * @param $attachment
     * @return null|Zend_Mime_Part
     */
    protected function _getMessagePartAttachment(&$attachment)
    {
        if (! isset($attachment['id']) || strpos($attachment['id'], '_') === false) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' No valid message id/part id');
            return null;
        }

        // might be an attachment defined by message id + part id -> fetch this and attach
        list($messageId, $partId) = explode('_', $attachment['id']);
        $part = $this->getMessagePart($messageId, $partId);
        $part->decodeContent();

        return $part;
    }
    
    /**
     * get max attachment size for outgoing mails
     * 
     * - currently it is set to memory_limit / 10
     * - returns size in Bytes
     * 
     * @return integer
     */
    protected function _getMaxAttachmentSize()
    {
        $configuredMemoryLimit = ini_get('memory_limit');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' memory_limit = ' . $configuredMemoryLimit);
        
        if ($configuredMemoryLimit === FALSE or $configuredMemoryLimit == -1) {
            // set to a big default value
            $configuredMemoryLimit = '512M';
        }
        
        return Tinebase_Helper::convertToBytes($configuredMemoryLimit) / 10;
    }
}
