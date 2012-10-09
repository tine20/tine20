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
     * return direct children of tree node
     * 
     * @return Tinebase_Record_RecordSet
     */
    public function getChildren($_nodeId)
    {
        $nodeId = $_nodeId instanceof Tinebase_Model_Tree_Node ? $_nodeId->getId() : $_nodeId;
        
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
     * @param unknown_type $_path
     * @return Tinebase_Model_Tree_Node
     */
    public function getLastPathNode($_path)
    {
        $fullPath = $this->getPathNodes($_path);
        
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
     * 
     * @param unknown_type $_path
     * @return Tinebase_Record_RecordSet
     */
    public function getPathNodes($_path)
    {
        $pathParts = $this->splitPath($_path);
        
        if (empty($pathParts)) {
            throw new Tinebase_Exception_InvalidArgument('empty path provided');
        }
        
        $level = 0;
        $select = $this->_db->select();

        $select->from(array('level0' => $this->_tablePrefix . $this->_tableName), array(
                "level{$level}_id"        => 'id', 
                "level{$level}_name"      => 'name',
                "level{$level}_parent_id" => 'parent_id',
                "level{$level}_object_id" => 'object_id',
                "level{$level}_islink"    => 'islink'
            ))
            ->joinLeft(
                /* table  */ array("level{$level}_fileobjects" => $this->_tablePrefix . 'tree_fileobjects'), 
                /* on     */ $this->_db->quoteIdentifier("level{$level}.object_id") . ' = ' . $this->_db->quoteIdentifier("level{$level}_fileobjects.id"),
                /* select */ array(
                                 "level{$level}_type"               => 'type', 
                                 "level{$level}_revision"           => 'revision',
                                 "level{$level}_contenttype"        => 'contenttype',
                                 "level{$level}_created_by"         => 'created_by',
                                 "level{$level}_creation_time"      => 'creation_time',
                                 "level{$level}_last_modified_by"   => 'last_modified_by',
                                 "level{$level}_last_modified_time" => 'last_modified_time'
                             )
            )
            ->joinLeft(
                /* table  */ array("level{$level}_filerevisions" => $this->_tablePrefix . 'tree_filerevisions'), 
                /* on     */ $this->_db->quoteIdentifier("level{$level}_fileobjects.id") . ' = ' . $this->_db->quoteIdentifier("level{$level}_filerevisions.id") . ' AND ' . $this->_db->quoteIdentifier("level{$level}_fileobjects.revision") . ' = ' . $this->_db->quoteIdentifier("level{$level}_filerevisions.revision"),
                /* select */ array("level{$level}_hash" => 'hash', "level{$level}_size" => 'size')
            )
            ->where($this->_db->quoteIdentifier('level0.name') . ' = ?', $pathParts[0])
            ->where($this->_db->quoteIdentifier('level0.parent_id') . ' IS NULL');

        while (isset($pathParts[++$level])) {
            $select->joinLeft(
                    /* table  */ array('level' . $level => $this->_tablePrefix . $this->_tableName), 
                    /* on     */ $this->_db->quoteIdentifier('level' . ($level-1) . '.id') . ' = ' . $this->_db->quoteIdentifier("level{$level}.parent_id"),
                    /* select */ array(
                                     "level{$level}_id"        => 'id', 
                                     "level{$level}_name"      => 'name',
                                     "level{$level}_parent_id" => 'parent_id',
                                     "level{$level}_object_id" => 'object_id',
                                     "level{$level}_islink"    => 'islink'
                                 )
                )
                ->joinLeft(
                    /* table  */ array("level{$level}_fileobjects" => $this->_tablePrefix . 'tree_fileobjects'), 
                    /* on     */ $this->_db->quoteIdentifier("level{$level}.object_id") . ' = ' . $this->_db->quoteIdentifier("level{$level}_fileobjects.id"),
                    /* select */ array(
                                     "level{$level}_type"               => 'type', 
                                     "level{$level}_revision"           => 'revision',
                                     "level{$level}_contenttype"        => 'contenttype',
                                     "level{$level}_created_by"         => 'created_by',
                                     "level{$level}_creation_time"      => 'creation_time',
                                     "level{$level}_last_modified_by"   => 'last_modified_by',
                                     "level{$level}_last_modified_time" => 'last_modified_time'
                                 )
                )
                ->joinLeft(
                    /* table  */ array("level{$level}_filerevisions" => $this->_tablePrefix . 'tree_filerevisions'), 
                    /* on     */ $this->_db->quoteIdentifier("level{$level}_fileobjects.id") . ' = ' . $this->_db->quoteIdentifier("level{$level}_filerevisions.id") . ' AND ' . $this->_db->quoteIdentifier("level{$level}_fileobjects.revision") . ' = ' . $this->_db->quoteIdentifier("level{$level}_filerevisions.revision"),
                    /* select */ array("level{$level}_hash" => 'hash', "level{$level}_size" => 'size')
                )
                ->where($this->_db->quoteIdentifier("level{$level}.name") . ' = ?', $pathParts[$level]);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
                
        if (!$queryResult) {
            throw new Tinebase_Exception_NotFound('path: ' . $_path . ' not found!');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));

        for ($i=0; $i < $level; $i++) {
            $resultArray[] = array(
                'id'                 => $queryResult["level{$i}_id"],
                'name'               => $queryResult["level{$i}_name"],
                'parent_id'          => $queryResult["level{$i}_parent_id"],
                'object_id'          => $queryResult["level{$i}_object_id"],
                'islink'             => $queryResult["level{$i}_islink"],
                'type'               => $queryResult["level{$i}_type"],
                'hash'               => $queryResult["level{$i}_hash"],
                'size'               => $queryResult["level{$i}_size"],
                'revision'           => $queryResult["level{$i}_revision"],
                'contenttype'        => $queryResult["level{$i}_contenttype"],
                'created_by'         => $queryResult["level{$i}_created_by"],
                'creation_time'      => $queryResult["level{$i}_creation_time"],
                'last_modified_by'   => $queryResult["level{$i}_last_modified_by"],
                'last_modified_time' => $queryResult["level{$i}_last_modified_time"]
            );
        }
        
        $resultSet = $this->_rawDataToRecordSet($resultArray);
        
        return $resultSet;
    }
    
    /**
     * @param  string  $_path
     * @return bool
     */
    public function pathExists($_path)
    {
        try {
            $this->getLastPathNode($_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @param  string  $_path
     * @return array
     */
    public function splitPath($_path)
    {
        return explode('/', trim($_path, '/'));
    }
}
