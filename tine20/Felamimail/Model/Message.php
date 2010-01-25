<?php
/**
 * class to hold message cache data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        add flags as consts here?
 * @todo        add more CONTENT_TYPE_ constants
 */

/**
 * class to hold message cache data
 * 
 * @package     Felamimail
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
     * attachment filename regexp 
     *
     */
    //const ATTACHMENT_FILENAME_REGEXP = "/name=\"*([a-zA-Z0-9\-\._ ]+)\"*/";
    //const ATTACHMENT_FILENAME_REGEXP = "/name=\"*([\wäÄöÖüÜß\-\._ ]+)\"*/";
    const ATTACHMENT_FILENAME_REGEXP = "/name=\"*([\w\-\._ ]+)\"*/u";
    
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
        'headers'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'content_type'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
     * fills a record from json data
     *
     * @param string $_data json encoded data
     * 
     * @todo    get/detect delimiter from row? could be ';' or ','
     * @todo    add recipient names
     */
    public function setFromJson($_data)
    {
        if(is_array($_data)) {
            $recordData = $_data;
        } else {
            $recordData = Zend_Json::decode($_data);
        }
        $this->setFromArray($recordData);
        
        // explode email addresses if multiple
        $recipientType = array('to', 'cc', 'bcc');
        $delimiter = ';';
        foreach ($recipientType as $field) {
            if (!empty($recordData[$field])) {
                $recipients = array();
                foreach($recordData[$field] as $addresses) {
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

                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recipients, true));
                
                $this->{$field} = array_unique($recipients);
            }
        }
    }
}
