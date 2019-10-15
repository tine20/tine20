<?php
/**
 * class to hold message cache data
 * 
 * @package     Felamimail
 * @subpackage    Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold message cache data
 * 
 * @package     Felamimail
 * @subpackage  Model
 * @property    string  $account_id         the account id
 * @property    string  $folder_id          the folder id
 * @property    string  $original_id        cache id of original message if replying / forwarding
 * @property    string  $subject            the subject of the email
 * @property    string  $from_email         the address of the sender (from)
 * @property    string  $from_name          the name of the sender (from)
 * @property    string  $sender             the sender of the email
 * @property    string  $content_type       the content type of the message
 * @property    string  $body_content_type  the content type of the message body
 * @property    array   $to                 the to receipients
 * @property    array   $cc                 the cc receipients
 * @property    array   $bcc                the bcc receipients
 * @property    array   $structure          the message structure
 * @property    array   $attachments        the attachments
 * @property    string  $messageuid         the message uid on the imap server
 * @property    string  $reply_to           reply-to header
 * @property    array   $preparedParts      prepared parts
 * @property    integer $reading_conf       true if it must send a reading confirmation
 * @property    boolean $massMailingFlag    true if message should be treated as mass mailing
 * @property    array   $fileLocations      file locations of this message
 */
class Felamimail_Model_Message extends Tinebase_Record_Abstract
{
    /**
     * message content type (rfc822)
     *
     */
    const CONTENT_TYPE_MESSAGE_RFC822 = 'message/rfc822';

    /**
     * content type html
     *
     */
    const CONTENT_TYPE_HTML = 'text/html';

    /**
     * content type plain text
     *
     */
    const CONTENT_TYPE_PLAIN = 'text/plain';

    /**
     * content type multipart/alternative
     */
    const CONTENT_TYPE_MULTIPART = 'multipart/alternative';
    
    /**
     * content type multipart/related
     */
    const CONTENT_TYPE_MULTIPART_RELATED = 'multipart/related';
    
    /**
     * content type multipart/mixed
     */
    const CONTENT_TYPE_MULTIPART_MIXED = 'multipart/mixed';
    
    /**
     * content type text/calendar
     */
    const CONTENT_TYPE_CALENDAR = 'text/calendar';
    
    /**
     * content type text/vcard
     */
    const CONTENT_TYPE_VCARD = 'text/vcard';
    
    /**
     * attachment filename regexp 
     *
     */
    const ATTACHMENT_FILENAME_REGEXP = "/name=\"(.*)\"/";
    
    /**
     * quote string ("> ")
     * 
     * @var string
     */
    const QUOTE = '&gt; ';
    
