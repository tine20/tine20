<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * the constructor
     * 
     * allowed options:
     *  - modelName
     *  - tableName
     *  - tablePrefix
     *  - modlogActive
     *  
     * @param Zend_Db_Adapter_Abstract $_dbAdapter (optional)
     * @param array $_options (optional)
     * @throws Tinebase_Exception_Backend_Database
     */
    public function __construct($_dbAdapter = NULL, $_options = array())
    {
        parent::__construct($_dbAdapter, $_options);

        /**
         * TODO move this code somewhere and make it optionally. Maybe even make it a new controller / frontend action and request the data async
         */
        if (Tinebase_Application::getInstance()->isInstalled('Addressbook')
            && Addressbook_Config::getInstance()->featureEnabled(Addressbook_Config::FEATURE_LIST_VIEW))
        {
            $this->_additionalColumns['emails'] = new Zend_Db_Expr('(' .
                $this->_db->select()
                    ->from($this->_tablePrefix . 'addressbook', array($this->_dbCommand->getAggregate('email')))
                    ->where($this->_db->quoteIdentifier('id') . ' IN ?', $this->_db->select()
                        ->from(array('addressbook_list_members' => $this->_tablePrefix . 'addressbook_list_members'), array('contact_id'))
                        ->where($this->_db->quoteIdentifier('addressbook_list_members.list_id') . ' = ' . $this->_db->quoteIdentifier('addressbook_lists.id'))
                    ) .
                ')');
        }
    }

    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Interface $_record
     * @return array
     */
    protected function _recordToRawData(Tinebase_Record_Interface $_record)
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
        /** @var Addressbook_Model_List $list */
        $list = $this->get($_listId);
        
        if (empty($_newMembers)) {
            return $list;
        }
        
        $newMembers = Tinebase_Record_RecordSet::getIdsFromMixed($_newMembers);
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
     * @param  mixed  $_membersToRemove
     * @return Addressbook_Model_List
     */
    public function removeListMember($_listId, $_membersToRemove)
    {
        /** @var Addressbook_Model_List $list */
        $list = $this->get($_listId);
        
        if (empty($_membersToRemove)) {
            return $list;
        }
        
        $removeMembers  = Tinebase_Record_RecordSet::getIdsFromMixed($_membersToRemove);
        $idsToRemove = array_intersect($list->members, $removeMembers);
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
     * @param string $containerId
     * @return NULL|Addressbook_Model_List
     */
    public function getByGroupName($groupName, $containerId)
    {
        if (empty($containerId)) {
            $containerId = Addressbook_Controller::getDefaultInternalAddressbook();
        }
        $filter = new Addressbook_Model_ListFilter([
            ['field' => 'name',         'operator' => 'equals', 'value' => $groupName],
            ['field' => 'type',         'operator' => 'equals', 'value' => Addressbook_Model_List::LISTTYPE_GROUP],
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $containerId],
        ]);
        
        $existingLists = $this->search($filter);
        
        return $existingLists->getFirstRecord();
    }
}
