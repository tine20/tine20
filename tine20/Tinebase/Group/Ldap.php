<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Group ldap backend
 * 
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group_Ldap implements Tinebase_Group_Interface
{
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     * don't use the constructor. use the singleton 
     */
    private function __construct(array $_options) {
        $this->_backend = new Tinebase_Ldap($_options);
        $this->_backend->bind();
    }
        
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Group_Ldap
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Group_Ldap
     */
    public static function getInstance(array $_options = array())
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Group_Ldap($_options);
        }
        
        return self::$instance;
    }
    
    /**
     * return all groups an account is member of
     *
     * @param mixed $_accountId the account as integer or Tinebase_Account_Model_Account
     * @return array
     */
    public function getGroupMemberships($_accountId)
    {
        if($_accountId instanceof Tinebase_Account_Model_FullAccount) {
            $memberuid = $_accountId->accountLoginName;
        } else {
            $account = Tinebase_Account::getInstance()->getFullAccountById($_accountId);
            $memberuid = $account->accountLoginName;
        }
        
        $filter = "(&(objectclass=posixgroup)(memberuid=$memberuid))";
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' search filter: ' . $filter);
        
        $groups = $this->_backend->fetchAll(Zend_Registry::get('configFile')->accounts->get('ldap')->groupsDn, $filter, array('gidnumber'));
        
        $memberships = array();
        
        foreach($groups as $group) {
            $memberships[] = $group['gidnumber'][0];
        }

        return $memberships;
    }
    
    /**
     * get list of groupmembers 
     *
     * @param int $_groupId
     * @return array with account ids
     */
    public function getGroupMembers($_groupId)
    {
        $groupId = Tinebase_Group_Model_Group::convertGroupIdToInt($_groupId);
        
        $members = array();
        
        $select = $this->groupMembersTable->select();
        $select->where('group_id = ?', $groupId);
        
        $rows = $this->groupMembersTable->fetchAll($select);
        
        foreach($rows as $member) {
            $members[] = $member->account_id;
        }

        return $members;
    }

    /**
     * get group by name
     *
     * @param string $_name
     * @return Tinebase_Group_Model_Group
     */
    public function _getGroupByName($_name)
    {        
        $select = $this->groupsTable->select();
        
        $select->where('name = ?', $_name);
        
        $row = $this->groupsTable->fetchRow($select);

        if($row === NULL) {
            throw new Tinebase_Record_Exception_NotDefined('group not found');
        }
        
        $result = new Tinebase_Group_Model_Group($row->toArray());
        
        return $result;
    }
    
    /**
     * get group by id
     *
     * @param string $_name
     * @return Tinebase_Group_Model_Group
     */
    public function getGroupById($_groupId)
    {   
        $groupId = Tinebase_Group_Model_Group::convertGroupIdToInt($_groupId);     
        
        try {
            $group = $this->_backend->fetch(Zend_Registry::get('configFile')->accounts->get('ldap')->groupsDn, 'gidnumber=' . $groupId, array('cn','description','gidnumber'));
        } catch (Exception $e) {
            throw new Tinebase_Record_Exception_NotDefined('group not found');
        }

        $result = new Tinebase_Group_Model_Group(array(
            'id'            => $group['gidnumber'][0],
            'name'          => $group['cn'][0],
            'description'   => isset($group['description'][0]) ? $group['description'][0] : '' 
        ));
        
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
    public function getGroups($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {        
        if(!empty($_filter)) {
            $searchString = "*" . Tinebase_Ldap::filterEscape($_filter) . "*";
            $filter = "(&(objectclass=posixgroup)(|(cn=$searchString)))";
        } else {
            $filter = 'objectclass=posixgroup';
        }
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' search filter: ' . $filter);
        
        $groups = $this->_backend->fetchAll(Zend_Registry::get('configFile')->accounts->get('ldap')->groupsDn, $filter, array('cn','description','gidnumber'));
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Group_Model_Group');
        
        foreach($groups as $group) {
            $groupObject = new Tinebase_Group_Model_Group(array(
                'id'            => $group['gidnumber'][0],
                'name'          => $group['cn'][0],
                'description'   => isset($group['description'][0]) ? $group['description'][0] : '' 
            ));
            
            $result->addRecord($groupObject);
        }
        
        return $result;
        
        
        $select = $this->groupsTable->select();
        
        if($_filter !== NULL) {
            $select->where('`name` LIKE ?', '%' . $_filter . '%');
        }
        if($_sort !== NULL) {
            $select->order("$_sort $_dir");
        }
        if($_start !== NULL) {
            $select->limit($_limit, $_start);
        }
        
        $rows = $this->groupsTable->fetchAll($select);

        $result = new Tinebase_Record_RecordSet('Tinebase_Group_Model_Group', $rows->toArray());
        
        return $result;
    }
}