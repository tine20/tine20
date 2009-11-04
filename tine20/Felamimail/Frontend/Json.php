<?php
/**
 * json frontend for Felamimail
 *
 * This class handles all Json requests for the Felamimail application
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @param string $filter
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
        $result = Felamimail_Controller_Folder::getInstance()->create($name, $parent, $accountId);
        
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
        $result = Felamimail_Controller_Folder::getInstance()->rename($newName, $oldGlobalName, $accountId);
        
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
        $result = Felamimail_Controller_Folder::getInstance()->delete($folder, $accountId);

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
        $result = Felamimail_Controller_Cache::getInstance()->clear($folderId);

        return array(
            'status'    => ($result) ? 'success' : 'failure'
        );
    }

    /**
     * remove all messages from folder
     *
     * @param string $folderId the folder id to delete
     * @return array
     */
    public function emptyFolder($folderId)
    {
        $result = Felamimail_Controller_Folder::getInstance()->emptyFolder($folderId);

        return array(
            'status'    => ($result) ? 'success' : 'failure'
        );
    }
    
    /**
     * update folder status
     *
     * @param string $accountId
     * @param string $folderId
     * @return array
     * 
     * @todo replace this with updateFolderCache?
     */
    public function updateFolderStatus($accountId, $folderId)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . $accountId . '/' . $folderId);
        
        $result = Felamimail_Controller_Folder::getInstance()->updateFolderStatus($accountId, NULL, $folderId);
        
        return $result->toArray();
    }
    
    /**
     * update folder cache
     *
     * @param string $accountId
     * @param string $folderNames of parent folder(s)
     * @return array
     * 
     * @todo update visible folders and return new folder data -> move this to another function?
     */
    public function updateFolderCache($accountId, $folderNames)
    {
        $decodedFolderNames = Zend_Json::decode($folderNames);
        foreach ((array)$decodedFolderNames as $folderName) {
            Felamimail_Controller_Cache::getInstance()->updateFolders($folderName, $accountId);
        }
        
        return array(
            'status' => 'success'
        );
    }
    
    /***************************** messages funcs *******************************/
    
    /**
     * search messages
     * - use output buffer mechanism to update incomplete cache
     *
     * @param string $filter
     * @param string $paging
     * @return array
     */
    public function searchMessages($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Felamimail_Controller_Message::getInstance(), 'Felamimail_Model_MessageFilter');
        
        return $result;
    }
    
    /**
     * update cache
     * - use output buffer mechanism to update incomplete cache
     *
     * @param string $folderId id of active folder
     * @return array
     */
    public function updateMessageCache($folderId)
    {
        $cacheController = Felamimail_Controller_Cache::getInstance();
        
        // update message cache of active folder and reload store (without loadmask)
        $folder = $cacheController->updateMessages($folderId);
        
        // return folder data
        $result = $folder->toArray();
        
        if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE
                || $folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING
        ) {
            $this->_backgroundCacheImport($result);
            // dies
        } else {
            return $result;
        }
    }
    
    /**
     * get message data
     *
     * @param string $id
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
     * @param string $ids  message ids
     * @return string
     * @return array
     */
    public function deleteMessages($ids)
    {
        return $this->_delete($ids, Felamimail_Controller_Message::getInstance());
    }

    /**
     * deletes existing messages by filter
     *
     * @param string $filter
     * @return array
     * 
     * @todo    do this in background process?
     */
    public function deleteMessagesByFilter($filter)
    {
        return $this->_deleteByFilter($filter, Felamimail_Controller_Message::getInstance(), 'Felamimail_Model_MessageFilter');
    }

    /**
     * move messsages to folder
     *
     * @param string $ids message ids
     * @param string $folderId
     * @return array
     */
    public function moveMessages($ids, $folderId)
    {
        $result = Felamimail_Controller_Message::getInstance()->moveMessages(Zend_Json::decode($ids), $folderId);
        
        return array(
            'status' => ($result) ? 'success' : 'failure'
        );
    }
    
    /**
     * save + send message
     * 
     * - this function has to be named 'saveMessage' because of the generic edit dialog function names
     *
     * @param  string $recordData
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
     * @param string $ids
     * @param string $flag
     * @return array
     */
    public function setFlag($ids, $flag)
    {
        $decodedFlag = Zend_Json::decode($flag);
        if (! empty($decodedFlag)) {
            foreach (Zend_Json::decode($ids) as $id) {
                $message = Felamimail_Controller_Message::getInstance()->get($id);
                Felamimail_Controller_Message::getInstance()->addFlags($message, (array) $decodedFlag);
            }
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' No flag set in request.');
        }
        
        return array('status' => 'success');
    }

    /**
     * clear flag of messages
     *
     * @param string $ids
     * @param string $flag
     * @return array
     */
    public function clearFlag($ids, $flag)
    {
        $encodedFlag = Zend_Json::decode($flag);
        foreach (Zend_Json::decode($ids) as $id) {
            $message = Felamimail_Controller_Message::getInstance()->get($id);
            Felamimail_Controller_Message::getInstance()->clearFlags($message, array($encodedFlag));
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
     * do initial import (as background process)
     * 
     * @param array $_result
     * @return unknown_type
     * 
     * @todo    generalize this
     */
    protected function _backgroundCacheImport(array $_result)
    {
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
        $response->setResult($_result);
        echo $response;
        
        $size = ob_get_length();
        header("Content-Length: $size");
        // need to set content type because the response should not be compressed by (apache) webserver
        // -> there has been an issue with mod_deflate / content-type text/html here
        header("Content-Type: application/json");
        ob_end_flush(); // Strange behaviour, will not work
        flush();        
        Zend_Session::writeClose(true);

        // update rest of cache here
        Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        Felamimail_Controller_Cache::getInstance()->initialImport($_result['id']);

        // don't output anything else ('null' or something like that)
        die();
    }
    
    /***************************** accounts funcs *******************************/
    
    /**
     * search accounts
     *
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
     * @param  string $recordData
     * @return array created/updated record
     */
    public function saveAccount($recordData)
    {
        return $this->_save($recordData, Felamimail_Controller_Account::getInstance(), 'Account');
    }
    
    /**
     * deletes existing accounts
     *
     * @param string $ids
     * @return string
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
            'accounts' => $this->searchAccounts(''),
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