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
        $this->_modelControllers = [];
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
     * @param Felamimail_Model_MessageFilter $messages
     * @param Tinebase_Record_RecordSet $locations
     * @return integer
     */
    public function fileMessages(Felamimail_Model_MessageFilter $messages, $locations)
    {
        $result = 0;
        foreach ($locations as $location) {
            $iterator = new Tinebase_Record_Iterator(array(
                'iteratable' => $this,
                'controller' => $this,
                'filter' => $messages,
                'function' => 'processFileIteration',
            ));
            $iterateResult = $iterator->iterate($location);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Filed ' . $iterateResult['totalcount'] . ' message(s) to location ' . print_r($location->toArray(), true));
            $result += $iterateResult['totalcount'];
        }

        return $result;
    }

    /**
     * file messages
     *
     * @param Tinebase_Record_RecordSet $messages
     * @param Felamimail_Model_MessageFileLocation $location
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function processFileIteration(Tinebase_Record_RecordSet $messages, Felamimail_Model_MessageFileLocation $location)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' About to file ' . count($messages) . ' messages to location ' . print_r($location->toArray(), true));

        if ($location->model === 'Addressbook_Model_EmailAddress') {
            // @todo this is a little bit unsuspected - maybe it can be improved at some point
            $location->model = Addressbook_Model_Contact::class;
            $recordController = Tinebase_Core::getApplicationInstance(Addressbook_Model_Contact::class);
            if (isset($location['record_id']['email'])) {
                $location->record_id = Addressbook_Controller_Contact::getInstance()->getContactByEmail($location['record_id']['email']);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Skipping location ' . print_r($location->toArray(), true));
                $location->record_id = null;
            }
            if ($location->record_id === null) {
                return;
            }
        } else {
            $recordController = $this->_getRecordController($location->model);
        }

        foreach ($messages as $message) {
            /** @var Tinebase_Controller_Record_Abstract $recordController */
            $record = $recordController->fileMessage($location, $message);
            if ($record) {
                Felamimail_Controller_MessageFileLocation::getInstance()->createMessageLocationForRecord($message, $location, $record);
            }
        }

        if (Felamimail_Config::getInstance()->get(Felamimail_Config::DELETE_ARCHIVED_MAIL)) {
            Felamimail_Controller_Message_Flags::getInstance()->addFlags($messages, array(Zend_Mail_Storage::FLAG_DELETED));
        }
    }

    protected function _getRecordController($model)
    {
        if (! isset($this->_modelControllers[$model])) {
            try {
                $this->_modelControllers[$model] = Tinebase_Core::getApplicationInstance($model);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' ' . $tead->getMessage());
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' ' . $tenf->getMessage());
            }
        }
        return $this->_modelControllers[$model];
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
