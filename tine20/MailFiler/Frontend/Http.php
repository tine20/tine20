<?php
/**
 * MailFiler Http frontend
 *
 * This class handles all Http requests for the MailFiler application
 *
 * @package     MailFiler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class MailFiler_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'MailFiler';
    
    /**
     * download file
     * 
     * @param string $path
     * @param string $id
     * 
     * @todo allow to download a folder as ZIP file
     */
    public function downloadFile($path, $id)
    {
        $nodeController = MailFiler_Controller_Node::getInstance();
        if ($path) {
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($path));
            $node = $nodeController->getFileNode($pathRecord);
        } elseif ($id) {
            $node = $nodeController->get($id);
            $nodeController->resolveMultipleTreeNodesPath($node);
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($node->path));
        } else {
            Tinebase_Exception_InvalidArgument('Either a path or id is needed to download a file.');
        }
        
        $this->_downloadFileNode($node, $pathRecord->streamwrapperpath);
        exit;
    }

    /**
     * download email attachment
     *
     * @param  string  $path
     * @param  string  $messageId
     * @param  string  $partId
     */
    public function downloadAttachment($path, $messageId, $partId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Downloading Attachment ' . $partId . ' of message with uid ' . $messageId . ' in path ' . $path
        );

        // remove filename from path
        $pathParts = explode('/', $path);
        array_pop($pathParts);
        $path = implode('/', $pathParts);

        $filter = array(
            array(
                'field'    => 'path',
                'operator' => 'equals',
                'value'    => $path
            ),
            array(
                'field'    => 'messageuid',
                'operator' => 'equals',
                'value'    => $messageId
        ));
        $node = MailFiler_Controller_Node::getInstance()->search(new MailFiler_Model_NodeFilter($filter))->getFirstRecord();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($filter, true));

        if ($node) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Found node for attachment download: ' . print_r($node->toArray(), true));

            $part = MailFiler_Controller_Message::getInstance()->getPartFromNode($node, $partId);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Found part for attachment download: ' . print_r($part, true));

            $contentType = $part->headerExists('content-type') ? $part->getHeaderField('content-type') : 'application/octet-stream';
            $filename = $part->headerExists('content-type') ?  $part->getHeaderField('content-type', 'name') : '';
            if (empty($filename)) {
                $filename = $part->headerExists('content-disposition') ? $part->getHeaderField('content-disposition', 'filename') : 'unknown';
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                . ' filename: '    . $filename
                . ' content type ' . $contentType
            );

//            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
//                . ' ' . $part);

            $this->_prepareHeader($filename, $contentType);
            $content = Felamimail_Message::getDecodedContent($part);
            echo $content;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No matching node found for filter ' . print_r($filter, true));
        }
    }
}
