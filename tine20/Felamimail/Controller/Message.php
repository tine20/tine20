<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * message controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message extends Tinebase_Controller_Record_Abstract //Felamimail_Controller_Abstract implements Tinebase_Controller_SearchInterface
{
    /**
     * holdes the instance of the singleton
     *
     * @var Felamimail_Controller_Message
     */
    private static $_instance = NULL;
    
    /**
     * cache controller
     *
     * @var Felamimail_Controller_Cache
     */
    protected $_cacheController = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_modelName = 'Felamimail_Model_Message';
        $this->_doContainerACLChecks = FALSE;
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();
        
        $this->_currentAccount = Tinebase_Core::getUser();
        
        $this->_cacheController = Felamimail_Controller_Cache::getInstance();
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
     * @return Felamimail_Controller_Message
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {            
            self::$_instance = new Felamimail_Controller_Message();
        }
        
        return self::$_instance;
    }
    
    /************************* functions required by Tinebase_Controller_SearchInterface *************************/
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @return Tinebase_Record_RecordSet
     * 
     * @todo add support for multiple folders
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        // get folder_id from filter (has to be set)
        $filterValues = $this->_extractFilter($_filter);
        $folderId = $filterValues['folder_id'];
        
        if (empty($folderId) || $folderId == '/') {
            $result = new Tinebase_Record_RecordSet('Felamimail_Model_Message');
        } else {
            // update cache?
            $this->_cacheController->update($folderId);
        
            $result = parent::search($_filter, $_pagination);
        }
        
        return $result;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        // get folder_id from filter (has to be set)
        $filterValues = $this->_extractFilter($_filter);
        
        if (empty($filterValues['folder_id'])) {
            $result = 0;
        } else {
            $result = parent::searchCount($_filter);
            /*
            $this->_getBackend($filterValues['backendId'])->selectFolder($filterValues['folder']);
            $result = $this->_getBackend($filterVales['backendId'])->countMessages();
            */
        }
            
        return $result;
        //$this->_lastSearchCount[$this->_currentAccount->getId()][$filterValues['backendId']];    
    }
    
    /************************* other public funcs *************************/
    
    // @todo check if those are needed
    
    /**
     * send one message through smtp
     *
     * @todo use userspecific settings
     */
    public function sendMessage(Zend_Mail $_mail)
    {
        $config = array(
            'ssl' => 'tls',
            'port' => 25
        );
        $transport = new Zend_Mail_Transport_Smtp('localhost', $config);
        
        Tinebase_Smtp::getInstance()->sendMessage($_mail, $transport);
        
        $this->_getBackend()->appendMessage($_mail, 'Sent');
    }
    
    /**
     * fetch message from folder
     *
     * @param string $_globalName the complete folder name
     * @param string $_messageId the message id
     * @return Zend_Mail_Message
     */
    public function getMessage($_globalName, $_messageId)
    {        
        if($this->_getBackend()->getCurrentFolder() != $_globalName) {
            $this->_getBackend()->selectFolder($_globalName);
        }
        
        $message = $this->_getBackend()->getMessage($_messageId);
        
        return $message;
    }
    
    /**
     * fetch message from folder
     *
     * @param string $_globalName the complete folder name
     * @param string $_messageId the message id
     * @return void
     */
    public function deleteMessage($_serverId, $_globalName, $_messageId)
    {        
        if($this->_getBackend()->getCurrentFolder() != $_globalName) {
            $this->_getBackend()->selectFolder($_globalName);
        }
        
        $message = $this->_getBackend()->removeMessage($_messageId);
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $_globalName
     * @param unknown_type $_messageId
     * @param unknown_type $from
     * @param unknown_type $to
     * @return array
     */
    public function getUid($_globalName, $from, $to = null)
    {
        if($this->_getBackend()->getCurrentFolder() != $_globalName) {
            $this->_getBackend()->selectFolder($_globalName);
        }
        
        $foundEntries = $this->_getBackend()->getUid($from, $to);
        
        return $foundEntries;
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $_serverId
     * @param unknown_type $_globalName
     * @param unknown_type $_id
     * @param unknown_type $_flags
     */
    public function addFlags($_serverId, $_globalName, $_id, $_flags)
    {
        if($this->_getBackend()->getCurrentFolder() != $_globalName) {
            $this->_getBackend()->selectFolder($_globalName);
        }
        
        $this->_getBackend()->addFlags($_id, $_flags);
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $_serverId
     * @param unknown_type $_globalName
     * @param unknown_type $_id
     * @param unknown_type $_flags
     */
    public function clearFlags($_serverId, $_globalName, $_id, $_flags)
    {
        if($this->_getBackend()->getCurrentFolder() != $_globalName) {
            $this->_getBackend()->selectFolder($_globalName);
        }
        
        $this->_getBackend()->clearFlags($_id, $_flags);
    }

    /************************* protected funcs *************************/
    
    /**
     * extract values from folder filter
     *
     * @param Felamimail_Model_FolderFilter $_filter
     * @return array (assoc) with filter values
     */
    protected function _extractFilter(Felamimail_Model_MessageFilter $_filter)
    {
        //$result = array('backendId' => 'default', 'folder' => '');
        $result = array('folder_id' => '');
        
        $filters = $_filter->getFilterObjects();
        foreach($filters as $filter) {
            if (in_array($filter->getField(), array_keys($result))) {
                $result[$filter->getField()] = $filter->getValue();
            }
        }
        
        return $result;
    }
}
