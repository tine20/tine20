<?php
/**
 * class to hold message cache data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        add flags as consts here?
 * @todo        add more CONTENT_TYPE_ constants
 */

/**
 * class to hold message cache data
 * 
 * @package     Felamimail
 * @property    string  $subject        the subject of the email
 * @property    string  $from           the address of the sender
 * @property    string  $content_type   the address of the sender
 * @property    array   $to             the to receipients
 * @property    array   $cc             the cc receipients
 * @property    array   $bcc            the bcc receipients
 * @property    array   $structure      the message structure
 * @property    string  $messageuid     the message uid on the imap server
 */
class Felamimail_Model_Message extends Tinebase_Record_Abstract
{
    /**
     * date format constants
     *
     */
    const DATE_FORMAT = 'EEE, d MMM YYYY hh:mm:ss zzz';
    const DATE_FORMAT_RECEIVED = 'dd-MMM-YYYY hh:mm:ss zzz';
    
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
     * attachment filename regexp 
     *
     */
    //const ATTACHMENT_FILENAME_REGEXP = "/name=\"*([\w\-\._ ]+)\"*/u";
    const ATTACHMENT_FILENAME_REGEXP = "/name=\"(.*)\"/";
    
    /**
     * email address regexp
     */
    // '/(?<!mailto:)([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})/i';
    const EMAIL_ADDRESS_REGEXP = '/([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})/i'; 
    
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
        'original_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'messageuid'            => array(Zend_Filter_Input::ALLOW_EMPTY => false), 
        'folder_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => false), 
        'subject'               => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'from'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
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
        'content_type'          => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => self::CONTENT_TYPE_PLAIN,
            'InArray' => array(self::CONTENT_TYPE_HTML, self::CONTENT_TYPE_PLAIN)
        ),
        'attachments'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // save email as contact note
        'note'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // Felamimail_Message object
        'message'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
     * parse headers and set 'date', 'from', 'to', 'cc', 'bcc', 'subject' fields
     * 
     * @param array $_headers
     * @return void
     * 
     * @todo save from values in from_email/from_name (see http://www.tine20.org/bugtracker/view.php?id=2870)
     */
    public function parseHeaders(array $_headers)
    {
        // remove duplicate headers (which can't be set twice in real life)
        foreach (array('date', 'from', 'to', 'cc', 'bcc', 'subject') as $field) {
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
        
        foreach (array('to', 'cc', 'bcc', 'from') as $field) {
            if (isset($_headers[$field])) {
                // if sender set the headers twice we only use the first
                $this->$field = Felamimail_Message::convertAddresses($_headers[$field]);
                if ($field == 'from') {
                    // as we don't save the from field as array yet, we have to make sure that it is converted to a string
                    if (count($this->from) > 0 && array_key_exists('email', $this->from[0])) {
                        $this->from = (array_key_exists('name', $this->from[0])) ? $this->from[0]['name'] . ' <' . $this->from[0]['email'] . '>' : $this->from[0]['email'];
                    } else {
                        $this->from = '';
                    }
                }
            }
        }
    }
    
    /**
     * parse message structure
     * 
     * @return void
     */
    public function parseStructure()
    {
        $this->structure     = $this->structure;
        $this->content_type  = isset($this->structure['contentType']) ? $this->structure['contentType'] : Zend_Mime::TYPE_TEXT;
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
        // maybe we want no part at all => just return the whole structure
        if($_partId == null) {
            return $this->structure;
        }
        
        // maybe we want the first part => just return the whole structure
        if($this->structure['partId'] == $_partId) {
            return $this->structure;
        }
                
        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($this->structure),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $key => $value) {
            if ($key == $_partId) {
                $result = ($_useMessageStructure && array_key_exists('messageStructure', $value)) ? $value['messageStructure'] : $value;
                return $result;
            }
        }
        
        throw new Felamimail_Exception("Structure for partId $_partId not found!");
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
            throw new Felamimail_Exception('Structure should be an array (' . $structure . ')');
        }
        
        if (array_key_exists('parts', $structure)) {
            $bodyParts = $bodyParts + $this->_parseMultipart($structure, $_preferedMimeType);
        } else {
            $bodyParts = $bodyParts + $this->_parseSinglePart($structure);
        }
        
        return $bodyParts;
    }
    
    /**
     * parse single part message
     * 
     * @param array $_structure
     * @return array
     */
    protected function _parseSinglePart(array $_structure)
    {
        $result = array();
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_structure, TRUE));

        if ($_structure['type'] != 'text') {
            return $result;
        }
        
        if ($this->_partIsAttachment($_structure)) {
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_structure, TRUE));
        
        if ($_structure['subType'] == 'alternative') {
            $alternativeType = $_preferedMimeType == Zend_Mime::TYPE_HTML ? Zend_Mime::TYPE_TEXT : Zend_Mime::TYPE_HTML;
            
            foreach ($_structure['parts'] as $part) {
                $foundParts[$part['contentType']] = $part['partId'];
            }
            
            if (array_key_exists($_preferedMimeType, $foundParts)) {
                $result[$foundParts[$_preferedMimeType]] = $_structure['parts'][$foundParts[$_preferedMimeType]];
            } elseif (array_key_exists($alternativeType, $foundParts)) {
                $result[$foundParts[$alternativeType]]   = $_structure['parts'][$foundParts[$alternativeType]];
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
     * get body as plain text with replaced blockquotes, stripped tags and replaced <br>s
     * -> use DOM extension
     * 
     * @return string
     */
    public function getPlainTextBody()
    {
        $result = '';
        
        $dom = new DOMDocument('1.0', 'utf-8');
        // use a hack to make sure html is loaded as utf8 (@see http://php.net/manual/en/domdocument.loadhtml.php#95251)
        $dom->loadHTML('<?xml encoding="UTF-8">' . $this->body);
        $bodyElements = $dom->getElementsByTagName('body');
        if ($bodyElements->length > 0) {
            $result = $this->_addQuotesAndStripTags($bodyElements->item(0));
        } else {
            throw new Felamimail_Exception('No body element found!');
        }
        
        $result = html_entity_decode($result, ENT_COMPAT, 'UTF-8');
        
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
     * @return string
     * 
     * @todo we can transform more tags here, i.e. the <strong>BOLDTEXT</strong> tag could be replaced with *BOLDTEXT*
     * @todo think about removing the tidy code
     */
    protected function _addQuotesAndStripTags($_node, $_quoteIndent = 0) {
        
        $result = '';
        
        $hasChildren = ($_node instanceof DOMNode) ? $_node->hasChildNodes() : $_node->hasChildren();
        $nameProperty = ($_node instanceof DOMNode) ? 'nodeName' : 'name';
        $valueProperty = ($_node instanceof DOMNode) ? 'nodeValue' : 'value';
        
        if ($hasChildren) {
            $quoted = FALSE;
            $lastChild = NULL;
            $children = ($_node instanceof DOMNode) ? $_node->childNodes : $_node->child;
            foreach ($children as $child) {
                $isTextLeaf = ($child instanceof DOMNode) ? $child->{$nameProperty} == '#text' : ! $child->{$nameProperty};
                if ($isTextLeaf) { 
                    // leaf -> add quotes and append to content string
                    if ($_quoteIndent > 0) {
                        $result .= str_repeat(self::QUOTE, $_quoteIndent);
                        $quoted = TRUE;
                    }
                    $result .= $child->{$valueProperty};
                    
                } else if ($child->{$nameProperty} == 'blockquote') {
                    // new blockquote -> increase quote indention
                    $_quoteIndent++;
                    
                } else if ($child->{$nameProperty} == 'br') {
                    // reset quoted state on newline
                    if ($lastChild !== NULL && $lastChild->{$nameProperty} == 'br') {
                        // add quotes to repeating newlines
                        $result .= str_repeat(self::QUOTE, $_quoteIndent);
                    }
                    $result .= "\n";
                    $quoted = FALSE;
                }
                
                $result .= $this->_addQuotesAndStripTags($child, $_quoteIndent);
                
                if ($child->{$nameProperty} == 'blockquote') {
                    // close blockquote -> decrease quote indention
                    $_quoteIndent--;
                }
                
                $lastChild = $child;
            }
        }
        
        return $result;
    }    
}
