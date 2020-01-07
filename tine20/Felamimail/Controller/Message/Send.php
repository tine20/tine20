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
     * List of MassMailingPlugins the current user has access to
     *
     * @var array|null
     */
    protected $_massMailingPlugins = null;

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
        if ($_message->massMailingFlag) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                ' Sending mass mailing message with subject ' . $_message->subject);

            $this->_sendMassMailing($_message);

            return $_message;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' Sending message with subject ' . $_message->subject . ' to ' . print_r($_message->to, TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_message->toArray(), TRUE));
        
        // increase execution time (sending message with attachments can take a long time)
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(300); // 5 minutes

        $account = Felamimail_Controller_Account::getInstance()->get($_message->account_id);

        // only check send grant for shared accounts
        if ($account->type === Felamimail_Model_Account::TYPE_SHARED) {
            // TODO generalize that? Tinebase_Core::getUser()->hasGrant() anyone?
            $userGrants = Felamimail_Controller_Account::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $account);
            if (!$userGrants->{Felamimail_Model_AccountGrants::GRANT_ADD}) {
                throw new Tinebase_Exception_AccessDenied('User is not allowed to send a message with this account');
            }
        }

        try {
            $this->_resolveOriginalMessage($_message);
            $mail = $this->createMailForSending($_message, $account, $nonPrivateRecipients);
            $this->_sendMailViaTransport($mail, $account, $_message, true);
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message: ' . $e);
            $translation = Tinebase_Translation::getTranslation('Felamimail');
            // TODO move handling of certain mailserver exceptions to \Tinebase_Smtp::sendMessage ?
            //   because this might happen to notification mails, too
            if (preg_match('/^501 5\.1\.3/', $e->getMessage())) {
                $messageText = $translation->_('Bad recipient address syntax');
            } else if (preg_match('/^550 5\.1\.1 <(.*?)>/', $e->getMessage(), $match)) {
                $messageText = '<' . $match[1] . '>: ' . $translation->_('Recipient address rejected');
            } else {
                $messageText = $e->getMessage();
            }
            $tesg = $this->_getErrorException($messageText);
            throw $tesg;
        }
        
        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
        
        return $_message;
    }

    /**
     * iterate over each to, clone message, send the message only to each single to, run each mass mailing plugin the
     * current user has access on each message before sending it
     *
     * @param Felamimail_Model_Message $_message
     */
    protected function _sendMassMailing(Felamimail_Model_Message $_message)
    {
        foreach ($_message->to as $to) {
            $clonedMessage = clone $_message;
            $clonedMessage->to = [$to];
            $clonedMessage->cc = [];
            $clonedMessage->bcc = [];
            $clonedMessage->massMailingFlag = false;

            $this->_runMassMailingPlugins($clonedMessage);

            $this->sendMessage($clonedMessage);
        }
    }

    /**
     * run each mass mailing plugin the current user has access to on the message
     *
     * @param Felamimail_Model_Message $_message
     */
    protected function _runMassMailingPlugins(Felamimail_Model_Message $_message)
    {
        if (null === $this->_massMailingPlugins) {
            $this->_initMassMailingPlugins();
        }

        /** @var Felamimail_Controller_MassMailingPluginInterface $plugin */
        foreach ($this->_massMailingPlugins as $plugin) {
            $plugin->prepareMassMailingMessage($_message);
        }
    }

    /**
     * load all application controllers implementing Felamimail_Controller_MassMailingPluginInterface the current user
     * has access too
     */
    protected function _initMassMailingPlugins()
    {
        $this->_massMailingPlugins = [];

        foreach (Tinebase_Core::getUser()->getApplications() as $application) {
            $class = $application->name . '_Controller';
            if (class_exists($class) && in_array(Felamimail_Controller_MassMailingPluginInterface::class,
                    class_implements($class)) && method_exists($class, 'getInstance')) {
                $this->_massMailingPlugins[] = $class::getInstance();
            }
        }
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
     * @param array flags
     * @return Felamimail_Model_Message
     */
    public function saveMessageInFolder($_folder, $_message, $_flags = [])
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
        if ($folder->globalname === $targetAccount->drafts_folder) {
            $flags = array_merge($_flags, [Zend_Mail_Storage::FLAG_DRAFT]);
        } else {
            $flags = $_flags;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Appending message ' . $_message->subject . ' to folder ' . $folder->globalname . ' in account ' . $targetAccount->name);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . 
            ' ' . $mailAsString);

        $imap = Felamimail_Backend_ImapFactory::factory($targetAccount);
        $uid = $imap->appendMessage(
            $mailAsString,
            Felamimail_Model_Folder::encodeFolderName($folder->globalname),
            $flags
        );

        if ($uid) {
            $_message->messageuid = $uid;
        }

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
        
        $this->_setMailFrom($mail, $_account, $_message);
        $_nonPrivateRecipients = $this->_setMailRecipients($mail, $_message);

        $this->_setMailHeaders($mail, $_account, $_message);
        $this->_addAttachments($mail, $_message);
        $this->_setMailBody($mail, $_message);

        return $mail;
    }
    
    /**
     * send mail via transport (smtp)
     * 
     * @param Zend_Mail $_mail
     * @param Felamimail_Model_Account $_account
     * @param boolean $_saveInSent
     * @param Felamimail_Model_Message $_message
     */
    protected function _sendMailViaTransport(Zend_Mail $_mail,
                                             Felamimail_Model_Account $_account,
                                             Felamimail_Model_Message $_message = null,
                                             $_saveInSent = false)
    {
        $smtpConfig = $_account->getSmtpConfig();
        if (empty($smtpConfig) || ! isset($smtpConfig['hostname'])) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message, no smtp config found.');
        }

        $transport = Felamimail_Transport::getNewInstance($smtpConfig['hostname'], $smtpConfig);
        $this->_logSendingConfig($smtpConfig);
        Tinebase_Smtp::getInstance()->sendMessage($_mail, $transport);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Sending successful.');

        if ($_saveInSent) {
            $sentFolder = $this->_saveInSent($transport, $_account, $this->_getAdditionalHeaders($_message));
            $this->_fileSentMessage($_message, $sentFolder);
        }

        // add reply/forward flags if set
        if ($_message && ! empty($_message->flags)
            && ($_message->flags == Zend_Mail_Storage::FLAG_ANSWERED || $_message->flags == Zend_Mail_Storage::FLAG_PASSED)
            && $_message->original_id instanceof Felamimail_Model_Message
        ) {
            Felamimail_Controller_Message_Flags::getInstance()->addFlags($_message->original_id, array($_message->flags));
        }
    }

    /**
     * @param $smtpConfig
     */
    protected function _logSendingConfig($smtpConfig)
    {
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
    }

    /**
     * file message to given location(s)
     *
     * @todo or use raw message here?
     *       but we would need to change Felamimail_Controller_Message_File::getInstance()->fileMessages ...
     *
     * @param $_message
     * @param $_sentFolder
     */
    protected function _fileSentMessage($_message, $_sentFolder)
    {
        if (! $_message || ! $_message->fileLocations || count($_message->fileLocations) === 0) {
            return;
        }

        $sentMessage = Felamimail_Controller_Message::getInstance()->fetchRecentMessageFromFolder($_sentFolder, $_message);

        if ($sentMessage) {
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Message::class, [
                ['field' => 'id', 'operator' => 'in', 'value' => [$sentMessage->getId()]]
            ]);
            $locations = $_message->fileLocations instanceof Tinebase_Record_RecordSet
                ? $_message->fileLocations
                : new Tinebase_Record_RecordSet(Felamimail_Model_MessageFileLocation::class, $_message->fileLocations, true);
            try {
                Felamimail_Controller_Message_File::getInstance()->fileMessages($filter, $locations);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        } else {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Did not find sent message for filing');
        }
    }

    /**
     * append mail to send folder
     *
     * @param Felamimail_Transport $_transport
     * @param Felamimail_Model_Account $_account
     * @param array $_additionalHeaders
     * @return Felamimail_Model_Folder
     */
    protected function _saveInSent(Felamimail_Transport_Interface $_transport, Felamimail_Model_Account $_account, $_additionalHeaders = array())
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

        return $sentFolder;
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
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' Sending message with subject "' . $mail->getSubject() . '" to ' . print_r($mail->getRecipients(), TRUE));
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
        if (strpos($_message->body, '-----BEGIN PGP MESSAGE-----') === 0) {
            $_mail->setBodyPGPMime($_message->body);
            return;
        }

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
        
        $from = $this->_getSenderName($_message, $_account);
        
        $email = ($_message !== NULL && ! empty($_message->from_email)) ? $_message->from_email : $_account->email;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set from for mail: ' . $email . ' / ' . $from);
        
        $_mail->setFrom($email, $from);
    }

    /**
     * @param $_message
     * @param $_account
     * @return string
     */
    protected function _getSenderName($_message, $_account)
    {
        $messageFrom = ($_message && ! empty($_message->from_name)) ? $_message->from_name : null;

        return $messageFrom
            ? $messageFrom
            : (isset($_account->from) && ! empty($_account->from)
                ? $_account->from
                : Tinebase_Core::getUser()->accountFullName);
    }
    
    /**
     * set mail recipients
     * 
     * @param Tinebase_Mail $_mail
     * @param Felamimail_Model_Message $_message
     * @return array
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _setMailRecipients(Zend_Mail $_mail, Felamimail_Model_Message $_message)
    {
        $nonPrivateRecipients = array();
        $punycodeConverter = $this->getPunycodeConverter();
        $invalidEmailAddresses = array();
        
        foreach (array('to', 'cc', 'bcc') as $type) {
            if (isset($_message->{$type})) {
                foreach((array) $_message->{$type} as $address) {

                    $punyCodedAddress = $punycodeConverter->encode($address);

                    if (! preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $punyCodedAddress)) {
                        $invalidEmailAddresses[] = $address;
                        continue;
                    }

                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                        . __LINE__ . ' Add ' . $type . ' address: ' . $punyCodedAddress);
                    
                    switch($type) {
                        case 'to':
                            $_mail->addTo($punyCodedAddress);
                            $nonPrivateRecipients[] = $punyCodedAddress;
                            break;
                        case 'cc':
                            $_mail->addCc($punyCodedAddress);
                            $nonPrivateRecipients[] = $punyCodedAddress;
                            break;
                        case 'bcc':
                            $_mail->addBcc($punyCodedAddress);
                            break;
                    }
                }
            }
        }

        if (count($invalidEmailAddresses) > 0) {
            $translation = Tinebase_Translation::getTranslation('Felamimail');
            $messageText = '<' . implode(',', $invalidEmailAddresses) . '>: ' . $translation->_('Invalid address format');
            throw new Tinebase_Exception_SystemGeneric($messageText);
        }
        
        return $nonPrivateRecipients;
    }

    protected function _getErrorException($messageText)
    {
        $translation = Tinebase_Translation::getTranslation('Felamimail');
        $message = sprintf($translation->_('Error: %s'), $messageText);
        $tesg = new Tinebase_Exception_SystemGeneric($message);
        $tesg->setTitle($translation->_('Could not send message'));

        return $tesg;
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

        // add reply-to
        $replyTo = $_message && ! empty($_message->reply_to)
            ? $_message->reply_to
            : (! empty($_account->reply_to) ? $_account->reply_to : null);
        if ($replyTo && preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $replyTo)) {
            $_mail->setReplyTo($replyTo, $this->_getSenderName($_message, $_account));
        }
        
        // set message-id (we could use Zend_Mail::createMessageId() here)
        if ($_mail->getMessageId() === NULL) {
            $domainPart = substr($_account->email, strpos($_account->email, '@'));
            $uid = Tinebase_Record_Abstract::generateUID();
            $_mail->setMessageId($uid . $domainPart);
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
        if (is_scalar($value) && strlen($value) + strlen($key) > 998) {
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
            try {
                $part = $this->_getAttachmentPartByType($attachment, $_message);
            } catch (Felamimail_Exception_IMAPMessageNotFound $feimnf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Skipping attachment ' . $feimnf->getMessage());
                continue;
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' ' . $teia->getMessage()
                    . ' - Skipping attachment ' . print_r($attachment, true));
                continue;
            }

            if (! $part || ! isset($attachment['type'])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Skipping attachment ' . print_r($attachment, true));
                continue;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Adding attachment: ' . (is_object($attachment) ? print_r($attachment->toArray(), TRUE) : print_r($attachment, TRUE)));

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
     * @param $attachment
     * @return null|Zend_Mime_Part
     */
    protected function _getAttachmentPartByType(&$attachment, $_message)
    {
        $part = null;

        $attachmentType = $this->_getAttachmentType($attachment, $_message);

        switch ($attachmentType) {
            case 'rfc822':
                $part = $this->_getRfc822Attachment($attachment, $_message);
                break;
            case 'systemlink_fm':
                $this->_setSystemlinkAttachment($attachment, $_message);
                break;
            case 'download_public':
            case 'download_public_fm':
                // no attachment part
                $this->_setDownloadLinkAttachment($attachment, $_message);
                break;
            case 'download_protected':
            case 'download_protected_fm':
                // no attachment part
                $this->_setDownloadLinkAttachment($attachment, $_message, /* protected */ true);
                break;
            case 'filenode':
                $part = $this->_getFileNodeAttachment($attachment);
                break;
            case 'tempfile':
                $part = $this->_getTempFileAttachment($attachment);
                break;
            case 'messagepart':
            default:
                $part = $this->_getMessagePartAttachment($attachment);
        }

        return $part;
    }

    /**
     * @param $attachment
     * @param $_message
     * @return null|string
     */
    protected function _getAttachmentType($attachment, $_message)
    {
        if (isset($attachment['type'])
            && $attachment['type'] === Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822
            && $_message->original_id
        ) {
            return 'rfc822';
        } elseif (isset($attachment['attachment_type'])) {
            if ($attachment['attachment_type'] === 'attachment') {
                if (isset($attachment['tempFile'])) {
                    return 'tempfile';
                } else if ($this->_isMessagePartAttachment($attachment)) {
                    return 'messagepart';
                } else {
                    return 'filenode';
                }
            } else {
                return $attachment['attachment_type'];
            }
        } elseif ($attachment instanceof Tinebase_Model_TempFile || isset($attachment['tempFile'])) {
            // tempfile last because we other attachment_types also have a tempfile
            return 'tempfile';
        }

        return null;
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

        // replace some chars from attachment name
        $attachment['name'] = preg_replace("/[\s'\"]*/", "", $attachment['name']) . '.eml';

        return $part;
    }

    /**
     * @param            $_attachment
     * @param            $_message
     * @param bool|false $_protected
     * @return boolean success
     */
    protected function _setDownloadLinkAttachment($_attachment, $_message, $_protected = false)
    {
        if (! Tinebase_Core::getUser()->hasRight('Filemanager', Tinebase_Acl_Rights::RUN)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' No right to run Filemanager');
            return false;
        }

        $password = $_protected && isset($_attachment['password']) ? $_attachment['password'] : '';
        $tempFile = $this->_getTempFileFromAttachment($_attachment);
        if ($tempFile) {
            $translate = Tinebase_Translation::getTranslation('Felamimail');
            $downloadLinkFolder = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL
                . '/' . Tinebase_Core::getUser()->getId()
                . '/.' . $translate->_('My Mail Download Links');
            $downloadLink = Filemanager_Controller_Node::getInstance()->createNodeWithDownloadLinkFromTempFile(
                $tempFile,
                $downloadLinkFolder,
                $password
            );
        } else {
            $node = Filemanager_Controller_Node::getInstance()->get($_attachment['id']);

            if (!Tinebase_Core::getUser()->hasGrant($node, Tinebase_Model_Grants::GRANT_PUBLISH)) {
                return false;
            }

            $downloadLink = Filemanager_Controller_DownloadLink::getInstance()->create(new Filemanager_Model_DownloadLink(array(
                'node_id'       => $node->getId(),
                'expiry_date'   => Tinebase_DateTime::now()->addDay(30)->toString(),
                'password'      => $password
            )));
        }

        $this->_insertDownloadLinkIntoMailBody($downloadLink->url, $_message);

        return true;
    }

    /**
     * @param $_attachment
     * @param $_message
     * @return bool
     */
    protected function _setSystemlinkAttachment($_attachment, $_message)
    {
        if (! Tinebase_Core::getUser()->hasRight('Filemanager', Tinebase_Acl_Rights::RUN)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' No right to run Filemanager');
            return false;
        }

        $node = Filemanager_Controller_Node::getInstance()->get($_attachment['id']);

        $this->_insertDownloadLinkIntoMailBody(Filemanager_Model_Node::getDeepLink($node), $_message);

        return true;
    }

    /**
     * @param $_link
     * @param $_message
     *
     * @TODO rethink encrypted mails handling
     */
    protected function _insertDownloadLinkIntoMailBody($_link, $_message)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Inserting download link into mail body: ' . $_link);
        }

        if ('text/html' === $_message->content_type) {
             $link = sprintf(
                '<br /><a href="%s">%s</a><br /><br />',
                $_link, urldecode($_link)
            );
             $signaturePattern = '/(<span class="felamimail-body-signature">-- )/';
        } else {
            $link = "\n" . $_link . "\n\n";
            $signaturePattern = '/($-- )/';
        }

        if (preg_match($signaturePattern, $_message->body)) {
            // insert above signature (if found)
            $_message->body = preg_replace($signaturePattern, $link . '\\1', $_message->body);
        } else {
            $_message->body .= $link;
        }
    }

    /**
     * get attachment defined by a file node
     *
     * @param $attachment
     * @return null|Zend_Mime_Part
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getFileNodeAttachment(&$attachment)
    {
        $nodeController = Filemanager_Controller_Node::getInstance();
        if (! isset($attachment['id'])) {
            throw new Tinebase_Exception_InvalidArgument('Node ID missing');
        }
        $node = $nodeController->get($attachment['id']);

        if (!Tinebase_Core::getUser()->hasGrant($node, Tinebase_Model_Grants::GRANT_DOWNLOAD)) {
            return null;
        }

        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath(
            Filemanager_Controller_Node::getInstance()->addBasePath($node->path)
        );

        if ($node) {
            $content = fopen($pathRecord->streamwrapperpath, 'r');

            $part = new Zend_Mime_Part($content);
            $encoding = Zend_Mime::ENCODING_BASE64;
            if ($node->contenttype) {
                $attachment['type'] = $node->contenttype;
                if ($attachment['type'] === Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
                    $encoding = Zend_Mime::ENCODING_8BIT;
                }
                // not relevant?
                $part->type = $node->contenttype;
            }
            $part->encoding = $encoding;

        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Could not find file node attachment');
            $part = null;
        }

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
        $tempFile = $this->_getTempFileFromAttachment($attachment);
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

        if (!isset($attachment['name']) || empty($attachment['name'])) {
            $attachment['name'] = $tempFile->name;
        }
        $attachment['type'] = $tempFile->type;

        if (! empty($tempFile->size)) {
            $attachment['size'] = $tempFile->size;
        }

        return $part;
    }

    /**
     * @param $attachment
     * @return null|Tinebase_Model_TempFile|Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getTempFileFromAttachment($attachment)
    {
        $tempFileBackend = Tinebase_TempFile::getInstance();
        $tempFile = ($attachment instanceof Tinebase_Model_TempFile)
            ? $attachment
            : (((isset($attachment['tempFile']) || array_key_exists('tempFile', $attachment))) ? $tempFileBackend->get($attachment['tempFile']['id']) : NULL);

        return $tempFile;
    }

    /**
     * get attachment part defined by message id + part id
     *
     * @param $attachment
     * @return null|Zend_Mime_Part
     */
    protected function _getMessagePartAttachment(&$attachment)
    {
        if (! $this->_isMessagePartAttachment($attachment)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' No valid message id/part id');
            return null;
        }

        // might be an attachment defined by message id + part id -> fetch this and attach
        list($messageId, $partId) = explode('_', $attachment['id']);
        $part = $this->getMessagePart($messageId, $partId);
        $part->decodeContent();

        return $part;
    }

    protected function _isMessagePartAttachment($attachment)
    {
        return isset($attachment['id']) && strpos($attachment['id'], '_') !== false;
    }
    
    /**
     * get max attachment size for outgoing mails
     * 
     * - currently it is set to memory_limit
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
        
        return Tinebase_Helper::convertToBytes($configuredMemoryLimit);
    }
}
