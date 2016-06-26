<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * send message controller for Expressomail
 *
 * @package     Expressomail
 * @subpackage  Controller
 */
class Expressomail_Controller_Message_Send extends Expressomail_Controller_Message
{
    /**
     * holds the instance of the singleton
     *
     * @var Expressomail_Controller_Message_Send
     */
    private static $_instance = NULL;
                        
    private $_sentFolder = '';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_backend = new Expressomail_Backend_Message();
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
     * @return Expressomail_Controller_Message_Send
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Expressomail_Controller_Message_Send();
        }

        return self::$_instance;
    }

    /**
     * send one message through smtp
     *
     * @param Expressomail_Model_Message $_message
     * @return Expressomail_Model_Message
     */
    public function sendMessage(Expressomail_Model_Message $_message)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Sending message with subject ' . $_message->subject . ' to ' . print_r($_message->to, TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_message->toArray(), TRUE));

        // increase execution time (sending message with attachments can take a long time)
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(300); // 5 minutes

        $account = Expressomail_Controller_Account::getInstance()->get($_message->account_id);
        $this->_resolveOriginalMessage($_message);
        $mail = $this->createMailForSending($_message, $account);
        $this->_sendMailViaTransport($mail, $account, $_message, true);

        // get an array with all recipients
        $recipients = $this->_getRecipients($_message);
        $nonPrivateRecipients = array_merge($recipients['to'], $recipients['cc']);
        $allRecipients = array_merge($recipients['bcc'], $nonPrivateRecipients);

        $config = Tinebase_Core::getConfig();
        $maxRecipients = Expressomail_Config::getInstance()->get(Expressomail_Config::MAX_CONTACT_ADD_TO_UNKNOWN);
        if (isset($config->email->maxContactAddToUnknown)){
            $maxRecipients = $config->email->maxContactAddToUnknown;
        }
        if ((count($allRecipients) <= $maxRecipients) && ($_message->add_contacts)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . ' Starting search and import of ' . count($allRecipients) . ' contacts');
            $_message->added_contacts = $this->_saveUnknownContacts($account->user_id, $allRecipients);
            Tinebase_Core::getLogger()->debug(__METHOD__ . ' Search and import completed, ' . $_message->added_contacts . ' where added');
        }

        if ($_message->note) {
            // save note to contacts
            $this->_addEmailNote($nonPrivateRecipients, $_message->subject, $_message->getPlainTextBody());
        }

        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);

        $this->removeTempFiles($_message);

        return $_message;
    }

    /**
     * save unknown contacts
     *
     * @param integer $_user_id
     * @param array $_recipients
     * @return integer
     */
    protected function _saveUnknownContacts($_user_id, $_recipients)
    {
        $result = 0;
        $_allRecipients = array();
        foreach ($_recipients as $_recipts) {
            array_push($_allRecipients, strtolower($_recipts));
        }

        try {
            $decodedFilter = array(array('field' => 'email_query', 'operator' => 'in', 'value' => $_allRecipients),
                                   array('field' => 'container_id', 'operator' => 'equals', 'value' => array('path' => '/personal/' . $_user_id)));

            $filter = new Addressbook_Model_ContactFilter($decodedFilter);

            $foundContacts = $this->_searchContacts($filter, null);

            $knownContacts = array();
            foreach ($foundContacts as $contact) {
                array_push($knownContacts, $contact['email']);
            }

            $unknownContacts = array_diff($_allRecipients, $knownContacts);

            $unknownContactsFolder = $this->_getUnknownContactsFolder($_user_id);

            foreach ($unknownContacts as $recipient) {
                //gets everything before the @
                $fullname  = preg_replace('/@.*/', '', $recipient);
                $fullname = ucwords(preg_replace('/[\-\_\.]/', ' ', $fullname));
                //cuts the email in the @, removes everythig before the first . and upercases it
                $org_name = strtoupper(preg_replace('/.*?@/', '', $recipient));
                $org_name = strtoupper(preg_replace('/\..*/', '', $org_name));

                $n_given = $fullname;
                $n_family = "";
                $pieces = explode(' ',$fullname);
                if (count($pieces) > 1) {
                    $n_given  = preg_replace('/(\s.*)/', '', $fullname);
                    $n_family = preg_replace('/.*?\s/', '', $fullname);;
                }

                $contactData = array(
                    'email'        => $recipient,
                    'n_family'     => $n_family,
                    'n_given'      => $n_given,
                    'org_name'     => $org_name,
                    'container_id' => $unknownContactsFolder->id,
                );
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " adding new contact " . print_r($contactData, true));
                $contact = new Addressbook_Model_Contact(NULL, FALSE);
                $contact->setFromArray($contactData);
                Addressbook_Controller_Contact::getInstance()->create($contact, FALSE);
                $result += 1;
            }
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not save message note: ' . $zmpe->getMessage());
            throw $zmpe;
            $result = -1;
        }

        return $result;
    }

    /**
     * Search for records matching given arguments
     *
     * @param string|array                        $_filter json encoded / array
     * @param string|array                        $_paging json encoded / array
     * @return array
     */
    protected function _searchContacts($_filter, $_paging)
    {
        $_controller = Addressbook_Controller_Contact::getInstance();
        $decodedPagination = is_array($_paging) ? $_paging : Zend_Json::decode($_paging);
        $pagination = new Tinebase_Model_Pagination($decodedPagination);

        $records = $_controller->search($_filter, $pagination, FALSE);

        return $records;
    }

    /**
     * Get (and create if it does not exist) unknown contacts folder
     *
     * @param integer $_user_id
     * @return Tinebase_Model_Container
     */
    protected function _getUnknownContactsFolder($_user_id)
    {
        $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), 'Addressbook', $_user_id, Tinebase_Model_Grants::GRANT_READ);

        $translate = Tinebase_Translation::getTranslation($this->_applicationName);
        $unknownContactsContainerName = $translate->_('Unknown Contacts');

        $unknownContactsContainer = '';
        foreach ($containers as $container) {
            if ($container->name == $unknownContactsContainerName) {
                $unknownContactsContainer = $container;
            }
        };

        if ($unknownContactsContainer=='') {
            $newContainer = new Tinebase_Model_Container(array(
                'name'              => $unknownContactsContainerName,
                'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
                'backend'           => 'Sql',
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
                'model'             => 'Addressbook_Model_Contact'
            ));
            $unknownContactsContainer = Tinebase_Container::getInstance()->addContainer($newContainer);
        }

        return $unknownContactsContainer;
    }

    /**
     * get all recipients from message
     *
     * @param Expressomail_Model_Message $_message
     * @return array
     */
    protected function _getRecipients(Expressomail_Model_Message $_message)
    {
        $allRecipients = array('to'=>array(),'cc'=>array(),'bcc'=>array());
        $punycodeConverter = $this->getPunycodeConverter();

        foreach (array('to', 'cc', 'bcc') as $type) {
            if (isset($_message->{$type})) {
                foreach((array) $_message->{$type} as $address) {
                    $address = trim($address);
                    if(strlen($address)>0)
                    {
                        $address = $punycodeConverter->encode($address);

                        switch($type) {
                            case 'to':
                                $allRecipients['to'][] = $address;
                                break;
                            case 'cc':
                                $allRecipients['cc'][] = $address;
                                break;
                            case 'bcc':
                                $allRecipients['bcc'][] = $address;
                                break;
                        }
                    }
                }
            }
        }

        return $allRecipients;
    }
    
    /**
     * places a Expressomail_Model_Message in original_id field of given message (if it had an original_id set)
     *
     * @param Expressomail_Model_Message $_message
     */
    protected function _resolveOriginalMessage(Expressomail_Model_Message $_message)
    {
        if (! $_message->original_id || $_message->original_id instanceof Expressomail_Model_Message) {
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
     * @param string|Expressomail_Model_Folder $_folder globalname or folder record
     * @param Expressomail_Model_Message $_message
     * @return Expressomail_Model_Message
     */
    public function saveMessageInFolder($_folder, $_message)
    {
        $sourceAccount = Expressomail_Controller_Account::getInstance()->get($_message->account_id);
        $folder = ($_folder instanceof Expressomail_Model_Folder) ? $_folder : Expressomail_Controller_Folder::getInstance()->getByBackendAndGlobalName($_message->account_id, $_folder);
        $targetAccount = ($_message->account_id == $folder->account_id) ? $sourceAccount : Expressomail_Controller_Account::getInstance()->get($folder->account_id);
        $this->_resolveOriginalMessage($_message);
        $mailToAppend = $this->createMailForSending($_message, $sourceAccount);

        $transport = new Expressomail_Transport();
        $mailAsString = $transport->getRawMessage($mailToAppend, $this->_getAdditionalHeaders($_message));
        $flags = ($folder->globalname === $targetAccount->drafts_folder) ? array(Zend_Mail_Storage::FLAG_DRAFT) : null;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Appending message ' . $_message->subject . ' to folder ' . $folder->globalname . ' in account ' . $targetAccount->name);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' ' . $mailAsString);
        try {
            Expressomail_Backend_ImapFactory::factory($targetAccount)->appendMessage($mailAsString, $folder->globalname, $flags);
        }
        catch (Zend_Mail_Protocol_Exception $e)
        {
            if ($folder->system_folder)
            {
                Expressomail_Backend_ImapFactory::factory($targetAccount)->createFolder($folder->localname,
                        $folder->parent);
                Expressomail_Backend_ImapFactory::factory($targetAccount)->appendMessage($mailAsString, $folder->globalname, $flags);
            }
            else {
                throw $e;
            }
        }

        // update original_id of saved message
        $res = Expressomail_Backend_ImapFactory::factory($targetAccount)->examineFolder($folder->globalname);
        $_messageid = Expressomail_Backend_Message::createMessageId($_message->account_id, $folder->id, $res['uidnext']-1);
        $_message->original_id = $_messageid;

        return $_message;
    }

    /**
     * Bcc recipients need to be added separately because they are removed by default
     *
     * @param Expressomail_Model_Message $message
     * @return array
     */
    protected function _getAdditionalHeaders($message)
    {
        //Bcc should be formated in single element array with the following format:
        //Bcc: contact1@mailadress.com,
        // contact2@mailadress.com
        $bcc = implode (",\n ", $message->bcc);
        $additionalHeaders = ($message && ! empty($message->bcc)) ? array('Bcc' => $bcc) : array();
        return $additionalHeaders;
    }
    
    protected function _processSignedMessage($rawMessage)
    {
        $headers = '';
        
        $matches = array();
        preg_match('/.*boundary="(.*)"/', $headers['content-type'], $matches);
        list(,$boundary) = $matches;
        
        $signedMail = Zend_Mime_Message::createFromMessage($rawMessage, $boundary);
        return $signedMail;
    }

    /**
     * create new mail for sending via SMTP
     *
     * @param Expressomail_Model_Message $_message
     * @param Expressomail_Model_Account $_account
     * @return Expressomail_mail
     */
    public function createMailForSending(Expressomail_Model_Message $_message, Expressomail_Model_Account $_account)
    {
        // create new mail to send
        if (!empty($_message->signedMessage))
        {
            $signedMessage = new Zend_Mail_Message(array('raw' => $_message->signedMessage));
            $mail = Expressomail_Mail::createFromZMM($signedMessage);
            
            // todo: fix date timezone ???
            
            return $mail;
        } else {
            $mail = new Expressomail_Mail('UTF-8');
        }
        
        $mail->setSubject($_message->subject);
        
        $this->_setMailBody($mail, $_message);
        $this->_setMailFrom($mail, $_account, $_message);
        $this->_setMailRecipients($mail, $_message);
        $this->_setMailHeaders($mail, $_account, $_message);

        $this->_addAttachments($mail, $_message);

        return $mail;
    }

    /**
     * send mail via transport (smtp)
     *
     * @param Zend_Mail $_mail
     * @param Expressomail_Model_Account $_account
     * @param boolean $_saveInSent
     * @param Expressomail_Model_Message $_message
     */
    protected function _sendMailViaTransport(Zend_Mail $_mail, Expressomail_Model_Account $_account, Expressomail_Model_Message $_message = NULL, $_saveInSent = false)
    {
        $smtpConfig = $_account->getSmtpConfig();
        if (! empty($smtpConfig) && array_key_exists('hostname', $smtpConfig)) {
            $transport = new Expressomail_Transport($smtpConfig['hostname'], $smtpConfig);

            // send message via smtp
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' About to send message via SMTP ...');
            Tinebase_Smtp::getInstance()->sendMessage($_mail, $transport);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' successful.');

            // append mail to sent folder
            if ($_saveInSent) {
                $this->_selectSentFolder($_message, $_account);
                $this->_saveInSent($transport, $_account, $this->_getAdditionalHeaders($_message));
            }

            if ($_message !== NULL) {
                // add reply/forward flags if set
                if (! empty($_message->flags)
                    && ($_message->flags == Zend_Mail_Storage::FLAG_ANSWERED || $_message->flags == Zend_Mail_Storage::FLAG_PASSED)
                    && $_message->original_id instanceof Expressomail_Model_Message
                ) {
                    Expressomail_Controller_Message_Flags::getInstance()->addFlags($_message->original_id, array($_message->flags));
                }
            }
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message, no smtp config found.');
        }
    }
    
    /**
     ** Sets the correct sent folder in the message
     * 
     * @param type $_message
     * @param type $_account 
     */
    protected function _selectSentFolder($_message, $_account)
    {
        if (!(is_null($_message))){
            $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
            $sendFolder = Expressomail_Controller_Account::getInstance()->getSystemFolder($_message->account_id, Expressomail_Model_Folder::FOLDER_SENT);
            if($_message->sender_account){
                if(isset($imapConfig['useEmailAsLoginName']) && $imapConfig['useEmailAsLoginName'] === '1'){
                    $this->_sentFolder = 'user/'.str_ireplace('@','/'.$sendFolder->localname.'@', $_message->from_email);
                }else{
                    $this->_sentFolder = 'user/'.$_message->sender_account.substr($sendFolder->globalname, 5);
                }
            }else {
                $this->_sentFolder = $sendFolder->globalname;
            }
        }else{
            $sendFolder = Expressomail_Controller_Account::getInstance()->getSystemFolder($_account, Expressomail_Model_Folder::FOLDER_SENT);
            $this->_sentFolder = $sendFolder->globalname;
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
     * @param Expressomail_Transport $_transport
     * @param Expressomail_Model_Account $_account
     * @param array $_additionalHeaders
     * @return void
     */
    protected function _saveInSent(Expressomail_Transport $_transport, Expressomail_Model_Account $_account, $_additionalHeaders = array())
    {
        try {
            $mailAsString = $_transport->getRawMessage(NULL, $_additionalHeaders);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)){
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                    ' About to save message in sent folder (' . $this->_sentFolder . ') ...'
                );
            }
            Expressomail_Backend_ImapFactory::factory($_account)->appendMessage($mailAsString, $this->_sentFolder);

            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Saved sent message in "' . $this->_sentFolder . '".'
            );
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Could not save sent message in "' . $this->_sentFolder . '".'
                . ' Please check if a folder with this name exists.'
                . '(' . $zmpe->getMessage() . ')'
            );
        } catch (Zend_Mail_Storage_Exception $zmse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Could not save sent message in "' . $this->_sentFolder . '".'
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
     * @param  Expressomail_Model_Message $originalMessage
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
        $account = ($accountId instanceof Expressomail_Model_Account) ? $accountId : Expressomail_Controller_Account::getInstance()->get($accountId);

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
     * @param Expressomail_mail $_mail
     * @param Expressomail_Model_Message $_message
     */
    protected function _setMailBody(Expressomail_mail $_mail, Expressomail_Model_Message $_message)
    {
        if ($_message->content_type == Expressomail_Model_Message::CONTENT_TYPE_HTML) {
            // checking embedded images
            $embeddedImages = $this->processEmbeddedImagesInHtmlBody($_message->body);
            // now checking embedded signature base64 image
            $base64Images = $this->processEmbeddedImageSignatureInHtmlBody($_message->body);
            //now checking embed images for reply/forward
            $embeddedImagesReply = $this->processEmbeddedImagesInHtmlBodyForReply($_message->body);

            $cid = array();

            if(count($embeddedImagesReply)>0)
            {
               foreach($embeddedImagesReply as $index => $embeddedImage )
               {
                    $cid[$index] = $_mail->createCid($embeddedImage['messageId']);
                    $_message->body = str_replace($embeddedImage['match'], 'src="cid:'.$cid[$index].'"', $_message->body);
               }
            }


            if(count($embeddedImages)>0){
                $_message->body = str_ireplace('src="index.php?method=Expressomail.showTempImage&amp;tempImageId=','src="cid:', $_message->body);
            }
            if(count($base64Images)>0){
                // there should be only one image in the signature
                $signature_cid = $_mail->createCid($base64Images[0][1]);
                $_message->body = preg_replace('/<img id="?user-signature-image-?[0-9]*"? alt="?[^\"]+"? src="data:image\/jpeg;base64,[^"]+">/','<img id="user-signature-image" src="cid:'.$signature_cid.'"/>', $_message->body);
            }
            $_mail->setBodyHtml(Expressomail_Message::addHtmlMarkup($_message->body));
            if(count($embeddedImages)>0){
                foreach($embeddedImages as $embeddedImage ){
                    $file = Tinebase_Core::getTempDir().'/'.$embeddedImage[1];
                    $image = file_get_contents($file);
                    $_mail->createHtmlRelatedAttachment($image,$embeddedImage[1],'image/jpg',Zend_Mime::DISPOSITION_INLINE,Zend_Mime::ENCODING_BASE64,$embeddedImage[0]);
                }
            }
            if(count($base64Images)>0){
                // again, there should be only one image in the signature
                $image = base64_decode($base64Images[0][1]);
                $_mail->createHtmlRelatedAttachment($image,$signature_cid,'image/jpg',Zend_Mime::DISPOSITION_INLINE,Zend_Mime::ENCODING_BASE64,$base64Images[0][0]);
            }

            if(count($embeddedImagesReply)>0)
            {
               foreach($embeddedImagesReply as $index => $embeddedImage )
               {
                   try {

                    $part = Expressomail_Controller_Message::getInstance()->getMessagePart($embeddedImage['messageId'], $embeddedImage['messagePart']);
                    $image = base64_decode(stream_get_contents($part->getRawStream()));
                    $_mail->createHtmlRelatedAttachment($image,$cid[$index],$part->type,Zend_Mime::DISPOSITION_INLINE,Zend_Mime::ENCODING_BASE64,$part->filename);

                   }catch (Exception $exc) {
                   }

               }
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $_mail->getBodyHtml(TRUE));
        }

        $plainBodyText = $_message->getPlainTextBody();
        $_mail->setBodyText($plainBodyText);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $_mail->getBodyText(TRUE));
    }

        /**
     * Find and process Embedded imagens in HTML body.
     *
     * @param  string|Zend_Mime_Part    $html
     * @return array
     */
    public function processEmbeddedImagesInHtmlBody($html)
    {
        //todo find everything in the format <img style="....." alt="'+data.name+'" src="index.php?method=Expressomail.showTempImage&tempImageId='+data.url+'"/>
        //     create multipart/related parts and replace src with CID:cid
        $expImages = '/<img .?[^>]*?alt="([^\"]+)" src="index.php\?method=Expressomail\.showTempImage&amp;tempImageId=([a-z0-9A-Z]+)"(?: \w+=".+?")*>/i';
        preg_match_all($expImages, $html, $result);
        $return = array();
        foreach($result[0] as $key => $embeddedImage){
            $return[$key][0] = $result[1][$key];
            $return[$key][1] = $result[2][$key];
        }

        return $return;
    }

     /**
     * Find and process Embedded imagens in HTML body for a reply/forward
     *
     * @param  string|Zend_Mime_Part    $html
     * @return array
     */
    public function processEmbeddedImagesInHtmlBodyForReply($html)
    {
   //todo achar tudo no formato <img alt="'+data.name+'" src="index.php?method=Expressomail.showTempImage&tempImageId='+data.url+'"/>, criar as partes multipart/related e substituir o src por CID:cid
        $exp = "/src=['\"]index.php\?method=Expressomail\.downloadAttachment&amp;messageId=([a-z0-9A-Z]+)&amp;partId=([0-9\.]+)(?:&amp;[^?&\\=;'\"]+=[^?&\\=;'\"]+)*['\"]/i";
       //a-z0-9A-Z. _-
        preg_match_all($exp, $html, $result);
        $return = array();
        foreach($result[0] as $key => $embeddedImage){
            $return[$key]["match"] = $result[0][$key];
            $return[$key]["messageId"] = $result[1][$key];
            $return[$key]["messagePart"] = $result[2][$key];
        }

        return $return;
    }

    /**
     * Find and process Embedded imagens in HTML body.
     *
     * @param  string|Zend_Mime_Part    $html
     * @return array
     */
    public function processEmbeddedImageSignatureInHtmlBody($html)
    {

        // also treats base64 signatures
        $expImages = '/<img id="?user-signature-image-?[0-9]*"? alt="?([^\"]+)"? src="data:image\/jpeg;base64,([^"]+)">/';
       //a-z0-9A-Z. _-
        preg_match_all($expImages, $html, $result);
        $return = array();
        foreach($result[0] as $key => $embeddedImage){
            $return[$key][0] = $result[1][$key];
            $return[$key][1] = $result[2][$key];
        }

        return $return;
    }

   /**
     * set from in mail to be sent
     *
     * @param Expressomail_mail $_mail
     * @param Expressomail_Model_Account $_account
     * @param Expressomail_Model_Message $_message
     */
    protected function _setMailFrom(Zend_Mail $_mail, Expressomail_Model_Account $_account, Expressomail_Model_Message $_message = NULL)
    {
        $_mail->clearFrom();

        $from = (isset($_account->from) && ! empty($_account->from))
            ? $_account->from
            : Tinebase_Core::getUser()->accountFullName;

        isset($_message->from_name)?$from = $_message->from_name:$from = $from;
        $user = Tinebase_Core::getUser();
        try {
            $allowedEmails = Expressomail_Session::getSessionNamespace()->allowedEmails[$user->accountId];
        } catch (Zend_Session_Exception $zse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . "::" . __LINE__ .":: It was not possible to get Expressomail Session Namespace");
            $allowedEmails = array($user->accountEmailAddress);
        }
        $email = ($_message !== NULL && ! empty($_message->from_email)) ? $_message->from_email : $_account->email;
        if (array_search($email, $allowedEmails) === FALSE) {
            throw new Tinebase_Exception_Record_NotAllowed('You Can\'t send a email with this FROM address: '.$email);
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 'User with id: ' .$user->accountId. 'tried to send email with address not allowed:  ' . $email);
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set from for mail: ' . $email . ' / ' . $from);

        $_mail->setFrom($email, $from);
    }

    /**
     * set mail recipients
     *
     * @param Expressomail_mail $_mail
     * @param Expressomail_Model_Message $_message
     * @return array
     */
    protected function _setMailRecipients(Zend_Mail $_mail, Expressomail_Model_Message $_message)
    {
        $punycodeConverter = $this->getPunycodeConverter();

        foreach (array('to', 'cc', 'bcc') as $type) {
            if (isset($_message->{$type})) {
                foreach((array) $_message->{$type} as $address) {
                    $address = trim($address);
                    if(strlen($address)>0)
                    {
                        $address = $punycodeConverter->encode($address);

                        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Add ' . $type . ' address: ' . $address);

                        switch($type) {
                            case 'to':
                                $_mail->addTo($address);
                                break;
                            case 'cc':
                                $_mail->addCc($address);
                                break;
                            case 'bcc':
                                $_mail->addBcc($address);
                                break;
                        }
                    }
                }
            }
        }

    }

    /**
     * set headers in mail to be sent
     *
     * @param Expressomail_mail $_mail
     * @param Expressomail_Model_Account $_account
     * @param Expressomail_Model_Message $_message
     */
    protected function _setMailHeaders(Zend_Mail $_mail, Expressomail_Model_Account $_account, Expressomail_Model_Message $_message = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting mail headers');

        // add user agent
        $_mail->addHeader('User-Agent', 'Tine 2.0 Email Client (version ' . TINE20_CODENAME . ' - ' . TINE20_PACKAGESTRING . ')');

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
            if ($_message->flags && $_message->flags == Zend_Mail_Storage::FLAG_ANSWERED && $_message->original_id instanceof Expressomail_Model_Message) {
                $this->_addReplyHeaders($_message);
            }

            //set the header request response
            if ($_message->reading_conf) {
                $_mail->addHeader('Disposition-Notification-To', $_message->from_email);
            }
            // set the Importance header
            if ($_message->importance) {
                $_mail->addHeader('Importance', 'high');
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
     * @param Expressomail_Model_Message $message
     *
     * @see http://www.faqs.org/rfcs/rfc2822.html / Section 3.6.4.
     */
    protected function _addReplyHeaders(Expressomail_Model_Message $message)
    {
        $originalHeaders = Expressomail_Controller_Message::getInstance()->getMessageHeaders($message->original_id);
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
     * @param Expressomail_mail $_mail
     * @param Expressomail_Model_Message $_message
     */
    protected function _addAttachments(Expressomail_mail $_mail, Expressomail_Model_Message $_message)
    {
        if (! isset($_message->attachments) || empty($_message->attachments)) {
            return;
        }

        $size = 0;
        $tempFileBackend = Tinebase_TempFile::getInstance();
        foreach ($_message->attachments as $attachment) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Adding attachment: ' . (is_object($attachment) ? print_r($attachment->toArray(), TRUE) : print_r($attachment, TRUE)));

            if ($attachment['partId'] && $_message->original_id instanceof Expressomail_Model_Message) {
                $originlPart = $this->getMessagePart($_message->original_id,$attachment['partId']);
                
                switch ($originlPart->encoding) {
                    case Zend_Mime::ENCODING_BASE64:
                          $part = new Zend_Mime_Part(base64_decode(stream_get_contents($originlPart->getRawStream())));
                          $part->encoding = Zend_Mime::ENCODING_BASE64;
                    break;
                    case Zend_Mime::ENCODING_QUOTEDPRINTABLE:
                          $part = new Zend_Mime_Part(quoted_printable_decode(stream_get_contents($originlPart->getRawStream())));
                          $part->encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE;
                    break;
                    default:
                          $part = new Zend_Mime_Part(stream_get_contents($originlPart->getRawStream()));
                          $part->encoding = null;
                    break;
                }

                $name = $attachment['name'];
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
                $part->encoding = ($tempFile->type == Expressomail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) ? null : Zend_Mime::ENCODING_BASE64;

                $name = $tempFile->name;                
                $type = $tempFile->type;
                // try to detect the correct file type, on error fallback to the default application/octet-stream
                if($tempFile->type == "undefined" || $tempFile->type == "unknown") {
                	try {
                		$finfo = finfo_open(FILEINFO_MIME_TYPE);
                		$type = finfo_file($finfo, $tempFile->path);
                	} catch (Exception $e) {
                		$type = "application/octet-stream";
                	}
                	try {
                		finfo_close($finfo);
                	} catch (Exception $e) { }
                }	
            }

            $part->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
            $name = Zend_Mime::encodeQuotedPrintableHeader(addslashes($name), 'utf-8');
            $partTypeString = $type . '; name="' . $name . '"';
            $part->type = $partTypeString;

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Adding attachment ' . $partTypeString);

            $_mail->addAttachment($part);
        }
    }
}