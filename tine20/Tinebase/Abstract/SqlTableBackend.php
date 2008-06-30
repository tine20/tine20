<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Abstract
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Sebastian Lenk <s.lenk@metaways.de>
 * @version     $Id$
 */

/**
 * Abstract class for a Tine 2.0 sql table backend
 * 
 * @package     Tinebase
 * @subpackage  Abstract
 */
abstract class Tinebase_Abstract_SqlTableBackend
{
    /**
     * Table name
     *
     * @var string
     */
    protected $_tableName;
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName;
    
    /**
     * Identifier
     *
     * @var string
     */
    protected $_identifier = 'id';
    
    
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * Creates new entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws InvalidArgumentException|Exception
     * @return object Record
     */
    public function create(Tinebase_Record_Interface $_record) {
    	if (!$_record instanceof $this->_modelName) {
    		throw new InvalidArgumentException('$_record is of invalid model type');
    	}
        
        $recordArray = $_record->toArray();
        $tableDefinition = $this->_table->info();
        $recordArray = array_intersect_key($recordArray, array_flip($tableDefinition['cols']));
        
        $this->_db->insert($this->_tableName, $recordArray);
        $id = $this->_db->lastInsertId();

        // if we insert a record without an id, we need to get back one
        if(empty($_record->id) && $id == 0) {
            throw new Exception("returned lead id is 0");
        }
        
        // if the record had no id set, set the id now
        if(empty($_record->id)) {
            $_record->id = $id;
        }
        
        return $this->get($_record->id);
        
    }
    
    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Exception|InvalidArgumentException
     * @return object Record
     */
    public function update(Tinebase_Record_Interface $_record) {
        if (!$_record instanceof $this->_modelName) {
            throw new InvalidArgumentException('$_record is of invalid model type');
        }
        
    	if(!$_record->isValid()) {
            throw new Exception('record object is not valid');
        }
        
        $id = $_record->getId();

        $recordArray = $_record->toArray();
        $tableDefinition = $this->_table->info();
        $recordArray = array_intersect_key($recordArray, array_flip($tableDefinition['cols']));
        
        $where  = array(
            $this->_table->getAdapter()->quoteInto($this->_identifier . ' = ?', $id),
        );
        
        $this->_db->update($this->_tableName, $recordArray, $where);
                
        return $this->get($id);
    }
    
    /**
     * Gets one entry (by id)
     *
     * @throws InvalidArgumentException|UnderflowException
     * @param integer $_id
     */
    public function get($_id) {
        $id = (int) $_id;
        
        if($id != $_id) {
            throw new InvalidArgumentException('$_id must be integer');
        }
        
        $select = $this->_db->select();
        $select->from($this->_tableName)
            ->where($this->_identifier . ' = ?', $_id);
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        
        if (!$queryResult) {
            throw new UnderflowException('Entry with id ' . $id . ' not found');
        }        
        $result = new $this->_modelName($queryResult);
        
        return $result;
    }
    
    /**
     * Get multiple entries
     *
     * @param string|array $_id Ids
     * @return Tinebase_Record_RecordSet
     */
    public function getMultiple($_id) {
    	$resultRecordSet = new Tinebase_Record_RecordSet($this->_modelName);
    	
    	foreach ((array) $_id as $id) {
    		$resultRecordSet->addRecord($this->get($id));
    	}
    	
    	return $resultRecordSet;
    }
    
    /**
      * Deletes entries
      * 
      * @param string|array $_id Ids
      * @return void
      * @throws Exception
      * 
      * @todo Change to deleting only ONE record. "Delete all" style should be removed from backend.
      */
    public function delete($_id) {
    	foreach ((array) $_id as $id) {
	        $where = array(
	            $this->_db->quoteInto($this->_identifier . ' = ?', $id)
	        );
	        
	        $this->_db->delete($this->_tableName, $where);
    	}
    }
    
    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws InvalidArgumentException
     * @return Tinebase_Record_RecordSet
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC') {
        if(in_array($_orderDirection, array('ASC', 'DESC')) === FALSE) {
            throw new InvalidArgumentException('$_orderDirection is invalid');
        }
        
        $select = $this->_db->select();
        $select->from($this->_tableName)
            ->order($_orderBy . ' ' . $_orderDirection);
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        $result = new Tinebase_Record_RecordSet($this->_modelName, $queryResult);
        
        return $result;
    }
}
