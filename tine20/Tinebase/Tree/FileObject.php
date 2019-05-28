<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
    use Tinebase_Controller_Record_ModlogTrait;

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

    protected $_allowSetRevision = false;

    /**
     * the singleton pattern
     *
     * @return Tinebase_Tree_FileObject
     */
    public static function getInstance()
    {
        return Tinebase_FileSystem::getInstance()->getFileObjectBackend();
    }

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
     * @return boolean
     */
    public function getKeepOldRevision()
    {
        return $this->_keepOldRevisions;
    }

    /**
     * @param boolean $_value
     */
    public function setKeepOldRevision($_value)
    {
        $this->_keepOldRevisions = true === $_value;
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
            /* select */ array('hash', 'size', 'preview_count', 'lastavscan_time', 'is_quarantined')
        )->joinLeft(
            /* table  */ array('tree_filerevisions2' => $this->_tablePrefix . 'tree_filerevisions'),
            /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.id') . ' = ' . $this->_db->quoteIdentifier('tree_filerevisions2.id'),
            /* select */ array('available_revisions' => Tinebase_Backend_Sql_Command::factory($select->getAdapter())->getAggregate('tree_filerevisions2.revision'))
        )->group($this->_tableName . '.id');

        // NOTE: we need to do it here if $this->_modlogActive is false
        if (false === $this->_modlogActive && !$_getDeleted) {
            // don't fetch deleted objects
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.is_deleted') . ' = 0');
        }

        if (count($this->_getSelectHook) > 0) {
            foreach($this->_getSelectHook as $hook) {
                call_user_func_array($hook, array($select));
            }
        }
            
        return $select;
    }

    /**
     * @param string $_hash
     * @param integer $_count
     */
    public function updatePreviewCount($_hash, $_count)
    {
        $this->_db->update($this->_tablePrefix . $this->_revisionsTableName, array('preview_count' => (int)$_count), $this->_db->quoteInto('hash = ?', $_hash));
    }

    /**
     * get value of next revision for given fileobject
     * 
     * @param Tinebase_Model_Tree_FileObject $_objectId
     * @return int
     */
    protected function _getNextRevision(Tinebase_Model_Tree_FileObject $_objectId)
    {
        $objectId = $_objectId instanceof Tinebase_Model_Tree_FileObject ? $_objectId->getId() : $_objectId;
        
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        // lock row first
        $select = $this->_db->select()->from($this->_tablePrefix . $this->_tableName)
            ->where($this->_db->quoteIdentifier($this->_tablePrefix . $this->_tableName . '.id') . ' = ?', $objectId);
        $stmt = $this->_db->query($select);
        $stmt->fetchAll();

        $select = $this->_db->select()
            ->from($this->_tablePrefix . $this->_revisionsTableName, new Zend_Db_Expr('MAX(' .
                $this->_db->quoteIdentifier('revision') . ') + 1 AS ' . $this->_db->quoteIdentifier('revision')))
            ->where($this->_db->quoteIdentifier($this->_tablePrefix . $this->_revisionsTableName . '.id') . ' = ?', $objectId);

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        if (empty($queryResult)) {
            $revision = 1;
        } else {
            $revision = (int)$queryResult[0]['revision'];
            if (0 === $revision) {
                $revision = 1;
            }
        }

        // increase revision
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $objectId);
        $data  = array('revision' => $revision);
        $this->_db->update($this->_tablePrefix . $this->_tableName, $data, $where);
        
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

        if (false === $this->_allowSetRevision) {
            // get updated by _getNextRevision only
            unset($record['revision']);
        }
        
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
        $updateRevision = false;
        $currentRecord = null;

        if (is_file($_record->getFilesystemPath())) {
            if (false === ($_record->size = filesize($_record->getFilesystemPath()))) {
                throw new Tinebase_Exception_Backend('couldn\'t get filesize() for hash: ' . $_record->hash);
            }
        } elseif(empty($_record->size)) {
            $_record->size = 0;
        }

        // do not create a revision if the hash/size did not change!
        // What point in creating a revision if the file in the filesystem is still the same?
        if ($_mode !== 'create') {
            /** @var Tinebase_Model_Tree_FileObject $currentRecord */
            $currentRecord = $this->get($_record, true);
            if (true === $this->_allowSetRevision && $_record->revision < $currentRecord->revision) {
                if (!in_array($_record->revision, $currentRecord->available_revisions)) {
                    throw new Tinebase_Exception_Backend('can\'t set revision to ' . $_record->revision .
                        ' for fileobject ' . $_record->getId());
                }
                $updateRevision = true;
                $createRevision = false;
            }
            if ($currentRecord->hash !== null && !empty($currentRecord->revision)) {
                if (($currentRecord->hash === $_record->hash && (int)$currentRecord->size === (int)$_record->size) ||
                        Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $_record->type) {
                    $createRevision = false;
                }
                $updateRevision = true;
            } else {
                $createRevision = true;
            }
        }

        if (!$createRevision && !$updateRevision) {
            return;
        }

        $data = array(
            'creation_time' => Tinebase_DateTime::now()->toString(Tinebase_Record_Abstract::ISO8601LONG),
            'created_by' => is_object(Tinebase_Core::getUser()) ? Tinebase_Core::getUser()->getId() : null,
            'hash' => $_record->hash,
            'size' => $_record->size,
            'lastavscan_time' => $_record->lastavscan_time ? ($_record->lastavscan_time instanceof Tinebase_DateTime ?
                $_record->lastavscan_time->toString(Tinebase_Record_Abstract::ISO8601LONG) : $_record->lastavscan_time)
                : null,
            'is_quarantined' => $_record->is_quarantined ? 1 : 0,
            'preview_count' => (int)$_record->preview_count,
            'revision' => false === $createRevision ? $currentRecord->revision : $this->_getNextRevision($_record),
        );

        if ($createRevision) {
            $data['id'] = $_record->getId();
            $this->_db->insert($this->_tablePrefix . 'tree_filerevisions', $data);

            if (Tinebase_Model_Tree_FileObject::TYPE_FILE === $_record->type) {
                // update total size
                $this->_db->update($this->_tablePrefix . $this->_tableName,
                    array('revision_size' => new Zend_Db_Expr($this->_db->quoteIdentifier('revision_size') . ' + ' . (int)$_record->size)),
                    $this->_db->quoteInto($this->_db->quoteIdentifier($this->_tablePrefix . $this->_tableName . '.id') . ' = ?',
                        $_record->getId()));
            }

        } else {
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ? AND ' .
                    $this->_db->quoteIdentifier('revision') . ' = ' . (int)$data['revision'], $_record->getId()),
            );
            $this->_db->update($this->_tablePrefix . 'tree_filerevisions', $data, $where);

            if (1 === count($currentRecord->available_revisions) &&
                    Tinebase_Model_Tree_FileObject::TYPE_FILE === $_record->type &&
                    (int)$currentRecord->revision_size !== (int)$_record->size) {
                // update total size
                $this->_db->update($this->_tablePrefix . $this->_tableName,
                    array('revision_size' => $_record->size),
                    $this->_db->quoteInto($this->_db->quoteIdentifier($this->_tablePrefix . $this->_tableName . '.id') . ' = ?',
                        $_record->getId()));
            }
        }
    }

    /**
     * do something after creation of record
     *
     * @param Tinebase_Record_Interface $_newRecord
     * @param Tinebase_Record_Interface $_recordToCreate
     * @return void
     */
    protected function _inspectAfterCreate(Tinebase_Record_Interface $_newRecord, Tinebase_Record_Interface $_recordToCreate)
    {
        $this->_writeModLog($_newRecord, null);
    }

    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @param boolean $_isReplicable
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record, $_isReplicable = true)
    {
        $oldIsReplicable = Tinebase_Model_Tree_FileObject::setReplicable($_isReplicable);

        $oldRecord = $this->get($_record->getId(), true);
        $newRecord = parent::update($_record);

        $currentMods = $this->_writeModLog($newRecord, $oldRecord);
        if (null !== $currentMods && $currentMods->count() > 0) {
            /** @var Tinebase_Model_ModificationLog $mod */
            foreach ($currentMods->getClone(true) as $mod) {
                $diff = new Tinebase_Record_Diff(json_decode($mod->new_value, true));
                if (isset($diff->diff['hash'])) {
                    $a = $diff->diff;
                    unset($a['hash']);
                    $diff->diff = $a;
                    if (count($a) === 0) {
                        $currentMods->removeRecord($mod);
                    } else {
                        $mod->new_value = json_encode($diff->toArray());
                    }
                }
            }
            // add notes to tree_nodes!
            foreach (Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->getObjectUsage($newRecord->getId()) as
                    $node) {
                Tinebase_Notes::getInstance()->addSystemNote($node->getId(), Tinebase_Core::getUser(),
                    Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $currentMods, 'Sql', 'Tinebase_Model_Tree_Node');
            }
        }

        Tinebase_Model_Tree_FileObject::setReplicable($oldIsReplicable);

        return $newRecord;
    }

    /**
     * @param array $_ids
     */
    protected function _inspectBeforeSoftDelete(array $_ids)
    {
        if (!empty($_ids)) {
            foreach($this->getMultiple($_ids) as $object) {
                $this->_writeModLog(null, $object);
            }
        }
    }

    /**
     * returns all hashes of revisions that still exists in the db
     * 
     * @param array $_hashes
     * @return array
     */
    public function checkRevisions(array $_hashes)
    {
        if (empty($_hashes)) {
            return array();
        }
        
        $select = $this->_db->select();
        $select->from(array($this->_revisionsTableName => $this->_tablePrefix . $this->_revisionsTableName), array('hash'));
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier($this->_revisionsTableName . '.hash') . ' IN (?)', $_hashes));
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
        
        return $queryResult;
    }

    /**
     * returns all hashes of all revisions for given file object ids
     *
     * @param array $_ids
     * @return array
     */
    public function getHashes(array $_ids)
    {
        if (empty($_ids)) {
            return array();
        }

        $select = $this->_db->select()->distinct()
            ->from(array($this->_revisionsTableName => $this->_tablePrefix . $this->_revisionsTableName),
                array('hash'))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier($this->_revisionsTableName . '.id') . ' IN (?)',
                $_ids));

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

                    $stmt->closeCursor();

                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' revision size mismatch on ' . $id . ': ' . $row[0] .' != ' . $record->revision_size);

                    $record->revision_size = $row[0];
                    $this->update($record);
                } else {
                    $stmt->closeCursor();
                }

                $transactionManager->commitTransaction($transactionId);

            // this shouldn't happen
            } catch (Exception $e) {
                $transactionManager->rollBack();
                Tinebase_Exception::log($e);
                $success = false;
            }

            Tinebase_Lock::keepLocksAlive();
        }

        return $success;
    }

    /**
     * @param Zend_Db_Select $_select
     */
    protected function addNotIndexedWhere(Zend_Db_Select $_select)
    {
        $quotedIndexedHash = $this->_db->quoteIdentifier($this->_tableName . '.indexed_hash');
        $_select->where($quotedIndexedHash . ' IS NULL OR ' . $quotedIndexedHash . ' <> ' .
            $this->_db->quoteIdentifier($this->_revisionsTableName . '.hash'));
    }

    /**
     * @return array
     */
    public function getNotIndexedObjectIds()
    {
        $this->_getSelectHook = array(array($this, 'addNotIndexedWhere'));

        $fileObjects = $this->search(new Tinebase_Model_Tree_FileObjectFilter([
                ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FILE]
            ]), null, true);

        $this->_getSelectHook = array();

        return $fileObjects;
    }

    /**
     * delete old file revisions that are older than $_months months
     *
     * @param string $_id
     * @param int $_months
     * @return int number of deleted revisions
     */
    public function clearOldRevisions($_id, $_months)
    {
        $months = (int)$_months;
        if ($months < 1) {
            return 0;
        }

        if ($this->_db instanceof Zend_Db_Adapter_Pdo_Mysql) {
            $stmt = $this->_db->query('DELETE revisions.* FROM ' . SQL_TABLE_PREFIX . $this->_revisionsTableName . ' AS revisions LEFT JOIN ' .
                SQL_TABLE_PREFIX . $this->_tableName . ' AS  objects ON revisions.id = objects.id AND revisions.revision = objects.revision WHERE ' .
                $this->_db->quoteInto('revisions.id = ?', $_id) . ' AND objects.id IS NULL AND revisions.creation_time < "' . date('Y-m-d H:i:s', time() - 3600 * 24 * 30 * $months) . '"');

        } else {
            // pgsql -> subquery
            $stmt = $this->_db->query('DELETE FROM ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $this->_revisionsTableName) . ' WHERE ' . $this->_db->quoteIdentifier('id') . $this->_db->quoteInto(' = ?', $_id) .
                ' AND ' . $this->_db->quoteIdentifier('revision') . ' < (SELECT ' . $this->_db->quoteIdentifier('revision') . ' FROM '. $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $this->_tableName) .
                ' WHERE ' . $this->_db->quoteIdentifier('id') . $this->_db->quoteInto(' = ?', $_id) . ')');
        }

        return $stmt->rowCount();
    }

    /**
     * @param string $_id
     * @param array $_revisions
     * @return int
     */
    public function deleteRevisions($_id, array $_revisions)
    {
        // TODO PGSQL =>  this is only supported by MySQL
        // pgsql -> subquery with ids?
        if ($this->_db instanceof Zend_Db_Adapter_Pdo_Mysql) {
            $stmt = $this->_db->query('DELETE revisions.* FROM ' . SQL_TABLE_PREFIX . $this->_revisionsTableName . ' AS revisions LEFT JOIN ' .
                SQL_TABLE_PREFIX . $this->_tableName . ' AS  objects ON revisions.id = objects.id AND revisions.revision = objects.revision WHERE ' .
                $this->_db->quoteInto('revisions.id = ?', $_id) . ' AND objects.id IS NULL AND revisions.revision IN ' .
                $this->_db->quoteInto('(?)', $_revisions));

        } else {
            // pgsql -> subquery
            $stmt = $this->_db->query('DELETE FROM ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $this->_revisionsTableName) . ' WHERE ' . $this->_db->quoteIdentifier('id') . $this->_db->quoteInto(' = ?', $_id) .
                ' AND ' . $this->_db->quoteIdentifier('revision') . $this->_db->quoteInto(' IN (?)', $_revisions) . ' AND ' . $this->_db->quoteIdentifier('revision') . ' < (SELECT ' . $this->_db->quoteIdentifier('revision') . ' FROM '. $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $this->_tableName) .
                ' WHERE ' . $this->_db->quoteIdentifier('id') . $this->_db->quoteInto(' = ?', $_id) . ')');
        }

        return $stmt->rowCount();
    }

    /**
     * @param array $_hashes
     * @param bool $_forUpdate
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getRevisionForHashes(array $_hashes, $_forUpdate = false)
    {
        $result = [];
        $stmt = $this->_db->select()->from(SQL_TABLE_PREFIX . $this->_revisionsTableName, ['id', 'revision'])
            ->where($this->_db->quoteIdentifier('hash') . ' in (?)', $_hashes)
            ->forUpdate($_forUpdate)->query(Zend_Db::FETCH_NUM);
        while ($row = $stmt->fetch()) {
            if (isset($result[$row[0]])) {
                $result[$row[0]][] = $row[1];
            } else {
                $result[$row[0]] = [$row[1]];
            }
        }

        return $result;
    }

    /**
     * @param Tinebase_Model_ModificationLog $_modification
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function applyReplicationModificationLog(Tinebase_Model_ModificationLog $_modification)
    {
        switch($_modification->change_type) {
            case Tinebase_Timemachine_ModificationLog::CREATED:
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                $record = new Tinebase_Model_Tree_FileObject($diff->diff);
                $this->_prepareReplicationRecord($record);
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($record, 'create');
                $this->create($record);
                if (Tinebase_Model_Tree_FileObject::TYPE_FILE === $record->type && null !== $record->hash &&
                        !is_file($record->getFilesystemPath())) {
                    Tinebase_Timemachine_ModificationLog::getInstance()->fetchBlobFromMaster($record->hash);
                }
                break;

            case Tinebase_Timemachine_ModificationLog::UPDATED:

                try {
                /** @var Tinebase_Model_Tree_FileObject $record */
                $record = $this->get($_modification->record_id, true);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    break;
                }

                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                $currentRecord = clone $record;
                $record->applyDiff($diff);
                $this->_prepareReplicationRecord($record);
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($record, 'update', $currentRecord);
                if (Tinebase_Model_Tree_FileObject::TYPE_FILE === $record->type && $record->size > 0
                        && $currentRecord->hash !== $record->hash && null !== $record->hash
                        && !is_file($record->getFilesystemPath())) {
                    Tinebase_Timemachine_ModificationLog::getInstance()->fetchBlobFromMaster($record->hash);
                }
                $this->update($record);
                break;

            case Tinebase_Timemachine_ModificationLog::DELETED:
                try {
                    $this->softDelete(array($_modification->record_id));
                } catch (Tinebase_Exception_NotFound $tenf) {}
                break;

            default:
                throw new Tinebase_Exception_UnexpectedValue('change_type ' . $_modification->change_type . ' unknown');
        }
    }

    /**
     * @param Tinebase_Model_Tree_FileObject $_record
     */
    protected function _prepareReplicationRecord(Tinebase_Model_Tree_FileObject $_record)
    {
        // unset properties that are maintained only locally
        $_record->indexed_hash = null;
    }

    /**
     * @param Tinebase_Model_ModificationLog $_modification
     * @param bool $_dryRun
     */
    public function undoReplicationModificationLog(Tinebase_Model_ModificationLog $_modification, $_dryRun)
    {
        switch($_modification->change_type) {
            case Tinebase_Timemachine_ModificationLog::CREATED:
                if (true === $_dryRun) {
                    return;
                }
                $this->softDelete($_modification->record_id);
                break;

            case Tinebase_Timemachine_ModificationLog::UPDATED:
                $object = $this->get($_modification->record_id);
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                $object->undo($diff);

                if (true !== $_dryRun) {
                    $oldAllowSetRevision = $this->setAllowRevisionUpdate(true);
                    try {
                        $this->update($object);
                    } finally {
                        $this->_allowSetRevision = $oldAllowSetRevision;
                    }
                }
                break;

            case Tinebase_Timemachine_ModificationLog::DELETED:
                if (true === $_dryRun) {
                    return;
                }
                $object = $this->get($_modification->record_id, true);
                $object->is_deleted = false;
                $this->update($object);
                break;

            default:
                throw new Tinebase_Exception_UnexpectedValue('change_type ' . $_modification->change_type . ' unknown');
        }
    }

    /**
     * @param bool $_value
     * @return bool
     */
    public function setAllowRevisionUpdate($_value)
    {
        $oldValue = $this->_allowSetRevision;
        $this->_allowSetRevision = $_value;
        return $oldValue;
    }

    /**
     * deletes fileObjects which are not referenced by any tree nodes
     * returns number of deleted file objects
     *
     * @return int
     */
    public function deletedUnusedObjects()
    {
        $result = 0;
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
        try {
            $ids = $this->_db->select()->from([$this->_tableName => $this->_tablePrefix . $this->_tableName],
                [$this->_tableName . '.id'])->joinLeft(
                    ['tree_nodes' => $this->_tablePrefix . 'tree_nodes'],
                    $this->_db->quoteIdentifier('tree_nodes.object_id') . ' = ' . $this->_db->quoteIdentifier(
                        $this->_tableName . '.id'),
                    [$this->_tableName . '.id']
            )->where($this->_db->quoteIdentifier('tree_nodes.object_id') . ' IS NULL')
                ->query()->fetchAll(Zend_Db::FETCH_COLUMN);

            if (!empty($ids)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' deleting ' . count($ids) . ' unused file objects: ' . print_r($ids, true));
                $result = $this->_db->delete($this->_tablePrefix . $this->_tableName, $this->_db->quoteInto(
                    $this->_db->quoteIdentifier('id') . ' IN (?)', $ids));
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        return $result;
    }
}
