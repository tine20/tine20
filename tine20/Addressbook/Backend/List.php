<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        move visibility='displayed' check from getSelect to contact filter
 */

/**
 * sql backend class for the addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Backend_List extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'addressbook_lists';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Addressbook_Model_List';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;

    /**
     * default column(s) for count
     *
     * @var string
     */
    protected $_defaultCountCol = 'id';

    /**
     * foreign tables 
     * name => array(table, joinOn, field)
     *
     * @var array
     */
    protected $_foreignTables = array(
        'members'    => array(
            'table'  => 'addressbook_list_members',
            'field'  => 'contact_id',
            'joinOn' => 'list_id',
            'preserve' => TRUE,
        ),
        'group_id'    => array(
            'table'        => 'groups',
            'field'        => 'id',
            'joinOn'       => 'list_id',
        // use first element of result array
            'singleValue'  => TRUE,
        )
    );

    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Abstract $_record
     * @return array
     */
    protected function _recordToRawData($_record)
    {
        $result = parent::_recordToRawData($_record);
        
        // stored in foreign key
        unset($result['members']);
        unset($result['group_id']);

        return $result;
    }
    
    /**
     * add new members to list
     * 
     * @param  mixed  $_listId
     * @param  mixed  $_newMembers
     * @return Addressbook_Model_List
     */
    public function addListMember($_listId, $_newMembers)
    {
        $list = $this->get($_listId);
        
        if (empty($_newMembers)) {
            return $list;
        }
        
        $newMembers = $this->_getIdsFromMixed($_newMembers);
        $idsToAdd   = array_diff($newMembers, $list->members);
        
        $listId     = Tinebase_Record_Abstract::convertId($_listId, $this->_modelName);
        
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        foreach ($idsToAdd as $id) {
            $recordArray = array (
                $this->_foreignTables['members']['joinOn'] => $listId,
                $this->_foreignTables['members']['field']  => $id
            );
            $this->_db->insert($this->_tablePrefix . $this->_foreignTables['members']['table'], $recordArray);
        }
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        return $this->get($_listId);
    }
    
    /**
     * Delete all lists returned by {@see getAll()} using {@see delete()}
     * @return void
     */
    public function deleteAllLists()
    {
        $lists = $this->getAll();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting ' . count($lists) .' lists');
        
        if(count($lists) > 0) {
            $this->delete($lists->getArrayOfIds());
        }
    }
    
    /**
     * remove members from list
     * 
     * @param  mixed  $_listId
     * @param  mixed  $_newMembers
     * @return Addressbook_Model_List
     */
    public function removeListMember($_listId, $_oldMembers)
    {
        $list = $this->get($_listId);
        
        if (empty($_oldMembers)) {
            return $list;
        }
        
        $oldMembers  = $this->_getIdsFromMixed($_oldMembers);
        $idsToRemove = array_intersect($list->members, $oldMembers);
        $listId      = Tinebase_Record_Abstract::convertId($_listId, $this->_modelName);
        
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        if (!empty($idsToRemove)) {
            $where = '(' . 
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_tablePrefix . $this->_foreignTables['members']['table'] . '.' . $this->_foreignTables['members']['joinOn']) . ' = ?', $listId) .
                ' AND ' .
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_tablePrefix . $this->_foreignTables['members']['table'] . '.' . $this->_foreignTables['members']['field']) . ' IN (?)', $idsToRemove) .
            ')';
                
            $this->_db->delete($this->_tablePrefix . $this->_foreignTables['members']['table'], $where);
        }
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        return $this->get($_listId);
    }
    
    /**
    * set all lists an user is member of
    *
    * @param  string  $contactId
    * @param  mixed  $listIds
    * @return array
    */
    public function setMemberships($contactId, $listIds)
    {
        $contactId = Tinebase_Record_Abstract::convertId($contactId, 'Addressbook_Model_Contact');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Set ' . count($listIds) . ' list memberships for contact ' . $contactId);
        
        if ($listIds instanceof Tinebase_Record_RecordSet) {
            $listIds = $listIds->getArrayOfIds();
        }
    
        $listMemberships = $this->getMemberships($contactId);
    
        $removeListMemberships = array_diff($listMemberships, $listIds);
        $addListMemberships    = array_diff($listIds, $listMemberships);
    
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' current memberships: ' . print_r($listMemberships, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' new memberships: ' . print_r($listIds, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' added memberships: ' . print_r($addListMemberships, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' removed memberships: ' . print_r($removeListMemberships, true));
    
        foreach ($addListMemberships as $listId) {
            $this->addListMember($listId, $contactId);
        }
    
        foreach ($removeListMemberships as $listId) {
            $this->removeListMember($listId, $contactId);
        }
    
        return $this->getMemberships($contactId);
    }
    
    /**
     * get group memberships of contact id
     * 
     * @param mixed $contactId
     * @return array
     */
    public function getMemberships($contactId)
    {
        $contactId = Tinebase_Record_Abstract::convertId($contactId, 'Addressbook_Model_Contact');
        
        $select = $this->_db->select()
            ->from($this->_tablePrefix . $this->_foreignTables['members']['table'], 'list_id')
            ->where($this->_db->quoteIdentifier('contact_id') . ' = ?', $contactId);
        
        $stmt = $this->_db->query($select);
        $rows = (array) $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $result = array();
        foreach ($rows as $membership) {
            $result[] = $membership['list_id'];
        }
        
        return $result;
    }
    
    /**
     * get list by group name
     * 
     * @param string $groupName
     * @return NULL|Addressbook_Model_List
     */
    public function getByGroupName($groupName)
    {
        $filter = new Addressbook_Model_ListFilter(array(
            array('field' => 'name', 'operator' => 'equals', 'value' => $groupName),
            array('field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_List::LISTTYPE_GROUP)
        ));
        
        $existingLists = $this->search($filter);
        
        return $existingLists->getFirstRecord();
    }
}