    /**
     * key in $_validators/$_properties array for the field which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Felamimail';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'account_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'original_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'original_part_id'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'messageuid'            => array(Zend_Filter_Input::ALLOW_EMPTY => false), 
        'message_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'folder_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'subject'               => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'from_email'            => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'from_name'             => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'sender'                => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'to'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'cc'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'bcc'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'received'              => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'sent'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'size'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'flags'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'timestamp'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'body'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // this is: refactor body and content type handling / or names
        'body_content_type_of_body_property_of_this_record' => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => self::CONTENT_TYPE_PLAIN,
            array('InArray', array(self::CONTENT_TYPE_HTML, self::CONTENT_TYPE_PLAIN)),
        ),
        'structure'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'text_partid'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'html_partid'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'has_attachment'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'reply_to'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'headers'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // this is: content_type_of_envelope
        'content_type'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // this is: body_content_type_from_message_structrue
        'body_content_type'     => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => self::CONTENT_TYPE_PLAIN,
            array('InArray', array(self::CONTENT_TYPE_HTML, self::CONTENT_TYPE_PLAIN)),
        ),
        'attachments'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // Felamimail_Message object
        'message'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // prepared parts (iMIP invitations, contact vcards, ...)
        'preparedParts'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'reading_conf'          => array(Zend_Filter_Input::ALLOW_EMPTY => true,
                                         Zend_Filter_Input::DEFAULT_VALUE => 0),
        'massMailingFlag'       => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => 0,
            array('InArray', array(0, 1)),
        ),
        'fileLocations'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'timestamp',
        'received',
        'sent',
    );

    /**
     * create Felamimail message from raw mime content
     *
     * @param string $_mimeContent
     * @return Felamimail_Model_Message
     *
     * @todo move to converter?
     */
    public static function createFromMime($_mimeContent)
    {
        $message = \ZBateson\MailMimeParser\Message::from($_mimeContent);

        $data = [];
        foreach (['date' => 'sent'] as $headerKey => $property) {
            $headerValue = $message->getHeader($headerKey);
            if ($headerValue) {
                $data[$property] = new Tinebase_DateTime($headerValue->getDateTime());
            }
        }
        foreach (['subject', ] as $headerKey) {
            $data[$headerKey] = $message->getHeaderValue($headerKey);
        }
        $from = $message->getHeader('from');
        if ($from) {
            $data['from_email'] = $from->getValue();
            $data['from_name'] = $from->getPersonName();
        }
        foreach (['to', 'cc', 'bcc'] as $headerKey) {
            $headerValue = $message->getHeader($headerKey);
            if (! $headerValue) {
                continue;
            }
            $addresses = $headerValue->getAddresses();
            $data[$headerKey] = [];
            foreach ($addresses as $address) {
                // TODO add name?
                $data[$headerKey][] = $address->getEmail();
            }
        }

        $contentPart = $message->getPartByMimeType(Zend_Mime::TYPE_HTML);
        if (! $contentPart) {
            $contentPart = $message->getPartByMimeType(Zend_Mime::TYPE_TEXT);
        }

        if ($contentPart) {
            if ($contentPart->getContentType() == Zend_Mime::TYPE_TEXT) {
                $data['body'] = $contentPart->getContent();
                $data['body_content_type'] = Zend_Mime::TYPE_TEXT;
            } else {
                if (method_exists($contentPart, 'getHtmlContent')) {
                    $data['body'] = $contentPart->getHtmlContent();
                    // TODO why do we need this?
                    $data['body_content_type_of_body_property_of_this_record'] = Zend_Mime::TYPE_HTML;
                } else {
                    $data['body'] = $contentPart->getContent();
                }

                $data['body_content_type'] = Zend_Mime::TYPE_HTML;
            }
        }

        $data['attachments'] = [];
        foreach ($message->getAllAttachmentParts() as $id => $attachment) {
            /** @var ZBateson\MailMimeParser\Message\Part\MessagePart $attachment */
            /** @var GuzzleHttp\Psr7\Stream $partStream */
            $partStream = $attachment->getStream();
            $contentStream = $attachment->getContentStream();
            $data['attachments'][] = [
                'content-type' => $attachment->getContentType(),
                'filename'     => $attachment->getFilename(),
                'partId'       => $id,
                // TODO why can't we get the size from the contentStream?
                //'size'         => $contentStream->getSize(),
                'size'         => $partStream->getSize(),
                // unset before sending to client?
                'contentstream'=> $contentStream,
            ];
        }
        if (count($data['attachments']) > 0) {
            $data['has_attachment'] = true;
        }

        return new Felamimail_Model_Message($data);
    }

    protected function _getBodyContent($messagePart, &$data)
    {
    }

    /**
     * gets record related properties
     * 
     * @param string _name of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @return mixed value of property
     */
    public function __get($_name)
    {
        $result = parent::__get($_name);
        
        if ($_name === 'structure' && empty($result)) {
            $result = $this->_fetchStructure();
        }
        
        return $result;
    }
    
