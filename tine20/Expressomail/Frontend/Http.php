<?php
/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the felamimail application
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Expressomail_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'Expressomail';

    /**
     * upload image
     *
     * @param  string  $base64
     */
    public function uploadImage($base64 = 'no')
    {
        $tmpFile = tempnam(Tinebase_Core::getTempDir(), '');

        $file = new Zend_Form_Element_File('file');
        $maxsize = $file->getMaxFileSize();

        $sessionId = Tinebase_Core::get(Tinebase_Session::SESSIONID);
        if(move_uploaded_file($_FILES['upload']['tmp_name'], $tmpFile)) {
            $image_id = str_replace(Tinebase_Core::getTempDir().'/','',$tmpFile);
            $image_size = filesize($tmpFile);
            if($base64==='yes') {
                // converts image to base64
                try {
                    $image = file_get_contents($tmpFile);
                    $encoded_data = base64_encode($image);
                    echo '{"success":true , "id":"' . $image_id . '", "session_id":"' . $sessionId . '", "size":"' . $image_size . '", "path":"' . $tmpFile . '", "base64":"' . $encoded_data . '"}';
                }
                catch (Exception $e) {
                    echo '{"success":false, "error":' . $e.description . '}';
                }
            }
            else {
                echo '{"success":true , "id":"' . $image_id . '", "session_id":"' . $sessionId . '", "size":"' . $image_size . '", "path":"' . $tmpFile . '"}';
            }
        }
        else {
            echo '{"success":false, "method":"uploadImage", "maxsize":"' . $maxsize .'"}';
        }

    }

    /**
     * show temporary image
     *
     * @param  string  $tempImageId
     */
    public function showTempImage($tempImageId){
        
        //$this->checkAuth();

        // close session to allow other requests
        Expressomail_Session::getSessionNamespace()->lock();

        $clientETag      = null;
        $ifModifiedSince = null;

        if (isset($_SERVER['If_None_Match'])) {
            $clientETag     = trim($_SERVER['If_None_Match'], '"');
            $ifModifiedSince = trim($_SERVER['If_Modified_Since'], '"');
        } elseif (isset($_SERVER['HTTP_IF_NONE_MATCH']) && isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $clientETag     = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
            $ifModifiedSince = trim($_SERVER['HTTP_IF_MODIFIED_SINCE'], '"'); 
        }
        
        // todo: change to use PECL FileInfo library
        $contentType = mime_content_type(Tinebase_Core::getTempDir().'/'.$tempImageId);
        $image = file_get_contents(Tinebase_Core::getTempDir().'/'.$tempImageId);
        
        $serverETag = sha1($fileData);

        // cache for 3600 seconds
        $maxAge = 3600;
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");

        // overwrite Pragma header from session
        header("Pragma: cache");

        // if the cache id is still valid
        if ($clientETag == $serverETag) {
            header("Last-Modified: " . $ifModifiedSince);
            header("HTTP/1.0 304 Not Modified");
            header('Content-Length: 0');
        } else {
            
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header('Content-Type: '. $contentType);
            header("Content-Disposition: inline");
            header('Etag: "' . $serverETag . '"');

            flush();

            die($image);
            
        }
        
    }

    /**
     * download email attachment
     *
     * @param  string  $messageId
     * @param  string  $partId
     * @param  string $$getAsJson
     */
    public function downloadAttachment($messageId, $partId, $getAsJson)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Downloading Attachment ' . $partId . ' of message with id ' . $messageId
        );
        if( $partId == 'A'){
                $this->_downloadAllAtachements($messageId);
                return;
            }
        else {
            if (strpos($messageId, '_') !== false) {
                list($tmpMessageId, $partId) = explode('_', $messageId);
                $messageId = $tmpMessageId;
            }
        }

        $messages = explode(',',$messageId);

        $this->_downloadMessagePart($messages, $partId, $getAsJson);
    }

    /**
     * view attachment
     *
     * @param  string $Id
     */
    public function viewAttachment($Id, $partId = NULL)
    {
        //$this->checkAuth();
        $tempFile = Tinebase_TempFile::getInstance()->getTempFile($Id);

        if ($tempFile) {
            // todo: change to use PECL FileInfo library
            $fileData = file_get_contents($tempFile->path);


            // cache for 3600 seconds
            $maxAge = 3600;
            header('Cache-Control: private, max-age=' . $maxAge);
            header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");

            // overwrite Pragma header from session
            header("Pragma: cache");


            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header('Content-Type: '. $tempFile->type);
            header('Content-Disposition: attachment; filename="'. $tempFile->name .'"');
            header('Etag: "' . $serverETag . '"');
            flush();
            die($fileData);

            return;
        }

        $this->_downloadMessagePart(explode(',',$Id), $partId);
    }

    /**
     * download message
     *
     * @param  string  $messageId
     */
    public function downloadMessage($messageId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Downloading Message ' . $messageId);

        $messages = explode(',',$messageId);

        $this->_downloadMessagePart($messages);
    }

     /**
     * download all messages from folder
     *
     * @param  string  $folderId
     */
    public function downloadAllMessagesFromFolder($folderId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Downloading Messages from folder  ' . $folderId);

        $folderId = explode(',',$messageId);

        $this->_downloadMessagePart($messages);
    }

    /**
     * download message part
     *
     * @param string $_messageId
     * @param string $_partId
     * @param string $_getAsJson
     */
    protected function _downloadMessagePart($_messageId, $_partId = NULL, $_getAsJson = 'false')
    {
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(0);

        try {

            if(count($_messageId) == 1){
                $part = Expressomail_Controller_Message::getInstance()->getMessagePart($_messageId[0], $_partId);
                if ($part instanceof Zend_Mime_Part) {
                        $filename = (! empty($part->filename)) ? $part->filename : $_messageId[0] . '_' . $_partId . '.eml';
                        $contentType = ($_partId === NULL) ? Expressomail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822 : $part->type;

                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                            . ' filename: ' . $filename
                            . ' content type' . $contentType
                            //. print_r($part, TRUE)
                            //. ' ' . stream_get_contents($part->getDecodedStream())
                        );


                        if (strtolower($_getAsJson) === 'true') {
                            $fileData = stream_get_contents($part->getRawStream());
                            
                            die(Zend_Json::encode(array(
                                'id'        => $_messageId[0],
                                'status'    => 'success',
                                'name'      => $filename,
                                'type'      => $contentType,
                                'encoding'  => $part->encoding,
                                'partId'    => $_partId,
                                'fileData'  => $fileData,
                            )));
                            
                        } else {
                            // cache for 3600 seconds
                            $maxAge = 3600;
                            header('Cache-Control: private, max-age=' . $maxAge);
                            header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");

                            // overwrite Pragma header from session
                            header("Pragma: cache");

                            header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
                            header("Content-Type: " . $contentType);
                            $stream = ($_partId === NULL) ? $part->getRawStream() : $part->getDecodedStream();
                            fpassthru($stream);
                            fclose($stream);
                        }
                }
            }else{
                $ZIPfile = new ZipArchive();
                $tmpFile = tempnam(Tinebase_Core::getTempDir(), 'tine20_');
                if ($ZIPfile->open($tmpFile) === TRUE) {
                    foreach($_messageId as $messageID){
                        $part = Expressomail_Controller_Message::getInstance()->getRawMessage($messageID);
                        $filename = $messageID . '.eml';
                        $ZIPfile->addFromString($filename, $part);
                    }
                    $ZIPfile->close();
                }
                $maxAge = 3600;
                header('Cache-Control: private, max-age=' . $maxAge);
                header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");
                // overwrite Pragma header from session
                header("Pragma: cache");
                header('Content-Disposition: attachment; filename="mensagem.zip"');
                header("Content-Type: application/zip");

                $stream = fopen($tmpFile, 'r');
                fpassthru($stream);
                fclose($stream);
                unlink($tmpFile);
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Failed to get message part: ' . $e->getMessage());
        }

        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);

        exit;
    }

    /**
     * download all attachments
     *
     * @param string $_messageId
     */
    protected function _downloadAllAtachements($_messageId)
    {
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(0);

        $ZIPfile = new ZipArchive();
        $tmpFile = tempnam(Tinebase_Core::getTempDir(), 'tine20_');

        if (strpos($_messageId, '_') !== false) {
            list($messageId, $partId) = explode('_', $_messageId);
        } else {
            $messageId = $_messageId;
            $partId    = null;
        }

        if ($ZIPfile->open($tmpFile) === TRUE) {
            $attachments = Expressomail_Controller_Message::getInstance()->getAttachments($messageId, $partId);
            foreach ($attachments as $attachment) {
                $part = Expressomail_Controller_Message::getInstance()->getMessagePart($messageId, $attachment['partId']);
                $filename = is_null($part->filename)? $attachment['filename'] : $part->filename;
                if($part->encoding == Zend_Mime::ENCODING_BASE64)
                    $ZIPfile->addFromString(iconv("UTF-8", "ASCII//TRANSLIT", $filename), base64_decode(stream_get_contents($part->getRawStream())));
                else
                    $ZIPfile->addFromString(iconv("UTF-8", "ASCII//TRANSLIT", $filename), stream_get_contents($part->getRawStream()));
            }
            $ZIPfile->close();
        }

        $maxAge = 3600;
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");
        // overwrite Pragma header from session
        header("Pragma: cache");
        header('Content-Disposition: attachment; filename="mensagens.zip"');
        header("Content-Type: application/zip");

        $stream = fopen($tmpFile, 'r');
        fpassthru($stream);
        fclose($stream);
        unlink($tmpFile);

        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);

        exit;
     }
}
