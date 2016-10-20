<?php
/**
 * class to hold message cache data
 * 
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold message cache data
 * 
 * @package     Felamimail
 * @subpackage  Model
 * @property    string  $node_id        the node id
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
 * @property    integer $reading_conf   true if it must send a reading confirmation
 */
class MailFiler_Model_Message extends Felamimail_Model_Message
{
    /**
     * list of zend validator
     *
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'node_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'original_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'original_part_id'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'messageuid'            => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'subject'               => array(Zend_Filter_Input::ALLOW_EMPTY => true), // _('Subject')
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
        'headers'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // this is: content_type_of_envelope
        'content_type'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // this is: body_content_type_from_message_structrue
        'body_content_type'     => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => self::CONTENT_TYPE_PLAIN,
            array('InArray', array(self::CONTENT_TYPE_HTML, self::CONTENT_TYPE_PLAIN)),
        ),
        // actual content type of body property
        'body_content_type_of_body_property_of_this_record' => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => self::CONTENT_TYPE_PLAIN,
            array('InArray', array(self::CONTENT_TYPE_HTML, self::CONTENT_TYPE_PLAIN)),
        ),
        'attachments'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // save email as contact note
        'note'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        // Felamimail_Message object
        'message'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // prepared parts (iMIP invitations, contact vcards, ...)
        'preparedParts'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'reading_conf'          => array(Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => 0),
    );

    /**
     * TODO generalize for non-modelconf?
     */
    public function runConvertToRecord()
    {
        $this->structure = Tinebase_Model_Converter_Json::convertToRecord($this->structure);
        $this->headers = Tinebase_Model_Converter_Json::convertToRecord($this->headers);
    }

    /**
     * TODO generalize for non-modelconf?
     */
    public function runConvertToData()
    {
        $this->structure = Tinebase_Model_Converter_Json::convertToData($this->structure);
        $this->headers = Tinebase_Model_Converter_Json::convertToData($this->headers);
    }

    /**
     * fetch structure - overwritten
     *
     * @return array
     */
    protected function _fetchStructure()
    {
        // do not fetch structure from mailserver if empty
       return array();
    }
}
