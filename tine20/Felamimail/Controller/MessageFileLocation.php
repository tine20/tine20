<?php
/**
 * MessageFileLocation controller for Felamimail application
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * MessageFileLocation controller class for Felamimail application
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_MessageFileLocation extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = 'Felamimail';
        $this->_modelName = Felamimail_Model_MessageFileLocation::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => 'felamimail_message_filelocation',
            'modlogActive' => true
        ));
        // we don't want them to stack up
        $this->_purgeRecords = true;
    }

    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_MessageFileLocation
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_MessageFileLocation
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @param string $referenceString
     * @return Tinebase_Record_RecordSet
     */
    public function getLocationsByReference($referenceString)
    {
        if (is_array($referenceString)) {
            // only use the first element?
            $referenceString = array_pop($referenceString);
        }  
      
        if (strpos(',', $referenceString) !== false) {
            $references = explode(',', $referenceString);
        } else if (strpos(' ', $referenceString) !== false) {
            $references = explode(' ', $referenceString);
        } else {
            $references = [$referenceString];
        }

        $trimmedReferences = array_map('trim', $references);
        $hashedReferences = array_map('sha1', $trimmedReferences);
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_MessageFileLocation::class, [
                ['field' => 'message_id_hash', 'operator' => 'in', 'value' => $hashedReferences]
            ]
        );
        $locations = $this->search($filter);

        return $locations;
    }

    /**
     * get cached message file locations
     *
     * @param Felamimail_Model_Message $message
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     */
    public function getLocationsForMessage(Felamimail_Model_Message $message)
    {
        $result = new Tinebase_Record_RecordSet(Felamimail_Model_MessageFileLocation::class);
        if (! $message->getId()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No id - no locations');
            return $result;
        }

        $cache = Tinebase_Core::getCache();
        $cacheId = Tinebase_Helper::convertCacheId('getLocationsForMessage' . $message->getId());
        if ($cache->test($cacheId)) {
            $locations = $cache->load($cacheId);
            if (count($locations) > 0) {
                return $locations;
            }
        }

        if (! $message->folder_id) {
            // skip message without folder
            return $result;
        }

        try {
            $messageId = $this->_getMessageId($message);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Message might be removed from cache. Error: ' . $e->getMessage());
            return $result;
        }
        $locations = Felamimail_Controller_MessageFileLocation::getInstance()->getLocationsByReference(
            $messageId
        );
        $cache->save($locations, $cacheId);

        return $locations;
    }

    /**
     * @param $message
     * @param $location
     * @param $record
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function createMessageLocationForRecord($message, $location, $record, $node)
    {
        if (! $record || ! $record->getId()) {
            throw new Tinebase_Exception_InvalidArgument('existing record is required');
        }

        $messageId = $this->_getMessageId($message);
        $locationToCreate = clone($location);
        $locationToCreate->message_id = $messageId;
        $locationToCreate->message_id_hash = sha1($messageId);
        $locationToCreate->record_id = $record->getId();
        $locationToCreate->node_id = $node->getId();
        if (empty($locationToCreate->record_title)) {
            $locationToCreate->record_title = $record->getTitle();
        }
        if (empty($locationToCreate->type)) {
            $locationToCreate->type = $locationToCreate->model === Filemanager_Model_Node::class
                ? Felamimail_Model_MessageFileLocation::TYPE_NODE
                : Felamimail_Model_MessageFileLocation::TYPE_ATTACHMENT;
        }
        $this->create($locationToCreate);

        // invalidate location cache
        $cache = Tinebase_Core::getCache();
        $cacheId = Tinebase_Helper::convertCacheId('getLocationsForMessage' . $message->getId());
        $cache->remove($cacheId);
    }

    /**
     * @param $message
     * @return mixed
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getMessageId($message)
    {
        if (! isset($message->headers['message-id'])) {
            $headers = Felamimail_Controller_Message::getInstance()->getMessageHeaders($message, null, true);
            if (! isset($headers['message-id'])) {
                throw new Tinebase_Exception_NotFound('no message-id header found');
            }
            $messageId = $headers['message-id'];
        } else {
            $messageId = $message->headers['message-id'];
        }
        return $messageId;
    }

    /**
     * implement logic for each controller in this function
     *
     * @param Tinebase_Event_Abstract $_eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if ($_eventObject instanceof Tinebase_Event_Observer_DeleteFileNode) {
            if (! Setup_Backend_Factory::factory()->tableExists('felamimail_message_filelocation')) {
                // prevent problems during uninstall
                return;
            }

            // delete all MessageFileLocations of observered node that is deleted
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Felamimail_Model_MessageFileLocation::class, [
                    ['field' => 'node_id', 'operator' => 'equals', 'value' => $_eventObject->observable->getId()]
                ]
            );
            $this->deleteByFilter($filter);
        }
    }
}
