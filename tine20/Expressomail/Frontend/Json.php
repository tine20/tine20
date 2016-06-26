<?php
/**
 * json frontend for Expressomail
 *
 * This class handles all Json requests for the Expressomail application
 *
 * @package     Expressomail
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Expressomail_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * application name
     *
     * @var string
     */
    protected $_applicationName = 'Expressomail';

    /***************************** folder funcs *******************************/

    /**
     * Search for records matching given arguments
     *
     * @param string                              $_filter json encoded
     * @param string                              $_paging json encoded
     * @param Tinebase_Controller_SearchInterface $_controller the record controller
     * @param string                              $_filterModel the class name of the filter model to use
     * @param bool                                $_getRelations
     * @param string                              $_totalCountMethod
     * @return array
     *
     * @todo It should be on Tinebase_Frontend_Json_Abstract
     */
    protected function _search($_filter, $_paging, Tinebase_Controller_SearchInterface $_controller, $_filterModel, $_getRelations = FALSE, $_totalCountMethod = self::TOTALCOUNT_CONTROLLER)
    {
        $decodedPagination = is_array($_paging) ? $_paging : Zend_Json::decode($_paging);
        $pagination = new Tinebase_Model_Pagination($decodedPagination);
        $filter = $this->_decodeFilter($_filter, $_filterModel);

        $records = $_controller->search($filter, $pagination, $_getRelations);

        $result = $this->_multipleRecordsToJson($records, $filter);

        return array(
            'results'       => array_values($result),
            'totalcount'    => $records instanceof Expressomail_Record_SearchTotalCountInterface ?
                $records->getSearchTotalCount() : (
                    $_totalCountMethod == self::TOTALCOUNT_CONTROLLER ?
                    $_controller->searchCount($filter) : count($result)
                ),
            'filter'        => $filter->toArray(TRUE),
        );
    }

    /**
     * search folders and update/initialize cache of subfolders
     *
     * @param  array $filter
     * @return array
     */
    public function searchFolders($filter)
    {
        // close session to allow other requests
        Expressomail_Session::getSessionNamespace()->lock();
        $result = $this->_search($filter, '', Expressomail_Controller_Folder::getInstance(), 'Expressomail_Model_FolderFilter');
        return $result;
    }
      /**
     * get ACLs for a folder
     *
     * @param string $accountId
     * @param string $globalName
     * @return array
     */
    public function getFolderAcls($accountId, $globalName)
    {
        $result = Expressomail_Controller_Folder::getInstance()->getAcls($accountId,$globalName);
        return $result;
    }


      /**
     * get ACLs for a folder
     *
     * @param string $accountId
     * @return array
     */
    public function getSenders($accountId)
    {
        $result = Expressomail_Controller_Folder::getInstance()->getUsersWithSendAsAcl($accountId);
        return $result;
    }

    /**
     * set ACLs for a folder
     *
     * @param string $accountId
     * @param string $globalName
     * @param array ACLs
     * @return array
     */
    public function setFolderAcls($accountId, $globalName, $acls)
    {
        $result = Expressomail_Controller_Folder::getInstance()->setAcls($accountId,$globalName,$acls);
        return $result;
    }

     /**
     * import message to folder from a file(eml)
     *
     * @param string $accountId
     * @param string $globalName
     * @param array $file
     * @return array
     */
    public function importMessage($accountId,$folderId, $file)
    {
        $result = Expressomail_Controller_Message::getInstance()->importMessagefromfile($accountId,$folderId, $file);
        return array(
            'status'    =>  'success' );
    }

     /**
     * Remove messages from trash before date ...
     *
     * @param string $accountId
     * @return array
     */
    public function deleteMsgsBeforeDate($accountId)
    {
        $result_msgids = 0;
        $preference_value = Tinebase_Core::getPreference('Expressomail')->{Expressomail_Preference::DELETE_FROMTRASH};
        $account = $this->getAccount($accountId);
        if($account['trash_folder'] && $preference_value) {
            $folder = Expressomail_Controller_Folder::getInstance()->getByBackendAndGlobalName($accountId, $account['trash_folder']);
            $before_date = date("Y-m-d H:i:s", strtotime("-".$preference_value." day"));
            $result_msgids = Expressomail_Controller_Message::getInstance()->SelectBeforeDate($folder,$before_date);
        }

        return array(
            'status'    =>  'success',
            'msgs'    =>  $result_msgids);
    }

    /**
     * add new folder
     *
     * @param string $name
     * @param string $parent
     * @param string $accountId
     * @return array
     */
    public function addFolder($name, $parent, $accountId)
    {
        $result = Expressomail_Controller_Folder::getInstance()->create($accountId, $name, $parent);
        return $result->toArray();
    }

    /**
     * rename folder
     *
     * @param string $newName
     * @param string $oldGlobalName
     * @param string $accountId
     * @return array
     */
    public function renameFolder($newName, $oldGlobalName, $accountId)
    {
        $result = Expressomail_Controller_Folder::getInstance()->rename($accountId, $newName, $oldGlobalName);
        return $result->toArray();
    }

    /**
     * delete folder
     *
     * @param string $folder the folder global name to delete
     * @param string $accountId
     * @return array
     */
    public function deleteFolder($folder, $accountId)
    {
        $result = Expressomail_Controller_Folder::getInstance()->delete($accountId, $folder);
        return array(
            'status'    => ($result) ? 'success' : 'failure'
        );
    }

    /**
     * refresh folder
     *
     * @param string $folderId the folder id to delete
     * @return array
     */
    public function refreshFolder($folderId)
    {
        $result = Expressomail_Controller_Message::getInstance()->clear($folderId);
        return array(
            'status'    => ($result) ? 'success' : 'failure'
        );
    }

    /**
     * remove all messages from folder and delete subfolders
     *
     * @param  string $folderId the folder id to delete
     * @return array with folder status
     */
    public function emptyFolder($folderId)
    {
        // close session to allow other requests
        Expressomail_Session::getSessionNamespace()->lock();

        $result = Expressomail_Controller_Folder::getInstance()->emptyFolder($folderId, TRUE);
        return $this->_recordToJson($result);
    }

    /**
     * update folder cache
     *
     * @param string $accountId
     * @param string  $folderName of parent folder
     * @return array of (sub)folders in cache
     */
    public function updateFolderCache($accountId, $folderName)
    {
        $result = Expressomail_Controller_Folder::getInstance()->updateFolderCache($accountId, $folderName, TRUE);
        return $this->_multipleRecordsToJson($result);
    }

    /**
     * get folder status
     *
     * @param array  $filterData
     * @return array of folder status
     */
    public function getFolderStatus($filterData)
    {
        // close session to allow other requests
        Expressomail_Session::getSessionNamespace()->lock();

        $filter = new Expressomail_Model_FolderFilter($filterData);
        $result = Expressomail_Controller_Folder::getInstance()->getFolderStatus($filter);
        return $this->_multipleRecordsToJson($result);
    }

    /***************************** messages funcs *******************************/

    /**
     * search messages in message cache
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchMessages($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Expressomail_Controller_Message::getInstance(), 'Expressomail_Model_MessageFilter');
        return $result;
    }

    /**
     * update message cache
     * - use session/writeClose to update incomplete cache and allow following requests
     *
     * @param  string  $folderId id of active folder
     * @param  integer $time     update time in seconds
     * @return array
     */
    public function updateMessageCache($folderId, $time)
    {
        // close session to allow other requests
        Expressomail_Session::getSessionNamespace()->lock();

        $folder = Expressomail_Controller_Message::getInstance()->updateCache($folderId, $time);

        return $this->_recordToJson($folder);
    }

    /**
     * get message data
     *
     * @param  string $id
     * @return array
     */
    public function getMessage($id)
    {
        // close session to allow other requests
        Expressomail_Session::getSessionNamespace()->lock();

        if (strpos($id, '_') !== false) {
            list($messageId, $partId) = explode('_', $id);
        } else {
            $messageId = $id;
            $partId    = null;
        }

        $message = Expressomail_Controller_Message::getInstance()->getCompleteMessage($messageId, $partId, false);
        $message->id = $id;

        return $this->_recordToJson($message);
    }

    /**
     * move messsages to folder
     *
     * @param  array $filterData filter data
     * @param  string $targetFolderId
     * @return array source folder status
     */
    public function moveMessages($filterData, $targetFolderId)
    {
        // close session to allow other requests
        Expressomail_Session::getSessionNamespace()->lock();

        $filter = new Expressomail_Model_MessageFilter(array());
        $filter->setFromArrayInUsersTimezone($filterData);
        $updatedFolders = Expressomail_Controller_Message_Move::getInstance()->moveMessages($filter, $targetFolderId);

        $result = ($updatedFolders !== NULL) ? $this->_multipleRecordsToJson($updatedFolders) : array();

        return $result;
    }
    
    /**
     * calculates message size
     *
     * @param  array $recordData
     * @return array
     *
     */
    public function calcMessageSize($recordData)
    {
        return Expressomail_Controller_Message::getInstance()->calcMessageSize($recordData);
    }

    /**
     * removes temp files
     *
     * @param  array $recordData
     * @return array
     *
     */
    public function removeTempFiles($recordData)
    {
        return Expressomail_Controller_Message::getInstance()->removeTempFiles($recordData);
    }

    /**
     * save + send message
     *
     * - this function has to be named 'saveMessage' because of the generic edit dialog function names
     *
     * @param  array $recordData
     * @return array
     *
     */
    public function saveMessage($recordData)
    {
        $recordData = $this->updateRecordIds($recordData);

        $message = new Expressomail_Model_Message();
        $message->setFromJsonInUsersTimezone($recordData);
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r(Zend_Json::decode($recordData), TRUE));

        if (! $message->original_id) {
            $message->original_id = $message->draft_id;
        }
        try {
            $result = Expressomail_Controller_Message_Send::getInstance()->sendMessage($message);
            $result = $this->_recordToJson($result);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message: ' . $zmpe->getMessage());
            throw $zmpe;
        }

        if ($message->draft_id) {
            Expressomail_Controller_Message_Flags::getInstance()->addFlags($message->draft_id, array('\\Deleted'));
        }

        return $result;
    }

    /**
     * save message in folder
     *
     * @param  string $folderName
     * @param  array $recordData
     * @return array
     */
    public function saveMessageInFolder($folderName, $recordData)
    {
        $message = new Expressomail_Model_Message();
        $message->setFromJsonInUsersTimezone($recordData);

        $result = Expressomail_Controller_Message_Send::getInstance()->saveMessageInFolder($folderName, $message);
        $result = $this->_recordToJson($result);

        return $result;
    }

    /**
     * save draft in folder
     *
     * @param  string $folderName
     * @param  array $recordData
     * @return array
     */
    public function saveDraftInFolder($folderName, $recordData)
    {
        $recordData = $this->updateRecordIds($recordData);

        $message = new Expressomail_Model_Message();
        $message->setFromJsonInUsersTimezone($recordData);

        $draft_id = ($message->draft_id);
        if (! $message->original_id) {
            $message->original_id = $draft_id;
        }
        $message = Expressomail_Controller_Message_Send::getInstance()->saveMessageInFolder($folderName, $message);

        if ($draft_id) {
            Expressomail_Controller_Message_Flags::getInstance()->addFlags($draft_id, array('\\Deleted'));
        }

        $result = $this->getDraftMessage($message->original_id, $message->draft_id);

        return $result;
    }

    /**
     * update recorddata ids that maybe are outdated after last draft save
     *
     * @param  array $recordData
     * @return array
     */
    private function updateRecordIds($recordData)
    {
        if ($recordData['initial_id']) {
            $replaceIds = array();
            if(count($recordData['embedded_images'])>0)
            {
                for ($index = 0; $index < count($recordData['embedded_images']); $index++)
                {
                    try {
                        $replaceIds[$index] = $recordData['embedded_images'][$index]['id'];
                        $recordData['embedded_images'][$index]['id'] = $recordData['draft_id'];
                    } catch (Exception $exc) {
                    }
                }
            }
            foreach($replaceIds as $id) {
                $recordData['body'] = str_replace('src="index.php?method=Expressomail.downloadAttachment&amp;messageId='.$id, 'src="index.php?method=Expressomail.downloadAttachment&amp;messageId='.$recordData['draft_id'], $recordData['body']);
            }
            // update content
            $recordData['body'] = str_replace($recordData['initial_id'], $recordData['draft_id'], $recordData['body']);
        }
        return $recordData;
    }

    /**
     * report message(s) as phishing
     *
     * @param  array $msgIds
     * @param  array $recordData
     * @return array
     */
    public function reportPhishing($msgIds, $recordData)
    {
        $message = new Expressomail_Model_Message();
        $message->setFromJsonInUsersTimezone($recordData);

        $zipFile = Expressomail_Controller_Message::getInstance()->zipMessages($msgIds);

        $message = Expressomail_Controller_Message::getInstance()->parsePhishingNotification($message, $zipFile);

        $message = Expressomail_Controller_Message_Send::getInstance()->sendMessage($message);
        $result = $this->_recordToJson($message);

        return $result;
    }

    /**
     * get draft message data
     *
     * @param  string $id
     * @param  string $draft_id
     * @return array
     */
    public function getDraftMessage($id, $draft_id)
    {
        // close session to allow other requests
        Expressomail_Session::getSessionNamespace()->lock();

        if (strpos($id, '_') !== false) {
            list($messageId, $partId) = explode('_', $id);
        } else {
            $messageId = $id;
            $partId    = null;
        }

        $message = Expressomail_Controller_Message::getInstance()->getCompleteMessage($messageId, $partId, false);
        $message->id = $id;
        $message->original_id = $id;
        $message->draft_id = $draft_id;

        return $this->_recordToJson($message);
    }

    /**
     * add given flags to given messages
     *
     * @param  array        $filterData
     * @param  string|array $flags
     * @return array
     *
     * @todo remove legacy code
     */
    public function addFlags($filterData, $flags)
    {
        // close session to allow other requests
        Expressomail_Session::getSessionNamespace()->lock();

        // as long as we get array of ids or filter data from the client, we need to do this legacy handling (1 dimensional -> ids / 2 dimensional -> filter data)
        if (! empty($filterData) && is_array($filterData[0])) {
            $filter = new Expressomail_Model_MessageFilter(array());
            $filter->setFromArrayInUsersTimezone($filterData);
        } else {
            $filter = $filterData;
        }

        $affectedFolders = Expressomail_Controller_Message_Flags::getInstance()->addFlags($filter, (array) $flags);

        return array(
            'status'    => 'success',
            'result'    => $affectedFolders,
        );
    }

    /**
     * clear given flags from given messages
     *
     * @param array         $filterData
     * @param string|array  $flags
     * @return array
     *
     * @todo remove legacy code
     * @todo return $affectedFolders to client
     */
    public function clearFlags($filterData, $flags)
    {
        // as long as we get array of ids or filter data from the client, we need to do this legacy handling (1 dimensional -> ids / 2 dimensional -> filter data)
        if (! empty($filterData) && is_array($filterData[0])) {
            $filter = new Expressomail_Model_MessageFilter(array());
            $filter->setFromArrayInUsersTimezone($filterData);
        } else {
            $filter = $filterData;
        }
        $affectedFolders = Expressomail_Controller_Message_Flags::getInstance()->clearFlags($filter, (array) $flags);

        return array(
            'status' => 'success'
        );
    }

    /**
     * returns message prepared for json transport
     * - overwriten to convert recipients to array
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        if ($_record instanceof Expressomail_Model_Message) {
            foreach (array('to', 'cc', 'bcc') as $type) {
                if (! is_array($_record->{$type})) {
                    if (! empty($_record->{$type})) {
                        $exploded = explode(',', $_record->{$type});
                        $_record->{$type} = $exploded;
                    } else {
                        $_record->{$type} = array();
                    }
                }
            }

            if ($_record->preparedParts instanceof Tinebase_Record_RecordSet) {
                foreach ($_record->preparedParts as $preparedPart) {
                    if ($preparedPart->preparedData instanceof Calendar_Model_iMIP) {
                        try {
                            $iMIPFrontend = new Calendar_Frontend_iMIP();
                            $iMIPFrontend->prepareComponent($preparedPart->preparedData);
                        } catch (Exception $e) {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not prepare calendar iMIP component: ' . $e->getMessage());
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                        }
                    }
                }
            }

        } else if ($_record instanceof Expressomail_Model_Account) {
            // add usernames (imap + smtp)
            $_record->resolveCredentials();
            $_record->resolveCredentials(TRUE, FALSE, TRUE);

        } else if ($_record instanceof Expressomail_Model_Sieve_Vacation) {
            if (! $_record->mime) {
                $_record->reason = Expressomail_Message::convertFromTextToHTML($_record->reason);
            }
        }

        return parent::_recordToJson($_record);
    }

    /**
     * update flags
     * - use session/writeClose to allow following requests
     *
     * @param  string  $folderId id of active folder
     * @param  integer $time     update time in seconds
     * @return array
     */
    public function updateFlags($folderId, $time)
    {
        // close session to allow other requests
        Expressomail_Session::getSessionNamespace()->lock();

        $folder = Expressomail_Controller_Message::getInstance()->updateFlags($folderId, $time);

        return $this->_recordToJson($folder);
    }

    /***************************** accounts funcs *******************************/

    /**
     * search accounts
     *
     * @param  array $filter
     * @return array
     */
    public function searchAccounts($filter)
    {
        return $results = $this->_search($filter, '', Expressomail_Controller_Account::getInstance(), 'Expressomail_Model_AccountFilter');
    }

    /**
     * get account data
     *
     * @param string $id
     * @return array
     */
    public function getAccount($id)
    {
        return $this->_get($id, Expressomail_Controller_Account::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveAccount($recordData)
    {
        return $this->_save($recordData, Expressomail_Controller_Account::getInstance(), 'Account');
    }

    /**
     * deletes existing accounts
     *
     * @param  array $ids
     * @return array
     */
    public function deleteAccounts($ids)
    {
        return array('status' => $this->_delete($ids, Expressomail_Controller_Account::getInstance()));
    }

    /**
     * change account pwd / username
     *
     * @param string $id
     * @param string $username
     * @param string $password
     * @return array
     */
    public function changeCredentials($id, $username, $password)
    {
        $result = Expressomail_Controller_Account::getInstance()->changeCredentials($id, $username, $password);

        return array('status' => ($result) ? 'success' : 'failure');
    }

    /***************************** sieve funcs *******************************/

    /**
     * get sieve vacation for account
     *
     * @param  string $id account id
     * @return array
     */
    public function getVacation($id)
    {
        $record = Expressomail_Controller_Sieve::getInstance()->getVacation($id);

        return $this->_recordToJson($record);
    }

    /**
     * set sieve vacation for account
     *
     * @param  array $recordData
     * @return array
     */
    public function saveVacation($recordData)
    {
        $record = new Expressomail_Model_Sieve_Vacation(array(), TRUE);
        $record->setFromJsonInUsersTimezone($recordData);

        $record = Expressomail_Controller_Sieve::getInstance()->setVacation($record);

        return $this->_recordToJson($record);
    }

    /**
     * get sieve rules for account
     *
     * @param  string $accountId
     * @return array
     */
    public function getRules($accountId)
    {
        $records = Expressomail_Controller_Sieve::getInstance()->getRules($accountId);

        return array(
            'results'       => $this->_multipleRecordsToJson($records),
            'totalcount'    => count($records),
        );
    }

    /**
     * remove duplicate rules if exist ...
     *
     * @param   array $rulesData
     * @return  array
     */
    protected  function _fixRulesData($rulesData)
    {
        if(count($rulesData) == 0){
            return $rulesData;
        }
        
        $rulesDatax = array();
        $rulesDatax[] = $rulesData[0];
        foreach ($rulesData as $rule) {
            $rule_conditions = '';
            foreach($rule['conditions'] as $condition) {
                ksort($condition);
                foreach ($condition as $key => $value) {
                    $rule_conditions .= $key . ':'. $value . ';';
                }
            }
            $rule_action_argument = $rule['action_argument'];
            $rule_action_type = $rule['action_type'];
            $flg = true;
            foreach ($rulesDatax as $item) {
                $item_conditions = '';
                foreach($item['conditions'] as $condition) {
                    ksort($condition);
                    foreach ($condition as $key => $value) {
                        $item_conditions .= $key . ':'. $value . ';';
                    }
                }
                if ($rule_conditions == $item_conditions && $rule_action_argument == $item['action_argument'] && $rule_action_type == $item['action_type']) {
                    $flg = false;
                    break;
                }
            }
            if ($flg) {
                $rulesDatax[] = $rule;
            }
        }
        return $rulesDatax;
    }
    
    /**
     * set sieve rules for account
     *
     * @param   array $accountId
     * @param   array $rulesData
     * @return  array
     */
    public function saveRules($accountId, $rulesData)
    {
        $records = new Tinebase_Record_RecordSet('Expressomail_Model_Sieve_Rule', $this->_fixRulesData($rulesData));
        $records = Expressomail_Controller_Sieve::getInstance()->setRules($accountId, $records);

        return $this->_multipleRecordsToJson($records);
    }

        /**
     * get available vacation message templates
     *
     * @return array
     *
     * @todo perhaps we should use the node controller for the search and move it to tinebase
     */
    public function getVacationMessageTemplates()
    {
        try {
            $templateContainer = Tinebase_Container::getInstance()->getContainerById(Expressomail_Config::getInstance()->{Expressomail_Config::VACATION_TEMPLATES_CONTAINER_ID});
            $path = Tinebase_FileSystem::getInstance()->getContainerPath($templateContainer);
            $parentNode = Tinebase_FileSystem::getInstance()->stat($path);
            $filter = new Tinebase_Model_Tree_Node_Filter(array(
                array('field' => 'parent_id', 'operator' => 'equals', 'value' => $parentNode->getId())
            ));

            $templates = Tinebase_FileSystem::getInstance()->searchNodes($filter);
            $result = $this->_multipleRecordsToJson($templates, $filter);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Could not get vacation template files: ' . $e);
            $result = array();
        }

        return array(
            'totalcount' => count($result),
            'results'    => $result,
        );
    }
    
    /**
     * *************************** other funcs ******************************
     */
    
    /**
     * Returns registry data of expressomail.
     * 
     * @see Tinebase_Application_Json_Abstract
     *
     * @return mixed array 'variable name' => 'data'
     *        
     * @todo get default account data (host, port, ...) from preferences?
     */
    public function getRegistryData() {
        try {
            $accounts = $this->searchAccounts('');
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not get accounts: ' . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            $accounts = array(
                    'results' => array(),
                    'totalcount' => 0 
            );
        }
        
        $supportedFlags = Expressomail_Controller_Message_Flags::getInstance()->getSupportedFlags();
        $extraSenderAccounts = array();
        $allowedEmails = array();
        foreach ($accounts['results'] as $key => $account) {
            try {
                // build a imap backend so the system folder can be created if necessary
                $accountModel = Expressomail_Controller_Account::getInstance()->get($account['id']);
                $accountModel->resolveCredentials(FALSE); // force update the user credentials
                $imapConfig = $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct());
                $config = new stdClass();
                $config->{'host'} = $imapConfig->{'host'};
                $config->{'port'} = $imapConfig->{'port'};
                $config->{'ssl'} = $imapConfig->{'ssl'};
                $config->{'user'} = $accountModel->getUsername();
                $config->{'password'} = $accountModel->{'password'};
                $imap = Expressomail_Backend_ImapFactory::factory($account['id']);
                if ($imap->createDefaultImapSystemFoldersIfNecessary($config)) {
                    try {
                        // add the namespace 'INBOX/' to the new folders
                        $capabilities = $imap->getCapabilityAndNamespace();
                        Expressomail_Controller_Account::getInstance()->updateNamespacesAndDelimiter($accountModel, $capabilities);
                        // update account info in backend and session
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating capabilities for account: ' . $accountModel->name);
                        Expressomail_Controller_Account::getInstance()->getBackend()->update($accountModel);
                        // save capabilities in SESSION
                        Expressomail_Session::getSessionNamespace()->account[$accountModel->getId()] = $capabilities;
                    } catch (Exception $zdse) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getTraceAsString());
                    }
                }
                $allowedEmails[] = $accountModel->email;
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR))
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Exception: ' . $e->getMessage());
            }
            
            try {
                $extraSenderAccounts = Expressomail_Controller_Folder::getInstance()->getUsersWithSendAsAcl($account['id']);
            } catch (Expressomail_Exception_IMAPFolderNotFound $ex) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . $ex->getMessage());
                // Ignore this exception here, it happens when INBOX folder is unaccessible.
            } catch (Expressomail_Exception_IMAPServiceUnavailable $ex) {
                // Ignoring this Exception here.
            }
            unset($account['host']);
            unset($account['port']);
            unset($account['ssl']);
            unset($account['smtp_hostname']);
            unset($account['smtp_port']);
            unset($account['smtp_ssl']);
            unset($account['smtp_auth']);
            $accounts['results'][$key] = $account;
        }

        $user = Tinebase_Core::getUser();
        $allowedEmails = is_array($user->smtpUser->emailAliases)?array_merge($allowedEmails, $user->smtpUser->emailAliases):$allowedEmails;
        foreach ($extraSenderAccounts['results'] as $account) {
            $allowedEmails[] = $account['accountEmailAddress'];
        }

        Expressomail_Session::getSessionNamespace()->allowedEmails[$user->accountId] = $allowedEmails;
        $result = array(
                'extraSenderAccounts' => $extraSenderAccounts,
                'accounts' => $accounts,
                'supportedFlags' => array(
                        'results' => $supportedFlags,
                        'totalcount' => count($supportedFlags) 
                ),
                'aspellDicts' => Tinebase_Core::getConfig()->aspellDicts 
        );
        
        // TODO: get balanceid cookie name from config
        $balanceIdCookieName = 'BALANCEID';
        if (isset($_COOKIE[$balanceIdCookieName])) {
            $result['balanceId'] = array(
                    'cookieName' => $balanceIdCookieName,
                    'cookieValue' => $_COOKIE[$balanceIdCookieName] 
            );
        }
        $result['allowedDomais'] = Expressomail_Controller_Sieve::getInstance()->getAllowedSieveRedirectDomains();

        $result['vacationTemplates'] = $this->getVacationMessageTemplates();

        $config = Tinebase_Core::getConfig();
        if(isset($config->certificate)
                && is_object($config->certificate)) {
            $result['useKeyEscrow'] = $config->certificate->active && $config->certificate->useKeyEscrow;
        } else {
            $result['useKeyEscrow'] = false;
        }


        $config = Expressomail_Controller::getInstance()->getConfigSettings(false);
        // add autoSaveDraftsInterval to client registry
        $result['autoSaveDraftsInterval'] = $config->autoSaveDraftsInterval;
        // add reportPhishingEmail to client registry
        $result['reportPhishingEmail'] = $config->reportPhishingEmail;
        // add mail folders export feature to client registry
        $result['enableMailDirExport'] = $config->enableMailDirExport;

        return $result;
    }

    /*
     * Returns export mail folder scheduler action forr expressomail.
     *
     * @param string $account
     * @param string $folder
     *
     * @return array
     */
    public function schedulerFolder($folder){

        $result = Expressomail_Controller_Scheduler::getInstance()->addNewScheduler($folder);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($result, true));
        if(!$result['msg']){
            return array(
                'status'    => 'success'
            );
        } else{
            return array(
                'error'     => 0,
                'status'    => 'failure',
                'message'   => $result['msg']
            );
        }
    }

    /**
     * check spelling
     *
     * @param string $lang
     * @param string  $data
     * 
     * @return array 
     */
    public function checkSpelling($lang, $data)
    {
        $ret = $this->spellCheck($lang, $data);

        return $ret;
    }

    private function spellCheck($lang, $data) {
        $data = utf8_decode($data);

        if (!function_exists('pspell_new')) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' PSPELL or ASPELL not installed on server.');
            return array(
                'error' => 1,
                'description' => 'O corretor ortográfico não está disponível no servidor.'
            );
        }

        $pspell_link = pspell_new($lang, "", "", "", 1);

        $data = $this->stripslashes_custom($data); //we only need to strip slashes if magic quotes are on

        $data = $this->remove_word_junk($data);

        //make all the returns in the text look the same
        $data = preg_replace("/\r?\n/", "\n", $data);

        //splits the string on any html tags, preserving the tags and putting them in the $words array
        $words = preg_split("/(<[^<>]*>)/", $data, -1, PREG_SPLIT_DELIM_CAPTURE);

        $numResults = count($words); //the number of elements in the array.

        $misspelledCount = 0;

        $text_length = strlen($data);

        /* There is a problem with innerHTML of IE browsers - It removes the spaces.
         * If there are two misspelled words side by side, it makes then together (no space
         * between then). This will be checked using this variable.
         */
        $difference = 0;
        $results = array();

        //this loop looks through the words array and splits any lines of text that aren't html tags on space, preserving the spaces.
        $wpos = 0;
        for($i=0; $i<$numResults; $i++){
            // Words alternate between real words and html tags, starting with words.
            if(($i & 1) == 0) // Even-numbered entries are word sets.
            {
                $words[$i] = preg_split("/(\s+|\&nbsp;)/", $words[$i], -1, PREG_SPLIT_DELIM_CAPTURE); //then split it on the spaces

                // Now go through each word and link up the misspelled ones.
                $numWords = count($words[$i]);
                for($j=0; $j<$numWords; $j++)
                {
                    $word = $words[$i][$j];
                    $reg_expr = utf8_decode('A-ZáàâãäéèêëíìïîóòôõöúùûüýÿçñÁÀÂÃÄÉÈÊËÍÌÏÎÓÒÔÕÖÚÙÛÜÝÇÑ');

                    preg_match("/[$reg_expr]*/i", $word , $tmp); //get the word that is in the array slot $i

                    $tmpWord = $tmp[0]; //should only have one element in the array anyway, so it's just assign it to $tmpWord

                    //And we replace the word in the array with the span that highlights it and gives it an onClick parameter to show the suggestions.
                    if(!pspell_check($pspell_link, utf8_encode($tmpWord))) // Adicionar Nathalie
                    {
                            $suggestions = pspell_suggest($pspell_link, utf8_encode($tmpWord));
                            if (mb_check_encoding(implode($suggestions," "), "UTF-8")!="UTF-8") {
                                $suggestions = implode($suggestions, "\t");
                                $suggestions = utf8_encode($suggestions);
                                $suggestions = explode("\t",$suggestions);
                            }                            
                            $wlen = strlen($tmpWord);
                            $result = array(
                                'o' => $wpos,
                                'l' => $wlen,
                                's' => 0,
                                'suggestions' => $suggestions
                            );
                            array_push($results, $result);
                    }

                    $words[$i][$j] = str_replace("\n", "<br />", $words[$i][$j]); //replace any breaks with <br />'s, for html display

                    $wpos += strlen($word);

                }//end for $j
            }//end if

            else //otherwise, we wrap all the html tags in comments to make them not displayed
            {
                    $wpos += strlen($words[$i]);
                    $words[$i] = str_replace("<", "<!--<", $words[$i]);
                    $words[$i] = str_replace(">", ">-->", $words[$i]);
            }
        }//end for $i

        $words = $this->flattenArray($words); //flatten the array to be one dimensional.
        $numResults = count($words); //the number of elements in the array after it's been flattened.

        $data = ""; //return string

        //if there were no misspellings, start the string with a 0.
        if($misspelledCount == 0)
        {
                $data = "0";
        }

        else //else, there were misspellings, start the string with a 1.
        {
                $data = "1";
        }

        // Concatenate all the words/tags/etc. back into a string and append it to the result.
        $data .= implode('', $words);

        $data = preg_replace("/<!--</i", "<", $data);  //Retira os comentários das tags HTML
        $data = preg_replace("/>-->/i", ">", $data);

        $varret = array (
            'error'=> 0,
            'clipped'=> 0,
            'charschecked' => $text_length,
            'suggestedlang' => $lang,
            'results' => $results
        );

        return $varret;
    }
        
        
    /*************************************************************
    * stripslashes_custom($string)
    *
    * This is a custom stripslashes function that only strips
    * the slashes if magic quotes are on.  This is written for
    * compatibility with other servers in the event someone doesn't
    * have magic quotes on.
    *
    * $string - The string that might need the slashes stripped.
    *
    *************************************************************/
    private function stripslashes_custom($string){
        if(get_magic_quotes_gpc()){
            return stripslashes($string);
        }
        else {
            return $string;
        }
    }

    /*************************************************************
    * flattenArray($array)
    *
    * The flattenArray function is a recursive function that takes a
    * multidimensional array and flattens it to be a one-dimensional
    * array.  The one-dimensional flattened array is returned.
    *
    * $array - The array to be flattened.
    *
    *************************************************************/
    private function flattenArray($array)
    {
        $flatArray = array();
        foreach($array as $subElement){
            if(is_array($subElement))
                $flatArray = array_merge($flatArray, $this->flattenArray($subElement));
            else
                $flatArray[] = $subElement;
        }
        return $flatArray;
    } //end flattenArray function

    /*************************************************************
    * remove_word_junk($t)
    *
    * This function strips out all the crap that Word tries to
    * add to it's text in the even someone pastes in code from
    * Word.
    *
    * $t - The text to be cleaned
    *
    *************************************************************/
    private function remove_word_junk($t)
    {
        $a=array(
        "\xe2\x80\x9c"=>'"',
        "\xe2\x80\x9d"=>'"',
        "\xe2\x80\x99"=>"'",
        "\xe2\x80\xa6"=>"...",
        "\xe2\x80\x98"=>"'",
        "\xe2\x80\x94"=>"---",
        "\xe2\x80\x93"=>"--",
        "\x85"=>"...",
        "\221"=>"'",
        "\222"=>"'",
        "\223"=>'"',
        "\224"=>'"',
        "\x97"=>"---",
        "\x96"=>"--"
        );

        foreach($a as $k=>$v){
            $oa[]=$k;
            $ra[]=$v;
        }

        $t=trim(str_replace($oa,$ra,$t));
        return $t;

    } // end remove_word_junk
    
}
