<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Mail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * This class extends the Zend_Mail class 
 *
 * @package     Tinebase
 * @subpackage  Mail
 */
class Tinebase_Mail extends Zend_Mail
{
    /**
    * email address regexp
    */
    const EMAIL_ADDRESS_REGEXP = '/([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,5})/i';
    
    /**
     * Sender: address
     * @var string
     */
    protected $_sender = null;
    
    /**
     * fallback charset constant
     * 
     * @var string
     */
    const DEFAULT_FALLBACK_CHARSET = 'iso-8859-15';
    
    /**
     * create Tinebase_Mail from Zend_Mail_Message
     * 
     * @param  Zend_Mail_Message  $_zmm
     * @param  string             $_replyBody
     * @return Tinebase_Mail
     */
    public static function createFromZMM(Zend_Mail_Message $_zmm, $_replyBody = null)
    {
        $contentStream = fopen("php://temp", 'r+');
        fputs($contentStream, $_zmm->getContent());
        rewind($contentStream);
        
        $mp = new Zend_Mime_Part($contentStream);
        self::_getMetaDataFromZMM($_zmm, $mp);
        
        // append old body when no multipart/mixed
        if ($_replyBody !== null && $_zmm->headerExists('content-transfer-encoding')) {
            $mp = self::_appendReplyBody($mp, $_replyBody);
        } else {
            $mp->decodeContent();
            if ($_zmm->headerExists('content-transfer-encoding')) {
                switch ($_zmm->getHeader('content-transfer-encoding')) {
                    case Zend_Mime::ENCODING_BASE64:
                        // BASE64 encode has a bug that swallows the last char(s)
                        $bodyEncoding = Zend_Mime::ENCODING_7BIT;
                        break;
                    default: 
                        $bodyEncoding = $_zmm->getHeader('content-transfer-encoding');
                }
            } else {
                $bodyEncoding = Zend_Mime::ENCODING_7BIT;
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Using encoding: ' . $bodyEncoding);
            $mp->encoding = $bodyEncoding;
        }
        
        $result = new Tinebase_Mail('utf-8');
        $result->setBodyText($mp);
        $result->setHeadersFromZMM($_zmm);
        
        return $result;
    }
    
    /**
     * get meta data (like contentype, charset, ...) from zmm and set it in zmp
     * 
     * @param Zend_Mail_Message $zmm
     * @param Zend_Mime_Part $zmp
     */
    protected static function _getMetaDataFromZMM(Zend_Mail_Message $zmm, Zend_Mime_Part $zmp)
    {
        if ($zmm->headerExists('content-transfer-encoding')) {
            $zmp->encoding = $zmm->getHeader('content-transfer-encoding');
        } else {
            $zmp->encoding = Zend_Mime::ENCODING_7BIT;
        }
        
        if ($zmm->headerExists('content-type')) {
            $contentTypeHeader = Zend_Mime_Decode::splitHeaderField($zmm->getHeader('content-type'));
            
            $zmp->type = $contentTypeHeader[0];
            
            if (isset($contentTypeHeader['boundary'])) {
                $zmp->boundary = $contentTypeHeader['boundary'];
            }
            
            if (isset($contentTypeHeader['charset'])) {
                $zmp->charset = $contentTypeHeader['charset'];
            }
        } else {
            $zmp->type = Zend_Mime::TYPE_TEXT;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Encoding: ' . $zmp->encoding . ' / type: ' . $zmp->type . ' / charset: ' . $zmp->charset);
    }
    
    /**
     * appends old body to mime part
     * 
     * @param Zend_Mime_Part $mp
     * @param string $replyBody plain/text reply body
     * @return Zend_Mime_Part
     */
    protected static function _appendReplyBody(Zend_Mime_Part $mp, $replyBody)
    {
        $decodedContent = Tinebase_Mail::getDecodedContent($mp, NULL, FALSE);
        $type = $mp->type;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " mp content: " . $decodedContent);
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " reply body: " . $replyBody);
        }
        