    /**
     * fetch structure from cache or imap server, parse it and store it into cache
     * 
     * @return array
     */
    protected function _fetchStructure()
    {
        $cacheId = $this->_getStructureCacheId();
        $cache = Tinebase_Core::getCache();
        if ($cache->test($cacheId)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Getting message structure from cache: ' . $cacheId);
            $result = $cache->load($cacheId);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Getting message structure from IMAP server.');
            
            try {
                $summary = Felamimail_Controller_Cache_Message::getInstance()->getMessageSummary($this->messageuid, $this->account_id, $this->folder_id);
                $result = $summary['structure'];
            } catch (Zend_Mail_Protocol_Exception $zmpe) {
                // imap server might have gone away
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                    . ' IMAP protocol error during summary fetching: ' . $zmpe->getMessage());
                $result = array();
            }
            $this->_setStructure($result);
        }
        
        return $result;
    }
    
    /**
     * get cache id for structure
     * 
     * @return string
     */
    protected function _getStructureCacheId()
    {
        return 'messageStructure' . $this->folder_id . $this->messageuid;
    }
    
    /**
     * set structure and save into cache
     * 
     * @param array $structure
     */
    protected function _setStructure($structure)
    {
        if (! empty($structure['partId'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' Don\'t cache structure of subparts');
            return;
        }
        
        $cacheId = $this->_getStructureCacheId();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Caching message structure: ' . $cacheId);
        Tinebase_Core::getCache()->save($structure, $cacheId, array('messageStructure'), 86400); // 24 hours
        
        $this->structure = $structure;
    }
    
    /**
     * check if message has \SEEN flag
     * 
     * @return boolean
     */
    public function hasSeenFlag()
    {
        return (is_array($this->flags) && in_array(Zend_Mail_Storage::FLAG_SEEN, $this->flags));
    }
    
    /**
     * Send the reading confirmation in a message who has the correct header and is not seen yet
     *
     * @return void
     */
    public function sendReadingConfirmation()
    {
        if (! is_array($this->headers)) {
            $this->headers = Felamimail_Controller_Message::getInstance()->getMessageHeaders($this->getId(), NULL, TRUE);
        }
        
        if ((isset($this->headers['disposition-notification-to']) || array_key_exists('disposition-notification-to', $this->headers)) && $this->headers['disposition-notification-to']) {
            $translate = Tinebase_Translation::getTranslation($this->_application);
            $from = Felamimail_Controller_Account::getInstance()->get($this->account_id);
            
            $message = new Felamimail_Model_Message();
            $message->account_id = $this->account_id;
            
            $punycodeConverter = Felamimail_Controller_Message::getInstance()->getPunycodeConverter();
            $to = Felamimail_Message::convertAddresses($this->headers['disposition-notification-to'], $punycodeConverter);
            if (empty($to)) {
                throw new Felamimail_Exception('disposition-notification-to header does not contain a valid email address');
            }
             
            $message->content_type = Felamimail_Model_Message::CONTENT_TYPE_HTML;
            $message->to           = $to[0]['email'];
            $message->subject      = $translate->_('Reading Confirmation:') . ' '. $this->subject;
            $message->body         = $translate->_('Your message:'). ' ' . $this->subject . "\n" .
                                     $translate->_('Received on')  . ' ' . $this->received . "\n" .
                                     $translate->_('Was read by:') . ' ' . $from->from .  ' <' . $from->email .'> ' .
                                     $translate->_('on') . ' ' . (date('Y-m-d H:i:s'));
            $message->body         = Tinebase_Mail::convertFromTextToHTML($message->body, 'felamimail-body-blockquote');
            Felamimail_Controller_Message_Send::getInstance()->sendMessage($message);
        }
    }
    
    /**
     * parse headers and set 'date', 'from', 'to', 'cc', 'bcc', 'subject', 'sender' fields
     * 
     * @param array $_headers
     * @return void
     */
    public function parseHeaders(array $_headers)
    {
        // remove duplicate headers (which can't be set twice in real life)
        foreach (array('date', 'from', 'subject', 'sender') as $field) {
            if (isset($_headers[$field]) && is_array($_headers[$field])) {
                $_headers[$field] = $_headers[$field][0];
            }
        }

        if (isset($_headers['subject'])) {
            // @see 0008644: error when sending mail with note (wrong charset)
            // @todo might be removed in the future - but check if database supports utf8mb4 first
            $this->subject = Tinebase_Core::filterInputForDatabase(Felamimail_Message::convertText($_headers['subject']));
        } else {
            $this->subject = null;
        }

        if ((isset($_headers['date']) || array_key_exists('date', $_headers))) {
            $this->sent = Felamimail_Message::convertDate($_headers['date']);
        } elseif ((isset($_headers['resent-date']) || array_key_exists('resent-date', $_headers))) {
            $this->sent = Felamimail_Message::convertDate($_headers['resent-date']);
        }
        
        $punycodeConverter = Felamimail_Controller_Message::getInstance()->getPunycodeConverter();
        
        foreach (array('to', 'cc', 'bcc', 'from', 'sender', 'message-id') as $field) {
            if (isset($_headers[$field])) {
                if (is_array($_headers[$field])) {
                    $value = array();
                    foreach ($_headers[$field] as $headerValue) {
                        $value = array_merge($value, Felamimail_Message::convertAddresses($headerValue, $punycodeConverter));
                    }
                    $this->$field = $value;
                } else if ($field === 'message-id') {
                    $this->message_id = $_headers[$field];
                } else {
                    $value = Felamimail_Message::convertAddresses($_headers[$field], $punycodeConverter);
                    switch ($field) {
                        case 'from':
                            $this->from_email = (isset($value[0]) && (isset($value[0]['email']) || array_key_exists('email', $value[0]))) ? $value[0]['email'] : '';
                            $this->from_name = (isset($value[0]) && (isset($value[0]['name']) || array_key_exists('name', $value[0])) && ! empty($value[0]['name'])) ? $value[0]['name'] : $this->from_email;
                            break;
                            
                        case 'sender':
                            $this->sender = (isset($value[0]) && (isset($value[0]['email']) || array_key_exists('email', $value[0]))) ? '<' . $value[0]['email'] . '>' : '';
                            if ((isset($value[0]) && (isset($value[0]['name']) || array_key_exists('name', $value[0])) && ! empty($value[0]['name']))) {
                                $this->sender = '"' . $value[0]['name'] . '" ' . $this->sender;
                            }
                            break;
                            
                        default:
                            $this->$field = $value;
                    }
                }
            }
        }
    }
    
    /**
     * parse message structure to get content types
     * 
     * @param array $_structure
     * @return void
     */
    public function parseStructure($_structure = NULL)
    {
        if ($_structure !== NULL) {
            $this->_setStructure($_structure);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Parsing structure: ' . print_r($this->structure, TRUE));
        
        $this->content_type = (array_key_exists('contentType', $this->structure) && (!is_null($this->structure['contentType']))) ? $this->structure['contentType'] : Zend_Mime::TYPE_TEXT;
        $this->_setBodyContentType();
    }
    
    /**
     * parse parts to set body content type
     */
    protected function _setBodyContentType()
    {
        if ((isset($this->structure['parts']) || array_key_exists('parts', $this->structure))) {
            $bodyContentTypes = $this->_getBodyContentTypes($this->structure['parts']);
            $this->body_content_type = (in_array(self::CONTENT_TYPE_HTML, $bodyContentTypes)) ? self::CONTENT_TYPE_HTML : self::CONTENT_TYPE_PLAIN;
        } else {
            $this->body_content_type = (in_array($this->content_type, array(self::CONTENT_TYPE_HTML, self::CONTENT_TYPE_PLAIN))) ? $this->content_type : self::CONTENT_TYPE_PLAIN;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Set body content type to ' . $this->body_content_type);
    }
    
    /**
     * get all content types of mail body
     * 
     * @param array $_parts
     * @return array
     */
    protected function _getBodyContentTypes($_parts)
    {
        $_bodyContentTypes = array();
        foreach ($_parts as $part) {
            if (is_array($part) && (isset($part['contentType']) || array_key_exists('contentType', $part))) {
                if (in_array($part['contentType'], array(self::CONTENT_TYPE_HTML, self::CONTENT_TYPE_PLAIN))
                    && ! $this->_partIsAttachment($part))
                {
                    $_bodyContentTypes[] = $part['contentType'];
                } else if (in_array($part['contentType'], [
                        self::CONTENT_TYPE_MULTIPART,
                        self::CONTENT_TYPE_MULTIPART_RELATED,
                        self::CONTENT_TYPE_MULTIPART_MIXED
                    ]) && (isset($part['parts']) || array_key_exists('parts', $part)))
                {
                    $_bodyContentTypes = array_merge($_bodyContentTypes, $this->_getBodyContentTypes($part['parts']));
                }
            }
        }
        
        return $_bodyContentTypes;
    }
    
    /**
     * get message part structure
     * 
     * @param  string  $_partId                 the part id to search for
     * @param  boolean $_useMessageStructure    if you want to get only the messageStructure part
     * @return array
     */
    public function getPartStructure($_partId, $_useMessageStructure = TRUE)
    {
        $result = NULL;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Getting structure for part ' . $_partId . ' / complete structure: ' . print_r($this->structure, TRUE));

        // make sure structure is fetched
        $structure = $this->structure;
        if ($_partId == null) {
            // maybe we want no part at all => just return the whole structure
            $result = $structure;
        } else if (isset($structure['partId']) && $structure['partId'] == $_partId) {
            // maybe we want the first part => just return the whole structure
            $result = $structure;
        } else {
            // iterate structure to find the correct part
            $iterator = new RecursiveIteratorIterator(
                new RecursiveArrayIterator($structure),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $key => $value) {
                if ($key == $_partId && is_array($value) && isset($value['partId'])) {
                    $result = (
                        $_useMessageStructure
                        && isset($value['messageStructure'])
                        && (! isset($value['contentType']) || $value['contentType'] !== Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822)
                    )   ? $value['messageStructure']
                        : $value;
                    break;
                }
            }
        }
        
        if ($result === NULL) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . "Structure for partId $_partId not found!");
        }
        
        return $result;
    }
    
    /**
     * get body parts
     * 
     * @param array $_structure
     * @param string $_preferedMimeType
     * @return array
     */
    public function getBodyParts($_structure = NULL, $_preferedMimeType = Zend_Mime::TYPE_HTML)
    {
        $bodyParts = array();
        $structure = ($_structure !== NULL) ? $_structure : $this->structure;

        if (! is_array($structure)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Structure should be an array (' . $structure . ')');
            return $bodyParts;
        }
        
        if ((isset($structure['parts']) || array_key_exists('parts', $structure))) {
            $bodyParts = $bodyParts + $this->_parseMultipart($structure, $_preferedMimeType);
        } else {
            $bodyParts = $bodyParts + $this->_parseSinglePart(
                $structure,
                in_array($_preferedMimeType, array(Zend_Mime::TYPE_HTML, Zend_Mime::TYPE_TEXT))
                    && $structure['contentType'] !== Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822
            );
        }
        
        return $bodyParts;
    }
    
    /**
     * parse single part message
     * 
     * @param array $_structure
     * @return array
     */
    protected function _parseSinglePart(array $_structure, $_onlyGetNonAttachmentParts = TRUE)
    {
        $result = array();
        
        if (! (isset($_structure['type']) || array_key_exists('type', $_structure))
            || ($_structure['type'] != 'text' && $_structure['type'] != 'message')
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Structure has no type key or type != text: ' . print_r($_structure, TRUE));
            return $result;
        }
        
        if ($_onlyGetNonAttachmentParts && $this->_partIsAttachment($_structure)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Part is attachment: ' . print_r($_structure['disposition'], TRUE));
            return $result;
        }

        $partId = !empty($_structure['partId']) ? $_structure['partId'] : 1;
        
        $result[$partId] = $_structure;

        return $result;
    }
    
    /**
     * checks if part is attachment
     * 
     * @param array $_structure
     * @return boolean
     */
    protected function _partIsAttachment(array $_structure)
    {
        return (
            isset($_structure['disposition']['type']) && 
                ($_structure['disposition']['type'] == Zend_Mime::DISPOSITION_ATTACHMENT ||
                // treat as attachment if structure contains parameters 
                ($_structure['disposition']['type'] == Zend_Mime::DISPOSITION_INLINE && (isset($_structure['disposition']["parameters"]) || array_key_exists("parameters", $_structure['disposition']))
            )
        ));
    }
    
    /**
     * parse multipart message
     * 
     * @param array $_structure
     * @param string $_preferedMimeType
     * @return array
     */
    protected function _parseMultipart(array $_structure, $_preferedMimeType = Zend_Mime::TYPE_HTML)
    {
        $result = array();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Structure: ' . print_r($_structure, TRUE));
        
        if ($_structure['subType'] == 'alternative' || $_structure['subType'] == 'related') {
            foreach ($_structure['parts'] as $part) {
                $foundParts[$part['contentType']] = $part['partId'];
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Found parts: ' . print_r($foundParts, TRUE));
            
            if ((isset($foundParts[$_preferedMimeType]) || array_key_exists($_preferedMimeType, $foundParts))) {
                // found our desired body part
                $result[$foundParts[$_preferedMimeType]] = $_structure['parts'][$foundParts[$_preferedMimeType]];
            }

            $multipartTypes = array(self::CONTENT_TYPE_MULTIPART, self::CONTENT_TYPE_MULTIPART_RELATED, self::CONTENT_TYPE_MULTIPART_MIXED);
            foreach ($multipartTypes as $multipartType) {
                if ((isset($foundParts[$multipartType]) || array_key_exists($multipartType, $foundParts))) {
                    // dig deeper into the structure to find the desired part(s)
                    $partStructure = $_structure['parts'][$foundParts[$multipartType]];
                    /** @noinspection OpAssignShortSyntaxInspection */
                    $result = $result + $this->getBodyParts($partStructure, $_preferedMimeType);
                }
            }

            $alternativeType = ($_preferedMimeType == Zend_Mime::TYPE_HTML) 
                ? Zend_Mime::TYPE_TEXT 
                : (($_preferedMimeType == Zend_Mime::TYPE_TEXT) ? Zend_Mime::TYPE_HTML : '');
            if (empty($result) && (isset($foundParts[$alternativeType]) || array_key_exists($alternativeType, $foundParts))) {
                // found the alternative body part
                $result[$foundParts[$alternativeType]] = $_structure['parts'][$foundParts[$alternativeType]];
            }
        } else {
            foreach ($_structure['parts'] as $part) {
                /** @noinspection OpAssignShortSyntaxInspection */
                if ($part['contentType'] !== self::CONTENT_TYPE_MESSAGE_RFC822) {
                    $result = $result + $this->getBodyParts($part, $_preferedMimeType);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * parse structure to get text_partid and html_partid
     * 
     * @deprecated should be replaced by getBodyParts
     * @see 0007742: refactoring: replace parseBodyParts with getBodyParts
     */
    public function parseBodyParts()
    {
        $bodyParts = $this->_getBodyPartIds($this->structure);
        if (isset($bodyParts['text'])) {
            $this->text_partid = $bodyParts['text'];
        }
        if (isset($bodyParts['html'])) {
            $this->html_partid = $bodyParts['html'];
        }
    }
    
    /**
     * get body part ids
     * 
     * @param array $_structure
     * @return array
     */
    protected function _getBodyPartIds(array $_structure)
    {
        $result = array();

        if (! isset($_structure['type'])) {
            return $result;
        }

        if ($_structure['type'] == 'text') {
            $result = array_merge($result, $this->_getTextPartId($_structure));
        } elseif($_structure['type'] == 'multipart') {
            $result = array_merge($result, $this->_getMultipartIds($_structure));
        }
        
        return $result;
    }

    /**
     * get multipart ids
     * 
     * @param array $_structure
     * @return array
     */
    protected function _getMultipartIds(array $_structure)
    {
        $result = array();
        
        if ($_structure['subType'] == 'alternative' || $_structure['subType'] == 'mixed' || 
            $_structure['subType'] == 'signed' || $_structure['subType'] == 'related') {
            foreach ($_structure['parts'] as $part) {
                $result = array_merge($result, $this->_getBodyPartIds($part));
            }
        } else {
            // ignore other types for now
            #var_dump($_structure);
            #throw new Exception('unsupported multipart');
        }
        
        return $result;
    }
    
    /**
     * get text part id
     * 
     * @param array $_structure
     * @return array
     */
    protected function _getTextPartId(array $_structure)
    {
        $result = array();

        if ($this->_partIsAttachment($_structure)) {
            return $result;
        }
        
        if ($_structure['subType'] == 'plain') {
            $result['text'] = !empty($_structure['partId']) ? $_structure['partId'] : 1;
        } elseif($_structure['subType'] == 'html') {
            $result['html'] = !empty($_structure['partId']) ? $_structure['partId'] : 1;
        }
        
        return $result;
    }
    
    /**
     * fills a record from json data
     *
     * @param array $recordData
     * 
     * @todo    get/detect delimiter from row? could be ';' or ','
     * @todo    add recipient names
     */
    protected function _setFromJson(array &$recordData)
    {
        // explode email addresses if multiple
        $recipientType = array('to', 'cc', 'bcc');
        $delimiter = ';';

        foreach ($recipientType as $field) {
            if (!empty($recordData[$field])) {
                $recipients = array();
                if (! is_array($recordData[$field])) {
                    throw new Tinebase_Exception_Record_Validation($field . ' property should be an array');
                }
                foreach ($recordData[$field] as $addresses) {
                    if (substr_count($addresses, '@') > 1) {
                        $recipients = array_merge($recipients, explode($delimiter, $addresses));
                    } else {
                        // single recipient
                        $recipients[] =  $this->sanitizeMailAddress($addresses);
                    }
                }
                
                foreach ($recipients as $key => &$recipient) {
                    // extract email address if name and address given
                    if (preg_match('/(.*)<(.*)>/', $recipient, $matches) > 0) {
                        $recipient = $this->sanitizeMailAddress($matches[2]);
                    }
                    if (empty($recipient)) {
                        unset($recipients[$key]);
                    }
                }
                unset($recipient);

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recipients, true));
                
                $recordData[$field] = array_unique($recipients);
            }
        }
    }

    /**
     * Mails often copied from word and so on and contain problematic characters, non printable characters and whitespaces.
     *
     * @param $mail
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function sanitizeMailAddress($mail)
    {
        // Ensure encoding
        $mail = Tinebase_Helper::mbConvertTo($mail);

        // Remove non printable
        $mail = \preg_replace('/[[:^print:]]/u', '', $mail);

        // Remove whitespaces
        $mail = \trim($mail);

        return $mail;
    }

    /**
     * get body as plain text
     * 
     * @return string
     */
    public function getPlainTextBody()
    {
        if ($this->content_type === Felamimail_Model_Message::CONTENT_TYPE_PLAIN) {
            $result = $this->body;
        } else {
            $result = self::convertHTMLToPlainTextWithQuotes($this->body);
        }
        
        return $result;
    }
    
    /**
     * convert html to plain text with replaced blockquotes, stripped tags and replaced <br>s
     * -> use DOM extension
     * 
     * @param string $_html
     * @param string $_eol
     * @return string
     */
    public static function convertHTMLToPlainTextWithQuotes($_html, $_eol = "\n")
    {
        $html = $_html;
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Original body string: ' . $_html);
        
        $dom = new DOMDocument('1.0', 'UTF-8');
        
        // body tag might be missing
        if (strpos($html, '<body>') === FALSE) {
            $html = '<body>' . $_html . '</body>';
        }
        // need to set meta tag to make sure that the encoding is done right (@see https://bugs.php.net/bug.php?id=32547)
        if (strpos($html, '<html>') === FALSE) {
            $html = '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/></head>' . $html;
        }
        // use a hack to make sure html is loaded as utf-8 (@see http://php.net/manual/en/domdocument.loadhtml.php#95251)
        // we ignore errors here as html messages aren't always valid xml
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' HTML (DOMDocument): ' . $dom->saveHTML());
        
        $bodyElements = $dom->getElementsByTagName('body');
        if ($bodyElements->length > 0) {
            $firstBodyNode = $bodyElements->item(0);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Before quoting: ' . $firstBodyNode->nodeValue);
            $result = self::addQuotesAndStripTags($firstBodyNode, 0, $_eol);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' After quoting: ' . $result);
            $result = html_entity_decode($result, ENT_COMPAT, 'UTF-8');
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Entities decoded: ' . $result);
        }
        
        return $result;
    }
    
    /**
     * convert blockquotes to quotes ("> ") and strip tags
     * 
     * this function uses tidy or DOM to recursivly walk the dom tree of the html mail
     * @see http://php.net/manual/de/tidy.root.php
     * @see http://php.net/manual/en/book.dom.php
     * 
     * @param tidyNode|DOMNode $_node
     * @param integer $_quoteIndent
     * @param string $_eol
     * @return string
     * 
     * @todo we can transform more tags here, i.e. the <strong>BOLDTEXT</strong> tag could be replaced with *BOLDTEXT*
     * @todo think about removing the tidy code
     * @todo reduce complexity
     */
    public static function addQuotesAndStripTags($_node, $_quoteIndent = 0, $_eol = "\n")
    {
        $result = '';
        
        $hasChildren = ($_node instanceof DOMNode) ? $_node->hasChildNodes() : $_node->hasChildren();
        $nameProperty = ($_node instanceof DOMNode) ? 'nodeName' : 'name';
        $valueProperty = ($_node instanceof DOMNode) ? 'nodeValue' : 'value';

        $divNewline = FALSE;
        
        if ($hasChildren) {
            $lastChild = NULL;
            $children = ($_node instanceof DOMNode) ? $_node->childNodes : $_node->child;
            
            if ($_node->{$nameProperty} == 'div') {
                $divNewline = TRUE;
            }
            
            foreach ($children as $child) {
                
                $isTextLeaf = ($child instanceof DOMNode) ? $child->{$nameProperty} == '#text' : ! $child->{$nameProperty};
                if ($isTextLeaf) {
                    // leaf -> add quotes and append to content string
                    if ($_quoteIndent > 0) {
                        $result .= str_repeat(self::QUOTE, $_quoteIndent) . $child->{$valueProperty};
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . 
                            "value: " . $child->{$valueProperty} . " / name: " . $_node->{$nameProperty} . "\n");
                        if ($divNewline) {
                            $result .=  $_eol . str_repeat(self::QUOTE, $_quoteIndent);
                            $divNewline = FALSE;
                        }
                        $result .= $child->{$valueProperty};
                    }
                    
                } else if ($child->{$nameProperty} == 'blockquote') {
                    //  opening blockquote
                    $_quoteIndent++;
                    
                } else if ($child->{$nameProperty} == 'br') {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' .
                        "value: " . $child->{$valueProperty} . " / name: " . $_node->{$nameProperty} . "\n");
                    // reset quoted state on newline
                    if ($lastChild !== NULL && $lastChild->{$nameProperty} == 'br') {
                        // add quotes to repeating newlines
                        $result .= str_repeat(self::QUOTE, $_quoteIndent);
                    }
                    $result .= $_eol;
                    $divNewline = FALSE;
                }
                
                $result .= self::addQuotesAndStripTags($child, $_quoteIndent, $_eol);
                
                if ($child->{$nameProperty} == 'blockquote') {
                    // closing blockquote
                    $_quoteIndent--;
                    // add newline after last closing blockquote
                    if ($_quoteIndent == 0) {
                        $result .= $_eol;
                    }
                }
                
                $lastChild = $child;
            }
            
            // add newline if closing div
            if ($divNewline) {
                $result .=  $_eol . str_repeat(self::QUOTE, $_quoteIndent);
            }
        }
        
        return $result;
    }
}
