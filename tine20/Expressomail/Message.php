<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * message model for Expressomail
 *
 * @package     Expressomail
 * @subpackage  Model
 */
class Expressomail_Message extends Zend_Mail_Message
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
        if (preg_match('/=?[\d,\w,-]*?[q,Q,b,B]?.*?=/', $string)) {
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
     * @todo we should use Expressomail_Model_Message::getPlainTextBody here / move all conversion to one place
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
     * - replace quotes ('>  ') with blockquotes 
     * - does htmlspecialchars()
     * - converts linebreaks to <br />
     * 
     * @param string $_text
     * @return string
     */
    public static function convertFromTextToHTML($_text)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Input: ' . $_text);
        
        $lines = preg_split('/\r\n|\n|\r/', $_text);
        $result = array();
        $indention = 0;
        foreach ($lines as $line) {
            // get indention level and remove quotes
            if (preg_match('/^>[> ]*/', $line, $matches)) {
                $indentionLevel = substr_count($matches[0], '>');
                $line = str_replace($matches[0], '', $line);
            } else {
                $indentionLevel = 0;
            }
            
            // convert html special chars
            $line = htmlspecialchars($line, ENT_COMPAT, 'UTF-8');
            
            // set blockquote tags for current indentionLevel
            while ($indention < $indentionLevel) {
                $line = '<blockquote class="expressomail-body-blockquote">' . $line;
                $indention++;
            }
            while ($indention > $indentionLevel) {
                $line = '</blockquote>' . $line;
                $indention--;
            }
            
            $result[] = $line;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Line: ' . $line);
        }
        
        $result = implode('<br />', $result);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Result: ' . $result);
        
        return $result;
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
                . '.expressomail-body-blockquote {'
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
        // uris
        $pattern = '@(https?://|ftp://)([^\s<>\)]+)@u';
        $result = preg_replace($pattern, "<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>", $_content);
        
        return $result;
    }
    
    /**
     * replace text uris with links
     * http(s) and www are optional and URIs are validated against TLDs (.com, .net, .com.br, .gov, ...)
     * @param string $text
     * @return string  
     */
    public static function replaceUrisUsingTlds($text)
    {
//        $regexUrl = 
//           '(https?://)?                                                 #protocol
//            (?:
//                ([^]\\\\\x00-\x20\"(),:-<>[\x7f-\xff]{1,64})             #username
//                (:[^]\\\\\x00-\x20\"(),:-<>[\x7f-\xff]{1,64})?@          #pw
//            )?
//            (
//                (?:[-a-zA-Z0-9\x7f-\xff]{1,63}\.)+[a-zA-Z\x7f-\xff][-a-zA-Z0-9\x7f-\xff]{1,62}  #domain
//                |
//                (?:[1-9][0-9]{0,2}\.|0\.){3}(?:[1-9][0-9]{0,2}|0)                               #ip
//            )
//            (
//                (:[0-9]{1,5})?                                            #port   
//                (/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?                  #path
//                (\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?                 #query
//                (#[!$-/0-9?:;=@_\':;!a-zA-Z\x7f-\xff]+?)?                 #frag
//            )';
        // compressed
        $regexUrl = '(https?://)?(?:([^]\\\\\x00-\x20\"(),:-<>[\x7f-\xff]{1,64})(:[^]\\\\\x00-\x20\"(),:-<>[\x7f-\xff]{1,64})?@)?((?:[-a-zA-Z0-9\x7f-\xff]{1,63}\.)+[a-zA-Z\x7f-\xff][-a-zA-Z0-9\x7f-\xff]{1,62}|(?:[1-9][0-9]{0,2}\.|0\.){3}(?:[1-9][0-9]{0,2}|0))((:[0-9]{1,5})?(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?(#[!$-/0-9?:;=@_\':;!a-zA-Z\x7f-\xff]+?)?)';
        $regexPunct = "[)'?.!,;:]";
        $regexEndChars = "[^-_#$%+.!*'(),;/?:@=&a-zA-Z0-9\x7f-\xff]";
        $regexEndEntities = "(?:(?:&|&amp;)(?:lt|gt|quot|apos|raquo|laquo|rsaquo|lsaquo);)";
        $regexUrlEnd = "$regexPunct*($regexEndChars|$regexEndEntities|$)";
        
        $finalRegex = "{\\b$regexUrl(?=$regexUrlEnd)}i";
        
        // TLDs list:  http://data.iana.org/TLD/tlds-alpha-by-domain.txt
        $validTlds = array_fill_keys(explode(" ", ".ac .ad .ae .aero .af .ag .ai .al .am .an .ao .aq .ar .arpa .as .asia .at .au .aw .ax .az .ba .bb .bd .be .bf .bg .bh .bi .biz .bj .bm .bn .bo .br .bs .bt .bv .bw .by .bz .ca .cat .cc .cd .cf .cg .ch .ci .ck .cl .cm .cn .co .com .coop .cr .cu .cv .cw .cx .cy .cz .de .dj .dk .dm .do .dz .ec .edu .ee .eg .er .es .et .eu .fi .fj .fk .fm .fo .fr .ga .gb .gd .ge .gf .gg .gh .gi .gl .gm .gn .gov .gp .gq .gr .gs .gt .gu .gw .gy .hk .hm .hn .hr .ht .hu .id .ie .il .im .in .info .int .io .iq .ir .is .it .je .jm .jo .jobs .jp .ke .kg .kh .ki .km .kn .kp .kr .kw .ky .kz .la .lb .lc .li .lk .lr .ls .lt .lu .lv .ly .ma .mc .md .me .mg .mh .mil .mk .ml .mm .mn .mo .mobi .mp .mq .mr .ms .mt .mu .museum .mv .mw .mx .my .mz .na .name .nc .ne .net .nf .ng .ni .nl .no .np .nr .nu .nz .om .org .pa .pe .pf .pg .ph .pk .pl .pm .pn .post .pr .pro .ps .pt .pw .py .qa .re .ro .rs .ru .rw .sa .sb .sc .sd .se .sg .sh .si .sj .sk .sl .sm .sn .so .sr .st .su .sv .sx .sy .sz .tc .td .tel .tf .tg .th .tj .tk .tl .tm .tn .to .tp .tr .travel .tt .tv .tw .tz .ua .ug .uk .us .uy .uz .va .vc .ve .vg .vi .vn .vu .wf .ws .xn--0zwm56d .xn--11b5bs3a9aj6g .xn--3e0b707e .xn--45brj9c .xn--80akhbyknj4f .xn--80ao21a .xn--90a3ac .xn--9t4b11yi5a .xn--clchc0ea0b2g2a9gcd .xn--deba0ad .xn--fiqs8s .xn--fiqz9s .xn--fpcrj9c3d .xn--fzc2c9e2c .xn--g6w251d .xn--gecrj9c .xn--h2brj9c .xn--hgbk6aj7f53bba .xn--hlcj6aya9esc7a .xn--j6w193g .xn--jxalpdlp .xn--kgbechtv .xn--kprw13d .xn--kpry57d .xn--lgbbat1ad8j .xn--mgb9awbf .xn--mgbaam7a8h .xn--mgbayh7gpa .xn--mgbbh1a71e .xn--mgbc0a9azcg .xn--mgberp4a5d4ar .xn--o3cw4h .xn--ogbpf8fl .xn--p1ai .xn--pgbs0dh .xn--s9brj9c .xn--wgbh1c .xn--wgbl6a .xn--xkc2al3hye2a .xn--xkc2dl3a5ee0h .xn--yfro4i67o .xn--ygbi2ammx .xn--zckzah .xxx .ye .yt .za .zm .zw"), true);

        $return = '';
        $pos = 0;
        while (preg_match($finalRegex, $text, $matches, PREG_OFFSET_CAPTURE, $pos))
        {
            list($url, $urlPos) = $matches[0];
            // get text before url
            $return .= substr($text, $pos, $urlPos - $pos);

            $protocol    = $matches[1][0];
            $username    = $matches[2][0];
            $password    = $matches[3][0];
            $domain      = $matches[4][0];
            $afterDomain = $matches[5][0];

            $domain = str_replace("\xe2\x80\x8b", '', $domain); // remove zero width space from domain
            
            $tld = strtolower(strrchr($domain, '.'));
            if (preg_match('{^\.[0-9]{1,3}$}', $tld) || isset($validTlds[$tld]) || $protocol)
            {
                if (!$protocol && $password)
                {
                    $return .= htmlspecialchars($username);
                    $pos = $urlPos + strlen($username);
                    continue;
                }

                if (!$protocol && $username && !$password && !$afterDomain) {
                    $fullUrl = "mailto:$url";
                }
                else {
                    $fullUrl = $protocol ? $url : "http://$url";
                }
                $link = '<a href="' . $fullUrl . '" target="_blank">' . $url . '</a>';
                $link = str_replace('@', '&#64;', $link);
                $return .= $link;
            }
            else { // is not a URL
                $return .= $url;
            }
            $pos = $urlPos + strlen($url);
        }
        // get text folowing
        $return .= substr($text, $pos);
        return $return;
    }
    
    /**
     * process html text and replace text nodes uris with links
     * 
     * @param string $text
     * @return string 
     */ 
    public static function linkify($text)
    {
 
        $pieces = preg_split('/(<.+?>)/is', $text, 0, PREG_SPLIT_DELIM_CAPTURE);
        $ignoreTags = array('head', 'script', 'link', 'a', 'style', 'code', 'pre', 'select', 'textarea', 'button');
        $openTag = null;

        for ($i = 0; $i < count($pieces); $i++) {
            if ($i % 2 === 0) { // text
                // process it if there are no unclosed $ignoreTags
                if ($openTag === null) {
                    $pieces[$i] = self::replaceUrisUsingTlds($pieces[$i]);
                }
            } else { // tags
                // process it if there are no unclosed $ignoreTags
                if ($openTag === null) { //tag exists in $ignoreTags and is not self-closing
                    if (preg_match("`<(" . implode('|', $ignoreTags) . ").*(?<!/)>$`is", $pieces[$i], $matches)) {
                        $openTag = $matches[1];
                    }
                } else { // check if this is the closing tag for $openTag.
                    if (preg_match('`</\s*' . $openTag . '>`i', $pieces[$i], $matches)) {
                        $openTag = null;
                    }
                }
            }
        }
        $text = implode($pieces);
        return $text;
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
        $mailtoPattern = '/<[aA][^>]*href="mailto:'
         .'((([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4}){1}|([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4},{0,1})*).*)"[^>]*>(.*)<\/[Aa]>/iU';
        $match = array();
        preg_match_all($mailtoPattern, $_content, $match, PREG_SET_ORDER);
        $result = $_content;
        
        foreach ($match as $mailto)
        {
            // Ugly hack because of malformed mailto URI
            $mailto[1] = preg_replace('/(=\?[a-zA-Z0-9\-]*\?[qQbB]\?[^?]*\?=)/e', "rawurlencode(iconv_mime_decode('\\1', 2, 'UTF-8'))", $mailto[1]);
            $result = str_replace($mailto[0], "<a href=\"#\" id=\"123:{$mailto[1]}\" class=\"tinebase-email-link\">$mailto[5]</a>", $result);
        }
        return $result;
    }

    /**
     * create Expressomail message from Zend_Mail_Message
     * 
     * @param Zend_Mail_Message $_zendMailMessage
     * @return Expressomail_Model_Message
     */
    public static function createMessageFromZendMailMessage(Zend_Mail_Message $_zendMailMessage)
    {
        $message = new Expressomail_Model_Message();
        
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
                    
                    $addresses = Expressomail_Message::parseAdresslist($headerValue);
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