        if ($type === Zend_Mime::TYPE_HTML && /* checks if $replyBody does not contains tags */ $replyBody === strip_tags($replyBody)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Converting plain/text reply body to HTML");
            $replyBody = self::convertFromTextToHTML($replyBody);
        }
        
        if ($type === Zend_Mime::TYPE_HTML && preg_match('/(<\/body>[\s\r\n]*<\/html>)/i', $decodedContent, $matches)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Appending reply body to html body.');
            
            $decodedContent = str_replace($matches[1], $replyBody . $matches[1], $decodedContent);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Appending reply body to mime text part.");
            
            $decodedContent .= $replyBody;
        }
        
        $mp = new Zend_Mime_Part($decodedContent);
        $mp->charset = 'utf-8';
        $mp->type = $type;
        
        return $mp;
    }
    
    /**
     * Sets the HTML body for the message
     *
     * @param  string|Zend_Mime_Part    $html
     * @param  string    $charset
     *  @param  string    $encoding
     * @return Zend_Mail Provides fluent interface
     */
    public function setBodyHtml($html, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        if ($html instanceof Zend_Mime_Part) {
            $mp = $html;
        } else {
            if ($charset === null) {
                $charset = $this->_charset;
            }
        
            $mp = new Zend_Mime_Part($html);
            $mp->encoding = $encoding;
            $mp->type = Zend_Mime::TYPE_HTML;
            $mp->disposition = Zend_Mime::DISPOSITION_INLINE;
            $mp->charset = $charset;
        }
        
        $this->_bodyHtml = $mp;
    
        return $this;
    }
    
    /**
     * Sets the text body for the message.
     *
     * @param  string|Zend_Mime_Part $txt
     * @param  string $charset
     * @param  string $encoding
     * @return Zend_Mail Provides fluent interface
    */
    public function setBodyText($txt, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        if ($txt instanceof Zend_Mime_Part) {
            $mp = $txt;
        } else {
            if ($charset === null) {
                $charset = $this->_charset;
            }
    
            $mp = new Zend_Mime_Part($txt);
            $mp->encoding = $encoding;
            $mp->type = Zend_Mime::TYPE_TEXT;
            $mp->disposition = Zend_Mime::DISPOSITION_INLINE;
            $mp->charset = $charset;
        }
        
        $this->_bodyText = $mp;

        return $this;
    }
    
    /**
     * set headers
     * 
     * @param Zend_Mail_Message $_zmm
     * @return Zend_Mail Provides fluent interface
     */
    public function setHeadersFromZMM(Zend_Mail_Message $_zmm)
    {
        foreach ($_zmm->getHeaders() as $header => $values) {
            foreach ((array)$values as $value) {
                switch ($header) {
                    case 'content-transfer-encoding':
                    // these are implicitly set by Zend_Mail_Transport_Abstract::_getHeaders()
                    case 'content-type':
                    case 'mime-version':
                        // do nothing
                        break;
                        
                    case 'bcc':
                        $addresses = self::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $this->addBcc($address['address'], $address['name']);
                        }
                        break;
                        
                    case 'cc':
                        $addresses = self::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $this->addCc($address['address'], $address['name']);
                        }
                        break;
                        
                    case 'date':
                        try {
                            $this->setDate($value);
                        } catch (Zend_Mail_Exception $zme) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " Could not set date: " . $value);
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " " . $zme);
                            $this->setDate();
                        }
                        break;
                        
                    case 'from':
                        $addresses = self::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $this->setFrom($address['address'], $address['name']);
                        }
                        break;
                        
                    case 'message-id':
                        $this->setMessageId($value);
                        break;
                        
                    case 'return-path':
                        $this->setReturnPath($value);
                        break;
                        
                    case 'subject':
                        $this->setSubject($value);
                        break;
                        
                    case 'to':
                        $addresses = self::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $this->addTo($address['address'], $address['name']);
                        }
                        break;
                        
                    default:
                        $this->addHeader($header, $value);
                        break;
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Sets Sender-header and sender of the message
     *
     * @param  string    $email
     * @param  string    $name
     * @return Zend_Mail Provides fluent interface
     * @throws Zend_Mail_Exception if called subsequent times
     */
    public function setSender($email, $name = '')
    {
        if ($this->_sender === null) {
            $email = strtr($email,"\r\n\t",'???');
            $this->_from = $email;
            $this->_storeHeader('Sender', $this->_encodeHeader('"'.$name.'"').' <'.$email.'>', true);
        } else {
            throw new Zend_Mail_Exception('Sender Header set twice');
        }
        return $this;
    }
    
    /**
     * Formats e-mail address
     * 
     * NOTE: we always add quotes to the name as this caused problems when name is encoded
     * @see Zend_Mail::_formatAddress
     *
     * @param string $email
     * @param string $name
     * @return string
     */
    protected function _formatAddress($email, $name)
    {
        if ($name === '' || $name === null || $name === $email) {
            return $email;
        } else {
            $encodedName = $this->_encodeHeader($name);
            $format = '"%s" <%s>';
            return sprintf($format, $encodedName, $email);
        }
    }

    /**
     * check if Zend_Mail_Message is/contains calendar iMIP message
     * 
     * @param Zend_Mail_Message $zmm
     * @return boolean
     */
    public static function isiMIPMail(Zend_Mail_Message $zmm)
    {
        foreach ($zmm as $part) {
            if (preg_match('/text\/calendar/', $part->contentType)) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    /**
     * get decoded body content
     * 
     * @param Zend_Mime_Part $zmp
     * @param array $partStructure
     * @param boolean $appendCharsetFilter
     * @return string
     */
    public static function getDecodedContent(Zend_Mime_Part $zmp, $_partStructure = NULL, $appendCharsetFilter = TRUE)
    {
        $charset = self::_getCharset($zmp, $_partStructure);
        if ($appendCharsetFilter) {
            $charset = self::_appendCharsetFilter($zmp, $charset);
        }
        $encoding = ($_partStructure && ! empty($_partStructure['encoding'])) ? $_partStructure['encoding'] : $zmp->encoding;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Trying to decode mime part content. Encoding/charset: " . $encoding . ' / ' . $charset);
        
        // need to set error handler because stream_get_contents just throws a E_WARNING
        set_error_handler('Tinebase_Mail::decodingErrorHandler', E_WARNING);
        try {
            $body = $zmp->getDecodedContent();
            restore_error_handler();
            
        } catch (Tinebase_Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . " Decoding of " . $zmp->encoding . '/' . $encoding . ' encoded message failed: ' . $e->getMessage());
            
            // trying to fix decoding problems
            restore_error_handler();
            $zmp->resetStream();
            if (preg_match('/convert\.quoted-printable-decode/', $e->getMessage())) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Trying workaround for http://bugs.php.net/50363.');
                $body = quoted_printable_decode(stream_get_contents($zmp->getRawStream()));
                $body = iconv($charset, 'utf-8', $body);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Try again with fallback encoding.');
                $zmp->appendDecodeFilter(self::_getDecodeFilter());
                set_error_handler('Tinebase_Mail::decodingErrorHandler', E_WARNING);
                try {
                    $body = $zmp->getDecodedContent();
                    restore_error_handler();
                } catch (Tinebase_Exception $e) {
                    restore_error_handler();
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Fallback encoding failed. Trying base64_decode().');
                    $zmp->resetStream();
                    $body = base64_decode(stream_get_contents($zmp->getRawStream()));
                    $body = iconv($charset, 'utf-8', $body);
                }
            }
        }
        
        return $body;
    }
    /**
     * convert charset (and return charset)
     *
     * @param  Zend_Mime_Part  $_part
     * @param  array           $_structure
     * @return string   
     */
    protected static function _getCharset(Zend_Mime_Part $_part, $_structure = NULL)
    {
        return ($_structure && isset($_structure['parameters']['charset'])) 
            ? $_structure['parameters']['charset']
            : ($_part->charset ? $_part->charset : self::DEFAULT_FALLBACK_CHARSET);
    }
    
    /**
     * convert charset (and return charset)
     *
     * @param  Zend_Mime_Part  $_part
     * @param  string          $charset
     * @return string   
     */
    protected static function _appendCharsetFilter(Zend_Mime_Part $_part, $charset)
    {
        if ($charset == 'utf8') {
            $charset = 'utf-8';
        } else if ($charset == 'us-ascii') {
            // us-ascii caused problems with iconv encoding to utf-8
            $charset = self::DEFAULT_FALLBACK_CHARSET;
        } else if (strpos($charset, '.') !== false) {
            // the stream filter does not like charsets with a dot in its name
            // stream_filter_append(): unable to create or locate filter "convert.iconv.ansi_x3.4-1968/utf-8//IGNORE"
            $charset = self::DEFAULT_FALLBACK_CHARSET;
        } else if (iconv($charset, 'utf-8', '') === false) {
            // check if charset is supported by iconv
            $charset = self::DEFAULT_FALLBACK_CHARSET;
        }
        
        $_part->appendDecodeFilter(self::_getDecodeFilter($charset));
        
        return $charset;
    }
    
    /**
     * get decode filter for stream_filter_append
     * 
     * @param string $_charset
     * @return string
     */
    protected static function _getDecodeFilter($_charset = self::DEFAULT_FALLBACK_CHARSET)
    {
        if (in_array(strtolower($_charset), array('iso-8859-1', 'windows-1252', 'iso-8859-15')) && extension_loaded('mbstring')) {
            require_once 'StreamFilter/ConvertMbstring.php';
            $filter = 'convert.mbstring';
        } else {
            $filter = "convert.iconv.$_charset/utf-8//IGNORE";
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Appending decode filter: ' . $filter);
        
        return $filter;
    }
    
    /**
     * error exception handler for iconv decoding errors / only gets E_WARNINGs
     *
     * NOTE: PHP < 5.3 don't throws exceptions for Catchable fatal errors per default,
     * so we convert them into exceptions manually
     *
     * @param integer $severity
     * @param string $errstr
     * @param string $errfile
     * @param integer $errline
     * @throws Tinebase_Exception
     * 
     * @todo maybe we can remove that because php 5.3+ is required now
     */
    public static function decodingErrorHandler($severity, $errstr, $errfile, $errline)
    {
        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " $errstr in {$errfile}::{$errline} ($severity)");
        
        throw new Tinebase_Exception($errstr);
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
     * convert text to html
     * - replace quotes ('>  ') with blockquotes 
     * - does htmlspecialchars()
     * - converts linebreaks to <br />
     * 
     * @param string $text
     * @param string $blockquoteClass
     * @return string
     */
    public static function convertFromTextToHTML($text, $blockquoteClass = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Input: ' . $text);
        
        $lines = preg_split('/\r\n|\n|\r/', $text);
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
                $class = $blockquoteClass ? 'class="' . $blockquoteClass . '"' : '';
                $line = '<blockquote ' . $class . '>' . $line;
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
       
        $cache = Tinebase_Core::get('cache');
        $cacheId = Tinebase_Helper::convertCacheId('getDomainList');
        $validTlds = $cache->load($cacheId);
        
        if (! is_array($validTlds)) {
            try {                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Caching list of valid domains from iana.org');

                $client = new Zend_Http_Client('http://data.iana.org/TLD/tlds-alpha-by-domain.txt');
                $dlist = $client->request()->getBody();

                $validTlds = array_fill_keys(explode("\n", $dlist), true);
                # First key would be a comment and should be removed
                array_shift($validTlds);
               
                // Cache for at least one day 24 * 3600 = 86400
                $cache->save($validTlds, $cacheId, array('settings'), 86400);
            } catch (Exception $ex) {
                $validTlds = Zend_Json::decode('["AC":1,"ACADEMY":1,"ACCOUNTANTS":1,"ACTIVE":1,"ACTOR":1,"AD":1,"AE":1,"AERO":1,"AF":1,"AG":1,"AGENCY":1,"AI":1,"AIRFORCE":1,"AL":1,"AM":1,"AN":1,"AO":1,"AQ":1,"AR":1,"ARCHI":1,"ARMY":1,"ARPA":1,"AS":1,"ASIA":1,"ASSOCIATES":1,"AT":1,"ATTORNEY":1,"AU":1,"AUCTION":1,"AUDIO":1,"AUTOS":1,"AW":1,"AX":1,"AXA":1,"AZ":1,"BA":1,"BAR":1,"BARGAINS":1,"BAYERN":1,"BB":1,"BD":1,"BE":1,"BEER":1,"BERLIN":1,"BEST":1,"BF":1,"BG":1,"BH":1,"BI":1,"BID":1,"BIKE":1,"BIO":1,"BIZ":1,"BJ":1,"BLACK":1,"BLACKFRIDAY":1,"BLUE":1,"BM":1,"BMW":1,"BN":1,"BNPPARIBAS":1,"BO":1,"BOO":1,"BOUTIQUE":1,"BR":1,"BRUSSELS":1,"BS":1,"BT":1,"BUILD":1,"BUILDERS":1,"BUSINESS":1,"BUZZ":1,"BV":1,"BW":1,"BY":1,"BZ":1,"BZH":1,"CA":1,"CAB":1,"CAMERA":1,"CAMP":1,"CANCERRESEARCH":1,"CAPETOWN":1,"CAPITAL":1,"CARAVAN":1,"CARDS":1,"CARE":1,"CAREER":1,"CAREERS":1,"CASH":1,"CAT":1,"CATERING":1,"CC":1,"CD":1,"CENTER":1,"CEO":1,"CERN":1,"CF":1,"CG":1,"CH":1,"CHEAP":1,"CHRISTMAS":1,"CHURCH":1,"CI":1,"CITIC":1,"CITY":1,"CK":1,"CL":1,"CLAIMS":1,"CLEANING":1,"CLICK":1,"CLINIC":1,"CLOTHING":1,"CLUB":1,"CM":1,"CN":1,"CO":1,"CODES":1,"COFFEE":1,"COLLEGE":1,"COLOGNE":1,"COM":1,"COMMUNITY":1,"COMPANY":1,"COMPUTER":1,"CONDOS":1,"CONSTRUCTION":1,"CONSULTING":1,"CONTRACTORS":1,"COOKING":1,"COOL":1,"COOP":1,"COUNTRY":1,"CR":1,"CREDIT":1,"CREDITCARD":1,"CRUISES":1,"CU":1,"CUISINELLA":1,"CV":1,"CW":1,"CX":1,"CY":1,"CYMRU":1,"CZ":1,"DAD":1,"DANCE":1,"DATING":1,"DAY":1,"DE":1,"DEALS":1,"DEGREE":1,"DEMOCRAT":1,"DENTAL":1,"DENTIST":1,"DESI":1,"DIAMONDS":1,"DIET":1,"DIGITAL":1,"DIRECT":1,"DIRECTORY":1,"DISCOUNT":1,"DJ":1,"DK":1,"DM":1,"DNP":1,"DO":1,"DOMAINS":1,"DURBAN":1,"DZ":1,"EAT":1,"EC":1,"EDU":1,"EDUCATION":1,"EE":1,"EG":1,"EMAIL":1,"ENGINEER":1,"ENGINEERING":1,"ENTERPRISES":1,"EQUIPMENT":1,"ER":1,"ES":1,"ESQ":1,"ESTATE":1,"ET":1,"EU":1,"EUS":1,"EVENTS":1,"EXCHANGE":1,"EXPERT":1,"EXPOSED":1,"FAIL":1,"FARM":1,"FEEDBACK":1,"FI":1,"FINANCE":1,"FINANCIAL":1,"FISH":1,"FISHING":1,"FITNESS":1,"FJ":1,"FK":1,"FLIGHTS":1,"FLORIST":1,"FM":1,"FO":1,"FOO":1,"FOUNDATION":1,"FR":1,"FRL":1,"FROGANS":1,"FUND":1,"FURNITURE":1,"FUTBOL":1,"GA":1,"GAL":1,"GALLERY":1,"GB":1,"GBIZ":1,"GD":1,"GE":1,"GENT":1,"GF":1,"GG":1,"GH":1,"GI":1,"GIFT":1,"GIFTS":1,"GIVES":1,"GL":1,"GLASS":1,"GLOBAL":1,"GLOBO":1,"GM":1,"GMAIL":1,"GMO":1,"GN":1,"GOP":1,"GOV":1,"GP":1,"GQ":1,"GR":1,"GRAPHICS":1,"GRATIS":1,"GREEN":1,"GRIPE":1,"GS":1,"GT":1,"GU":1,"GUIDE":1,"GUITARS":1,"GURU":1,"GW":1,"GY":1,"HAMBURG":1,"HAUS":1,"HEALTHCARE":1,"HELP":1,"HERE":1,"HIPHOP":1,"HIV":1,"HK":1,"HM":1,"HN":1,"HOLDINGS":1,"HOLIDAY":1,"HOMES":1,"HORSE":1,"HOST":1,"HOSTING":1,"HOUSE":1,"HOW":1,"HR":1,"HT":1,"HU":1,"ID":1,"IE":1,"IL":1,"IM":1,"IMMO":1,"IMMOBILIEN":1,"IN":1,"INDUSTRIES":1,"INFO":1,"ING":1,"INK":1,"INSTITUTE":1,"INSURE":1,"INT":1,"INTERNATIONAL":1,"INVESTMENTS":1,"IO":1,"IQ":1,"IR":1,"IS":1,"IT":1,"JE":1,"JETZT":1,"JM":1,"JO":1,"JOBS":1,"JOBURG":1,"JP":1,"JUEGOS":1,"KAUFEN":1,"KE":1,"KG":1,"KH":1,"KI":1,"KIM":1,"KITCHEN":1,"KIWI":1,"KM":1,"KN":1,"KOELN":1,"KP":1,"KR":1,"KRD":1,"KRED":1,"KW":1,"KY":1,"KZ":1,"LA":1,"LACAIXA":1,"LAND":1,"LAWYER":1,"LB":1,"LC":1,"LEASE":1,"LGBT":1,"LI":1,"LIFE":1,"LIGHTING":1,"LIMITED":1,"LIMO":1,"LINK":1,"LK":1,"LOANS":1,"LONDON":1,"LOTTO":1,"LR":1,"LS":1,"LT":1,"LTDA":1,"LU":1,"LUXE":1,"LUXURY":1,"LV":1,"LY":1,"MA":1,"MAISON":1,"MANAGEMENT":1,"MANGO":1,"MARKET":1,"MARKETING":1,"MC":1,"MD":1,"ME":1,"MEDIA":1,"MEET":1,"MELBOURNE":1,"MEME":1,"MENU":1,"MG":1,"MH":1,"MIAMI":1,"MIL":1,"MINI":1,"MK":1,"ML":1,"MM":1,"MN":1,"MO":1,"MOBI":1,"MODA":1,"MOE":1,"MONASH":1,"MORTGAGE":1,"MOSCOW":1,"MOTORCYCLES":1,"MOV":1,"MP":1,"MQ":1,"MR":1,"MS":1,"MT":1,"MU":1,"MUSEUM":1,"MV":1,"MW":1,"MX":1,"MY":1,"MZ":1,"NA":1,"NAGOYA":1,"NAME":1,"NAVY":1,"NC":1,"NE":1,"NET":1,"NETWORK":1,"NEUSTAR":1,"NEW":1,"NF":1,"NG":1,"NGO":1,"NHK":1,"NI":1,"NINJA":1,"NL":1,"NO":1,"NP":1,"NR":1,"NRA":1,"NRW":1,"NU":1,"NYC":1,"NZ":1,"OKINAWA":1,"OM":1,"ONG":1,"ONL":1,"OOO":1,"ORG":1,"ORGANIC":1,"OTSUKA":1,"OVH":1,"PA":1,"PARIS":1,"PARTNERS":1,"PARTS":1,"PE":1,"PF":1,"PG":1,"PH":1,"PHOTO":1,"PHOTOGRAPHY":1,"PHOTOS":1,"PHYSIO":1,"PICS":1,"PICTURES":1,"PINK":1,"PIZZA":1,"PK":1,"PL":1,"PLACE":1,"PLUMBING":1,"PM":1,"PN":1,"POST":1,"PR":1,"PRAXI":1,"PRESS":1,"PRO":1,"PROD":1,"PRODUCTIONS":1,"PROPERTIES":1,"PROPERTY":1,"PS":1,"PT":1,"PUB":1,"PW":1,"PY":1,"QA":1,"QPON":1,"QUEBEC":1,"RE":1,"REALTOR":1,"RECIPES":1,"RED":1,"REHAB":1,"REISE":1,"REISEN":1,"REN":1,"RENTALS":1,"REPAIR":1,"REPORT":1,"REPUBLICAN":1,"REST":1,"RESTAURANT":1,"REVIEWS":1,"RICH":1,"RIO":1,"RO":1,"ROCKS":1,"RODEO":1,"RS":1,"RSVP":1,"RU":1,"RUHR":1,"RW":1,"RYUKYU":1,"SA":1,"SAARLAND":1,"SARL":1,"SB":1,"SC":1,"SCA":1,"SCB":1,"SCHMIDT":1,"SCHULE":1,"SCOT":1,"SD":1,"SE":1,"SERVICES":1,"SEXY":1,"SG":1,"SH":1,"SHIKSHA":1,"SHOES":1,"SI":1,"SINGLES":1,"SJ":1,"SK":1,"SL":1,"SM":1,"SN":1,"SO":1,"SOCIAL":1,"SOFTWARE":1,"SOHU":1,"SOLAR":1,"SOLUTIONS":1,"SOY":1,"SPACE":1,"SPIEGEL":1,"SR":1,"ST":1,"SU":1,"SUPPLIES":1,"SUPPLY":1,"SUPPORT":1,"SURF":1,"SURGERY":1,"SUZUKI":1,"SV":1,"SX":1,"SY":1,"SYSTEMS":1,"SZ":1,"TATAR":1,"TATTOO":1,"TAX":1,"TC":1,"TD":1,"TECHNOLOGY":1,"TEL":1,"TF":1,"TG":1,"TH":1,"TIENDA":1,"TIPS":1,"TIROL":1,"TJ":1,"TK":1,"TL":1,"TM":1,"TN":1,"TO":1,"TODAY":1,"TOKYO":1,"TOOLS":1,"TOP":1,"TOWN":1,"TOYS":1,"TP":1,"TR":1,"TRADE":1,"TRAINING":1,"TRAVEL":1,"TT":1,"TV":1,"TW":1,"TZ":1,"UA":1,"UG":1,"UK":1,"UNIVERSITY":1,"UNO":1,"UOL":1,"US":1,"UY":1,"UZ":1,"VA":1,"VACATIONS":1,"VC":1,"VE":1,"VEGAS":1,"VENTURES":1,"VERSICHERUNG":1,"VET":1,"VG":1,"VI":1,"VIAJES":1,"VILLAS":1,"VISION":1,"VLAANDEREN":1,"VN":1,"VODKA":1,"VOTE":1,"VOTING":1,"VOTO":1,"VOYAGE":1,"VU":1,"WALES":1,"WANG":1,"WATCH":1,"WEBCAM":1,"WEBSITE":1,"WED":1,"WF":1,"WHOSWHO":1,"WIEN":1,"WIKI":1,"WILLIAMHILL":1,"WORKS":1,"WS":1,"WTC":1,"WTF":1,"XN--1QQW23A":1,"XN--3BST00M":1,"XN--3DS443G":1,"XN--3E0B707E":1,"XN--45BRJ9C":1,"XN--4GBRIM":1,"XN--55QW42G":1,"XN--55QX5D":1,"XN--6FRZ82G":1,"XN--6QQ986B3XL":1,"XN--80ADXHKS":1,"XN--80AO21A":1,"XN--80ASEHDB":1,"XN--80ASWG":1,"XN--90A3AC":1,"XN--C1AVG":1,"XN--CG4BKI":1,"XN--CLCHC0EA0B2G2A9GCD":1,"XN--CZR694B":1,"XN--CZRU2D":1,"XN--D1ACJ3B":1,"XN--FIQ228C5HS":1,"XN--FIQ64B":1,"XN--FIQS8S":1,"XN--FIQZ9S":1,"XN--FPCRJ9C3D":1,"XN--FZC2C9E2C":1,"XN--GECRJ9C":1,"XN--H2BRJ9C":1,"XN--I1B6B1A6A2E":1,"XN--IO0A7I":1,"XN--J1AMH":1,"XN--J6W193G":1,"XN--KPRW13D":1,"XN--KPRY57D":1,"XN--KPUT3I":1,"XN--L1ACC":1,"XN--LGBBAT1AD8J":1,"XN--MGB9AWBF":1,"XN--MGBA3A4F16A":1,"XN--MGBAAM7A8H":1,"XN--MGBAB2BD":1,"XN--MGBAYH7GPA":1,"XN--MGBBH1A71E":1,"XN--MGBC0A9AZCG":1,"XN--MGBERP4A5D4AR":1,"XN--MGBX4CD0AB":1,"XN--NGBC5AZD":1,"XN--NQV7F":1,"XN--NQV7FS00EMA":1,"XN--O3CW4H":1,"XN--OGBPF8FL":1,"XN--P1AI":1,"XN--PGBS0DH":1,"XN--Q9JYB4C":1,"XN--RHQV96G":1,"XN--S9BRJ9C":1,"XN--SES554G":1,"XN--UNUP4Y":1,"XN--VHQUV":1,"XN--WGBH1C":1,"XN--WGBL6A":1,"XN--XHQ521B":1,"XN--XKC2AL3HYE2A":1,"XN--XKC2DL3A5EE0H":1,"XN--YFRO4I67O":1,"XN--YGBI2AMMX":1,"XN--ZFR164B":1,"XXX":1,"XYZ":1,"YACHTS":1,"YANDEX":1,"YE":1,"YOKOHAMA":1,"YOUTUBE":1,"YT":1,"ZA":1,"ZM":1,"ZONE":1,"ZW":1]');
             
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Could not get iana.org list, fallback to static list.');
            }
        }
        
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
            if (preg_match('{^\.[0-9]{1,3}$}', $tld) || isset($validTlds[strtoupper(substr($tld, 1))]) || $protocol)
            {
                if (!$protocol && $password)
                {
                    $return .= htmlspecialchars($username);
                    $pos = $urlPos + strlen($username);
                    continue;
                }

                if (!$protocol && $username && !$password && !$afterDomain) {
                    $fullUrl = "mailto:$url";
                } else {
                    $fullUrl = $protocol ? $url : "http://$url";
                }
                $link = '<a href="' . $fullUrl . '" target="_blank">' . $url . '</a>';
                $link = str_replace('@', '&#64;', $link);
                $return .= $link;
            } else { // is not a URL
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
}
