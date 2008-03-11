<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * primary class to handle groups
 *
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Group
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Group
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Group;
        }
        
        return self::$_instance;
    }    

    /**
     * return all groups an account is member of
     *
     * @param mixed $_accountId the account as integer or Tinebase_Account_Model_Account
     * @return array
     */
    public function getGroupMemberships($_accountId)
    {
        if($_account instanceof Tinebase_Account_Model_Account) {
            $accountId = $_account->accountId;
        } else {
            $accountId = (int) $_accountId;
        }
        $result = $this->_backend->getGroupMemberships($accountId);
        
        return $result;
    }
    
    /**
     * get list of groupmembers
     *
     * @param int $_groupId
     * @return array
     */
    public function getGroupMembers($_groupId)
    {
        $result = $this->_backend->getGroupMembers($_groupId);
        
        return $result;
    }
    
    /**
     * replace all current groupmembers with the new groupmembers list
     *
     * @param int $_groupId
     * @param array $_groupMembers
     * @return unknown
     */
    public function setGroupMembers($_groupId, $_groupMembers)
    {
        $result = $this->_backend->setGroupMembers($_groupId, $_groupMembers);
        
        return $result;
    }

    /**
     * add a new groupmember to the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return unknown
     */
    public function addGroupMember($_groupId, $_accountId)
    {
        $result = $this->_backend->addGroupMember($_groupId, $_accountId);
        
        return $result;
    }

    /**
     * remove one groupmember from the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return unknown
     */
    public function removeGroupMember($_groupId, $_accountId)
    {
        $result = $this->_backend->removeGroupMember($_groupId, $_accountId);
        
        return $result;
    }
    
    /**
     * create a new group
     *
     * @param string $_groupName
     * @return unknown
     */
    public function addGroup($_groupName)
    {
        $result = $this->_backend->addGroup($_groupName);
        
        return $result;
    }
    
    /**
     * remove group
     *
     * @param int $_groupId
     * @return unknown
     */
    public function deleteGroup($_groupId)
    {
        $result = $this->_backend->deleteGroup($_groupId);
        
        return $result;
    }
    
}