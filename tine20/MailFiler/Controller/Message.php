<?php
/**
 * Tine 2.0
 *
 * @package     MailFiler
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * message controller for MailFiler
 *
 * @package     MailFiler
 * @subpackage  Controller
 */
class MailFiler_Controller_Message extends Felamimail_Controller_Message
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'MailFiler';

    /**
     * holds the instance of the singleton
     *
     * @var MailFiler_Controller_Message
     */
    private static $_instance = NULL;

    /**
     * message backend
     *
     * @var MailFiler_Backend_Message
     */
    protected $_backend = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_modelName = 'MailFiler_Model_Message';
        $this->_doContainerACLChecks = FALSE;
        $this->_backend = new MailFiler_Backend_Message();
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return MailFiler_Controller_Message
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new MailFiler_Controller_Message();
        }

        return self::$_instance;
    }

    /**
     * Removes containers where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string                            $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        // TODO acl - we already checked acl on the nodes and this has no public api
    }

    /**
     * get message body from node
     *
     * @param MailFiler_Model_Message $message
     * @param MailFiler_Model_Node    $node
     * @param string $contentType
     * @return string
     */
    public function getMessageBodyFromNode(MailFiler_Model_Message $message, MailFiler_Model_Node $node, $contentType = Zend_Mime::TYPE_TEXT)
    {
        $cacheId = Tinebase_Helper::convertCacheId('getMessageBodyFromNode_' . $node->getId());
        if (Tinebase_Core::getCache()->test($cacheId)) {
            return Tinebase_Core::getCache()->load($cacheId);
        }

        $mimeMessage = $this->_getMimeMessage($node);

        try {
            if ($mimeMessage->isMultipart()) {

                $partid = ($contentType === Zend_Mime::TYPE_TEXT && !empty($message['text_partid']))
                    ? $message['text_partid']
                    : (!empty($message['html_partid']) ? $message['html_partid'] : $message['text_partid']);
                if (empty($partid)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' no text or html body part in message ...using first part');
                    $partid = 1;
                }

                $body = $mimeMessage->getDecodedPartContent($partid);
            } else {
                $body = Felamimail_Message::getDecodedContent($mimeMessage);
            }
            Tinebase_Core::getCache()->save($body, $cacheId, array('getMessageBodyFromNode'), 86400);

            // set body content type correctly (needed for mail actions like reply from MailFiler)
            $message->body_content_type_of_body_property_of_this_record = $contentType;
            $message->body_content_type = $contentType;
        } catch (Zend_Mail_Exception $zme) {
            Tinebase_Exception::log($zme);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Message: ' . print_r($message->toArray(), true));
            $body =  '';
        }

        return $body;
    }

    /**
     * @param MailFiler_Model_Node $node
     * @return Felamimail_Message|mixed
     */
    protected function _getMimeMessage(MailFiler_Model_Node $node)
    {
        $cacheId = Tinebase_Helper::convertCacheId('_getMimeMessage' . $node->getId());
        if (Tinebase_Core::getCache()->test($cacheId)) {
            $mimeMessage = Tinebase_Core::getCache()->load($cacheId);
            return $mimeMessage;
        }
        MailFiler_Controller_Node::getInstance()->resolveMultipleTreeNodesPath($node);
        // create Mime Message from node content
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath(MailFiler_Controller_Node::getInstance()->addBasePath($node->path));
        $handle = fopen($pathRecord->streamwrapperpath, 'r');
        if (! $handle) {
            throw new Tinebase_Exception_Backend('could not open node');
        }
        $rawMessage = stream_get_contents($handle);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' raw message: ' . $rawMessage);
        fclose($handle);

        $mimeMessage = new Felamimail_Message(array('raw' => $rawMessage));

        Tinebase_Core::getCache()->save($mimeMessage, $cacheId, array(), 86400);

        return $mimeMessage;
    }

    /**
     * get message attachments from node
     *
     * @param MailFiler_Model_Node    $node
     * @return Zend_Mail_Part
     */
    public function getPartFromNode(MailFiler_Model_Node $node, $partId)
    {
        $mimeMessage = $this->_getMimeMessage($node);

        return $mimeMessage->getPart($partId);
    }
}
