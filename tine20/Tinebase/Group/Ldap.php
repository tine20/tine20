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
class Tinebase_Group_Ldap extends Tinebase_Group_Abstract
{
    /**
     * the ldap backend
     *
     * @var Tinebase_Ldap
     */
    protected $_ldap;
    
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     * don't use the constructor. use the singleton 
     */
    private function __construct(array $_options) {
        $this->_ldap = new Tinebase_Ldap($_options);
        $this->_ldap->bind();
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
     * @param mixed $_accountId the account as integer or Tinebase_Model_User
     * @return array
     */
    public function getGroupMemberships($_accountId)
    {
        if($_accountId instanceof Tinebase_Model_FullUser) {
            $memberuid = $_accountId->accountLoginName;
        } else {
            $account = Tinebase_User::getInstance()->getFullUserById($_accountId);
            $memberuid = $account->accountLoginName;
        }
        
        $filter = "(&(objectclass=posixgroup)(memberuid=$memberuid))";
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' search filter: ' . $filter);
        
        $groups = $this->_ldap->fetchAll(Zend_Registry::get('configFile')->accounts->get('ldap')->groupsDn, $filter, array('gidnumber'));
        
        $memberships = array();
        
        foreach($groups as $group) {
            $memberships[] = $group['gidnumber'][0];
        }

        return $memberships;
    }
    
    /**
     * get list of groupmembers 
     *
     * @param   int $_groupId
     * @return  array with account ids
     * @throws  Tinebase_Exception_Record_NotDefined
     */
    public function getGroupMembers($_groupId)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);     
        
        try {
            $groupMembers = $this->_ldap->fetch(Zend_Registry::get('configFile')->accounts->get('ldap')->groupsDn, 'gidnumber=' . $groupId, array('member', 'memberuid'));
        } catch (Exception $e) {
            throw new Tinebase_Exception_Record_NotDefined('Group not found.');
        }
        
        $members = array();

        if(isset($groupMembers['member'])) {
            unset($groupMembers['member']['count']);
            foreach($groupMembers['member'] as $dn) {
                try {
                    $accountData = $this->_ldap->fetchDn($dn, 'objectclass=*', array('uidnumber'));
                    $members[] = $accountData['uidnumber'][0];
                } catch (Exception $e) {
                    // ignore ldap errors
                }
            }
        } else {
            unset($groupMembers['memberuid']['count']);
            foreach($groupMembers['memberuid'] as $loginName) {
                error_log('LARS:: ' . $loginName);
                $account = Tinebase_User::getInstance()->getUserByLoginName($loginName);
                $members[] = $account->getId();
            }
        }
        
        return $members;        
    }

    /**
     * get group by name
     *
     * @param   string $_name
     * @return  Tinebase_Model_Group
     * @throws  Tinebase_Exception_Record_NotDefined
     */
    public function getGroupByName($_name)
    {        
        $groupName = Zend_Ldap::filterEscape($_name);
        
        try {
            $group = $this->_ldap->fetch(Zend_Registry::get('configFile')->accounts->get('ldap')->groupsDn, 'cn=' . $groupName, array('cn','description','gidnumber'));
        } catch (Exception $e) {
            throw new Tinebase_Exception_Record_NotDefined('Group not found.');
        }

        $result = new Tinebase_Model_Group(array(
            'id'            => $group['gidnumber'][0],
            'name'          => $group['cn'][0],
            'description'   => isset($group['description'][0]) ? $group['description'][0] : '' 
        ));
        
        return $result;
    }
    
    /**
     * get group by id
     *
     * @param string $_name
     * @return Tinebase_Model_Group
     * @throws  Tinebase_Exception_Record_NotDefined
     */
    public function getGroupById($_groupId)
    {   
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);     
        
        try {
            $group = $this->_ldap->fetch(Zend_Registry::get('configFile')->accounts->get('ldap')->groupsDn, 'gidnumber=' . $groupId, array('cn','description','gidnumber'));
        } catch (Exception $e) {
            throw new Tinebase_Exception_Record_NotDefined('Group not found.');
        }

        $result = new Tinebase_Model_Group(array(
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
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_Group
     */
    public function getGroups($_filter = NULL, $_sort = 'name', $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {        
        if(!empty($_filter)) {
            $searchString = "*" . Tinebase_Ldap::filterEscape($_filter) . "*";
            $filter = "(&(objectclass=posixgroup)(|(cn=$searchString)))";
        } else {
            $filter = 'objectclass=posixgroup';
        }
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' search filter: ' . $filter);
        
        $groups = $this->_ldap->fetchAll(Zend_Registry::get('configFile')->accounts->get('ldap')->groupsDn, $filter, array('cn','description','gidnumber'), 'cn');
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Group');
        
        foreach($groups as $group) {
            $groupObject = new Tinebase_Model_Group(array(
                'id'            => $group['gidnumber'][0],
                'name'          => $group['cn'][0],
                'description'   => isset($group['description'][0]) ? $group['description'][0] : '' 
            ));
            
            $result->addRecord($groupObject);
        }
        
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
        throw new Tinebase_Exception_NotImplemented('not yet implemented');
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
        throw new Tinebase_Exception_NotImplemented('not yet implemented');
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
        throw new Tinebase_Exception_NotImplemented('not yet implemented');
    }
    
    /**
     * create a new group
     *
     * @param string $_groupName
     * @return unknown
     */
    public function addGroup(Tinebase_Model_Group $_group) 
    {
        throw new Tinebase_Exception_NotImplemented('not yet implemented');
    }
    
    /**
     * updates an existing group
     *
     * @param Tinebase_Model_Group $_account
     * @return Tinebase_Model_Group
     */
    public function updateGroup(Tinebase_Model_Group $_group) 
    {
        throw new Tinebase_Exception_NotImplemented('not yet implemented');
    }

    /**
     * remove groups
     *
     * @param mixed $_groupId
     * 
     */
    public function deleteGroups($_groupId) 
    {
        throw new Tinebase_Exception_NotImplemented('not yet implemented');
    }
}