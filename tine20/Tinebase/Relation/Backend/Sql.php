<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Relations
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @todo        remove db table usage and extend Tinebase_Backend_Sql_Abstract
 */


/**
 * class Tinebase_Relation_Backend_Sql
 * 
 * Tinebase_Relation_Backend_Sql enables records to define cross application relations to other records.
 * It acts as a gneralised storage backend for the records relation property of these records.
 * 
 * Relations between records have a certain degree (PARENT, CHILD and SIBLING). This degrees are defined
 * in Tinebase_Model_Relation. Moreover Relations are of a type which is defined by the application defining 
 * the relation. In case of users manually created relations this type is 'MANUAL'. This manually created
 * relatiions can also hold a free-form remark.
 * 
 * NOTE: Relations are viewed as time dependend properties of records. As such, relations could
 * be broken, but never become deleted.
 * 
 * @package     Tinebase
 * @subpackage  Relations
 */
class Tinebase_Relation_Backend_Sql
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * Holds instance for SQL_TABLE_PREFIX . 'record_relations' table
     * 
     * @var Tinebase_Db_Table
     */
    protected $_dbTable;
    
    /**
     * constructor
     */
    public function __construct()
    {
        $this->_db = Tinebase_Core::getDb();
        
        // temporary on the fly creation of table
        $this->_dbTable = new Tinebase_Db_Table(array(
            'name' => SQL_TABLE_PREFIX . 'relations',
            'primary' => 'id'
        ));
    }
    
    /**
     * adds a new relation
     * 
     * @param  Tinebase_Model_Relation $_relation 
     * @return Tinebase_Model_Relation the new relation
     * 
     * @todo    move check existance and update / modlog to controller?
     */
    public function addRelation($_relation)
    {
        if ($_relation->getId()) {
            throw new Tinebase_Exception_Record_NotAllowed('Could not add existing relation');
        }
        
        $relId = $_relation->generateUID();
        $_relation->setId($relId);

        // check if relation is already set (with is_deleted=1)
        if ($deletedRelId = $this->_checkExistance($_relation)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Removing existing relation (rel_id): ' . $deletedRelId);
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('rel_id') . ' = ?', $deletedRelId)
            );
            $this->_dbTable->delete($where);
        } 
                
        $data = $_relation->toArray();
        $data['rel_id'] = $data['id'];
        $data['id'] = $_relation->generateUID();
        unset($data['related_record']);
        
        if (isset($data['remark']) && is_array($data['remark'])) {
            $data['remark'] = Zend_Json::encode($data['remark']);
        }
        
        $this->_dbTable->insert($data);
        
        $swappedData = $this->_swapRoles($data);
        $swappedData['id'] = $_relation->generateUID();
        $this->_dbTable->insert($swappedData);
                
        return $this->getRelation($relId, $_relation['own_model'], $_relation['own_backend'], $_relation['own_id']);
    }
    
    /**
     * update an existing relation
     * 
     * @param  Tinebase_Model_Relation $_relation 
     * @return Tinebase_Model_Relation the updated relation
     */
    public function updateRelation($_relation)
    {
        $id = $_relation->getId();
        
        $data = $_relation->toArray();
        $data['rel_id'] = $data['id'];
        unset($data['id']);
        unset($data['related_record']);
        
        if (isset($data['remark']) && is_array($data['remark'])) {
            $data['remark'] = Zend_Json::encode($data['remark']);
        }
        
        foreach (array($data, $this->_swapRoles($data)) as $toUpdate) {
            $where = array(
                $this->_db->quoteIdentifier('rel_id') . '      = ' . $this->_db->quote($id),
                $this->_db->quoteIdentifier('own_model') . '   = ' . $this->_db->quote($toUpdate['own_model']),
                $this->_db->quoteIdentifier('own_backend') . ' = ' . $this->_db->quote($toUpdate['own_backend']),
                $this->_db->quoteIdentifier('own_id') . '      = ' . $this->_db->quote($toUpdate['own_id']),
            );
            $this->_dbTable->update($toUpdate, $where);
        }
        
        return $this->getRelation($id, $_relation['own_model'], $_relation['own_backend'], $_relation['own_id']);
            
    }
    
    /**
     * breaks a relation
     * 
     * @param Tinebase_Model_Relation $_relation 
     * @return void 
     */
    public function breakRelation($_id)
    {
        $where = array(
            $this->_db->quoteIdentifier('rel_id') . ' = ' . $this->_db->quote($_id)
        );
        
        $this->_dbTable->update(array(
            'is_deleted'   => (int)true,
            'deleted_by'   => Tinebase_Core::getUser()->getId(),
            'deleted_time' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
        ), $where);
    }
    
    /**
     * breaks all relations, optionally only of given role
     * 
     * @param  string $_model    own model to break all relations for
     * @param  string $_backend  own backend to break all relations for
     * @param  string $_id       own id to break all relations for
     * @param  string $_degree   only breaks relations of given degree
     * @param  array  $_type     only breaks relations of given type
     * @return void
     */
    public function breakAllRelations($_model, $_backend, $_id, $_degree = NULL, array $_type = array())
    {
        $relationIds = $this->getAllRelations($_model, $_backend, $_id, $_degree, $_type)->getArrayOfIds();
        if (!empty($relationIds)) {
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('rel_id') . ' IN (?)', $relationIds)
            );
        
            $this->_dbTable->update(array(
                'is_deleted'   => (int)true,
                'deleted_by'   => Tinebase_Core::getUser()->getId(),
                'deleted_time' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            ), $where);
        }
    }
    
    /**
     * returns all relations of a given record and optionally only of given role
     * 
     * @param  string       $_model         own model to get all relations for
     * @param  string       $_backend       own backend to get all relations for
     * @param  string|array $_id            own id to get all relations for 
     * @param  string       $_degree        only return relations of given degree
     * @param  array        $_type          only return relations of given type
     * @param  boolean      $_returnAll     gets all relations (default: only get not deleted/broken relations)
     * @param  array        $_relatedModels  only return relations having this related model
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Relation
     */
    public function getAllRelations($_model, $_backend, $_id, $_degree = NULL, array $_type = array(), $_returnAll = false, $_relatedModels = NULL)
    {
        $_id = $_id ? (array)$_id : array('');
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('own_model') .' = ?', $_model),
            $this->_db->quoteInto($this->_db->quoteIdentifier('own_backend') .' = ?',$_backend),
            $this->_db->quoteInto($this->_db->quoteIdentifier('own_id') .' IN (?)' , $_id),
        );
        
        if (is_array($_relatedModels) && ! empty($_relatedModels)) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('related_model') .' IN (?)', $_relatedModels);
        }
        
        if (!$_returnAll) {
            $where[] = $this->_db->quoteIdentifier('is_deleted') . ' = '.(int)FALSE;
        }
        if ($_degree) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('own_degree') . ' = ?', $_degree);
        }
        if (! empty($_type)) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('type') . ' IN (?)', $_type);
        }
        
        $relations = new Tinebase_Record_RecordSet('Tinebase_Model_Relation', array(), true);
        foreach ($this->_dbTable->fetchAll($where) as $relation) {
            $relations->addRecord($this->_rawDataToRecord($relation->toArray(), true));
        }
           return $relations;
    }
    
    /**
     * returns one side of a relation
     *
     * @param  string $_id
     * @param  string $_ownModel 
     * @param  string $_ownBackend
     * @param  string $_ownId
     * @param  bool   $_returnBroken
     * @return Tinebase_Model_Relation
     */
    public function getRelation($_id, $_ownModel, $_ownBackend, $_ownId, $_returnBroken = false)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('rel_id') . ' = ?', $_id),
            $this->_db->quoteInto($this->_db->quoteIdentifier('own_model') . ' = ?', $_ownModel),
            $this->_db->quoteInto($this->_db->quoteIdentifier('own_backend') . ' = ?', $_ownBackend),
            $this->_db->quoteInto($this->_db->quoteIdentifier('own_id') . ' = ?', $_ownId),
        );
        if ($_returnBroken !== true) {
            $where[] = $this->_db->quoteIdentifier('is_deleted') . ' = '. (int)FALSE;
        }
        $relationRow = $this->_dbTable->fetchRow($where);
        
        if($relationRow) {
            return $this->_rawDataToRecord($relationRow->toArray());
        } else {
            throw new Tinebase_Exception_Record_NotDefined("No relation found.");
        }
    }
    
    /**
     * converts raw data from adapter into a single record
     *
     * @param  array $_rawData
     * @return Tinebase_Record_Abstract
     */
    protected function _rawDataToRecord(array $_rawData)
    {
        $_rawData['id'] = $_rawData['rel_id'];
        $result = new Tinebase_Model_Relation($_rawData, true);
        
        return $result;
    }
    
    /**
     * purges(removes from table) all relations
     * 
     * @param  string $_ownModel 
     * @param  string $_ownBackend
     * @param  string $_ownId
     * @return void
     * 
     * @todo should this function only purge deleted/broken relations?
     */
    public function purgeAllRelations($_ownModel, $_ownBackend, $_ownId)
    {
        $relationIds = $this->getAllRelations($_ownModel, $_ownBackend, $_ownId, NULL, array(), true)->getArrayOfIds();
        
        if (!empty($relationIds)) {
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('rel_id') . ' IN (?)', $relationIds)
            );
        
            $this->_dbTable->delete($where);
        }
    }
    
    /**
     * Search for records matching given filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)    
    {
        $backend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Relation', 
            'tableName' => 'relations',
        ));
        
        $_filter->addFilter(new Tinebase_Model_Filter_Bool('is_deleted', 'equals', (int)FALSE));
        
        return $backend->search($_filter, $_pagination, $_onlyIds);
    }
    
    /**
     * swaps roles own/related
     * 
     * @param  array data of a relation
     * @return array data with swaped roles
     */
    protected function _swapRoles($_data)
    {
        $data = $_data;
        $data['own_model']       = $_data['related_model'];
        $data['own_backend']     = $_data['related_backend'];
        $data['own_id']          = $_data['related_id'];
        $data['related_model']   = $_data['own_model'];
        $data['related_backend'] = $_data['own_backend'];
        $data['related_id']      = $_data['own_id'];
        switch ($_data['own_degree']) {
            case Tinebase_Model_Relation::DEGREE_PARENT:
                $data['own_degree'] = Tinebase_Model_Relation::DEGREE_CHILD;
                break;
            case Tinebase_Model_Relation::DEGREE_CHILD:
                $data['own_degree'] = Tinebase_Model_Relation::DEGREE_PARENT;
                break;
        }
        return $data;
    }
    
    /**
     * check if relation already exists but is_deleted
     *
     * @param Tinebase_Model_Relation $_relation
     * @return string relation id
     */
    protected function _checkExistance($_relation)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('own_model') . ' = ?', $_relation->own_model),
            $this->_db->quoteInto($this->_db->quoteIdentifier('own_backend') . ' = ?', $_relation->own_backend),
            $this->_db->quoteInto($this->_db->quoteIdentifier('own_id') . ' = ?', $_relation->own_id),
            $this->_db->quoteInto($this->_db->quoteIdentifier('related_id') . ' = ?', $_relation->related_id),
            $this->_db->quoteIdentifier('is_deleted') . ' = 1'
        );
        $relationRow = $this->_dbTable->fetchRow($where);
        
        if ($relationRow) {
            return $relationRow->rel_id;
        } else {
            return FALSE;
        }
    }
    
    /**
     * transfers relations
     * 
     * @param string $sourceId
     * @param string $destinationId
     * @param string $model
     * 
     * @return array
     */
    public function transferRelations($sourceId, $destinationId, $model)
    {
        $controller = Tinebase_Controller_Record_Abstract::getController($model);
        
        // just for validation, the records aren't needed
        $sourceRecord      = $controller->get($sourceId);
        $destinationRecord = $controller->get($destinationId);
        
        $tableName = SQL_TABLE_PREFIX . 'relations';
        
        // own side
        $select = $this->_db->select()->where($this->_db->quoteIdentifier('own_id') . ' = ?', $sourceId);
        $select->from($tableName);
        $stmt = $this->_db->query($select);
        $entries = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $stmt->closeCursor();
        
        // rel side
        $select = $this->_db->select()->where($this->_db->quoteIdentifier('related_id') . ' = ?', $sourceId);
        $select->from($tableName);
        $stmt = $this->_db->query($select);
        $relentries = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $stmt->closeCursor();
        
        $skipped = array();
        
        foreach($entries as $entry) {
            $select = $this->_db->select()->where(
                $this->_db->quoteInto($this->_db->quoteIdentifier('own_id') . ' = ?', $destinationId). ' AND ' . 
                $this->_db->quoteInto($this->_db->quoteIdentifier('related_id') . ' = ?', $entry['related_id'])
            );
            $select->from($tableName);
            $stmt = $this->_db->query($select);
            $existing = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if (count($existing) > 0) {
                $skipped[$entry['rel_id']] = $entry;
            } else {
                $this->_dbTable->update(
                    array('own_id' => $destinationId),
                    $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $entry['id'])
                );
            }
        }
        
        
        foreach($relentries as $entry) {
            $select = $this->_db->select()->where(
                $this->_db->quoteInto($this->_db->quoteIdentifier('related_id') . ' = ?', $destinationId). ' AND ' . 
                $this->_db->quoteInto($this->_db->quoteIdentifier('own_id') . ' = ?', $entry['own_id'])
            );
            $select->from($tableName);
            $stmt = $this->_db->query($select);
            $existing = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if (count($existing) > 0) {
            } else {
                $this->_dbTable->update(
                    array('related_id' => $destinationId), 
                    $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $entry['id'])
                );
            }
        }

        return $skipped;
    }
}
