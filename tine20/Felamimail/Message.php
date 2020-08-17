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
        // TODO try to handle date format like this:
        // Wed, 04 Jan 2017 16:02:58 0000
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
        if (preg_match('/=?[\d,\w,-]*?[q,Q,b,B]?.*?=/', $string)) {
            $string = preg_replace_callback('/(=[1-9,a-f]{2})/', function ($matches) { 
                return strtoupper($matches[1]);
            }, $string);
            if ($_isHeader) {
                $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
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
     * @return array
     */
    public static function convertAddresses($_addresses)
    {
        $result = array();
        if (!empty($_addresses)) {
            $addresses = Tinebase_Mail::parseAdresslist($_addresses);
            if (is_array($addresses)) {
                foreach ($addresses as $address) {
                    if (preg_match('/@xn--/', $address['address'])) {
                        $email = Tinebase_Helper::convertDomainToUnicode($address['address']);
                    } else {
                        $email = $address['address'];
                    }
                    
                    $result[] = array(
                        'email' => trim($email), 
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
            $text = Tinebase_Mail::convertFromTextToHTML($_text, 'felamimail-body-blockquote');
            $text = self::addHtmlMarkup($text);
        } else {
            $text = self::convertFromHTMLToText($_text);
        }
        
        return $text;
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
        $text = str_replace('&nbsp;', ' ', $text);
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
     * replace uris with links
     *
     * @param string $_content
     * @return string
     */
    public static function replaceUris($_content) 
    {
        $pattern = '@(https?://|ftp://)([^\s<>\)]+)@u';
        $result = preg_replace($pattern, "<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>", $_content);

        // special handling for &gt; at the end of an uri
        $result = preg_replace('/&gt;" target="/', '" target="', $result);
        
        return $result;
    }

    /**
     * replace emails with links
     *
     * @param string $_content
     * @return string
     */
    public static function replaceEmails($_content) 
    {
        // add anchor to email addresses (remove mailto hrefs first)
        $mailtoPattern = '/<a[="a-z\-0-9 ]*href="mailto:([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})"[^>]*>.*<\/a>/iU';
        $result = preg_replace($mailtoPattern, "\\1", $_content);
        $emailRegex = Tinebase_Mail::EMAIL_ADDRESS_CONTAINED_REGEXP;
        // don't match emails with '=' as used in subscription uris (?email=blabla@aha.com)
        $emailRegex = str_replace('/(', '/^=(', $emailRegex);
        $result = preg_replace($emailRegex, "<a href=\"#\" id=\"123:\\1\" class=\"tinebase-email-link\">\\1</a>", $result);
        
        return $result;
    }

    /**
     * replace targets in links
     *
     * @param string $_content
     * @return string
     */
    public static function replaceTargets($_content) 
    {
        // uris
        $pattern = "/target=[\'\"][^\'\"]*[\'\"]/";
        $result = preg_replace($pattern, "target=\"_blank\"", $_content);
        
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
        $message->headers = $_zendMailMessage->getHeaders();
        
        foreach ($message->headers as $headerName => $headerValue) {
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
                    
                    $addresses = Tinebase_Mail::parseAdresslist($headerValue);
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
        //$charset        = $_zendMailMessage->getHeaderField('content-type', 'charset');
        $message->body = self::getDecodedContent($_zendMailMessage);

        return $message;
    }

    public static function getDecodedContent($partOrMessage)
    {
        $encoding       = $partOrMessage->headerExists('content-transfer-encoding')
            ? $partOrMessage->getHeaderField('content-transfer-encoding')
            : Zend_Mime::ENCODING_QUOTEDPRINTABLE; // TODO best default?

        switch ($encoding) {
            case Zend_Mime::ENCODING_QUOTEDPRINTABLE:
                $result = quoted_printable_decode($partOrMessage->getContent());
                break;
            case Zend_Mime::ENCODING_BASE64:
                $result = base64_decode($partOrMessage->getContent());
                break;
            default:
                $result = $partOrMessage->getContent();
                break;
        }

        return $result;
    }

    /**
     * getDecodedPartContent
     *
     * @param $partId
     * @return string
     * @throws Zend_Mail_Exception
     */
    public function getDecodedPartContent($partId)
    {
        $part = $this->getPart($partId);
        return self::getDecodedContent($part);
    }

    /**
     * Get part of multipart message
     *
     * @param  int|string $num number of part starting with 1 for first part
     * @return Zend_Mail_Part wanted part
     * @throws Zend_Mail_Exception
     */
    public function getPart($num)
    {
        if (strpos($num, '.') !== false) {
            // recurse into sub-parts (for example 1.1, 1.1.2, ...)
            $parts = explode('.', $num);
            $part = $this;
            while (count($parts) > 0) {
                $part = $part->getPart(array_shift($parts));
            }
        } else {
            $part = parent::getPart((integer) $num);
        }

        return $part;
    }
}
