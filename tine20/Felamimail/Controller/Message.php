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
 * 
 * @todo        make  Messages normal tine records?
 * @todo        add support for caching backend(s)
 */

/**
 * message controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message extends Felamimail_Controller_Abstract /* implements Tinebase_Controller_SearchInterface */
{
    /**
     * holdes the instance of the singleton
     *
     * @var Felamimail_Controller_Message
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_currentAccount = Tinebase_Core::getUser();
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
     * @return array of Felamimail_Model_Message
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        // get backendId and globalName from filter
        $filterValues = $this->_extractFilter($_filter);
        
        if (empty($filterValues['folder'])) {
            $result = array();
        } else {
            $this->_getBackend($filterValues['backendId'])->selectFolder($filterValues['folder']);
            $result = $this->_getBackend($filterValues['backendId'])->getMessages();
        }
        
        //$seenMessages = $imapConnection->getSummary(array_slice($seen, $_start, $_limit));
        
        return $result;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     * 
     * @todo activate pagination
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        $filterValues = $this->_extractFilter($_filter);
        return $this->_getBackend($filterVales['backendId'])->countMessages();
        //$this->_lastSearchCount[$this->_currentAccount->getId()][$filterValues['backendId']];    
    }
    
    /************************* other public funcs *************************/
    
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
        $result = array('backendId' => 'default', 'folder' => '');
        
        $filters = $_filter->getFilterObjects();
        foreach($filters as $filter) {
            switch($filter->getField()) {
                case 'backendId':
                    $result['backendId'] = $filter->getValue();
                    break;
                case 'folder':
                    $result['folder'] = $filter->getValue();
                    break;
            }
        }
        
        return $result;
    }
}
