<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        remove leading spaces in multipart messages
 */

/**
 * message model for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Model
 */
class Felamimail_Message extends Zend_Mail_Message
{
    /**
     * number of leading spaces
     * 
     * @var int
     */
    protected $_leadingSpaces = 0;
    
    /**
     * Public constructor
     *
     * In addition to the parameters of Zend_Mail_Message::__construct() this constructor supports:
     * - uid  use UID FETCH if ftru
     *
     * @param  array $params  list of parameters
     * @throws Zend_Mail_Exception
     */
    public function __construct(array $params)
    {
        if (isset($params['uid'])) {
            $this->_useUid = (bool)$params['uid'];
        }
        
        if (isset($params['spaces'])) {
            $this->_leadingSpaces = $params['spaces'];
        }
        
        parent::__construct($params);
    }
    
    /**
     * parse address list
     *
     * @param unknown_type $_adressList
     * @return array
     */
    public static function parseAdresslist($_addressList)
    {
    	// create stream to be used with fgetcsv
        $stream = fopen("php://temp", 'r+');
        fputs($stream, $_addressList);
        rewind($stream);
        
        // split addresses
        $addresses = fgetcsv($stream);
        
        foreach ($addresses as $key => $address) {
            if (preg_match('/(.*)<(.+@[^@]+)>/', $address, $matches)) {
                $addresses[$key] = array('name' => trim(trim($matches[1]), '"'), 'address' => trim($matches[2]));
            } else {
                $addresses[$key] = array('name' => null, 'address' => $address);
            }
        }

        return $addresses;
    }

    /**
     * convert text
     *
     * @param string $_string
     * @param boolean $_isHeader (if not, use base64 decode)
     * @param integer $_ellipsis use substring (0 ... value) if value is > 0
     * @return string
     * 
     * @todo make it work for message body (use table for quoted printables?)
     */
    public static function convertText($_string, $_isHeader = TRUE, $_ellipsis = 0)
    {
        $string = $_string;
        if(preg_match('/=?[\d,\w,-]*?[q,Q,b,B]?.*?=/', $string)) {
            $string = preg_replace('/(=[1-9,a-f]{2})/e', "strtoupper('\\1')", $string);
            if ($_isHeader) {
                $string = iconv_mime_decode($string, 2);
            } else {
                //$string = base64_decode($string);
                
                /*
                $string = preg_replace("/\=([A-F][A-F0-9])/","%$1",$string);
                $string = urldecode($string);
                $string = utf8_encode($string);
                */                
            }
        }
        
        if ($_ellipsis > 0 && strlen($string) > $_ellipsis) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' String to long, cutting it to ' . $_ellipsis . ' chars.');
            $string = substr($string, 0, $_ellipsis);
        }
        
        return $string;
    }
    
    public static function createMessageFromZendMailMessage(Zend_Mail_Message $_zendMailMessage)
    {
        $message = new Felamimail_Model_Message();
        
        foreach ($_zendMailMessage->getHeaders() as $headerName => $headerValue) {
            switch($headerName) {
                case 'subject':
                    $message->$headerName = $headerValue;
                    
                    break;
                    
                case 'from':
                    // do nothing
                    break;
                    
                case 'to':
                case 'bcc':
                case 'cc':
                    $receipients = array();
                    
                    $addresses = Felamimail_Message::parseAdresslist($headerValue);
                    foreach ($addresses as $address) {
                        $receipients[] = $address['address'];
                    }
                    
                    $message->$headerName = $receipients;
                    
                    break;                    
            }
        }
        
        
        $contentType    = $_zendMailMessage->getHeaderField('content-type', 0);
        $message->content_type = $contentType;
        
        // @todo convert to utf-8 if needed
        $charset        = $_zendMailMessage->getHeaderField('content-type', 'charset');
        
        $encoding       = $_zendMailMessage->getHeaderField('content-transfer-encoding');
        
        switch ($encoding) {
            case Zend_Mime::ENCODING_QUOTEDPRINTABLE:
                $message->body = quoted_printable_decode($_zendMailMessage->getContent());
                break;
            case Zend_Mime::ENCODING_BASE64:
                $message->body = base64_decode($_zendMailMessage->getContent());
                break;
                
            default:
                $message->body = $_zendMailMessage->getContent();
                break;
        }
        
        return $message;
    }
    
//    /**
//     * our own decode (and utf-8 encode) function
//     *
//     * @param string $_charset
//     * @param string $_content
//     * @return string
//     * 
//     * @todo catch iconv errors and try different charsets
//     */
//    protected function _decode($_charset, $_content)
//    {
//        if($_charset == 'utf8') {
//            $_charset = 'utf-8';
//        }
//        
//        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " message body: iconv() from " . $_charset . " to utf-8.");
//        
//        $result = iconv($_charset, 'utf-8//IGNORE', $_content);
//        
//        return $result;
//    }
}
