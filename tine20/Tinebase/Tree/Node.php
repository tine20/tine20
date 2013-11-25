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
                /* select */ array('type', 'created_by', 'creation_time', 'last_modified_by', 'last_modified_time', 'revision', 'contenttype')
            )
            ->joinLeft(
                /* table  */ array('tree_filerevisions' => $this->_tablePrefix . 'tree_filerevisions'), 
                /* on     */ $this->_db->quoteIdentifier('tree_fileobjects.id') . ' = ' . $this->_db->quoteIdentifier('tree_filerevisions.id') . ' AND ' . $this->_db->quoteIdentifier('tree_fileobjects.revision') . ' = ' . $this->_db->quoteIdentifier('tree_filerevisions.revision'),
                /* select */ array('hash', 'size')
            );
            
        return $select;
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
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'object_id',
                'operator'  => 'equals',
                'value'     => $_objectId
            )
        ));
        $result = $this->search($searchFilter);
        
        return $result->count();
    }
    
    /**
     * getPathNodes
     * 
     * @param string $_path
     * @return Tinebase_Record_RecordSet
     */
    public function getPathNodes($path)
    {
        $pathParts = $this->splitPath($path);
        
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
                throw new Tinebase_Exception_NotFound('path: ' . $path . ' not found!');
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
    public function splitPath($path)
    {
        return explode('/', $this->sanitizePath($path));
    }
}
