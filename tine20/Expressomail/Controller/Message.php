<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        parse mail body and add <a> to telephone numbers?
 */

/**
 * message controller for Expressomail
 *
 * @package     Expressomail
 * @subpackage  Controller
 */
class Expressomail_Controller_Message extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Expressomail';

    /**
     * holds the instance of the singleton
     *
     * @var Expressomail_Controller_Message
     */
    private static $_instance = NULL;

    /**
     * cache controller
     *
     * @var Expressomail_Controller_Cache_Message
     */
    protected $_cacheController = NULL;

    /**
     * message backend
     *
     * @var Expressomail_Backend_Message
     */
    protected $_backend = NULL;

    /**
     * punycode converter
     *
     * @var idna_convert
     */
    protected $_punycodeConverter = NULL;

    /**
     * fallback charset constant
     *
     * @var string
     */
    const DEFAULT_FALLBACK_CHARSET = 'iso-8859-15';

    /**
     * foreign application content types
     *
     * @var array
     */
    protected $_supportedForeignContentTypes = array(
        'Calendar'     => Expressomail_Model_Message::CONTENT_TYPE_CALENDAR,
        'Addressbook'  => Expressomail_Model_Message::CONTENT_TYPE_VCARD,
        'Expressomail'  => Expressomail_Model_Message::CONTENT_TYPE_READCONF
    );

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_modelName = 'Expressomail_Model_Message';
        $this->_doContainerACLChecks = FALSE;
        $this->_backend = new Expressomail_Backend_Message();

       // $this->_cacheController = Expressomail_Controller_Cache_Message::getInstance();
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
     * @return Expressomail_Controller_Message
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Expressomail_Controller_Message();
        }

        return self::$_instance;
    }

    /**
     * Removes accounts where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     *
     * @todo move logic to Expressomail_Model_MessageFilter
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $accountFilter = $_filter->getFilter('account_id');

        // force a $accountFilter filter (ACL) / all accounts of user
        if ($accountFilter === NULL || $accountFilter['operator'] !== 'equals' || ! empty($accountFilter['value'])) {
            $_filter->createFilter('account_id', 'equals', array());
        }
    }

        /**
     * remove all cached messages for this folder and reset folder values / folder status is updated in the database
     *
     * @param string|Expressomail_Model_Folder $_folder
     * @return Expressomail_Model_Folder
     */
