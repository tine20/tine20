<?php
/**
 * class to hold message cache data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold message cache data
 * 
 * @package     Felamimail
 * @property    string  $subject        the subject of the email
 * @property    string  $from_email     the address of the sender (from)
 * @property    string  $from_name      the name of the sender (from)
 * @property    string  $sender         the sender of the email
 * @property    string  $content_type   the content type of the message
 * @property    string  $body_content_type   the content type of the message body
 * @property    array   $to             the to receipients
 * @property    array   $cc             the cc receipients
 * @property    array   $bcc            the bcc receipients
 * @property    array   $structure      the message structure
 * @property    array   $attachments    the attachments
 * @property    string  $messageuid     the message uid on the imap server
 * @property    array   $preparedParts  prepared parts
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
     *
     */
    const CONTENT_TYPE_MULTIPART = 'multipart/alternative';
    
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
     * email address regexp
     */
    const EMAIL_ADDRESS_REGEXP = '/([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,5})/i'; 
    
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
        'folder_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => false), 
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
        'structure'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'text_partid'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'html_partid'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'has_attachment'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'headers'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'content_type'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'body_content_type'     => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => self::CONTENT_TYPE_PLAIN,
            'InArray' => array(self::CONTENT_TYPE_HTML, self::CONTENT_TYPE_PLAIN)
        ),
        'attachments'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // save email as contact note
        'note'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // Felamimail_Message object
        'message'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // prepared parts (iMIP invitations, contact vcards, ...)
        'preparedParts'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
     * check if message has \SEEN flag
     * 
     * @return boolean
     */
    public function hasSeenFlag()
    {
        return (is_array($this->flags) && in_array(Zend_Mail_Storage::FLAG_SEEN, $this->flags));
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
        foreach (array('date', 'from', 'to', 'cc', 'bcc', 'subject', 'sender') as $field) {
            if (isset($_headers[$field]) && is_array($_headers[$field])) {
                $_headers[$field] = $_headers[$field][0];
            }
        }
        
        $this->subject = (isset($_headers['subject'])) ? Felamimail_Message::convertText($_headers['subject']) : null;
        
        if (array_key_exists('date', $_headers)) {
            $this->sent = Felamimail_Message::convertDate($_headers['date']);
        } elseif (array_key_exists('resent-date', $_headers)) {
            $this->sent = Felamimail_Message::convertDate($_headers['resent-date']);
        }
        
        $punycodeConverter = Felamimail_Controller_Message::getInstance()->getPunycodeConverter();
        
        foreach (array('to', 'cc', 'bcc', 'from', 'sender') as $field) {
            if (isset($_headers[$field])) {
                $value = Felamimail_Message::convertAddresses($_headers[$field], $punycodeConverter);
                
                switch($field) {
                    case 'from':
                        $this->from_email = (isset($value[0]) && array_key_exists('email', $value[0])) ? $value[0]['email'] : '';
                        $this->from_name = (isset($value[0]) && array_key_exists('name', $value[0]) && ! empty($value[0]['name'])) ? $value[0]['name'] : $this->from_email;
                        break;
                    case 'sender':
                        $this->sender = (isset($value[0]) && array_key_exists('email', $value[0])) ? '<' . $value[0]['email'] . '>' : '';
                        if ((isset($value[0]) && array_key_exists('name', $value[0]) && ! empty($value[0]['name']))) {
                            $this->sender = '"' . $value[0]['name'] . '" ' . $this->sender;
                        }
                        break;
                    default:
                        $this->$field = $value;
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
            $this->structure = $_structure;
        }
        $this->content_type  = isset($this->structure['contentType']) ? $this->structure['contentType'] : Zend_Mime::TYPE_TEXT;
        $this->_setBodyContentType();
    }
    
    /**
     * parse parts to set body content type
     */
    protected function _setBodyContentType()
    {
        if (array_key_exists('parts', $this->structure)) {
            $bodyContentTypes = $this->_getBodyContentTypes($this->structure['parts']);
            // HTML > plain
            $this->body_content_type = (in_array(self::CONTENT_TYPE_HTML, $bodyContentTypes)) ? self::CONTENT_TYPE_HTML : self::CONTENT_TYPE_PLAIN;
        } else {
            $this->body_content_type = $this->content_type;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set body content type to ' . $this->body_content_type);
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
            if (is_array($part) && array_key_exists('contentType', $part)) {
                if (in_array($part['contentType'], array(self::CONTENT_TYPE_HTML, self::CONTENT_TYPE_PLAIN)) && ! $this->_partIsAttachment($part)) {
                    $_bodyContentTypes[] = $part['contentType'];
                } else if ($part['contentType'] == self::CONTENT_TYPE_MULTIPART && array_key_exists('parts', $part)) {
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
        
        if ($_partId == null) {
            // maybe we want no part at all => just return the whole structure
            $result = $this->structure;
        } else if ($this->structure['partId'] == $_partId) {
            // maybe we want the first part => just return the whole structure
            $result = $this->structure;
        } else {
            // iterate structure to find the correct part
            $iterator = new RecursiveIteratorIterator(
                new RecursiveArrayIterator($this->structure),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $key => $value) {
                if ($key == $_partId && is_array($value) && array_key_exists('partId', $value)) {
                    $result = ($_useMessageStructure && is_array($value) && array_key_exists('messageStructure', $value)) ? $value['messageStructure'] : $value;
                }
            }
        }
        
        if ($result === NULL) {
            throw new Felamimail_Exception("Structure for partId $_partId not found!");
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
        
        if (array_key_exists('parts', $structure)) {
            $bodyParts = $bodyParts + $this->_parseMultipart($structure, $_preferedMimeType);
        } else {
            $bodyParts = $bodyParts + $this->_parseSinglePart($structure, in_array($_preferedMimeType, array(Zend_Mime::TYPE_HTML, Zend_Mime::TYPE_TEXT)));
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
        
        if (! array_key_exists('type', $_structure) || $_structure['type'] != 'text') {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Structure has no type key or type != text: ' . print_r($_structure, TRUE));
            return $result;
        }
        
        if ($_onlyGetNonAttachmentParts && $this->_partIsAttachment($_structure)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Part is attachment: ' . $_structure['disposition']);
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
                ($_structure['disposition']['type'] == Zend_Mime::DISPOSITION_INLINE && array_key_exists("parameters", $_structure['disposition'])
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_structure, TRUE));
        
        if ($_structure['subType'] == 'alternative') {
            $alternativeType = ($_preferedMimeType == Zend_Mime::TYPE_HTML) 
                ? Zend_Mime::TYPE_TEXT 
                : (($_preferedMimeType == Zend_Mime::TYPE_TEXT) ? Zend_Mime::TYPE_HTML : '');
            
            foreach ($_structure['parts'] as $part) {
                $foundParts[$part['contentType']] = $part['partId'];
            }
            
            if (array_key_exists($_preferedMimeType, $foundParts)) {
                $result[$foundParts[$_preferedMimeType]] = $_structure['parts'][$foundParts[$_preferedMimeType]];
            } else if (array_key_exists($alternativeType, $foundParts)) {
                $result[$foundParts[$alternativeType]] = $_structure['parts'][$foundParts[$alternativeType]];
            } else if (array_key_exists('multipart/mixed', $foundParts)) {
                $result = $result + $this->getBodyParts($_structure['parts'][$foundParts['multipart/mixed']], $_preferedMimeType);
            }
        } else {
            foreach ($_structure['parts'] as $part) {
                $result = $result + $this->getBodyParts($part, $_preferedMimeType);
            }
        }
        
        return $result;
    }
    
    /**
     * parse structure to get text_partid and html_partid
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
                foreach ($recordData[$field] as $addresses) {
                    if (substr_count($addresses, '@') > 1) {
                        $recipients = array_merge($recipients, explode($delimiter, $addresses));
                    } else {
                        // single recipient
                        $recipients[] = $addresses;
                    }
                }
                
                foreach ($recipients as $key => &$recipient) {
                    // get address 
                    // @todo get name here
                    //<*([a-zA-Z@_\-0-9\.]+)>*/
                    if (preg_match(self::EMAIL_ADDRESS_REGEXP, $recipient, $matches) > 0) {
                        $recipient = $matches[1];
                    }
                    if (empty($recipient)) {
                        unset($recipients[$key]);
                    }
                }

                //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recipients, true));
                
                $recordData[$field] = array_unique($recipients);
            }
        }
    }    

    /**
     * get body as plain text
     * 
     * @return string
     */
    public function getPlainTextBody()
    {
        $result = self::convertHTMLToPlainTextWithQuotes($this->body);
        
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
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        
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
     */
    public static function addQuotesAndStripTags($_node, $_quoteIndent = 0, $_eol = "\n") {
        
        $result = '';
        
        $hasChildren = ($_node instanceof DOMNode) ? $_node->hasChildNodes() : $_node->hasChildren();
        $nameProperty = ($_node instanceof DOMNode) ? 'nodeName' : 'name';
        $valueProperty = ($_node instanceof DOMNode) ? 'nodeValue' : 'value';
        
        if ($hasChildren) {
            $lastChild = NULL;
            $children = ($_node instanceof DOMNode) ? $_node->childNodes : $_node->child;
            
            foreach ($children as $child) {
                $isTextLeaf = ($child instanceof DOMNode) ? $child->{$nameProperty} == '#text' : ! $child->{$nameProperty};
                if ($isTextLeaf) { 
                    // leaf -> add quotes and append to content string
                    if ($_quoteIndent > 0) {
                        $result .= str_repeat(self::QUOTE, $_quoteIndent) . $child->{$valueProperty};
                        // add newline if parent is div
                        if ($_node->{$nameProperty} == 'div') {
                            $result .=  $_eol . str_repeat(self::QUOTE, $_quoteIndent);
                        }
                    } else {
                        // add newline if parent is div
                        if ($_node->{$nameProperty} == 'div') {
                            $result .= $_eol;
                        }
                        $result .= $child->{$valueProperty};
                    }
                    
                } else if ($child->{$nameProperty} == 'blockquote') {
                    //  opening blockquote
                    $_quoteIndent++;
                    
                } else if ($child->{$nameProperty} == 'br') {
                    // reset quoted state on newline
                    if ($lastChild !== NULL && $lastChild->{$nameProperty} == 'br') {
                        // add quotes to repeating newlines
                        $result .= str_repeat(self::QUOTE, $_quoteIndent);
                    }
                    $result .= $_eol;
                }
                
                $result .= self::addQuotesAndStripTags($child, $_quoteIndent);
                
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
        }
        
        return $result;
    }    
}
