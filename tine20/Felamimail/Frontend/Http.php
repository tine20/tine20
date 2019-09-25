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
        
        $this->_outputMessagePart($messageId, $partId);
    }

    /**
     * download node attachment
     *
     * @param  string  $nodeId
     * @param  string  $partId
     *
     * @todo write a test
     */
    public function downloadNodeAttachment($nodeId, $partId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Downloading Attachment ' . $partId . ' of node with id ' . $nodeId
        );

        $message = Felamimail_Controller_Message::getInstance()->getMessageFromNode($nodeId);
        $attachment = isset($message['attachments'][$partId]) ? $message['attachments'][$partId] : null;

        if ($attachment === null) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Attachment not found');
            return;
        }

        $contentType = $attachment['content-type'];
        $filename = $attachment['filename'];
        /** @var GuzzleHttp\Psr7\Stream $stream */
        $stream = $attachment['contentstream'];

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
            . ' filename: '    . $filename
            . ' content type ' . $contentType
            // . ' contents: ' . $stream->getContents()
        );

        $this->_prepareHeader($filename, $contentType);

        $stream->rewind();
        echo $stream->getContents();
    }

    /**
     * download message
     *
     * @param  string  $messageId
     */
    public function downloadMessage($messageId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Downloading Message ' . $messageId);
        
        $this->_outputMessagePart($messageId);
    }
    
    /**
     * download message part
     * 
     * @param string $_messageId
     * @param string $_partId
     * @param string $disposition
     * @param boolean $validateImage
     */
    protected function _outputMessagePart($_messageId, $_partId = NULL, $disposition = 'attachment', $validateImage = FALSE)
    {
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(0);
        
        try {
            // fetch extracted winmail dat contents
            if (strstr($_partId, 'winmail-')) {
                $partIndex = explode('winmail-', $_partId);
                $partIndex = intval($partIndex[1]);
                
                $files = Felamimail_Controller_Message::getInstance()->extractWinMailDat($_messageId);
                $file = $files[$partIndex];
                
                $part = NULL;
                
                $path = Tinebase_Core::getTempDir() . '/winmail/';
                $path = $path . $_messageId . '/';
                
                $contentType = mime_content_type($path . $file);
                $this->_prepareHeader($file, $contentType);
                
                $stream = fopen($path . $file, 'r');
                
            } else { // fetch normal attachment
                $part = Felamimail_Controller_Message::getInstance()->getMessagePart($_messageId, $_partId);
                $contentType = ($_partId === NULL) ? Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822 : $part->type;
                $filename = $this->_getDownloadFilename($part, $_messageId, $contentType);
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                    . ' filename: '    . $filename
                    . ' content type ' . $contentType
                );
                
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '. print_r($part, TRUE));
                
                $this->_prepareHeader($filename, $contentType);
                
                $stream = ($_partId === NULL) ? $part->getRawStream() : $part->getDecodedStream();
            }
            
            if ($validateImage) {
                $tmpPath = tempnam(Tinebase_Core::getTempDir(), 'tine20_tmp_imgdata');
                $tmpFile = fopen($tmpPath, 'w');
                try {
                    stream_copy_to_stream($stream, $tmpFile);
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                        __METHOD__ . '::' . __LINE__
                        . ' Could not copy stream: ' . $e->getMessage());
                } finally {
                    fclose($tmpFile);
                }
                // @todo check given mimetype or all images types?
                if (! Tinebase_ImageHelper::isImageFile($tmpPath)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                        . ' Resource is no image file: ' . $filename);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                        . ' Verified ' . $contentType . ' image.');
                    readfile($tmpPath);
                }
                
            } else {
                fpassthru($stream);
            }
            
            fclose($stream);
            
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Failed to get message part: ' . $e->getMessage());
        }
        
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
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
        
        if ($contentType != Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822
            && preg_match('@[a-z]+/([a-z]+)@', $contentType, $extensionMatch))
        {
            $extension = '.' . $extensionMatch[1];
        } else {
            $extension = '.eml';
        }
        
        return $messageId . $extension;
    }

    /**
     * get resource, delivers the image (audio, video) data
     * 
     * @param string $cid
     * @param string $messageId
     * 
     * @todo add param string $folderId
     * @todo support audio/video 
     */
    public function getResource($cid, $messageId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Requesting resource <' . $cid . '> for message ' . $messageId);

        try {
            $resPart = Felamimail_Controller_Message::getInstance()->getResourcePartStructure($cid, $messageId);
            $this->_outputMessagePart($messageId, $resPart['partId'], 'inline', TRUE);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
        }
    }
}
