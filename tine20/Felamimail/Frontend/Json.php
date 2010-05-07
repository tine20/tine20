<?php
/**
 * json frontend for Felamimail
 *
 * This class handles all Json requests for the Felamimail application
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Felamimail_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * application name
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /***************************** folder funcs *******************************/
    
    /**
     * search folders and update/initialize cache of subfolders 
     *
     * @param  array $filter
     * @return array
     */
    public function searchFolders($filter)
    {
        $result = $this->_search($filter, '', Felamimail_Controller_Folder::getInstance(), 'Felamimail_Model_FolderFilter');
        
        return $result;
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
        $result = Felamimail_Controller_Folder::getInstance()->create($accountId, $name, $parent);
        
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
        $result = Felamimail_Controller_Folder::getInstance()->rename($accountId, $newName, $oldGlobalName);
        
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
        $result = Felamimail_Controller_Folder::getInstance()->delete($accountId, $folder);

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
        $result = Felamimail_Controller_Cache_Message::getInstance()->clear($folderId);

        return array(
            'status'    => ($result) ? 'success' : 'failure'
        );
    }

    /**
     * remove all messages from folder
     *
     * @param  string $folderId the folder id to delete
     * @return array with folder status
     */
    public function emptyFolder($folderId)
    {
        $result = Felamimail_Controller_Folder::getInstance()->emptyFolder($folderId);
        return $this->_recordToJson($result);
    }
    
    /**
     * update folder status (unreadcount/totalcount/cache status?)
     *
     * @param string $accountId
     * @param array  $folderIds
     * @return array
     */
    public function updateFolderStatus($accountId, $folderIds)
    {
        // close session to allow other requests
        Zend_Session::writeClose(true);
        
        $folderIds = (empty($folderIds)) ? NULL : $folderIds;
        $folders = Felamimail_Controller_Cache_Folder::getInstance()->updateStatus($accountId, $folderIds);
        
        return array(
            'results' => $this->_multipleRecordsToJson($folders)
        );
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
        $result = Felamimail_Controller_Cache_Folder::getInstance()->update($accountId, $folderName);
        return $this->_multipleRecordsToJson($result);
    }
    
    /***************************** messages funcs *******************************/
    
    /**
     * search messages
     * - use output buffer mechanism to update incomplete cache
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchMessages($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Felamimail_Controller_Message::getInstance(), 'Felamimail_Model_MessageFilter');
        
        return $result;
    }
    
    /**
     * update cache
     * - use session/writeClose to update incomplete cache and allow following requests
     *
     * @param string $folderId id of active folder
     * @param integer $time update time in seconds
     * @return array
     */
    public function updateMessageCache($folderId, $time)
    {
        // close session to allow other requests
        Zend_Session::writeClose(true);
        
        $folder = Felamimail_Controller_Cache_Message::getInstance()->update($folderId, $time);
        
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
        $controller = Felamimail_Controller_Message::getInstance();
        $message = $controller->getCompleteMessage($id, TRUE, TRUE);
        
        return $this->_recordToJson($message);
    }
    
    /**
     * deletes existing messages
     *
     * @param  array $ids  message ids
     * @return string
     * @return array
     * 
     * @todo only add flag to messages that should be deleted and delete them on server when updating cache?
     */
    public function deleteMessages($ids)
    {
        $deletedRecords = Felamimail_Controller_Message::getInstance()->delete($ids);
        $this->_backgroundDelete($deletedRecords);
    }

    /**
     * deletes existing messages by filter
     *
     * @param  array $filter
     * @return array
     */
    public function deleteMessagesByFilter($filter)
    {
        $filter = new Felamimail_Model_MessageFilter($filter);
        $deletedRecords = Felamimail_Controller_Message::getInstance()->deleteByFilter($filter);
        $this->_backgroundDelete($deletedRecords);
    }

    /**
     * move messsages to folder
     *
     * @param  array $ids message ids
     * @param  string $targetFolderId
     * @return array source folder status
     */
    public function moveMessages($ids, $targetFolderId)
    {
        $sourceFolder = Felamimail_Controller_Message::getInstance()->moveMessages($ids, $targetFolderId);
        return $this->_recordToJson($sourceFolder);
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
        $message = new Felamimail_Model_Message();
        $message->setFromJsonInUsersTimezone($recordData);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r(Zend_Json::decode($recordData), TRUE));
        
        try {
            $result = Felamimail_Controller_Message::getInstance()->sendMessage($message);
            $result = $this->_recordToJson($result);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message: ' . $zmpe->getMessage());
            throw $zmpe;
        }
        
        return $result;
    }

    /**
     * set flag of messages
     *
     * @param  array $ids
     * @param  array $flag
     * @return array
     */
    public function setFlag($ids, $flag)
    {
        if (! empty($flag)) {
            foreach ($ids as $id) {
                $message = Felamimail_Controller_Message::getInstance()->get($id);
                Felamimail_Controller_Message::getInstance()->addFlags($message, (array) $flag);
            }
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' No flag set in request.');
        }
        
        return array('status' => 'success');
    }

    /**
     * clear flag of messages
     *
     * @param array  $ids
     * @param string $flag
     * @return array
     */
    public function clearFlag($ids, $flag)
    {
        foreach ($ids as $id) {
            $message = Felamimail_Controller_Message::getInstance()->get($id);
            Felamimail_Controller_Message::getInstance()->clearFlags($message, array($flag));
        }
        
        return array('status' => 'success');
    }
    
    /**
     * returns task prepared for json transport
     * - overwriten to convert recipients to array
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        if ($_record instanceof Felamimail_Model_Message) {
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
        } else if ($_record instanceof Felamimail_Model_Account) {
            // add usernames (imap + smtp)
            $_record->resolveCredentials();
            $_record->resolveCredentials(TRUE, FALSE, TRUE);
        }
        
        return parent::_recordToJson($_record);
    }
    
    /**
     * delete messages (as background process)
     * 
     * @param array $_result
     * @param Tinebase_Record_RecordSet $_messagesToDelete
     * @return void
     * 
     * @todo    generalize this?
     */
    protected function _backgroundDelete(Tinebase_Record_RecordSet $_messagesToDelete)
    {
        Tinebase_Core::setExecutionLifeTime(600); // 10 minutes
        $result = array(
            'status'    => 'success'
        );
        
        if (headers_sent()) {
            // don't do background processing if headers were already sent
            Felamimail_Controller_Message::getInstance()->deleteMessagesFromImapServer($_messagesToDelete);
            return $result;
        } else {
        
            // use output buffer
            ignore_user_abort();
            header("Connection: close");
            
            ob_start();
    
            // output here (kind of hack to get request id and build response)
            $request = new Zend_Json_Server_Request_Http();
            $response = new Zend_Json_Server_Response_Http();
            if (null !== ($id = $request->getId())) {
                $response->setId($id);
            }
            if (null !== ($version = $request->getVersion())) {
                $response->setVersion($version);
            }
            $response->setResult($result);
            echo $response;
            
            $size = ob_get_length();
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Content-Size: ' . $size);
            
            /**
             * we set a special content-type to avoid compressing the output by mod_deflate
             * the browser is closing the connection after he has received  Content-Length bytes
             * if the output get's compressed, the browser waits until the php process finnishes 
             */
            header("Content-Length: $size");
            header("Content-Type: application/json-nodeflate");

            ob_end_flush(); // Strange behaviour, will not work
            flush();
            Zend_Session::writeClose(true);
    
            // update rest of cache here
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Starting background delete of ' . count($_messagesToDelete) . ' messages ...');
            Felamimail_Controller_Message::getInstance()->deleteMessagesFromImapServer($_messagesToDelete);
    
            // don't output anything else ('null' or something like that)
            die();
        }
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
        return $results = $this->_search($filter, '', Felamimail_Controller_Account::getInstance(), 'Felamimail_Model_AccountFilter');
    }
    
    /**
     * get account data
     *
     * @param string $id
     * @return array
     */
    public function getAccount($id)
    {
        return $this->_get($id, Felamimail_Controller_Account::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveAccount($recordData)
    {
        return $this->_save($recordData, Felamimail_Controller_Account::getInstance(), 'Account');
    }
    
    /**
     * deletes existing accounts
     *
     * @param  array $ids
     * @return array
     */
    public function deleteAccounts($ids)
    {
        return array('status' => $this->_delete($ids, Felamimail_Controller_Account::getInstance()));
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
        $result = Felamimail_Controller_Account::getInstance()->changeCredentials($id, $username, $password);
        
        return array('status' => ($result) ? 'success' : 'failure');
    }
    
    /***************************** other funcs *******************************/
    
	/**
     * Returns registry data of felamimail.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     * 
     * @todo get default account data (host, port, ...) from preferences?
     */
    public function getRegistryData()
    {
        $result = array(
            'accounts'              => $this->searchAccounts('')
        );
        
        $defaults = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        $defaults['smtp'] = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::SMTP);
        
        // remove sensitive data
        unset($defaults['user']);
        unset($defaults['password']);
        unset($defaults['smtp']['username']);
        unset($defaults['smtp']['password']);
        
        $result['defaults'] = $defaults;
        
        return $result; 
    }
}