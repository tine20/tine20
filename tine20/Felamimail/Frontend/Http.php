<?php
/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the felamimail application
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Felamimail_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    

    /**
     * download email attachment
     *
     * @param  string  $messageId
     * @param  string  $partId
     */
    public function downloadAttachment($messageId, $partId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Downloading Attachment ' . $partId . ' of message with id ' . $messageId
        );
        
        $this->_downloadMessagePart($messageId, $partId);
    }

    /**
     * download message
     *
     * @param  string  $messageId
     */
    public function downloadMessage($messageId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Downloading Message ' . $messageId);
        
        $this->_downloadMessagePart($messageId);
    }
    
    /**
     * download message part
     * 
     * @param string $_messageId
     * @param string $_partId
     */
    protected function _downloadMessagePart($_messageId, $_partId = NULL)
    {
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(0);
        
        try {
            $part = Felamimail_Controller_Message::getInstance()->getMessagePart($_messageId, $_partId);
            
            if ($part instanceof Zend_Mime_Part) {
                
                $contentType = ($_partId === NULL) ? Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822 : $part->type;
                $filename = $this->_getDownloadFilename($part, $_messageId, $contentType);

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                    . ' filename: '    . $filename
                    . ' content type ' . $contentType
                    //. ' ' . stream_get_contents($part->getDecodedStream())
                );

                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '. print_r($part, TRUE));
                
                // cache for 3600 seconds
                $maxAge = 3600;
                header('Cache-Control: private, max-age=' . $maxAge);
                header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");
                
                // overwrite Pragma header from session
                header("Pragma: cache");
                
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header("Content-Type: " . $contentType);
                
                $stream = ($_partId === NULL) ? $part->getRawStream() : $part->getDecodedStream();
                fpassthru($stream);
                fclose($stream);
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Failed to get message part: ' . $e->getMessage());
        }
        
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
        
        exit;
    }
    
    /**
     * get download filename from part
     * 
     * @param Zend_Mime_Part $part
     * @param string $messageId
     * @param string $contentType
     * @return string
     * 
     * @see 0007264: attachment download does not detect content-type
     */
    protected function _getDownloadFilename($part, $messageId, $contentType)
    {
        if (! empty($part->filename)) {
            return $part->filename;
        }
        
        if ($contentType != Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822 && preg_match('@[a-z]+/([a-z]+)@', $contentType, $extensionMatch)) {
            $extension = '.' . $extensionMatch[1];
        } else {
            $extension = '.eml';
        }
        
        return $messageId . $extension;
    }
}
