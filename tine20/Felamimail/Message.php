<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
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
     * date formats for convertDate()
     * 
     * @var array
     */
    public static $dateFormats = array(
        'D, j M Y H:i:s O',
        'd-M-Y H:i:s O',
    );
    
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

        parent::__construct($params);
    }
    
    /**
     * parse address list
     *
     * @param string $_adressList
     * @return array
     */
    public static function parseAdresslist($_addressList)
    {
        if (strpos($_addressList, ',') !== FALSE && substr_count($_addressList, '@') == 1) {
            // we have a comma in the name -> do not split string!
            $addresses = array($_addressList);
        } else {
        	// create stream to be used with fgetcsv
            $stream = fopen("php://temp", 'r+');
            fputs($stream, $_addressList);
            rewind($stream);
            
            // alternative solution to create stream; yet untested
            #$stream = fopen('data://text/plain;base64,' . base64_encode($_addressList), 'r');
            
            // split addresses
            $addresses = fgetcsv($stream);
        }
        
        if (! is_array($addresses)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 
                ' Could not parse addresses: ' . var_export($addresses, TRUE));
            return array();
        }
        
        foreach ($addresses as $key => $address) {
            if (preg_match('/(.*)<(.+@[^@]+)>/', $address, $matches)) {
                $name = trim(trim($matches[1]), '"');
                $address = trim($matches[2]);
                $addresses[$key] = array('name' => substr($name, 0, 250), 'address' => $address);
            } else {
                $address = preg_replace('/[,;]*/i', '', $address);
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
            }
        }
        
        if ($_ellipsis > 0 && strlen($string) > $_ellipsis) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' String to long, cutting it to ' . $_ellipsis . ' chars.');
            $string = substr($string, 0, $_ellipsis);
        }
        
        return $string;
    }
    
    /**
     * convert date from sent/received
     *
     * @param  string $_dateString
     * @return Zend_Date
     */
    public static function convertDate($_dateString)
    {
        try {
            $date = new Tinebase_DateTime($_dateString ? $_dateString : '@0');
            $date->setTimezone('UTC');

        } catch (Exception $e) {
            // try to fix missing timezone char
            if (preg_match('/UT$/', $_dateString)) {
                $_dateString .= 'C';
            }
            
            // try some explicit formats
            foreach (self::$dateFormats as $format) {
                $date = DateTime::createFromFormat($format, $_dateString);
                if ($date) break;
            }
            
            if (! $date) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Date $_dateString could  not be converted to DateTime -> using 1970-01-01 00:00:00.");
                $date = new Tinebase_DateTime('@0');
            }
        }
        
        return $date;
    }
    
    /**
     * convert addresses into array with name/address
     *
     * @param string $_addresses
     * @param idna_convert $_punycodeConverter
     * @return array
     */
    public static function convertAddresses($_addresses, $_punycodeConverter = NULL)
    {
        $result = array();
        if (!empty($_addresses)) {
            $addresses = self::parseAdresslist($_addresses);
            if (is_array($addresses)) {
                foreach($addresses as $address) {
                    if ($_punycodeConverter !== NULL && preg_match('/@xn--/', $address['address'])) {
                        $email = $_punycodeConverter->decode($address['address']);
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                            ' Converted email from punycode ' . $address['address'] . ' to ' . $email);
                    } else {
                        $email = $address['address'];
                    }
                    
                    $result[] = array(
                        'email' => $email, 
                        'name' =>  $address['name']
                    );
                }
            }
        }
        return $result;
    }
    
    /**
     * convert between content types (text/plain => text/html for example)
     * 
     * @param string $_from
     * @param string $_to
     * @param string $_text
     * @param string $_eol
     * @param boolean $_addMarkup
     * @return string
     * 
     * @todo we should use Felamimail_Model_Message::getPlainTextBody here / move all conversion to one place
     * @todo remove addHtmlMarkup?
     */
    public static function convertContentType($_from, $_to, $_text)
    {
        // nothing todo
        if ($_from == $_to) {
            return $_text;
        }
        
        if ($_from == Zend_Mime::TYPE_TEXT && $_to == Zend_Mime::TYPE_HTML) {
            $text = self::convertFromTextToHTML($_text);
            $text = self::addHtmlMarkup($text);
        } else {
            $text = self::convertFromHTMLToText($_text);
        }
        
        return $text;
    }
    
    /**
     * convert text to html
     * 
     * @param string $_text
     * @return string
     */
    public static function convertFromTextToHTML($_text)
    {
        $html = htmlspecialchars($_text, ENT_COMPAT, 'UTF-8');
        $html = strtr($html, array("\r\n" => '<br />', "\r" => '<br />', "\n" => '<br />'));
        
        return $html;
    }
    
    /**
     * convert html to text
     * 
     * @param string $_html
     * @param string $_eol
     * @return string
     */
    public static function convertFromHTMLToText($_html, $_eol = "\r\n")
    {
        $text = preg_replace('/\<br *\/*\>/', $_eol, $_html);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
                
        return $text;
    }
    
    /**
     * add html markup to message body
     *
     * @param string $_body
     * @return string
     */
    public static function addHtmlMarkup($_body)
    {
        $result = '<html>'
            . '<head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
            . '<title></title>'
            . '<style type="text/css">'
                . '.felamimail-body-blockquote {'
                    . 'margin: 5px 10px 0 3px;'
                    . 'padding-left: 10px;'
                    . 'border-left: 2px solid #000088;'
                . '} '
            . '</style>'
            . '</head>'
            . '<body>'
            . $_body
            . '</body></html>';
            
        return $result;
    }
    
    /**
     * replace uris with links and more than one space with &nbsp;
     *
     * @param string $_content
     * @return string
     */
    public static function replaceUriAndSpaces($_content) 
    {
        // uris
        $pattern = '@(https?://|ftp://)([^\s<>\)]+)@';
        $result = preg_replace($pattern, "<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>", $_content);
        
        // spaces
        #$result = preg_replace('/( {2,}|^ )/em', 'str_repeat("&nbsp;", strlen("\1"))', $result);
        
        return $result;
    }

    /**
     * replace emails with links
     *
     * @param string $_content
     * @return string
     * 
     * @todo try to skip email address that are already embedded in an url (such as unsubscription links with ?email=blabla@aha.com) 
     */
    public static function replaceEmails($_content) 
    {
        // add anchor to email addresses (remove mailto hrefs first)
        $mailtoPattern = '/<a[="a-z\-0-9 ]*href="mailto:([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})"[^>]*>.*<\/a>/iU';
        $result = preg_replace($mailtoPattern, "\\1", $_content);
        $result = preg_replace(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, "<a href=\"#\" id=\"123:\\1\" class=\"tinebase-email-link\">\\1</a>", $result);
        
        return $result;
    }
    
    /**
     * create Felamimail message from Zend_Mail_Message
     * 
     * @param Zend_Mail_Message $_zendMailMessage
     * @return Felamimail_Model_Message
     */
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
}
