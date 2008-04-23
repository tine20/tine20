<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * abstract class for all group backends
 *
 * @package     Tinebase
 * @subpackage  Group
 */
 
abstract class Tinebase_Group_Abstract
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
            self::$_instance = new self;
        }
        
        return self::$_instance;
    }    

    /**
     * return all groups an account is member of
     *
     * @param mixed $_accountId the account as integer or Tinebase_Account_Model_Account
     * @return array
     */
    abstract public function getGroupMemberships($_accountId);
    
    /**
     * get list of groupmembers
     *
     * @param int $_groupId
     * @return array
     */
    abstract public function getGroupMembers($_groupId);
    
    /**
     * replace all current groupmembers with the new groupmembers list
     *
     * @param int $_groupId
     * @param array $_groupMembers
     * @return unknown
     */
    abstract public function setGroupMembers($_groupId, $_groupMembers);

    /**
     * add a new groupmember to the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return unknown
     */
    abstract public function addGroupMember($_groupId, $_accountId);

    /**
     * remove one groupmember from the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return unknown
     */
    abstract public function removeGroupMember($_groupId, $_accountId);
    
    /**
     * create a new group
     *
     * @param string $_groupName
     * @return unknown
     */
    abstract public function addGroup(Tinebase_Group_Model_Group $_group);
    
    /**
     * updates an existing group
     *
     * @param Tinebase_Group_Model_Group $_account
     * @return Tinebase_Group_Model_Group
     */
    abstract public function updateGroup(Tinebase_Group_Model_Group $_group);

    /**
     * remove groups
     *
     * @param mixed $_groupId
     * 
     */
    abstract public function deleteGroups($_groupId);
    
    /**
     * get group by id
     *
     * @param int $_groupId
     * @return Tinebase_Group_Model_Group
     */
    abstract public function getGroupById($_groupId);
    
    /**
     * get group by name
     *
     * @param string $_groupName
     * @return Tinebase_Group_Model_Group
     */
    abstract public function getGroupByName($_groupName);

    /**
     * get default group
     *
     * @return Tinebase_Group_Model_Group
     * 
     * @todo    add to unit tests
     */
    public function getDefaultGroup()
    {
        // get default group name from config.ini
        //@todo add extra section for group settings?
        try {
            $config = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini', 'registration');
        } catch (Zend_Config_Exception $e) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' no config for registration found! '. $e->getMessage());
        }
        $defaultGroupName = ( isset($config->accountPrimaryGroup) ) ? $config->accountPrimaryGroup : 'Users' ;
        
        $result = $this->_backend->getGroupByName($defaultGroupName);
        
        return $result;
    }
    
    /**
     * get list of groups
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Group_Model_Group
     */
    abstract public function getGroups($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL);
 }