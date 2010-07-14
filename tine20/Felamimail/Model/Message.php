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
     * parse headers and set 'date', 'from', 'to', 'cc', 'bcc', 'subject' fields
     * 
     * @param array $_headers
     * @return void
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
        $this->from    = (isset($_headers['from']))    ? Felamimail_Message::convertText($_headers['from'], TRUE, 256) : null;
        
        if (array_key_exists('date', $_headers)) {
            $this->sent = Felamimail_Message::convertDate($_headers['date']);
        } elseif (array_key_exists('resent-date', $_headers)) {
            $this->sent = Felamimail_Message::convertDate($_headers['resent-date']);
        }
        
        foreach (array('to', 'cc', 'bcc') as $field) {
            if (isset($_headers[$field])) {
                // if sender set the headers twice we only use the first
                $this->$field = Felamimail_Message::convertAddresses($_headers[$field]);
            }
        }
    }
    
    /**
     * parse message structure
     * 
     * @param array $_structure
     * @return void
     */
    public function parseStructure(array $_structure)
    {
        $this->structure     = $_structure;
        $this->content_type  = isset($_structure['contentType']) ? $_structure['contentType'] : Zend_Mime::TYPE_TEXT;
    }
    
    /**
     * get message part structure
     * 
     * @param  string  $_partId            the part id to search for
     * @return array
     */
    public function getPartStructure($_partId)
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
                return $value;
            }
        }
        
        throw new Felamimail_Exception("Structure for partId $_partId not found!");
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
}