//    public function clear($_folder)
//    {
//        $folder = ($_folder instanceof Expressomail_Model_Folder) ? $_folder : Expressomail_Controller_Folder::getInstance()->get($_folder);
//
////        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Clearing cache of ' . $folder->globalname);
////
////        $this->deleteByFolder($folder);
////
////        $folder->cache_timestamp        = Tinebase_DateTime::now();
////        $folder->cache_status           = Expressomail_Model_Folder::CACHE_STATUS_EMPTY;
////        $folder->cache_job_actions_est = 0;
////        $folder->cache_job_actions_done = 0;
////
////        Expressomail_Controller_Folder::getInstance()->updateFolderCounter($folder, array(
////            'cache_totalcount'  => 0,
////            'cache_recentcount' => 0,
////            'cache_unreadcount' => 0
////        ));
////
////        $folder = Expressomail_Controller_Folder::getInstance()->update($folder);
//
//        return $folder;
//    }

    /**
     * append a new message to given folder
     *
     * @param  string|Expressomail_Model_Folder  $_folder   id of target folder
     * @param  string|resource  $_message  full message content
     * @param  array   $_flags    flags for new message
     */
    public function appendMessage($_folder, $_message, $_flags = null)
    {
        $folder  = ($_folder instanceof Expressomail_Model_Folder) ? $_folder : Expressomail_Controller_Folder::getInstance()->get($_folder);
        $message = (is_resource($_message)) ? stream_get_contents($_message) : $_message;
        $message = preg_replace("/(?<!\\r)\\n/", "\r\n", $message);
        $flags   = ($_flags !== null) ? (array) $_flags : null;
        $folderName = Expressomail_Model_Folder::encodeFolderName($folder->globalname);

        $imapBackend = $this->_getBackendAndSelectFolder(NULL, $folder);
        $imapBackend->appendMessage($message, $folderName, $flags);
        $return = $imapBackend->examineFolder($folderName);
        return $return['uidnext'] -1;


    }

         /**
     * import messages to folder from file(eml), or/and from a zip file.
     *
     * @param string $accountId
     * @param string $globalName
     * @param array $file
     * @return array
     */
    public function importMessagefromfile($accountId,$folderId, $file)
    {
        $zip = zip_open($file);
        if(gettype($zip) === 'resource')
            {
                // is a zip file ...
                while ($zip_entry = zip_read($zip))
                    {
                        $filename = zip_entry_name($zip_entry);
                        if (substr($filename, strlen($filename) - 4) == '.eml')
                        {
                            if (zip_entry_open($zip, $zip_entry, "r"))
                            {
                                $email = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                                $result = Expressomail_Controller_Message::getInstance()->appendMessage($folderId, $email);
                                zip_entry_close($zip_entry);
                            }
                        }
                    }
                zip_close($zip);
            }
        else
            {
                // Error!!! Not zip file. But may be a eml file. Then try ...
                $msg = file_get_contents($file);
                $result = Expressomail_Controller_Message::getInstance()->appendMessage($folderId, $msg);
            }
        return array(
            'status'    =>  'success' );
    }

    /**
     * get complete message by id
     *
     * @param string|Expressomail_Model_Message  $_id
     * @param string                            $_partId
     * @param boolean                          $_setSeen
     * @return Expressomail_Model_Message
     */
    public function getCompleteMessage($_id, $_partId = NULL, $_setSeen = FALSE)
    {
        if ($_id instanceof Expressomail_Model_Message) {
            $message = $_id;
        } else {
            $message = $this->get($_id);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' Getting message content ' . $message->messageuid
        );

        $folder = Expressomail_Controller_Folder::getInstance()->get($message->folder_id);
        $account = Expressomail_Controller_Account::getInstance()->get($folder->account_id);

        $this->_checkMessageAccount($message, $account);

        $message = $this->_getCompleteMessageContent($message, $account, $_partId);

        if ($_setSeen) {
            Expressomail_Controller_Message_Flags::getInstance()->setSeenFlag($message);
        }

        $this->prepareAndProcessParts($message);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($message->toArray(), true));

        return $message;
    }

    // Ignore $_partId
    public function getDigitalSignature($messageId, $_partId = null)
    {

        $signature = false;

        if (! $messageId instanceof Expressomail_Model_Message) {
            $message = $this->_backend->get($messageId);
        } else {
            $message = $messageId;
        }

        if ($message->smime == Expressomail_Smime::TYPE_SIGNED_DATA_VALUE || $message->smime == Expressomail_Smime::TYPE_ENVELOPED_DATA_VALUE)
        {
            $imapBackend = $this->_getBackendAndSelectFolder($message->folder_id);

            $rawHeaders = $imapBackend->getRawContent($message->messageuid, 'HEADER');
            $rawBody = $imapBackend->getRawContent($message->messageuid);
            $rawMessage = $rawHeaders . $rawBody;

            // do verification
            $signature = Expressomail_Smime::verify($rawHeaders, $rawBody, $message->from_email, $message->smime);
        }

        return  $signature;
    }

          /**
     * get Raw message
     *
     * @param string|Felamimail_Model_Message $_id
     * @return String
     */
    public function getRawMessage($_id)
    {
        $message = $this->get($_id);

        $partStructure  = $message->getPartStructure(NULL, FALSE);

        $rawContent = $this->_getPartContent($message, NULL, $partStructure, FALSE);

        return $rawContent;
    }

    /*
     * check if account of message is belonging to user
     *
     * @param Expressomail_Model_Message $message
     * @param Expressomail_Model_Account $account
     * @throws Tinebase_Exception_AccessDenied
     *
     * @todo think about moving this to get() / _checkGrant()
     */
    protected function _checkMessageAccount($message, $account = NULL)
    {
        $account = ($account) ? $account : Expressomail_Controller_Account::getInstance()->get($message->account_id);
        if ($account->user_id !== Tinebase_Core::getUser()->getId()) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to access this message');
        }
    }

    /**
     * get message content (body, headers and attachments)
     *
     * @param Expressomail_Model_Message $_message
     * @param Expressomail_Model_Account $_account
     * @param string $_partId
     */
    protected function _getCompleteMessageContent(Expressomail_Model_Message $_message, Expressomail_Model_Account $_account, $_partId = NULL)
    {
        $mimeType = ($_account->display_format == Expressomail_Model_Account::DISPLAY_HTML || $_account->display_format == Expressomail_Model_Account::DISPLAY_CONTENT_TYPE)
        ? Zend_Mime::TYPE_HTML
        : Zend_Mime::TYPE_TEXT;

        $headers     = $this->getMessageHeaders($_message, $_partId, true);
        $attachments = $this->getAttachments($_message, $_partId);
        $this->_attachments = $attachments;
        $body        = $this->getMessageBody($_message, $_partId, $mimeType, $_account, true);
        $signature   = $this->getDigitalSignature($_message, $_partId);

        if ($signature['ret_type'] == 'cipher') {
           $smimeEml = $signature['content'];
           unset($signature);
        }
        else {
           $smimeEml = '';
        }

        if ($_partId === null) {
            $message = $_message;

            $message->body        = $body;
            $message->headers     = $headers;
            $message->attachments = $attachments;
            // make sure the structure is present
            $message->structure   = $message->structure;
            $message->signature_info   = $signature;
            $message->smimeEml    = $smimeEml;

        } else {
            // create new object for rfc822 message
            $structure = $_message->getPartStructure($_partId, FALSE);

            $message = new Expressomail_Model_Message(array(
                'messageuid'  => $_message->messageuid,
                'folder_id'   => $_message->folder_id,
                'received'    => $_message->received,
                'size'        => (array_key_exists('size', $structure)) ? $structure['size'] : 0,
                'partid'      => $_partId,
                'body'        => $body,
                'headers'     => $headers,
                'attachments' => $attachments,
                'signature_info' => $signature,
                'smimeEml'    => $smimeEml,
                'structure'   => $structure,
            ));

            $message->parseHeaders($headers);
            $message->parseSmime($message->structure);

            $structure = array_key_exists('messageStructure', $structure) ? $structure['messageStructure'] : $structure;
            $message->parseStructure($structure);
        }
        if(!$_partId)
            $message->sendReadingConfirmation();

        if (isset($message['signature_info']) && $message['signature_info']) {
            if ($message['signature_info']['ret_type'] == 'signature') {
                $signature['smime'] = Expressomail_Smime::TYPE_ENVELOPED_DATA_VALUE;
                $extract1_headers = 'application/pkcs7';
                $extract1a_headers = 'name="smime.p7m"';
                $extract1b_headers = 'MIME-Version: 1.0';
                $extract2_headers = $message['headers']['content-disposition'];
                $extract3_headers = $message['headers']['content-transfer-encoding'];
                $imapBackend = $this->_getBackendAndSelectFolder($_message->folder_id);
                $contentHeaders =  preg_replace("/(?<!\\r)\\n/", "\r\n",$imapBackend->getRawContent($_message->messageuid, 'HEADER'));
                $header_lines = explode(chr(0x0D) . chr(0x0A), $contentHeaders);
                $newHeaders = '';
                foreach ($header_lines as $line) {
                    if(strpos($line,$extract1_headers) === false && strpos($line,$extract1a_headers) === false && strpos($line,$extract1b_headers) === false && strpos($line,$extract2_headers) === false && strpos($line,$extract3_headers) === false) {
                        $newHeaders = $newHeaders . $line . chr(0x0D) . chr(0x0A);
                    }
                }
                $newHeaders = substr($newHeaders,0,-4) . 'MIME-Version: 1.0' . chr(0x0D) . chr(0x0A);
                $msg = $newHeaders . $message['signature_info']['content'];
                $msg = preg_replace("/(?<!\\r)\\n/", "\r\n", $msg);
                $account = Expressomail_Controller_Account::getInstance()->get($message->account_id);
                $folder = Expressomail_Controller_Folder::getInstance()->getByBackendAndGlobalName($message->account_id, $account['trash_folder']);
                $_messageUid = Expressomail_Controller_Message::getInstance()->appendMessage($folder, $msg);
                $_messageid = Expressomail_Backend_Message::createMessageId($account['id'], $folder['id'], $_messageUid);
                $messageB = Expressomail_Controller_Message::getInstance()->getCompleteMessage($_messageid, Null, false);
                $messageB['signature_info'] = $message['signature_info'];
                return $messageB;
            }
        }

        return $message;
    }

