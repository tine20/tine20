<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        parse mail body and add <a> to telephone numbers?
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
     * @var Felamimail_Controller_Cache_Message
     */
    protected $_cacheController = NULL;
    
    /**
     * message backend
     *
     * @var Felamimail_Backend_Cache_Sql_Message
     */
    protected $_backend = NULL;
    
    /**
     * punycode converter
     *
     * @var idna_convert
     */
    protected $_punycodeConverter = NULL;
    
    /**
     * elements to remove from html body of a message / only images are supported atm
     * 
     * @var array
     */
    protected $_purifyElements = array('images');
    
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
        'Calendar'     => Felamimail_Model_Message::CONTENT_TYPE_CALENDAR,
        'Addressbook'  => Felamimail_Model_Message::CONTENT_TYPE_VCARD,
    );
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() 
    {
        $this->_modelName = 'Felamimail_Model_Message';
        $this->_doContainerACLChecks = FALSE;
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();
        
        $this->_currentAccount = Tinebase_Core::getUser();
        
        $this->_cacheController = Felamimail_Controller_Cache_Message::getInstance();
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
    
    /**
     * Removes accounts where current user has no access to
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     * 
     * @todo move logic to Felamimail_Model_MessageFilter
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
     * append a new message to given folder
     *
     * @param  string|Felamimail_Model_Folder  $_folder   id of target folder
     * @param  string|resource  $_message  full message content
     * @param  array   $_flags    flags for new message
     */
    public function appendMessage($_folder, $_message, $_flags = null)
    {
        $folder  = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->get($_folder);
        $message = (is_resource($_message)) ? stream_get_contents($_message) : $_message;
        $flags   = ($_flags !== null) ? (array) $_flags : null;
        
        $imapBackend = $this->_getBackendAndSelectFolder(NULL, $folder);
        $imapBackend->appendMessage($message, $folder->globalname, $flags);
    }
    
    /**
     * get complete message by id
     *
     * @param string|Felamimail_Model_Message  $_id
     * @param string 						   $_partId
     * @param boolean                          $_setSeen
     * @return Felamimail_Model_Message
     */
    public function getCompleteMessage($_id, $_partId = NULL, $_setSeen = FALSE)
    {
        if ($_id instanceof Felamimail_Model_Message) {
            $message = $_id;
        } else {
            $message = $this->get($_id);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . 
            ' Getting message content ' . $message->messageuid 
        );
        
        $folder = Felamimail_Controller_Folder::getInstance()->get($message->folder_id);
        $account = Felamimail_Controller_Account::getInstance()->get($folder->account_id);
        
        $this->_getCompleteMessageContent($message, $account, $_partId);
        
        if ($_setSeen) {
            Felamimail_Controller_Message_Flags::getInstance()->setSeenFlag($message);
        }
        
        $this->prepareAndProcessParts($message);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($message->toArray(), true));
        
        return $message;
    }
    
    /**
     * get message content (body, headers and attachments)
     * 
     * @param Felamimail_Model_Message $_message
     * @param Felamimail_Model_Account $_account
     * @param string $_partId
     */
    protected function _getCompleteMessageContent(Felamimail_Model_Message $_message, Felamimail_Model_Account $_account, $_partId = NULL)
    {
        $mimeType = ($_account->display_format == Felamimail_Model_Account::DISPLAY_HTML || $_account->display_format == Felamimail_Model_Account::DISPLAY_CONTENT_TYPE)
        ? Zend_Mime::TYPE_HTML
        : Zend_Mime::TYPE_TEXT;
        
        $headers     = $this->getMessageHeaders($_message, $_partId, true);
        $body        = $this->getMessageBody($_message, $_partId, $mimeType, $_account, true);
        $attachments = $this->getAttachments($_message, $_partId);
        
        if ($_partId === null) {
            $_message->body        = $body;
            $_message->headers     = $headers;
            $_message->attachments = $attachments;
        } else {
            // create new object for rfc822 message
            $structure = $_message->getPartStructure($_partId, FALSE);
        
            $_message = new Felamimail_Model_Message(array(
                'messageuid'  => $_message->messageuid,
                'folder_id'   => $_message->folder_id,
                'received'    => $_message->received,
                'size'        => (array_key_exists('size', $structure)) ? $structure['size'] : 0,
                'partid'      => $_partId,
                'body'        => $body,
                'headers'     => $headers,
                'attachments' => $attachments
            ));
        
            $_message->parseHeaders($headers);
        
            $structure = array_key_exists('messageStructure', $structure) ? $structure['messageStructure'] : $structure;
            $_message->parseStructure($structure);
        }
    }
    
    /**
     * prepare message parts that could be interesting for other apps
     * 
     * @param Felamimail_Model_Message $_message
     */
    public function prepareAndProcessParts(Felamimail_Model_Message $_message)
    {
        $preparedParts = new Tinebase_Record_RecordSet('Felamimail_Model_PreparedMessagePart');
        
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
                    $preparedParts->addRecord(new Felamimail_Model_PreparedMessagePart(array(
                        'id'             => $_message->getId() . '_' . $partId,
                        'contentType'	 => $contentType,
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
    * @param Felamimail_Model_Message $_message
    * @param string $_partId
    * @param array $_partData
    * @return NULL|Tinebase_Record_Abstract
    */
    protected function _getForeignMessagePart(Felamimail_Model_Message $_message, $_partId, $_partData)
    {
        $part = $this->getMessagePart($_message, $_partId);
        
        $userAgent = (isset($_message->headers['user-agent'])) ? $_message->headers['user-agent'] : NULL;
        $parameters = (isset($_partData['parameters'])) ? $_partData['parameters'] : array();
        $decodedContent = $part->getDecodedContent();
        
        switch ($part->type) {
            case Felamimail_Model_Message::CONTENT_TYPE_CALENDAR:
                $partData = new Calendar_Model_iMIP(array(
                    'id'             => $_message->getId() . '_' . $_partId,
                	'ics'            => $decodedContent,
                    'method'         => (isset($parameters['method'])) ? $parameters['method'] : NULL,
                    'originator'     => $_message->from_email,
                    'userAgent'      => $userAgent,
                ));
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
     * @param string|Felamimail_Model_Message $_id
     * @param string $_partId (the part id, can look like this: 1.3.2 -> returns the second part of third part of first part...)
     * @param boolean $_onlyBodyOfRfc822 only fetch body of rfc822 messages (FALSE to get headers, too)
     * @return Zend_Mime_Part
     */
    public function getMessagePart($_id, $_partId = NULL, $_onlyBodyOfRfc822 = FALSE)
    {
        if ($_id instanceof Felamimail_Model_Message) {
            $message = $_id;
        } else {
            $message = $this->get($_id);
        }
        
        $partStructure  = $message->getPartStructure($_partId, FALSE);
        
        $rawContent = $this->_getPartContent($message, $_partId, $partStructure, $_onlyBodyOfRfc822);
        
        $part = $this->_createMimePart($rawContent, $partStructure);
        
        return $part;
    }
    
    /**
     * get part content (and update structure) from message part
     * 
     * @param Felamimail_Model_Message $_message
     * @param string $_partId
     * @param array $_partStructure
     * @param boolean $_onlyBodyOfRfc822 only fetch body of rfc822 messages (FALSE to get headers, too)
     * @return string
     */
    protected function _getPartContent(Felamimail_Model_Message $_message, $_partId, &$_partStructure, $_onlyBodyOfRfc822 = FALSE)
    {
        $imapBackend = $this->_getBackendAndSelectFolder($_message->folder_id);
        
        $rawContent = '';
        
        // special handling for rfc822 messages
        if ($_partId !== NULL && $_partStructure['contentType'] === Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
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
     * set elements to purify
     * 
     * @param array $_elementsToPurify
     */
    public function setPurifyElements($_elementsToPurify = array('images'))
    {
        $this->_purifyElements = $_elementsToPurify;
    }
    
    /**
     * get message body
     * 
     * @param string|Felamimail_Model_Message $_messageId
     * @param string $_partId
     * @param string $_contentType
     * @param Felamimail_Model_Account $_account
     * @return string
     */
    public function getMessageBody($_messageId, $_partId, $_contentType, $_account = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Get Message body of content type ' . $_contentType);
        
        $message = ($_messageId instanceof Felamimail_Model_Message) ? $_messageId : $this->get($_messageId);
        
        $cache = Tinebase_Core::getCache();
        $cacheId = $this->_getMessageBodyCacheId($message, $_partId, $_contentType, $_account);
        
        if ($cache->test($cacheId)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting Message from cache.');
            return $cache->load($cacheId);
        }
        
        $messageBody = $this->_getAndDecodeMessageBody($message, $_partId, $_contentType, $_account);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Put message body into Tinebase cache (for 24 hours).');
        $cache->save($messageBody, $cacheId, array('getMessageBody'), 86400);
        
        return $messageBody;
    }
    
    /**
     * get message body cache id
     * 
     * @param string|Felamimail_Model_Message $_messageId
     * @param string $_partId
     * @param string $_contentType
     * @param Felamimail_Model_Account $_account
     * @return string
     */
    protected function _getMessageBodyCacheId($_message, $_partId, $_contentType, $_account)
    {
        $cacheId = 'getMessageBody_'
            . $_message->getId()
            . str_replace('.', '', $_partId)
            . substr($_contentType, -4)
            . (($_account !== NULL) ? 'acc' : '')
            . implode('', $this->_purifyElements);
                                    
        return $cacheId;
    }
    
    /**
     * get and decode message body
     * 
     * @param Felamimail_Model_Message $_message
     * @param string $_partId
     * @param string $_contentType
     * @param Felamimail_Model_Account $_account
     * @return string
     */
    protected function _getAndDecodeMessageBody(Felamimail_Model_Message $_message, $_partId, $_contentType, $_account = NULL)
    {
        $structure = $_message->getPartStructure($_partId);
        $bodyParts = $_message->getBodyParts($structure, $_contentType);
        
        if (empty($bodyParts)) {
            return '';
        }
        
        $messageBody = '';
        
        foreach ($bodyParts as $partId => $partStructure) {
            $bodyPart = $this->getMessagePart($_message, $partId, TRUE);
            
            $body = $this->_getDecodedBodyContent($bodyPart, $partStructure);
            
            if ($partStructure['contentType'] != Zend_Mime::TYPE_TEXT) {
                $body = $this->_purifyBodyContent($body);
            }
            
            if (! ($_account !== NULL && $_account->display_format === Felamimail_Model_Account::DISPLAY_CONTENT_TYPE && $bodyPart->type == Zend_Mime::TYPE_TEXT)) {
                $body = Felamimail_Message::convertContentType($partStructure['contentType'], $_contentType, $body);
                if ($bodyPart->type == Zend_Mime::TYPE_TEXT && $_contentType == Zend_Mime::TYPE_HTML) {
                    $body = Felamimail_Message::replaceUriAndSpaces($body);
                    $body = Felamimail_Message::replaceEmails($body);
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Do not convert ' . $bodyPart->type . ' part to ' . $_contentType);
            }
            
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
        $charset = $this->_appendCharsetFilter($_bodyPart, $_partStructure);
            
        // need to set error handler because stream_get_contents just throws a E_WARNING
        set_error_handler('Felamimail_Controller_Message::decodingErrorHandler', E_WARNING);
        try {
            $body = $_bodyPart->getDecodedContent();
            restore_error_handler();
            
        } catch (Felamimail_Exception $e) {
            // trying to fix decoding problems
            restore_error_handler();
            $_bodyPart->resetStream();
            if (preg_match('/convert\.quoted-printable-decode/', $e->getMessage())) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Trying workaround for http://bugs.php.net/50363.');
                $body = quoted_printable_decode(stream_get_contents($_bodyPart->getRawStream()));
                $body = iconv($charset, 'utf-8', $body);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Try again with fallback encoding.');
                $_bodyPart->appendDecodeFilter($this->_getDecodeFilter());
                set_error_handler('Felamimail_Controller_Message::decodingErrorHandler', E_WARNING);
                try {
                    $body = $_bodyPart->getDecodedContent();
                    restore_error_handler();
                } catch (Felamimail_Exception $e) {
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
     * @throws Felamimail_Exception
     */
    public static function decodingErrorHandler($severity, $errstr, $errfile, $errline)
    {
        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " $errstr in {$errfile}::{$errline} ($severity)");
        
        throw new Felamimail_Exception($errstr);
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
        } else if ($charset == 'us-ascii') {
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
        $filter = "convert.iconv.$_charset/utf-8//IGNORE";
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Appending decode filter: ' . $filter);
        
        return $filter;
    }
    
    /**
     * use html purifier to remove 'bad' tags/attributes from html body
     *
     * @param string $_content
     * @return string
     */
    protected function _purifyBodyContent($_content)
    {
        if (!defined('HTMLPURIFIER_PREFIX')) {
            define('HTMLPURIFIER_PREFIX', realpath(dirname(__FILE__) . '/../../library/HTMLPurifier'));
        }
        
        $config = Tinebase_Core::getConfig();
        $path = ($config->caching && $config->caching->active && $config->caching->path) 
            ? $config->caching->path : Tinebase_Core::getTempDir();

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Purifying html body. (cache path: ' . $path .')');
        
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.DefinitionID', 'purify message body contents'); 
        $config->set('HTML.DefinitionRev', 1);
        $config->set('Cache.SerializerPath', $path);
        
        if (in_array('images', $this->_purifyElements)) {
            $config->set('HTML.ForbiddenElements', array('img'));
            $config->set('CSS.ForbiddenProperties', array('background-image'));
        }
        
        // add target="_blank" to anchors
        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $a = $def->addBlankElement('a');
            $a->attr_transform_post[] = new Felamimail_HTMLPurifier_AttrTransform_AValidator();
        }
        
        $purifier = new HTMLPurifier($config);
        $content = $purifier->purify($_content);
        
        return $content;
    }
    
    /**
     * get message headers
     * 
     * @param string|Felamimail_Model_Message $_messageId
     * @param boolean $_readOnly
     * @return array
     * @throws Felamimail_Exception_IMAPMessageNotFound
     */
    public function getMessageHeaders($_messageId, $_partId = null, $_readOnly = false)
    {
        if (! $_messageId instanceof Felamimail_Model_Message) {
            $message = $this->_backend->get($_messageId);
        } else {
            $message = $_messageId;
        }
        
        $cache = Tinebase_Core::get('cache');
        $cacheId = 'getMessageHeaders' . $message->getId() . str_replace('.', '', $_partId);
        if ($cache->test($cacheId)) {
            return $cache->load($cacheId);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Fetching headers for message uid ' .  $message->messageuid . ' (part:' . $_partId . ')');
        
        try {
            $imapBackend = $this->_getBackendAndSelectFolder($message->folder_id);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zmse->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zmse->getTraceAsString());
            throw new Felamimail_Exception_IMAPMessageNotFound('Folder not found');
        }
        
        if ($imapBackend === null) {
            throw new Felamimail_Exception('Failed to get imap backend');
        }
        
        $section = ($_partId === null) ?  'HEADER' : $_partId . '.HEADER';
        
        try {
            $rawHeaders = $imapBackend->getRawContent($message->messageuid, $section, $_readOnly);
        } catch (Felamimail_Exception_IMAPMessageNotFound $feimnf) {
            $this->_backend->delete($message->getId());
            throw $feimnf;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Fetched Headers: ' . $rawHeaders);
                    
        Zend_Mime_Decode::splitMessage($rawHeaders, $headers, $null);
        
        $cache->save($headers, $cacheId, array('getMessageHeaders'), 86400);
        
        return $headers;
    }
    
    /**
     * get imap backend and folder (and select folder)
     *
     * @param string                    $_folderId
     * @param Felamimail_Backend_Folder &$_folder
     * @param boolean                   $_select
     * @param Felamimail_Backend_ImapProxy   $_imapBackend
     * @throws Felamimail_Exception_IMAPServiceUnavailable
     * @return Felamimail_Backend_ImapProxy
     */
    protected function _getBackendAndSelectFolder($_folderId = NULL, &$_folder = NULL, $_select = TRUE, Felamimail_Backend_ImapProxy $_imapBackend = NULL)
    {
        if ($_folder === NULL || empty($_folder)) {
            $folderBackend  = new Felamimail_Backend_Folder();
            $_folder = $folderBackend->get($_folderId);
        }
        
        try {
            $imapBackend = ($_imapBackend === NULL) ? Felamimail_Backend_ImapFactory::factory($_folder->account_id) : $_imapBackend;
            if ($_select) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                    . ' Select folder ' . $_folder->globalname);
                $backendFolderValues = $imapBackend->selectFolder(Felamimail_Model_Folder::encodeFolderName($_folder->globalname));
            }
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            // no imap connection
            throw new Felamimail_Exception_IMAPServiceUnavailable();
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
        if (! $_messageId instanceof Felamimail_Model_Message) {
            $message = $this->_backend->get($_messageId);
        } else {
            $message = $_messageId;
        }
        
        $structure = $message->getPartStructure($_partId);

        $attachments = array();
        
        if (!array_key_exists('parts', $structure)) {
            return $attachments;
        }
        
        foreach ($structure['parts'] as $part) {
            if ($part['type'] == 'multipart') {
                $attachments = $attachments + $this->getAttachments($message, $part['partId']);
            } else {
                if ($part['type'] == 'text' && 
                    (!is_array($part['disposition']) || ($part['disposition']['type'] == Zend_Mime::DISPOSITION_INLINE && !array_key_exists("parameters", $part['disposition'])))
                ) {
                    continue;
                }
                
                if (is_array($part['disposition']) && array_key_exists('parameters', $part['disposition']) && array_key_exists('filename', $part['disposition']['parameters'])) {
                    $filename = $part['disposition']['parameters']['filename'];
                } elseif (is_array($part['parameters']) && array_key_exists('name', $part['parameters'])) {
                    $filename = $part['parameters']['name'];
                } else {
                    $filename = 'Part ' . $part['partId'];
                }
                $attachments[] = array( 
                    'content-type' => $part['contentType'], 
                    'filename'     => $filename,
                    'partId'       => $part['partId'],
                    'size'         => $part['size'],
                    'description'  => $part['description']
                );
            }
        }
        
        return $attachments;
    }
    
    /**
     * delete messages from cache by folder
     * 
     * @param $_folder
     */
    public function deleteByFolder(Felamimail_Model_Folder $_folder)
    {
        $this->_backend->deleteByFolderId($_folder);
    }

    /**
     * update folder counts and returns list of affected folders
     * 
     * @param array $_folderCounter (folderId => unreadcounter)
     * @return Tinebase_Record_RecordSet of affected folders
     * @throws Felamimail_Exception
     */
    protected function _updateFolderCounts($_folderCounter)
    {
        foreach ($_folderCounter as $folderId => $counter) {
            $folder = Felamimail_Controller_Folder::getInstance()->get($folderId);
            
            // get error condition and update array by checking $counter keys
            if (array_key_exists('incrementUnreadCounter', $counter)) {
                // this is only used in clearFlags() atm
                $errorCondition = ($folder->cache_unreadcount + $counter['incrementUnreadCounter'] > $folder->cache_totalcount);
                $updatedCounters = array(
                    'cache_unreadcount' => '+' . $counter['incrementUnreadCounter'],
                );
            } else if (array_key_exists('decrementMessagesCounter', $counter) && array_key_exists('decrementUnreadCounter', $counter)) {
                $errorCondition = ($folder->cache_unreadcount < $counter['decrementUnreadCounter'] || $folder->cache_totalcount < $counter['decrementMessagesCounter']);
                $updatedCounters = array(
                    'cache_totalcount'  => '-' . $counter['decrementMessagesCounter'],
                    'cache_unreadcount' => '-' . $counter['decrementUnreadCounter']
                );
            } else {
                throw new Felamimail_Exception('Wrong folder counter given: ' . print_r($_folderCounter, TRUE));
            }
            
            if ($errorCondition) {
                // something went wrong => recalculate counter
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                    ' folder counters dont match => refresh counters'
                );
                $updatedCounters = Felamimail_Controller_Cache_Folder::getInstance()->getCacheFolderCounter($folder);
            }
            
            Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, $updatedCounters);
        }
        
        return Felamimail_Controller_Folder::getInstance()->getMultiple(array_keys($_folderCounter));
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
}
