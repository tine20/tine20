<?php


/**
 * Simple Read-Only Typo3 Group Backend
 * 
 * NOTE: At the moment we assume typo3 and tine20 share a common database
 * 
 * NOTE: We assume the Tine 2.0 Installation to have the default user and admin groups
 *       which are not part of the typo3 group system. Typo3 admins will be imported
 *       into the default admin group, others into the default user group.
 * 
 * This class does nothing more than importing Typo3 groups / groupmembers
 * into the Tine 2.0 group / groupmembers tables.
 */
class Tinebase_Group_Typo3 extends Tinebase_Group_Sql
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_t3db;
    
    /**
     * construct a typo3 user backend
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->_t3db = $this->_db;
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
        throw new Tinebase_Exception_AccessDenied();
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
        throw new Tinebase_Exception_AccessDenied();
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
        throw new Tinebase_Exception_AccessDenied();
    }
    
    /**
     * create a new group
     *
     * @param string $_groupName
     * @return unknown
     */
    public function addGroup(Tinebase_Model_Group $_group)
    {
        throw new Tinebase_Exception_AccessDenied();
    }
    
    /**
     * updates an existing group
     *
     * @param Tinebase_Model_Group $_account
     * @return Tinebase_Model_Group
     */
    public function updateGroup(Tinebase_Model_Group $_group)
    {
        throw new Tinebase_Exception_AccessDenied();
    }

    /**
     * remove groups
     *
     * @param mixed $_groupId
     * 
     */
    public function deleteGroups($_groupId)
    {
        throw new Tinebase_Exception_AccessDenied();
    }
    
    /**
     * import groups from typo3 
     * 
     * @return void
     */
    public function importGroups()
    {
        $select = $this->_t3db->select()
            ->from('be_groups');
            //->where('deleted = 0'))
            
        $groups = $select->query()->fetchAll(Zend_Db::FETCH_ASSOC);
        
        foreach($groups as $group) {
            $groupObject = new Tinebase_Model_Group(array(
                'id'            => $group['uid'],
                'name'          => $group['title'],
                'description'   => null
            ));

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' add group: ' . print_r($groupObject->toArray(), TRUE));
            try {
                parent::addGroup($groupObject);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .' Could not add group: ' . $groupObject->name . ' Error message: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * import groupmembers from typo3
     * 
     * NOTE: in typo3 the user object/dbrow knows the group memberships
     * 
     * @return void
     */
    public function importGroupMembers()
    {
        $select = $this->_t3db->select()
            ->from('be_users');
            
        $usersData = $select->query()->fetchAll(Zend_Db::FETCH_ASSOC);
        
        // build a groupMap
        $userGroup = Tinebase_Group::getInstance()->getDefaultGroup()->getId();
        $adminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId();
        $groupMap = array(
            $userGroup => array(),    //typo3 admin flag set
            $adminGroup => array(),   //typo3 admin flag not set
            //typo3GroupN             //typo3 defined groups
        );
        foreach($usersData as $t3user) {
            $userId = $t3user['uid'];
            
            // put user in default user OR admin group
            $groupMap[$t3user['admin'] == 1 ? $adminGroup : $userGroup][] = $userId;
            
            // evaluate typo3 groups
            if (empty($t3user['usergroup'])) continue;
            
            $t3userGroups = explode(',', $t3user['usergroup']);
                        
            foreach((array) $t3userGroups as $groupId) {
                if (! array_key_exists($groupId, $groupMap)) {
                    $groupMap[$groupId] = array();
                }
                
                $groupMap[$groupId][] = $userId;
            }
        }
        
        $sqlGroupBackend = new Tinebase_Group_Sql();
        
        foreach($groupMap as $groupId => $groupMembers) {
            try {
                $sqlGroupBackend->setGroupMembers($groupId, $groupMembers);
            } catch (Exception $e) {
                // ignore errors
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .' could not set groupmembers: ' . $e->getMessage());
            }
        }
    }
    
 }
 
