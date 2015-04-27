<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Transport
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * mail transport for Expressomail
 * - extended Zend_Mail_Transport_Smtp, added getBody/getHeaders and use these for appendMessage / sendMessage
 *
 * @package     Expressomail
 * @subpackage  Transport
 */
class Expressomail_Transport extends Zend_Mail_Transport_Smtp 
{
    /**
     * get mail body as string
     *
     * @param Zend_Mail $_mail
     * @return string
     */
    public function getBody(Zend_Mail $_mail = NULL)
    {
        if (! isset($this->body)) {
            $mime = $_mail->getMime();
            $message = new Zend_Mime_Message();
            $message->setMime($mime);
            $this->body = $message->generateMessage($this->EOL);
        }
        
        return $this->body;
    }

    /**
     * get mail headers as string
     *
     * @param array $_additionalHeaders
     * @return string
     */
    public function getHeaders($_additionalHeaders = array())
    {
        if (! isset($this->header)) {
            $this->_prepareHeaders($this->_headers);
        }
        
        $result = $this->header;
        foreach($_additionalHeaders as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $single) {
                    $this->header .= $key . ': ' . $single . $this->EOL;
                }
            } else {
                $this->header .= $key . ': ' . $value . $this->EOL;
            }
        }
        
        return $this->header;
    }
    
    /**
     * get raw message as string
     * 
     * @param Zend_Mail $mail
     * @param array $_additionalHeaders
     * @return string
     */
    public function getRawMessage(Zend_Mail $mail = NULL, $_additionalHeaders = array())
    {
        if ($mail !== NULL) {
            // this part is from Zend_Mail_Transport_Abstract::send()
            $this->_isMultipart = false;
            $this->_mail        = $mail;
            $this->_parts       = $mail->getParts();
            $mime               = $mail->getMime();
    
            // Build body content
            $this->_buildBody();
    
            // Determine number of parts and boundary
            $count    = count($this->_parts);
            $boundary = null;
            if ($count < 1) {
                /**
                 * @see Zend_Mail_Transport_Exception
                 */
                require_once 'Zend/Mail/Transport/Exception.php';
                throw new Zend_Mail_Transport_Exception('Mail is empty');
            }
    
            if ($count > 1) {
                // Multipart message; create new MIME object and boundary
                $mime     = new Zend_Mime($this->_mail->getMimeBoundary());
                $boundary = $mime->boundary();
            } elseif ($this->_isMultipart) {
                // multipart/alternative -- grab boundary
                $boundary = $this->_parts[0]->boundary;
            }
    
            // Determine recipients, and prepare headers
            $this->recipients = implode(',', $mail->getRecipients());
            $this->_prepareHeaders($this->_getHeaders($boundary));
    
            // Create message body
            // This is done so that the same Zend_Mail object can be used in
            // multiple transports
            $message = new Zend_Mime_Message();
            $message->setParts($this->_parts);
            $message->setMime($mime);
            $this->body = $message->generateMessage($this->EOL);
        }
        
        $mailAsString = $this->getHeaders($_additionalHeaders) . $this->EOL. $this->getBody();
        // convert \n to \r\n
        $mailAsString = preg_replace("/(?<!\\r)\\n(?!\\r)/", "\r\n", $mailAsString);

        return $mailAsString;
    }
    
    /**
     * Generate MIME compliant message from the current configuration
     *
     * If both a text and HTML body are present, generates a
     * multipart/alternative Zend_Mime_Part containing the headers and contents
     * of each. Otherwise, uses whichever of the text or HTML parts present.
     *
     * The content part is then prepended to the list of Zend_Mime_Parts for
     * this message.
     *
     * @return void
     */
    protected function _buildBody()
    {
//        if (($text = $this->_mail->getBodyText())
//            && ($html = $this->_mail->getBodyHtml()))
      
//        
        $text = $this->_mail->getBodyText();
        $html = $this->_mail->getBodyHtml();

        $htmlAttachments = $this->_mail->getHtmlRelatedAttachments();
        $htmlAttachmentParts = $htmlAttachments->getParts();
        $hasHtmlRelatedParts = count($htmlAttachmentParts);

        if (($text && $html)
            || ($html && $hasHtmlRelatedParts) && count($this->_parts))
          {
            // Generate unique boundary for multipart/alternative
            $mime = new Zend_Mime(null);
            $boundaryLine = $mime->boundaryLine($this->EOL);
            $boundaryEnd  = $mime->mimeEnd($this->EOL);

//            $text->disposition = false;
            $html->disposition = false;

//            $body = $boundaryLine
//                  . $text->getHeaders($this->EOL)
//                  . $this->EOL
//                  . $text->getContent($this->EOL)
//                  . $this->EOL
//                  . $boundaryLine
//                  . $html->getHeaders($this->EOL)
            
            if ($hasHtmlRelatedParts) {
                $message = new Zend_Mime_Message();
                array_unshift($htmlAttachmentParts, $html);
                $message->setParts($htmlAttachmentParts);
                $htmlMime = $htmlAttachments->getMime();
                $message->setMime($htmlMime);
                $html = new Zend_Mime_Part($message->generateMessage($this->EOL, false));
                $html->boundary = $htmlMime->boundary();
                $html->type = Zend_Mime::MULTIPART_RELATED;
                $html->encoding = null;
            }

            $body = $boundaryLine;

            if ($text) {
                $text->disposition = false;

                $body.= $text->getHeaders($this->EOL)
                      . $this->EOL
                      . $text->getContent($this->EOL)
                      . $this->EOL
                      . $boundaryLine;
            }

            $body.= $html->getHeaders($this->EOL)
                  . $this->EOL
                  . $html->getContent($this->EOL)
                  . $this->EOL
                  . $boundaryEnd;

            $mp           = new Zend_Mime_Part($body);
            $mp->type     = Zend_Mime::MULTIPART_ALTERNATIVE;
            $mp->boundary = $mime->boundary();

            $this->_isMultipart = true;

            // Ensure first part contains text alternatives
            array_unshift($this->_parts, $mp);

            // Get headers
            $this->_headers = $this->_mail->getHeaders();
            return;
        }

        // If not multipart, then get the body
        if (false !== ($body = $this->_mail->getBodyHtml())) {
            array_unshift($this->_parts, $body);
            if ($hasHtmlRelatedParts) {
                $this->_mail->setType(Zend_Mime::MULTIPART_RELATED);
                foreach ($htmlAttachmentParts as $part) {
                    $this->_parts[] = $part;
                }
            }
        } elseif (false !== ($body = $this->_mail->getBodyText())) {
            array_unshift($this->_parts, $body);
        }

        if (!$body) {
            /**
             * @see Zend_Mail_Transport_Exception
             */
            require_once 'Zend/Mail/Transport/Exception.php';
            throw new Zend_Mail_Transport_Exception('No body specified');
        }

        // Get headers
        $this->_headers = $this->_mail->getHeaders();
        $headers = $body->getHeadersArray($this->EOL);
        foreach ($headers as $header) {
            // Headers in Zend_Mime_Part are kept as arrays with two elements, a
            // key and a value
            $this->_headers[$header[0]] = array($header[1]);
        }
    }
}