//    /**
//     * send reading confirmation for message
//     *
//     * @param string $messageId
//     */
//    public function sendReadingConfirmation($messageId)
//    {
//        $message = $this->get($messageId);
//        $this->_checkMessageAccount($message);
//        $message->sendReadingConfirmation();
//    }

    /**
     * prepare message parts that could be interesting for other apps
     *
     * @param Expressomail_Model_Message $_message
     */
    public function prepareAndProcessParts(Expressomail_Model_Message $_message)
    {
        $preparedParts = new Tinebase_Record_RecordSet('Expressomail_Model_PreparedMessagePart');

        foreach ($this->_supportedForeignContentTypes as $application => $contentType) {
            if (! Tinebase_Application::getInstance()->isInstalled($application) || ! Tinebase_Core::getUser()->hasRight($application, Tinebase_Acl_Rights::RUN)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' ' . $application . ' not installed or access denied.');
                continue;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Looking for ' . $application . '[' . $contentType . '] content ...');

            $parts = $_message->getBodyParts(NULL, $contentType);
            foreach ($parts as $partId => $partData) {
                if ($partData['contentType'] !== $contentType) {
                    continue;
                }

                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' ' . $application . '[' . $contentType . '] content found.');

                $preparedPart = $this->_getForeignMessagePart($_message, $partId, $partData);
                if ($preparedPart) {
                    $this->_processForeignMessagePart($application, $preparedPart);
                    $preparedParts->addRecord(new Expressomail_Model_PreparedMessagePart(array(
                        'id'             => $_message->getId() . '_' . $partId,
                        'contentType'     => $contentType,
                        'preparedData'   => $preparedPart,
                    )));
                }
            }
        }

        $_message->preparedParts = $preparedParts;
    }

    /**
    * get foreign message parts
    *
    * - calendar invitations
    * - addressbook vcards
    * - ...
    *
    * @param Expressomail_Model_Message $_message
    * @param string $_partId
    * @param array $_partData
    * @return NULL|Tinebase_Record_Abstract
    */
    protected function _getForeignMessagePart(Expressomail_Model_Message $_message, $_partId, $_partData)
    {
        $part = $this->getMessagePart($_message, $_partId);

        $userAgent = (isset($_message->headers['user-agent'])) ? $_message->headers['user-agent'] : NULL;
        $parameters = (isset($_partData['parameters'])) ? $_partData['parameters'] : array();
        $decodedContent = $part->getDecodedContent();

        switch ($part->type) {
            case Expressomail_Model_Message::CONTENT_TYPE_CALENDAR:
                if (! version_compare(PHP_VERSION, '5.3.0', '>=')) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' PHP 5.3+ is needed for vcalendar support.');
                    return NULL;
                }

                $partData = new Calendar_Model_iMIP(array(
                    'id'             => $_message->getId() . '_' . $_partId,
                    'ics'            => $decodedContent,
                    'method'         => (isset($parameters['method'])) ? $parameters['method'] : NULL,
                    'originator'     => $_message->from_email,
                    'userAgent'      => $userAgent,
                ));
                break;
            case Expressomail_Model_Message::CONTENT_TYPE_READCONF :
               $partData = unserialize($decodedContent);
               break;
            default:
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not create iMIP of content type ' . $part->type);
                $partData = NULL;
        }

        return $partData;
    }

    /**
     * process foreign iMIP part
     *
     * @param string $_application
     * @param Tinebase_Record_Abstract $_iMIP
     * @return mixed
     *
     * @todo use iMIP factory?
     */
    protected function _processForeignMessagePart($_application, $_iMIP)
    {
        $iMIPFrontendClass = $_application . '_Frontend_iMIP';
        if (! class_exists($iMIPFrontendClass)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' iMIP class not found in application ' . $_application);
            return NULL;
        }

        $iMIPFrontend = new $iMIPFrontendClass();
        try {
            $result = $iMIPFrontend->autoProcess($_iMIP);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Processing failed: ' . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            $result = NULL;
        }

        return $result;
    }

    /**
     * get iMIP by message and part id
     *
     * @param string $_iMIPId
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Abstract
     */
    public function getiMIP($_iMIPId)
    {
        if (strpos($_iMIPId, '_') === FALSE) {
            throw new Tinebase_Exception_InvalidArgument('messageId_partId expecetd.');
        }

        list($messageId, $partId) = explode('_', $_iMIPId);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Fetching ' . $messageId . '[' . $partId . '] part with iMIP data ...');

        $message = $this->get($messageId);

        $iMIPPartStructure = $message->getPartStructure($partId);
        $iMIP = $this->_getForeignMessagePart($message, $partId, $iMIPPartStructure);

        return $iMIP;
    }

    /**
     * get message part
     *
     * @param string|Expressomail_Model_Message $_id
     * @param string $_partId (the part id, can look like this: 1.3.2 -> returns the second part of third part of first part...)
     * @param boolean $_onlyBodyOfRfc822 only fetch body of rfc822 messages (FALSE to get headers, too)
     * @param array $_partStructure (is fetched if NULL/omitted)
     * @return Zend_Mime_Part
     */
    public function getMessagePart($_id, $_partId = NULL, $_onlyBodyOfRfc822 = FALSE, $_partStructure = NULL)
    {
        if ($_id instanceof Expressomail_Model_Message) {
            $message = $_id;
        } else {
            $message = $this->get($_id);
        }

        // need to refetch part structure of RFC822 messages because message structure is used instead
        $partContentType = ($_partId && isset($message->structure['parts'][$_partId])) ? $message->structure['parts'][$_partId]['contentType'] : NULL;
        $partStructure  = ($_partStructure !== NULL && $partContentType !== Expressomail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) ? $_partStructure : $message->getPartStructure($_partId, FALSE);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($partStructure, TRUE));

        $rawContent = $this->_getPartContent($message, $_partId, $partStructure, $_onlyBodyOfRfc822);

        $part = $this->_createMimePart($rawContent, $partStructure);

        return $part;
    }

    /**
     * get part content (and update structure) from message part
     *
     * @param Expressomail_Model_Message $_message
     * @param string $_partId
     * @param array $_partStructure
     * @param boolean $_onlyBodyOfRfc822 only fetch body of rfc822 messages (FALSE to get headers, too)
     * @return string
     */
    protected function _getPartContent(Expressomail_Model_Message $_message, $_partId, &$_partStructure, $_onlyBodyOfRfc822 = FALSE)
    {
        $imapBackend = $this->_getBackendAndSelectFolder($_message->folder_id);

        $rawContent = '';

        // special handling for rfc822 messages
        if ($_partId !== NULL && $_partStructure['contentType'] === Expressomail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
            if ($_onlyBodyOfRfc822) {
                $logmessage = 'Fetch message part (TEXT) ' . $_partId . ' of messageuid ' . $_message->messageuid;
                if (array_key_exists('messageStructure', $_partStructure)) {
                    $_partStructure = $_partStructure['messageStructure'];
                }
            } else {
                $logmessage = 'Fetch message part (HEADER + TEXT) ' . $_partId . ' of messageuid ' . $_message->messageuid;
                $rawContent .= $imapBackend->getRawContent($_message->messageuid, $_partId . '.HEADER', true);
            }

            $section = $_partId . '.TEXT';
        } else {
            $logmessage = ($_partId !== NULL)
                ? 'Fetch message part ' . $_partId . ' of messageuid ' . $_message->messageuid
                : 'Fetch main of messageuid ' . $_message->messageuid;

            $section = $_partId;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_partStructure, TRUE));

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $logmessage);

        $rawContent .= $imapBackend->getRawContent($_message->messageuid, $section, TRUE);

        return $rawContent;
    }

    /**
     * create mime part from raw content and part structure
     *
     * @param string $_rawContent
     * @param array $_partStructure
     * @return Zend_Mime_Part
     */
    protected function _createMimePart($_rawContent, $_partStructure)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Content: ' . $_rawContent);

        $stream = fopen("php://temp", 'r+');
        fputs($stream, $_rawContent);
        rewind($stream);

        unset($_rawContent);

        $part = new Zend_Mime_Part($stream);
        $part->type        = $_partStructure['contentType'];
        $part->encoding    = array_key_exists('encoding', $_partStructure) ? $_partStructure['encoding'] : null;
        $part->id          = array_key_exists('id', $_partStructure) ? $_partStructure['id'] : null;
        $part->description = array_key_exists('description', $_partStructure) ? $_partStructure['description'] : null;
        $part->charset     = array_key_exists('charset', $_partStructure['parameters'])
            ? $_partStructure['parameters']['charset']
            : self::DEFAULT_FALLBACK_CHARSET;
        $part->boundary    = array_key_exists('boundary', $_partStructure['parameters']) ? $_partStructure['parameters']['boundary'] : null;
        $part->location    = $_partStructure['location'];
        $part->language    = $_partStructure['language'];
        if (is_array($_partStructure['disposition'])) {
            $part->disposition = $_partStructure['disposition']['type'];
            if (array_key_exists('parameters', $_partStructure['disposition'])) {
                $part->filename    = array_key_exists('filename', $_partStructure['disposition']['parameters']) ? $_partStructure['disposition']['parameters']['filename'] : null;
            }
        }
        if (empty($part->filename) && array_key_exists('parameters', $_partStructure) && array_key_exists('name', $_partStructure['parameters'])) {
            $part->filename = $_partStructure['parameters']['name'];
        }

        return $part;
    }


    /**
     * fetch message summary from IMAP server
     *
     * @param string $messageUid
     * @param string $accountId
     * @param string $folderId
     * @return array
     */
    public function getMessageSummary($messageUid, $accountId, $folderId = NULL)
    {
        $imap = Expressomail_Backend_ImapFactory::factory($accountId);

        if ($folderId !== NULL) {
            try {
                $folder = Expressomail_Controller_Folder::getInstance()->get($folderId);
                $imap->selectFolder(Expressomail_Model_Folder::encodeFolderName($folder->globalname));
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' Could not select folder ' . $folder->globalname . ': ' . $e->getMessage());
            }
        }

        $summary = $imap->getSummary($messageUid, NULL, TRUE);

        return $summary;
            }

    /**
     * get message body
     *
     * @param string|Expressomail_Model_Message $_messageId
     * @param string $_partId
     * @param string $_contentType
     * @param Expressomail_Model_Account $_account
     * @return string
     */
    public function getMessageBody($_messageId, $_partId, $_contentType, $_account = NULL)
    {
        $message = ($_messageId instanceof Expressomail_Model_Message) ? $_messageId : $this->get($_messageId);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Get Message body (part: ' . $_partId . ') of message id ' . $message->getId() . ' (content type ' . $_contentType . ')');

        $messageBody = $this->_getAndDecodeMessageBody($message, $_partId, $_contentType, $_account);

        return $messageBody;
    }

    /**
     * get message body cache id
     *
     * @param string|Expressomail_Model_Message $_messageId
     * @param string $_partId
     * @param string $_contentType
     * @param Expressomail_Model_Account $_account
     * @return string
     */
    protected function _getMessageBodyCacheId($_message, $_partId, $_contentType, $_account)
    {
        $cacheId = 'getMessageBody_'
            . $_message->getId()
            . str_replace('.', '', $_partId)
            . substr($_contentType, -4)
            . (($_account !== NULL) ? 'acc' : '');

        return $cacheId;
    }

    /**
     * get and decode message body
     *
     * @param Expressomail_Model_Message $_message
     * @param string $_partId
     * @param string $_contentType
     * @param Expressomail_Model_Account $_account
     * @return string
     *
     * @todo multipart_related messages should deliver inline images
     */
    protected function _getAndDecodeMessageBody(Expressomail_Model_Message $_message, $_partId, $_contentType, $_account = NULL)
    {
        $structure = $_message->getPartStructure($_partId);
        $bodyParts = $_message->getBodyParts($structure, $_contentType);

        if (empty($bodyParts)) {
            return '';
        }

        $messageBody = '';

        foreach ($bodyParts as $partId => $partStructure) {
            if($partStructure['disposition']['type'] == 'ATTACHMENT')
                continue;
            $bodyPart = $this->getMessagePart($_message, $partId, TRUE, $partStructure);

            $body = $this->_getDecodedBodyContent($bodyPart, $partStructure);

            if ($partStructure['contentType'] != Zend_Mime::TYPE_TEXT
                    && $partStructure['contentType'] != Expressomail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
                $bodyCharCountBefore = strlen($body);
                $body = $this->_getDecodedBodyImages($_message->getId(), $body);
                $body = $this->_purifyBodyContent($body);
                $body = Expressomail_Message::linkify($body);
                $bodyCharCountAfter = strlen($body);

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Purifying removed ' . ($bodyCharCountBefore - $bodyCharCountAfter) . ' / ' . $bodyCharCountBefore . ' characters.');
                if ($_message->text_partid && $bodyCharCountAfter < $bodyCharCountBefore / 10) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Purify may have removed (more than 9/10) too many chars, using alternative text message part.');
                    return $this->_getAndDecodeMessageBody($_message, $_message->text_partid , Zend_Mime::TYPE_TEXT, $_account);
                }
            } else {
                if ($partStructure['contentType'] === Expressomail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
                    $partContent = $this->_getPartContent($_message, $partId, $partStructure);
                    $translate = Tinebase_Translation::getTranslation('Expressomail');
                    $body .= "\n\n----- " . $translate->_('Returned Message Content') . " -----\n" . $partContent;
                }
                $body = '<pre class="message-viewer">'.$body.'</pre>';
            }

            if (! ($_account !== NULL && $_account->display_format === Expressomail_Model_Account::DISPLAY_CONTENT_TYPE && $bodyPart->type == Zend_Mime::TYPE_TEXT)) {
                      //$body = Expressomail_Message::convertContentType($partStructure['contentType'], $_contentType, $body);
                if ($bodyPart->type == Zend_Mime::TYPE_TEXT && $_contentType == Zend_Mime::TYPE_HTML) {
                    $body = Expressomail_Message::replaceUris($body);
                    //$body = Expressomail_Message::replaceEmails($body);
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Do not convert ' . $bodyPart->type . ' part to ' . $_contentType);
            }

            // Use only Felamimail to send e-mails
            $body = Expressomail_Message::replaceEmails($body);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Adding part ' . $partId . ' to message body.');

            $messageBody .= $body;
        }

        return $messageBody;
    }

    /**
     * get decoded body content
     *
     * @param Zend_Mime_Part $_bodyPart
     * @param array $partStructure
     * @return string
     *
     * @todo reduce complexity
     */
    protected function _getDecodedBodyContent(Zend_Mime_Part $_bodyPart, $_partStructure)
    {
        $rawBody = stream_get_contents($_bodyPart->getRawStream());
        $_bodyPart->resetStream();
        $charset = $this->_appendCharsetFilter($_bodyPart, $_partStructure);

        // need to set error handler because stream_get_contents just throws a E_WARNING
        set_error_handler('Expressomail_Controller_Message::decodingErrorHandler', E_WARNING);
        try {
            $body = $_bodyPart->getDecodedContent();
            unset($rawBody);
            restore_error_handler();

        } catch (Expressomail_Exception $e) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . " Decoding of " . $_bodyPart->encoding . '/' . $_partStructure['encoding'] . ' encoded message failed: ' . $e->getMessage());

            // trying to fix decoding problems
            restore_error_handler();
            $_bodyPart->resetStream();
            if ($_bodyPart->encoding == Zend_Mime::ENCODING_QUOTEDPRINTABLE) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Trying workaround for http://bugs.php.net/50363.');
                // '=0A' is a invalid UTF8 character so we need to remove ir defore the quoted_printable_decode
                $body = str_ireplace('=0A', '', $rawBody);
                $body = quoted_printable_decode($body);
                $body = iconv($charset, 'utf-8', $body);
            } else {
                unset($rawBody);
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Try again with fallback encoding.');
                $_bodyPart->appendDecodeFilter($this->_getDecodeFilter());
                set_error_handler('Expressomail_Controller_Message::decodingErrorHandler', E_WARNING);
                try {
                    $body = $_bodyPart->getDecodedContent();
                    restore_error_handler();
                } catch (Expressomail_Exception $e) {
                    restore_error_handler();
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Fallback encoding failed. Trying base64_decode().');
                    $_bodyPart->resetStream();
                    $body = base64_decode(stream_get_contents($_bodyPart->getRawStream()));
                    $body = iconv($charset, 'utf-8', $body);
                }
            }
        }

        return $body;
    }

    /**
     * error exception handler for iconv decoding errors / only gets E_WARNINGs
     *
     * NOTE: PHP < 5.3 don't throws exceptions for Catchable fatal errors per default,
     * so we convert them into exceptions manually
     *
     * @param integer $severity
     * @param string $errstr
     * @param string $errfile
     * @param integer $errline
     * @throws Expressomail_Exception
     */
    public static function decodingErrorHandler($severity, $errstr, $errfile, $errline)
    {
        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " $errstr in {$errfile}::{$errline} ($severity)");

        throw new Expressomail_Exception($errstr);
    }

    /**
     * convert charset (and return charset)
     *
     * @param  Zend_Mime_Part  $_part
     * @param  array           $_structure
     * @param  string          $_contentType
     * @return string
     */
    protected function _appendCharsetFilter(Zend_Mime_Part $_part, $_structure)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_structure, TRUE));

        $charset = isset($_structure['parameters']['charset']) ? $_structure['parameters']['charset'] : self::DEFAULT_FALLBACK_CHARSET;

        if ($charset == 'utf8') {
            $charset = 'utf-8';
        } else if ($charset == 'us-ascii' || stripos($charset,'iso646') !== FALSE) {
            // us-ascii caused problems with iconv encoding to utf-8
            $charset = self::DEFAULT_FALLBACK_CHARSET;
        } else if (strpos($charset, '.') !== false) {
            // the stream filter does not like charsets with a dot in its name
            // stream_filter_append(): unable to create or locate filter "convert.iconv.ansi_x3.4-1968/utf-8//IGNORE"
            $charset = self::DEFAULT_FALLBACK_CHARSET;
        } else if (iconv($charset, 'utf-8', '') === false) {
            // check if charset is supported by iconv
            $charset = self::DEFAULT_FALLBACK_CHARSET;
        }

        $_part->appendDecodeFilter($this->_getDecodeFilter($charset));

        return $charset;
    }

    /**
     * get decode filter for stream_filter_append
     *
     * @param string $_charset
     * @return string
     */
    protected function _getDecodeFilter($_charset = self::DEFAULT_FALLBACK_CHARSET)
    {
        if (in_array(strtolower($_charset), array('iso-8859-1', 'windows-1252', 'iso-8859-15')) && extension_loaded('mbstring')) {
            require_once 'StreamFilter/ConvertMbstring.php';
            $filter = 'convert.mbstring';
        } else {
            $filter = "convert.iconv.$_charset/utf-8//IGNORE";
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Appending decode filter: ' . $filter);

        return $filter;
    }

     /**
     * convert image cids to download image links
     *
     * @param string $_content
     * @return string
     */
    protected function _getDecodedBodyImages($_messageId, $_content)
    {
        $found = preg_match_all('/<img.[^>]*src=[\"|\']?cid:(.[^>\"\'\s]*).[^>]*>/i',$_content,$matches,PREG_SET_ORDER);
        if ($found) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Replacing cids from multipart messages with images');
            foreach ($matches as $match) {
                $pid = '';
                foreach ($this->_attachments as $attachment) {
                    if ($attachment['cid']==='<'.$match[1].'>') {
                        $pid = $attachment['partId'];
                        break;
                    }
                }
                $src = "index.php?method=Expressomail.downloadAttachment&amp;messageId=".$_messageId."&amp;partId=".$pid.'&amp;getAsJson=false';
                //$_content = preg_replace("/cid:$match[1]/",$src,$_content);
                $_content = str_replace('cid:'.$match[1], $src, $_content);
            }
        }

        return $_content;
    }

    /**
     * use html purifier to remove 'bad' tags/attributes from html body
     *
     * @param string $_content
     * @return string
     */
    protected function _purifyBodyContent($_content)
    {
        // Layout problem: Change html elements
        // with absolute position to relate position, CASE INSENSITIVE.
        $body = $_content;
        $body = @mb_eregi_replace("POSITION: ABSOLUTE;","",$body);
        // clears <head> and its inner tags as only commenting it bugs FF

        $body = preg_replace("/<head>([\s\S]*)<\/head>/msU", "", $body);
        $tag_list = Array('head','blink','object','frame',
                'iframe','layer','ilayer','plaintext','script',
                'applet','embed','frameset','xml','xmp','style');

        foreach($tag_list as $tag) {
                $new_body = @mb_eregi_replace("<$tag", "<!--$tag", $body);
                $body = @mb_eregi_replace("</$tag>", "</$tag-->", $new_body);
        }
        // Malicious Code Remove
        $dirtyCodePattern = "/(<([\w]+[\w0-9]*)(.*)on(mouse(move|over|down|up)|load|blur|change|error|click|dblclick|focus|key(down|up|press)|select)([\n\ ]*)=([\n\ ]*)[\"'][^>\"']*[\"']([^>]*)>)(.*)(<\/\\2>)?/misU";
        preg_match_all($dirtyCodePattern, $body, $rest, PREG_PATTERN_ORDER);
        foreach($rest[0] as $i => $val){
            $body = str_replace($rest[1][$i], "<".$rest[2][$i].$rest[3][$i].$rest[7][$i].">", $body);
        }

        //Removes the tags who doesn't need closing (ex: <meta yada yada yada />)
        $arrTags = array('meta', 'base');
        foreach($arrTags as $_tag) {
            $body = mb_eregi_replace('<\s?' . $_tag . '[^>]*>', '',$body);
        }

        $link = "/<(a)([^>]+)>/i";
        $target = '<\\1 target="_blank" \\2>';

        $body = preg_replace($link,$target,$body);

        //Removes the x-box-item class.
        $body = str_ireplace("x-box-item", "", $body);

        $body = preg_replace("/rowspan\s?=\s?[\'\"]0[\'\"]/i","rowspan='1'",$body);

        $body = mb_ereg_replace("(<p[^>]*)(text-indent:[^>;]*-[^>;]*;)([^>]*>)","\\1\\3",$body);
        $body = mb_ereg_replace("(<p[^>]*)(margin-right:[^>;]*-[^>;]*;)([^>]*>)","\\1\\3",$body);
        $body = mb_ereg_replace("(<p[^>]*)(margin-left:[^>;]*-[^>;]*;)([^>]*>)","\\1\\3",$body);
        $body = preg_replace('/javascript:[^\'"\s>]*/i','',$body);
        //Correção para compatibilização com Outlook, ao visualizar a mensagem
        $body = mb_ereg_replace('<!--\[','<!-- [',$body);
        $body = mb_ereg_replace('&lt;!\[endif\]--&gt;', '<![endif]-->', $body);
        $body = mb_ereg_replace('<!\[endif\]-->', '<!--[endif]--->', $body);
        $body = str_replace("\x00", '', $body);
	$body = preg_replace("|(id\s*=\s*\".*\")|U", '', $body); // Clear id to avoid errors in page
        return $body;
    }


    /**
     * use html purifier to remove 'bad' tags/attributes from html body
     *
     * @param string $_content
     * @return string
     */
    protected function _applyHtmlPurify($_content)
    {
        if (!defined('HTMLPURIFIER_PREFIX')) {
            define('HTMLPURIFIER_PREFIX', realpath(dirname(__FILE__) . '/../../library/HTMLPurifier'));
        }
        
        $config = Tinebase_Core::getConfig();
        $path = ($config->caching && $config->caching->active && $config->caching->path) 
            ? $config->caching->path : Tinebase_Core::getTempDir();

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Purifying html body. (cache path: ' . $path .')');
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Current mem usage before purify: ' . memory_get_usage()/1024/1024);
        
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.DefinitionID', 'purify message body contents');
        $config->set('HTML.DefinitionRev', 1);
        
        $config->set('Cache.SerializerPath', $path);
        $config->set('URI.AllowedSchemes', array(
            'http' => true,
            'https' => true,
            'mailto' => true,
            'data' => true
        ));

        $purifier = new HTMLPurifier($config);
        $content = $purifier->purify($_content);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Current mem usage after purify: ' . memory_get_usage()/1024/1024);

        return $content;
    }

    /**
     * get message headers
     *
     * @param string|Expressomail_Model_Message $_messageId
     * @param boolean $_readOnly
     * @return array
     * @throws Expressomail_Exception_IMAPMessageNotFound
     */
    public function getMessageHeaders($_messageId, $_partId = null, $_readOnly = false)
    {
        if (! $_messageId instanceof Expressomail_Model_Message) {
            $message = $this->_backend->get($_messageId);
        } else {
            $message = $_messageId;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Fetching headers for message uid ' .  $message->messageuid . ' (part:' . $_partId . ')');

        try {
            $imapBackend = $this->_getBackendAndSelectFolder($message->folder_id);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zmse->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zmse->getTraceAsString());
            throw new Expressomail_Exception_IMAPMessageNotFound('Folder not found');
        }

        if ($imapBackend === null) {
            throw new Expressomail_Exception('Failed to get imap backend');
        }

        $section = ($_partId === null) ?  'HEADER' : $_partId . '.HEADER';

        try {
            $rawHeaders = $imapBackend->getRawContent($message->messageuid, $section, $_readOnly);
            if(strtolower(mb_detect_encoding($rawHeaders)) == 'utf-8'){
                $rawHeaders = utf8_decode(imap_utf8($rawHeaders));
            }
        } catch (Expressomail_Exception_IMAPMessageNotFound $feimnf) {
            $this->_backend->delete($message->getId());
            throw $feimnf;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Fetched Headers: ' . $rawHeaders);

        Zend_Mime_Decode::splitMessage($rawHeaders, $headers, $null);

        return $headers;
    }

    /**
     * get imap backend and folder (and select folder)
     *
     * @param string                    $_folderId
     * @param Expressomail_Backend_Folder &$_folder
     * @param boolean                   $_select
     * @param Expressomail_Backend_ImapProxy   $_imapBackend
     * @throws Expressomail_Exception_IMAPServiceUnavailable
     * @throws Expressomail_Exception_IMAPFolderNotFound
     * @return Expressomail_Backend_ImapProxy
     */
    protected function _getBackendAndSelectFolder($_folderId = NULL, &$_folder = NULL, $_select = TRUE, Expressomail_Backend_ImapProxy $_imapBackend = NULL)
    {
        if ($_folder === NULL || empty($_folder)) {
            $folderBackend  = new Expressomail_Backend_Folder();
            $_folder = $folderBackend->get($_folderId);
        }

        try {
            $imapBackend = ($_imapBackend === NULL) ? Expressomail_Backend_ImapFactory::factory($_folder->account_id) : $_imapBackend;
            if ($_select) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' Select folder ' . $_folder->globalname);
                $backendFolderValues = $imapBackend->selectFolder(Expressomail_Model_Folder::encodeFolderName($_folder->globalname));
            }
        } catch (Zend_Mail_Storage_Exception $zmse) {
            // @todo remove the folder from cache if it could not be found on the IMAP server?
            throw new Expressomail_Exception_IMAPFolderNotFound($zmse->getMessage());
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            throw new Expressomail_Exception_IMAPServiceUnavailable($zmpe->getMessage());
        }

        return $imapBackend;
    }

    /**
     * get attachments of message
     *
     * @param  array  $_structure
     * @return array
     */
    public function getAttachments($_messageId, $_partId = null)
    {
        if (! $_messageId instanceof Expressomail_Model_Message) {
            $message = $this->_backend->get($_messageId);
        } else {
            $message = $_messageId;
        }

        $structure = $message->getPartStructure($_partId);

        $attachments = array();

        if (!array_key_exists('parts', $structure) && strtolower($structure['disposition']['type']) != Zend_Mime::DISPOSITION_ATTACHMENT) {
            return $attachments;
        }

        if($structure['disposition'] && strtolower($structure['disposition']['type']) == Zend_Mime::DISPOSITION_ATTACHMENT){
            $filename = $this->_getAttachmentFilename($structure);
                $attachmentData = array(
                    'content-type' => $structure['contentType'],
                    'filename'     => $filename,
                    'partId'       => $structure['partId'],
                    'size'         => $structure['size'],
                    'description'  => $structure['description'],
                    // If disposition equals attachment we will never need a cid
                );
                $attachments[] = $attachmentData;

                 return $attachments;
        }

        foreach ($structure['parts'] as $part) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' ' . print_r($part, TRUE));

            if ($part['type'] == 'multipart') {
                $attachments = $attachments + $this->getAttachments($message, $part['partId']);
            } else {
                if ($part['type'] == 'text' &&
                    (!is_array($part['disposition']) || ($part['disposition']['type'] == Zend_Mime::DISPOSITION_INLINE && !array_key_exists("parameters", $part['disposition'])))
                ) {
                    continue;
                }

                $filename = $this->_getAttachmentFilename($part);
                $attachmentData = array(
                    'content-type' => $part['contentType'],
                    'filename'     => $filename,
                    'partId'       => $part['partId'],
                    'size'         => $part['size'],
                    'description'  => $part['description'],
                );
                if ($structure['contentType'] === 'multipart/related' ) {
                    $attachmentData['cid'] = $part['id'];
                }

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Got attachment with name ' . $filename);

                $attachments[] = $attachmentData;
            }
        }

        return $attachments;
    }

    /**
     * fetch attachment filename from part
     *
     * @param array $part
     * @return string
     */
    protected function _getAttachmentFilename($part)
    {
        if (is_array($part['disposition']) && array_key_exists('parameters', $part['disposition'])
            && array_key_exists('filename', $part['disposition']['parameters']))
        {
            $filename = $part['disposition']['parameters']['filename'];
        } elseif (is_array($part['parameters']) && array_key_exists('name', $part['parameters'])) {
            $filename = $part['parameters']['name'];
        } elseif ($part['type'] == 'message') {
            $translate = Tinebase_Translation::getTranslation('Expressomail');
            $filename = sprintf($translate->_('Attached Message') . ' %d.eml', $part['partId']);
        } else {
            $filename = 'Part ' . $part['partId'];
        }

        return $filename;
    }

    /**
     * delete messages from cache by folder
     *
     * @param $_folder
     */
    public function deleteByFolder(Expressomail_Model_Folder $_folder)
    {
        $this->_backend->deleteByFolderId($_folder);
    }

    /**
     * update folder counts and returns list of affected folders
     *
     * @param array $_folderCounter (folderId => unreadcounter)
     * @return Tinebase_Record_RecordSet of affected folders
     * @throws Expressomail_Exception
     */
    protected function _updateFolderCounts($_folderCounter)
    {
        foreach ($_folderCounter as $folderId => $counter) {
            $folder = Expressomail_Controller_Folder::getInstance()->get($folderId, FALSE);
        }

        return Expressomail_Controller_Folder::getInstance()->getMultiple(array_keys($_folderCounter));
    }

    /**
     * get punycode converter
     *
     * @return NULL|idna_convert
     */
    public function getPunycodeConverter()
    {
        if ($this->_punycodeConverter === NULL) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating idna convert class for punycode conversion.');
            $this->_punycodeConverter = new idna_convert();
        }

        return $this->_punycodeConverter;
    }

        /**
     * get messages befora a date
     *
     * @param  mixed  $_folderId
     * @param  string $_date
     * @return array
     */
    public function SelectBeforeDate($_folderId,$_date)
    {
        $folderId = ($_folderId instanceof Expressomail_Model_Folder) ? $_folderId->getId() : $_folderId;
        $imapbbackend = Expressomail_Controller_Message::getInstance()->_getBackendAndSelectFolder($folderId);
        $filter = new Expressomail_Model_MessageFilter(array(
            array(
                'field'    => 'folder_id',
                'operator' => 'equals',
                'value'    => $folderId
            ),
           array(
                'field'    => 'received',
                'operator' => 'before',
                'value'    => $_date
            )
        ));

        $result = $this->_backend->searchMessageUids($filter);

        if (count($result) === 0) {
            return null;
        }

        $temp_result = array();

        foreach ($result as $key => $value) {
            $imapbbackend->addFlags($value, array('\\Deleted'));
            $temp_result[] = $key;
        }

        $result = $this->_deleteMessagesByIdAndUpdateCounters($temp_result, $_folderId);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Got Messages before date: ' . print_r($temp_result, TRUE));

        return $result;
    }

        /**
     * delete messages from cache
     *
     * @param array $_ids
     * @param Expressomail_Model_Folder $_folder
     * @return integer number of removed messages
     */
    protected function _deleteMessagesByIdAndUpdateCounters($_ids, Expressomail_Model_Folder $_folder)
    {
        if (count($_ids) == 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No messages to delete.');
            return 0;
        }

        $decrementMessagesCounter = 0;
        $decrementUnreadCounter   = 0;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Delete ' . count($_ids) . ' messages'
        );

        $messagesToBeDeleted = $this->_backend->getMultiple($_ids);

        foreach ($messagesToBeDeleted as $messageToBeDeleted) {
            $this->_backend->delete($messageToBeDeleted);

            $_folder->cache_job_actions_done++;
            $decrementMessagesCounter++;
            if (! $messageToBeDeleted->hasSeenFlag()) {
                $decrementUnreadCounter++;
            }
        }

        $_folder = Expressomail_Controller_Folder::getInstance()->updateFolderCounter($_folder, array(
            'cache_totalcount'  => "-$decrementMessagesCounter",
            'cache_unreadcount' => "-$decrementUnreadCounter",
        ));

        return $decrementMessagesCounter;
    }



            /**
     * update message cache
     *
     * @param string $_folder
     * @param integer $_time in seconds
     * @param integer $_updateFlagFactor 1 = update flags every time, x = update flags roughly each xth run (10 by default)
     * @return Expressomail_Model_Folder folder status (in cache)
     * @throws Expressomail_Exception
     */
    public function updateCache($_folder, $_time = 10, $_updateFlagFactor = 10)
    {
        $folder = Expressomail_Controller_Folder::getInstance()->get($_folder, false);
        return $folder;
    }

    public function calcMessageSize($_recordData)
    {
        $_recordData = $_recordData['data'];

        $textSize = 0;
        $imageSize = 0;
        $attachmentSize = 0;
        $maxMessageSize = -1;
        $tempPath = null;

        if ((isset(Tinebase_Core::getConfig()->maxMessageSize))) {
            $maxMessageSize = intval(Tinebase_Core::getConfig()->maxMessageSize);
            if ($maxMessageSize <= 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Attribute "$maxMessageSize" is not properly set. Messages will be sent without size limit.');
            }
        }
        if ((isset(Tinebase_Core::getConfig()->tmpdir))) {
            $tempPath = Tinebase_Core::getConfig()->tmpdir;
        }
        if ($_recordData['body']) {
            $textSize = strlen($_recordData['body']);
        }
        if ($tempPath && $_recordData['embedded_images']) {
            foreach($_recordData['embedded_images'] as $img) {
                $imageSize += filesize($tempPath . '/' . $img['id']);
            }
        }
        if ($_recordData['attachments']) {
            foreach($_recordData['attachments'] as $att) {
                $attachmentSize += filesize($att['tempFile']['path']);
            }
        }

        $response = array(
            'status' => 'true',
            'textSize' => $textSize,
            'imageSize' => $imageSize,
            'attachmentSize' => $attachmentSize,
            'maxMessageSize' => $maxMessageSize
        );

        return $response;
    }

    public function removeTempFiles($_recordData)
    {
        if($_recordData['data']) {
            $_recordData = $_recordData['data'];
        }
        
        $removed = array();

        if ($_recordData['attachments']) {
            foreach($_recordData['attachments'] as $att) {
                $filepath = $att['tempFile']['path'];
                array_push($removed, array($filepath, unlink($filepath)));
            }
        }

        if ($_recordData['embedded_images']) {
            foreach($_recordData['embedded_images'] as $att) {
                $filepath = $att['path'];
                array_push($removed, array($filepath, unlink($filepath)));
            }
        }

        $response = array(
            'status' => 'true',
            'isRemoded' => $removed
        );

        return $response;
    }

    /**
     *
     * @param array $source
     * @param resource | string $inputStream
     * @param string $flag
     * @throws Zend_Mail_Protocol_Exception
     */
    public function parseAndSendMessage($source, $inputStream, $flag=NULL)
    {
        $originalMessage = $this->getCompleteMessage($source['itemId'], null, false);

        $user = Tinebase_Core::getUser();

        if (! is_resource($inputStream)) {
            $stream = fopen("php://temp", 'r+');
            fwrite($stream, $inputStream);
            $inputStream = $stream;
            rewind($inputStream);
        }
        $incomingMessage = new Zend_Mail_Message(
                array(
                        'file' => $inputStream
                )
        );

        $headers = $incomingMessage->getHeaders();

        $body = ($headers['content-transfer-encoding'] == 'base64')
        ? base64_decode($incomingMessage->getContent())
        : $incomingMessage->getContent();
        $isTextPlain = strpos($headers['content-type'],'text/plain');
        $bodyLines = preg_split('/\r\n|\r|\n/', $body);
        $body = '';
        if ($isTextPlain !== false) {
            foreach ($bodyLines as &$line) {
                $body .= htmlentities($line) . '<br>';
            }
        } else {
            foreach ($bodyLines as &$line) {
                $body .= $line . '<br>';
            }
        }
        $body = '<div>'.$body.'</div>';

        $bodyOrigin = $originalMessage['body'];
        preg_match("/<body[^>]*>(.*?)<\/body>/is", $bodyOrigin, $matches);
        $bodyOrigin = (count($matches)>1) ? $matches[1] : $bodyOrigin;
        $body .= '<div>'.$bodyOrigin.'</div>';

        $attachments = array();
        foreach ($originalMessage['attachments'] as &$att) {
            try {
                $att['name'] = $att['filename'];
                $att['type'] = $att['content-type'];
            } catch (Exception $e) {}
            array_push($attachments, $att);
        }

        $recordData = array();
        $recordData['note'] = '';
        $recordData['content_type'] = 'text/html';
        $recordData['account_id'] = $originalMessage->account_id;
        $recordData['to'] = is_array($headers['to']) ? $headers['to'] : array($headers['to']);
        $recordData['cc'] = array();
        $recordData['bcc'] = array();
        $recordData['subject'] = $headers['subject'];
        $recordData['body'] = $body;
        //$recordData['flags'] = array_merge($incomingMessage->getFlags(), $originalMessage['flags']);
        $recordData['flags'] = ($flag != NULL) ? $flag : '';
        $recordData['original_id'] = $source['itemId'];
        $recordData['embedded_images'] = array();
        $recordData['attachments'] = $attachments;
        $recordData['from_email'] = $user->accountEmailAddress;
        $recordData['from_name'] = $user->accountFullName;
        $recordData['customfields'] = array();

        $message = new Expressomail_Model_Message();
        $message->setFromJsonInUsersTimezone($recordData);

        try {
            Expressomail_Controller_Message_Send::getInstance()->sendMessage($message);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message: ' . $zmpe->getMessage());
            throw $zmpe;
        }
    }

}
