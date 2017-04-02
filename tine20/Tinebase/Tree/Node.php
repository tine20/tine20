<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * sql backend class for tree nodes
 *
 * @package     Tinebase
 */
class Tinebase_Tree_Node extends Tinebase_Backend_Sql_Abstract
{
    use Tinebase_Controller_Record_ModlogTrait;

    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'tree_nodes';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Tree_Node';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = false;

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
        /*if (isset($_options[Tinebase_Config::FILESYSTEM_MODLOGACTIVE]) && true === $_options[Tinebase_Config::FILESYSTEM_MODLOGACTIVE]) {
            $this->_modlogActive = true;
        }*/

        parent::__construct($_dbAdapter, $_options);
    }

    /**
     * if set to an integer value, only revisions of that number will be selected
     * if set to null value, regular revision will be selected
     *
     * @param int|null $_revision
     */
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
        
        $select
            ->joinLeft(
                /* table  */ array('tree_fileobjects' => $this->_tablePrefix . 'tree_fileobjects'), 
                /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.object_id') . ' = ' . $this->_db->quoteIdentifier('tree_fileobjects.id'),
                /* select */ array('type', 'created_by', 'creation_time', 'last_modified_by', 'last_modified_time', 'revision', 'contenttype', 'revision_size', 'indexed_hash')
            )
            ->joinLeft(
                /* table  */ array('tree_filerevisions' => $this->_tablePrefix . 'tree_filerevisions'), 
                /* on     */ $this->_db->quoteIdentifier('tree_fileobjects.id') . ' = ' . $this->_db->quoteIdentifier('tree_filerevisions.id') . ' AND ' .
                $this->_db->quoteIdentifier('tree_filerevisions.revision') . ' = ' . (null !== $this->_revision ? (int)$this->_revision : $this->_db->quoteIdentifier('tree_fileobjects.revision')),
                /* select */ array('hash', 'size')
            )->joinLeft(
            /* table  */ array('tree_filerevisions2' => $this->_tablePrefix . 'tree_filerevisions'),
                /* on     */ $this->_db->quoteIdentifier('tree_fileobjects.id') . ' = ' . $this->_db->quoteIdentifier('tree_filerevisions2.id'),
                /* select */ array('available_revisions' => Tinebase_Backend_Sql_Command::factory($select->getAdapter())->getAggregate('tree_filerevisions2.revision'))
            )->group($this->_tableName . '.object_id');
            
        return $select;
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
        Tinebase_Notes::getInstance()->addSystemNote($_newRecord, Tinebase_Core::getUser(), Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED);
    }

    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $oldRecord = $this->get($_record->getId());
        $newRecord = parent::update($_record);

        $currentMods = $this->_writeModLog($newRecord, $oldRecord);
        Tinebase_Notes::getInstance()->addSystemNote($newRecord, Tinebase_Core::getUser(), Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $currentMods);

        return $newRecord;
    }
    
    /**
     * returns columns to fetch in first query and if an id/value pair is requested 
     * 
     * @param array|string $_cols
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return array
     */
    protected function _getColumnsToFetch($_cols, Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL)
    {
        $result = parent::_getColumnsToFetch($_cols, $_filter, $_pagination);
        
        // sanitize sorting fields
        $foreignTableSortFields = array(
            'size'               =>  'tree_filerevisions',
            'creation_time'      =>  'tree_fileobjects',
            'created_by'         =>  'tree_fileobjects',
            'last_modified_time' =>  'tree_fileobjects',
            'last_modified_by'   =>  'tree_fileobjects',
            'type'               =>  'tree_fileobjects',
            'contenttype'        =>  'tree_fileobjects',
            'revision'           =>  'tree_fileobjects',
        );
        
        foreach ($foreignTableSortFields as $field => $table) {
            if (isset($result[0][$field])) {
                $result[0][$field] = $table . '.' . $field;
            }
        }
        
        return $result;
    }
    
    /**
     * return child identified by name
     * 
     * @param  string|Tinebase_Model_Tree_Node  $parentId   the id of the parent node
     * @param  string|Tinebase_Model_Tree_Node  $childName  the name of the child node
     * @throws Tinebase_Exception_NotFound
     * @return Tinebase_Model_Tree_Node
     */
    public function getChild($parentId, $childName)
    {
        $parentId  = $parentId  instanceof Tinebase_Model_Tree_Node ? $parentId->getId() : $parentId;
        $childName = $childName instanceof Tinebase_Model_Tree_Node ? $childName->name   : $childName;
        
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'parent_id',
                'operator'  => $parentId ? 'equals' : 'isnull',
                'value'     => $parentId
            ),
            array(
                'field'     => 'name',
                'operator'  => 'equals',
                'value'     => $childName
            )
        ));
        $child = $this->search($searchFilter)->getFirstRecord();
        
        if (!$child) {
            throw new Tinebase_Exception_NotFound('child: ' . $childName . ' not found!');
        }
        
        return $child;
    }
    
    /**
     * return direct children of tree node
     * 
     * @param  string|Tinebase_Model_Tree_Node  $nodeId  the id of the node
     * @return Tinebase_Record_RecordSet
     */
    public function getChildren($nodeId)
    {
        $nodeId = $nodeId instanceof Tinebase_Model_Tree_Node ? $nodeId->getId() : $nodeId;
        
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'parent_id',
                'operator'  => 'equals',
                'value'     => $nodeId
            )
        ));
        $children = $this->search($searchFilter);
        
        return $children;
    }

    /**
     * returns all directory nodes up to the root
     *
     * @param Tinebase_Record_RecordSet $_nodes
     * @param Tinebase_Record_RecordSet $_result
     * @return Tinebase_Record_RecordSet
     */
    public function getAllFolderNodes(Tinebase_Record_RecordSet $_nodes, Tinebase_Record_RecordSet $_result = null)
    {
        if (null === $_result) {
            $_result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        }

        $ids = array();
        /** @var Tinebase_Model_Tree_Node $node */
        foreach($_nodes as $node) {
            if (Tinebase_Model_Tree_Node::TYPE_FOLDER === $node->type) {
                $_result->addRecord($node);
            }
            if (!empty($node->parent_id)) {
                $ids[] = $node->parent_id;
            }
        }

        if (!empty($ids)) {
            $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
                array(
                    'field'     => 'id',
                    'operator'  => 'in',
                    'value'     => $ids
                )
            ));
            $parents = $this->search($searchFilter);
            $this->getAllFolderNodes($parents, $_result);
        }

        return $_result;
    }
    
    /**
     * @param  string  $path
     * @return Tinebase_Model_Tree_Node
     */
    public function getLastPathNode($path)
    {
        $fullPath = $this->getPathNodes($path);
        
        return $fullPath[$fullPath->count()-1];
    }
    
    /**
     * get object count
     * 
     * @param string $_objectId
     * @return integer
     */
    public function getObjectCount($_objectId)
    {
        return $this->getObjectUsage($_objectId)->count();
    }

    /**
     * get object usage
     *
     * @param string $_objectId
     * @return Tinebase_Record_RecordSet
     */
    public function getObjectUsage($_objectId)
    {
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'object_id',
                'operator'  => 'equals',
                'value'     => $_objectId
            )
        ));
        return $this->search($searchFilter);
    }
    
    /**
     * getPathNodes
     * 
     * @param string $_path
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getPathNodes($_path)
    {
        $pathParts = $this->splitPath($_path);
        
        if (empty($pathParts)) {
            throw new Tinebase_Exception_InvalidArgument('empty path provided');
        }
        
        $parentId  = null;
        $pathNodes = new Tinebase_Record_RecordSet($this->_modelName);
        
        foreach ($pathParts as $pathPart) {
            $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
                array(
                    'field'     => 'parent_id',
                    'operator'  => $parentId ? 'equals' : 'isnull',
                    'value'     => $parentId
                ),
                array(
                    'field'     => 'name',
                    'operator'  => 'equals',
                    'value'     => $pathPart
                )
            ));
            $node = $this->search($searchFilter)->getFirstRecord();
            
            if (!$node) {
                throw new Tinebase_Exception_NotFound('path: ' . $_path . ' not found!');
            }
            
            $pathNodes->addRecord($node);
            
            $parentId = $node->getId();
        }
        
        return $pathNodes;
    }
    
    /**
     * pathExists
     * 
     * @param  string  $_path
     * @return bool
     */
    public function pathExists($_path)
    {
        try {
            $this->getLastPathNode($_path);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Found path: ' . $_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' ' . $teia);
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' ' . $tenf);
            return false;
        }
        
        return true;
    }
    
    public function sanitizePath($path)
    {
        return trim($path, '/');
    }
    
    /**
     * @param  string  $_path
     * @return array
     */
    public function splitPath($_path)
    {
        return explode('/', $this->sanitizePath($_path));
    }

    /**
     * recalculates all folder sizes
     *
     * on error it still continues and tries to calculate as many folder sizes as possible, but returns false
     *
     * @param Tinebase_Tree_FileObject $_fileObjectBackend
     * @return bool
     */
    public function recalculateFolderSize(Tinebase_Tree_FileObject $_fileObjectBackend)
    {
        // no transactions yet
        // get root node ids
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'parent_id',
                'operator'  => 'isnull',
                'value'     => null
            ), array(
                'field'     => 'type',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_Tree_Node::TYPE_FOLDER
            )
        ));
        return $this->_recalculateFolderSize($_fileObjectBackend, $this->_getIdsOfDeepestFolders($this->search($searchFilter, null, true)));
    }

    /**
     * @param Tinebase_Tree_FileObject $_fileObjectBackend
     * @param array $_folderIds
     * @param bool
     */
    protected function _recalculateFolderSize(Tinebase_Tree_FileObject $_fileObjectBackend, array $_folderIds)
    {
        $success = true;
        $parentIds = array();
        $transactionManager = Tinebase_TransactionManager::getInstance();

        foreach($_folderIds as $id) {
            $transactionId = $transactionManager->startTransaction($this->_db);

            try {
                try {
                    /** @var Tinebase_Model_Tree_Node $record */
                    $record = $this->get($id);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $transactionManager->commitTransaction($transactionId);
                    continue;
                }

                if (!empty($record->parent_id) && !isset($parentIds[$record->parent_id])) {
                    $parentIds[$record->parent_id] = $record->parent_id;
                }

                $childrenNodes = $this->getChildren($id);
                $size = 0;
                $revision_size = 0;

                /** @var Tinebase_Model_Tree_Node $child */
                foreach($childrenNodes as $child) {
                    $size += ((int)$child->size);
                    $revision_size += ((int)$child->revision_size);
                }

                if ($size !== ((int)$record->size) || $revision_size !== ((int)$record->revision_size)) {
                    /** @var Tinebase_Model_Tree_FileObject $fileObject */
                    $fileObject = $_fileObjectBackend->get($record->object_id);
                    $fileObject->size = $size;
                    $fileObject->revision_size = $revision_size;
                    $_fileObjectBackend->update($fileObject);
                }

                $transactionManager->commitTransaction($transactionId);

            // this shouldn't happen
            } catch (Exception $e) {
                $transactionManager->rollBack();
                Tinebase_Exception::log($e);
                $success = false;
            }
        }

        if (!empty($parentIds)) {
            $success = $this->_recalculateFolderSize($_fileObjectBackend, $parentIds) && $success;
        }

        return $success;
    }

    /**
     * returns ids of folders that do not have any sub folders
     *
     * @param array $_folderIds
     * @return array
     */
    protected function _getIdsOfDeepestFolders(array $_folderIds)
    {
        $result = array();
        $subFolderIds = array();
        foreach($_folderIds as $folderId) {
            // children folders
            $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
                array(
                    'field'     => 'parent_id',
                    'operator'  => 'equals',
                    'value'     => $folderId
                ), array(
                    'field'     => 'type',
                    'operator'  => 'equals',
                    'value'     => Tinebase_Model_Tree_Node::TYPE_FOLDER
                )
            ));
            $nodeIds = $this->search($searchFilter, null, true);
            if (empty($nodeIds)) {
                // no children, this is a result
                $result[] = $folderId;
            } else {
                $subFolderIds = array_merge($subFolderIds, $nodeIds);
            }
        }

        if (!empty($subFolderIds)) {
            $result = array_merge($result, $this->_getIdsOfDeepestFolders($subFolderIds));
        }

        return $result;
    }
}
