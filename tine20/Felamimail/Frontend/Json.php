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
     * search folders
     *
     * @param string $filter
     * @return array
     */
    public function searchFolders($filter)
    {
        return $results = $this->_search($filter, '', Felamimail_Controller_Folder::getInstance(), 'Felamimail_Model_FolderFilter');
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
        
        // no paging -> don't do initial cache import
        if (empty($paging) || $result['totalcount'] == 0) {
            return $result;
        }
        
        // use output buffer
        ignore_user_abort();
        header("Connection: close");
        
        ob_start();

        // output here
        //$result = $this->_search($filter, $paging, Felamimail_Controller_Message::getInstance(), 'Felamimail_Model_MessageFilter'); 
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . Zend_Json::encode($result));
        echo Zend_Json::encode($result);
        
        $size = ob_get_length();
        header("Content-Length: $size");
        ob_end_flush(); // Strange behaviour, will not work
        flush();        
        Zend_Session::writeClose(true);

        // update rest of cache here
        if ($result['totalcount'] > 0) {
            // get folder id from filter
            $folderId = '';
            foreach ($result['filter'] as $filterSetting) {
                if ($filterSetting['field'] == 'folder_id') {
                    $folderId = $filterSetting['value'];
                    break;
                }
            }
            Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
            Felamimail_Controller_Cache::getInstance()->initialImport($folderId);
        }
        
        // don't output anything else ('null' or something like that)
        die();
    }
    
    /**
     * get message data
     *
     * @param string $id
     * @return array
     */
    public function getMessage($id)
    {
        return $this->_get($id, Felamimail_Controller_Message::getInstance());
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
        return array('status' => $this->_delete($ids, Felamimail_Controller_Message::getInstance()));
    }

    /**
     * move messsages to folder
     *
     * @param string $ids message ids
     * @param string $folderId
     * @return array
     * 
     * @todo add test
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
        $encodedFlag = Zend_Json::decode($flag);
        foreach (Zend_Json::decode($ids) as $id) {
            $message = Felamimail_Controller_Message::getInstance()->get($id);
            Felamimail_Controller_Message::getInstance()->addFlags($message, array($encodedFlag));
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
            // add username
            $_record->resolveCredentials();
        }
        
        return parent::_recordToJson($_record);
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
        
        if (isset(Tinebase_Core::getConfig()->imap)) {
            $defaults = Tinebase_Core::getConfig()->imap->toArray();
            
            // remove sensitive data
            unset($defaults['user']);
            unset($defaults['password']);
            unset($defaults['smtp']['username']);
            unset($defaults['smtp']['password']);
            
            $result['defaults'] = $defaults;
        }
        
        return $result; 
    }
}