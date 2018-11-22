<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * message file controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message_File extends Felamimail_Controller_Message
{
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Message_File
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() 
    {
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();
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
     * @return Felamimail_Controller_Message_File
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Message_File();
        }
        
        return self::$_instance;
    }

    /**
     * file messages into Filemanager
     *
     * @param Felamimail_Model_MessageFilter|Tinebase_Record_RecordSet $messages
     * @param string $targetApp
     * @param string $targetPath
     * @return integer|boolean
     */
    public function fileMessages($messages, $targetApp, $targetPath)
    {
        $result = false;
        if (Tinebase_Core::getUser()->hasRight($targetApp, Tinebase_Acl_Rights::RUN)) {
            if ($messages instanceof Tinebase_Model_Filter_FilterGroup) {
                $iterator = new Tinebase_Record_Iterator(array(
                    'iteratable' => $this,
                    'controller' => $this,
                    'filter'     => $messages,
                    'function'   => 'processFileIteration',
                ));
                $iterateResult = $iterator->iterate($targetApp, $targetPath);

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Filed ' . $iterateResult['totalcount'] . ' message(s).');
                $result = $iterateResult['totalcount'];
            }

        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' User does not have RUN right for application');
        }

        return $result;
    }

    /**
     * file messages
     *
     * @param Tinebase_Record_RecordSet $messages
     * @param string $targetApp
     * @param string $targetPath
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function processFileIteration(Tinebase_Record_RecordSet $messages, $targetApp, $targetPath)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' About to file ' . count($messages) . ' messages to ' . $targetApp . '/' . $targetPath);

        foreach ($messages as $message) {
            /** @var Filemanager_Controller_Node $nodeController */
            $nodeController = Tinebase_Core::getApplicationInstance($targetApp . '_Model_Node');
            $node = $nodeController->fileMessage($targetPath, $message);
            if (! isset($message->headers['message-id'])) {
                throw new Tinebase_Exception_InvalidArgument('message id header must be present for filing messages');
            }
            $messageId = $message->headers['message-id'];
            $fileLocation = new Felamimail_Model_MessageFileLocation([
                'message_id' => $messageId,
                'message_id_hash' => sha1($messageId),
                'model' => get_class($node),
                'record_id' => $node->getId(),
                'record_title' => $node->name,
                'type' => Felamimail_Model_MessageFileLocation::TYPE_NODE,
            ]);
            Felamimail_Controller_MessageFileLocation::getInstance()->create($fileLocation);
        }

        if (Felamimail_Config::getInstance()->get(Felamimail_Config::DELETE_ARCHIVED_MAIL)) {
            Felamimail_Controller_Message_Flags::getInstance()->addFlags($messages, array(Zend_Mail_Storage::FLAG_DELETED));
        }
    }

    /**
     * @param Felamimail_Model_Message $message
     * @return Tinebase_Record_RecordSet
     */
    public function getFileSuggestions(Felamimail_Model_Message $message)
    {
        $suggestions = new Tinebase_Record_RecordSet(Felamimail_Model_MessageFileSuggestion::class);
        if ($message->getId()) {
            // make sure we have the current message with headers, ...
            $message = $this->get($message->getId());
            $headers = $this->getMessageHeaders($message, null, true);
            foreach (Felamimail_Controller_Message_File::getInstance()->getSenderContactsOfMessage($message)
                     as $sender
            ) {
                $suggestions->addRecord(new Felamimail_Model_MessageFileSuggestion([
                    'type' => Felamimail_Model_MessageFileSuggestion::TYPE_SENDER,
                    'record' => $sender,
                    'model' => get_class($sender),
                ]));
            }
        } else {
            $headers = isset($message->headers) ? $message->headers : [];
            foreach (Felamimail_Controller_Message_File::getInstance()->getRecipientContactsOfMessage($message)
                     as $recipient
            ) {
                $suggestions->addRecord(new Felamimail_Model_MessageFileSuggestion([
                    'type' => Felamimail_Model_MessageFileSuggestion::TYPE_RECIPIENT,
                    'record' => $recipient,
                    'model' => get_class($recipient),
                ]));
            }
        }

        $headerFieldsToCheck = ['message-id', 'references', 'in-reply-to'];
        foreach ($headerFieldsToCheck as $headerField) {
            if (! isset($headers[$headerField]) || empty($headers[$headerField])) {
                continue;
            }
            $referenceLocations = Felamimail_Controller_MessageFileLocation::getInstance()->getLocationsByReference(
                $headers[$headerField]
            );
            foreach ($referenceLocations as $referenceLocations) {
                $suggestions->addRecord(new Felamimail_Model_MessageFileSuggestion([
                    'type' => Felamimail_Model_MessageFileSuggestion::TYPE_FILE_LOCATION,
                    'record' => $referenceLocations,
                    'model' => get_class($referenceLocations),
                ]));
            }
        }

        return $suggestions;
    }
}
