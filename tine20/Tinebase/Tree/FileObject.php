<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * sql backend class for tree file(and directory) objects
 *
 * @package     Tinebase
 * @subpackage  Backend
 *
 * TODO refactor to Tinebase_Tree_Backend_FileObject
 */
class Tinebase_Tree_FileObject extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'tree_fileobjects';
    
    /**
     * Table name without prefix (file revisions)
     *
     * @var string
     */
    protected $_revisionsTableName = 'tree_filerevisions';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Tree_FileObject';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = FALSE;
    
    /**
     * keep old revisions in tree_filerevisions table
     * 
     * @var boolean
     */
    protected $_keepOldRevisions = false;

    protected $_getSelectHook = array();

    protected $_revision = null;

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
        if (isset($_options[Tinebase_Config::FILESYSTEM_MODLOGACTIVE]) && true === $_options[Tinebase_Config::FILESYSTEM_MODLOGACTIVE]) {
            $this->_modlogActive = true;
            $this->_keepOldRevisions = true;
        }

        parent::__construct($_dbAdapter, $_options);
    }

    public function setRevision($_revision)
    {
        $this->_revision = null !== $_revision ? (int)$_revision : null;
    }

    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        $select = parent::_getSelect($_cols, $_getDeleted);
        
        $select->joinLeft(
            /* table  */ array($this->_revisionsTableName => $this->_tablePrefix . $this->_revisionsTableName), 
            /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.id') . ' = ' . $this->_db->quoteIdentifier($this->_revisionsTableName . '.id') . ' AND ' 
                . $this->_db->quoteIdentifier($this->_revisionsTableName . '.revision') . ' = ' . (null !== $this->_revision ? (int)$this->_revision : $this->_db->quoteIdentifier($this->_tableName . '.revision')),
            /* select */ array('hash', 'size')
        )->joinLeft(
            /* table  */ array('tree_filerevisions2' => $this->_tablePrefix . 'tree_filerevisions'),
            /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.id') . ' = ' . $this->_db->quoteIdentifier('tree_filerevisions2.id'),
            /* select */ array('available_revisions' => Tinebase_Backend_Sql_Command::factory($select->getAdapter())->getAggregate('tree_filerevisions2.revision'))
        )->group($this->_tableName . '.id');

        if (count($this->_getSelectHook) > 0) {
            foreach($this->_getSelectHook as $hook) {
                call_user_func_array($hook, array($select));
            }
        }
            
        return $select;
    }        

    /**
     * get value of next revision for given fileobject
     * 
     * @param Tinebase_Model_Tree_FileObject $_objectId
     */
    protected function _getNextRevision(Tinebase_Model_Tree_FileObject $_objectId)
    {
        $objectId = $_objectId instanceof Tinebase_Model_Tree_FileObject ? $_objectId->getId() : $_objectId;
        
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $select = $this->_db->select()
            ->from($this->_tablePrefix . $this->_tableName)
            ->where($this->_db->quoteIdentifier($this->_tablePrefix . $this->_tableName . '.id') . ' = ?', $objectId);
        
        // lock row
        $stmt = $this->_db->query($select);
        $stmt->fetchAll();
        
        // increase revision
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $objectId);
        $data  = array('revision' => new Zend_Db_Expr($this->_db->quoteIdentifier('revision') . ' + 1'));
        $this->_db->update($this->_tablePrefix . $this->_tableName, $data, $where);

        // fetch updated revision
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        $revision = $queryResult[0]['revision'];
        
        // store new revisionid and unlock row
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        return $revision;
    }
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Interface $_record
     * @return array
     */
    protected function _recordToRawData(Tinebase_Record_Interface $_record)
    {
        $record = parent::_recordToRawData($_record);
        
        // get updated by _getNextRevision only
        unset($record['revision']);
        
        return $record;
    }
    
    /**
     * update foreign key values
     * 
     * @param string $_mode create|update
     * @param Tinebase_Record_Interface $_record
     */
    protected function _updateForeignKeys($_mode, Tinebase_Record_Interface $_record)
    {
        /** @var Tinebase_Model_Tree_FileObject $_record */
        if (empty($_record->hash)) {
            return;
        }
        
        $createRevision = $this->_keepOldRevisions || $_mode === 'create';
        $updateRevision = FALSE;

        // do not create a revision if the hash did not change! What point in creating a revision if the file in the filesystem is still the same?
        if ($_mode !== 'create') {
            /** @var Tinebase_Model_Tree_FileObject $currentRecord */
            $currentRecord = $this->get($_record);
            if ($currentRecord->hash !== NULL && !empty($currentRecord->revision)) {
                if ($currentRecord->hash === $_record->hash && (int)$currentRecord->size === (int)$_record->size) {
                    return;
                }
                $updateRevision = TRUE;
                if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $_record->type) {
                    $createRevision = FALSE;
                }
            } else {
                $createRevision = TRUE;
            }
        }
        
        if (! $createRevision && ! $updateRevision) {
            return;
        }

        $data = array(
            'creation_time' => Tinebase_DateTime::now()->toString(Tinebase_Record_Abstract::ISO8601LONG),
            'created_by'    => is_object(Tinebase_Core::getUser()) ? Tinebase_Core::getUser()->getId() : null,
            'hash'          => $_record->hash,
            'size'          => $_record->size,
            'revision'      => false === $createRevision && Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $_record->type ? 1 : $this->_getNextRevision($_record),
        );
            
        if ($createRevision) {
            $data['id'] = $_record->getId();
            $this->_db->insert($this->_tablePrefix . 'tree_filerevisions', $data);

            if (Tinebase_Model_Tree_FileObject::TYPE_FILE === $_record->type) {
                // update total size
                $this->_db->update($this->_tablePrefix . $this->_tableName,
                    array('revision_size' => new Zend_Db_Expr($this->_db->quoteIdentifier('revision_size') . ' + ' . (int)$_record->size)),
                    $this->_db->quoteInto($this->_db->quoteIdentifier($this->_tablePrefix . $this->_tableName . '.id') . ' = ?', $_record->getId()));
            }

        } elseif ($updateRevision) {
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $_record->getId()),
            );
            $this->_db->update($this->_tablePrefix . 'tree_filerevisions', $data, $where);

            if (Tinebase_Model_Tree_FileObject::TYPE_FILE === $_record->type && (int)$_record->revision_size !== (int) $_record->size) {
                // update total size
                $this->_db->update($this->_tablePrefix . $this->_tableName,
                    array('revision_size' => $_record->size),
                    $this->_db->quoteInto($this->_db->quoteIdentifier($this->_tablePrefix . $this->_tableName . '.id') . ' = ?', $_record->getId()));
            }
        }
    }

    /**
     * returns all hashes of revisions that still exists in the db
     * 
     * @param array $_hashes
     * @return array
     */
    public function checkRevisions($_hashes)
    {
        if (empty($_hashes)) {
            return array();
        }
        
        $select = $this->_db->select();
        $select->from(array($this->_revisionsTableName => $this->_tablePrefix . $this->_revisionsTableName), array('hash'));
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier($this->_revisionsTableName . '.hash') . ' IN (?)', (array) $_hashes));
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
        
        return $queryResult;
    }
    
    /**
     * update hash of multiple directory at once
     * 
     * @param  Tinebase_Record_RecordSet  $nodes
     * @return Tinebase_Record_RecordSet
     */
    public function updateDirectoryNodesHash(Tinebase_Record_RecordSet $nodes)
    {
        // legacy code => add missing revision to directory nodes 
        foreach ($nodes as $node) {
            if (!empty($node->hash)) {
                continue;
            }
            
            $object = $this->get($node->object_id);
            
            $object->hash = Tinebase_Record_Abstract::generateUID();
            $object->size = 0;
            
            $this->update($object);
        }
        
        $data  = array(
            'hash' => Tinebase_Record_Abstract::generateUID()
        );
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', $nodes->object_id),
            $this->_db->quoteInto($this->_db->quoteIdentifier('revision') . ' = ?', 1)
        );
        $this->_db->update($this->_tablePrefix . 'tree_filerevisions', $data, $where);
        
        return $this->getMultiple($nodes->object_id);
    }

    /**
     * recalculates all revision sizes of file objects of type file only
     *
     * on error it still continues and tries to calculate as many revision sizes as possible, but returns false
     *
     * @return bool
     */
    public function recalculateRevisionSize()
    {
        $success = true;

        // fetch ids only, no transaction
        $ids = $this->search(new Tinebase_Model_Tree_FileObjectFilter(array(
                array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FILE)
            )), null, true);
        $transactionManager = Tinebase_TransactionManager::getInstance();
        $dbExpr = new Zend_Db_Expr('sum(size)');

        foreach($ids as $id) {
            $transactionId = $transactionManager->startTransaction($this->_db);
            try {
                try {
                    /** @var Tinebase_Model_Tree_FileObject $record */
                    $record = $this->get($id);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $transactionManager->commitTransaction($transactionId);
                    continue;
                }

                $stmt = $this->_db->query($this->_db->select()->from($this->_tablePrefix . $this->_revisionsTableName, array($dbExpr))
                    ->where('id = ?', $id));
                if (($row = $stmt->fetch(Zend_Db::FETCH_NUM)) && ((int)$row[0]) !== ((int)$record->revision_size)) {

                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' revision size mismatch on ' . $id . ': ' . $row[0] .' != ' . $record->revision_size);

                    $record->revision_size = $row[0];
                    $this->update($record);
                }

                $transactionManager->commitTransaction($transactionId);

            // this shouldn't happen
            } catch (Exception $e) {
                $transactionManager->rollBack();
                Tinebase_Exception::log($e);
                $success = false;
            }
        }

        return $success;
    }

    /**
     * @param Zend_Db_Select $_select
     */
    protected function addNotIndexedWhere(Zend_Db_Select $_select)
    {
        $_select->where($this->_db->quoteIdentifier($this->_tableName . '.indexedHash') . ' <> ' . $this->_db->quoteIdentifier($this->_revisionsTableName . '.hash'));
    }

    /**
     * @return array
     */
    public function getNotIndexedObjectIds()
    {
        $this->_getSelectHook = array(array($this, 'addNotIndexedWhere'));

        $fileObjects = $this->search(new Tinebase_Model_Tree_FileObjectFilter(
                array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FILE)
            ), null, true);

        $this->_getSelectHook = array();

        return $fileObjects;
    }
}
