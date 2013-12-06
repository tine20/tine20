<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Backend_Sql
 * 
 * @package     Tests
 */
abstract class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * transaction id if test is wrapped in an transaction
     * 
     * @var string
     */
    protected $_transactionId = null;
    
    /**
     * usernames to be deleted (in sync backend)
     * 
     * @var string
     */
    protected $_usernamesToDelete = array();
    
    /**
     * groups (ID) to be deleted (in sync backend)
     * 
     * @var array
     */
    protected $_groupIdsToDelete = array();
    
    /**
     * remove group members, too when deleting groups
     * 
     * @var boolean
     */
    protected $_removeGroupMembers = true;
    
    /**
     * test personas
     * 
     * @var array
     */
    protected $_personas = array();
    
    /**
     * set up tests
     */
    protected function setUp()
    {
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(false);
        
        $this->_personas = Zend_Registry::get('personas');
    }
    
    /**
     * tear down tests
     */
    protected function tearDown()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP) {
            $this->_deleteUsers();
            $this->_deleteGroups();
        }
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->rollBack();
        }
        
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(true);
    }
    
    /**
     * test needs transaction
     */
    protected function _testNeedsTransaction()
    {
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
            $this->_transactionId = null;
        }
    }
    
    /**
     * delete groups and their members
     * 
     * - also deletes groups and users in sync backends
     */
    protected function _deleteGroups()
    {
        foreach ($this->_groupIdsToDelete as $groupId) {
            if ($this->_removeGroupMembers) {
                foreach (Tinebase_Group::getInstance()->getGroupMembers($groupId) as $userId) {
                    try {
                        Tinebase_User::getInstance()->deleteUser($userId);
                    } catch (Exception $e) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' error while deleting user: ' . $e->getMessage());
                    }
                }
            }
            try {
                Tinebase_Group::getInstance()->deleteGroups($groupId);
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' error while deleting group: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * delete users
     */
    protected function _deleteUsers()
    {
        foreach ($this->_usernamesToDelete as $username) {
            try {
                Tinebase_User::getInstance()->deleteUser(Tinebase_User::getInstance()->getUserByLoginName($username));
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' error while deleting user: ' . $e->getMessage());
            }
        }
    }
}
