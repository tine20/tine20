<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Sebastian Lenk <s.lenk@metaways.de>
 * @version     $Id$
 * 
 * @todo        remove Zend_Db_Table usage 
 */

/**
 * Abstract class for a Tine 2.0 sql backend
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Application_Backend_Sql_Abstract implements Tinebase_Application_Backend_Interface
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
    * Instance of backend
    *
    * @var mixed
    */
    protected $_table;
    
    /*************************** get/search funcs ************************************/

    /**
     * Gets one entry (by id)
     *
     * @throws InvalidArgumentException|UnderflowException
     * @param integer|Tinebase_Record_Interface $_id
     */
    public function get($_id) {
        
        $id = $this->_convertId($_id);
        
        $select = $this->_db->select();
        $select->from($this->_tableName)
            ->where($this->_identifier . ' = ?', $id);
            
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
    
    /**
    * Search for records matching given filter
     *
     * @param Tinebase_Record_Interface $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Record_Interface $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL)
    {
        $set = new Tinebase_Record_RecordSet($this->_modelName);
        
        // empty means, that e.g. no shared containers exist
        if (isset($_filter->container) && count($_filter->container) === 0) {
            return $set;
        }
        
        if ($_pagination === NULL) {
            $_pagination = new Tinebase_Model_Pagination();
        }
        
        // build query
        $select = $this->_getSelect();
        
        if (!empty($_pagination->limit)) {
            $select->limit($_pagination->limit, $_pagination->start);
        }
        if (!empty($_pagination->sort)) {
            $select->order($_pagination->sort . ' ' . $_pagination->dir);
        }        
        $this->_addFilter($select, $_filter);
        
        // get records
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        foreach ($rows as $row) {
            $record = new $this->_modelName($row, true, true);
            $set->addRecord($record);
        }
        
        return $set;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Record_Interface $_filter
     * @return int
     */
    public function searchCount(Tinebase_Record_Interface $_filter)
    {        
        if (isset($_filter->container) && count($_filter->container) === 0) {
            return 0;
        }        
        
        $select = $this->_getSelect(TRUE);
        $this->_addFilter($select, $_filter);
        
        $result = $this->_db->fetchOne($select);
        return $result;        
    }    
        
    /*************************** create / update / delete ****************************/
    
    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  InvalidArgumentException
     * @throws  UnexpectedValueException
     *  
     * @todo add support for unique ids (hashs)
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
        if (empty($_record->id) && $id == 0) {
            throw new UnexpectedValueException("Returned record id is 0.");
        }
        
        // if the record had no id set, set the id now
        if ($_record->id == NULL || $_record->id == 'NULL') {
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
      * Deletes entries
      * 
      * @param string|array $_id Ids
      * @return void
      * @throws Exception
      * 
      * @todo Change to delete only ONE record. "Delete all" style should be removed from backend to controller.
      */
    public function delete($_id) {
    	foreach ((array) $_id as $id) {
	        $where = array(
	            $this->_db->quoteInto($this->_identifier . ' = ?', $id)
	        );
	        
	        $this->_db->delete($this->_tableName, $where);
    	}
    }
    
    /*************************** protected helper funcs ************************************/
    
    /**
     * get the basic select object to fetch records from the database 
     * @param $_getCount only get the count
     *
     * @return Zend_Db_Select
     */
    protected function _getSelect($_getCount = FALSE)
    {        
        $select = $this->_db->select();
        
        if ($_getCount) {
            $select->from($this->_tableName, array('count' => 'COUNT(*)'));    
        } else {
            $select->from($this->_tableName);
        }
        
        return $select;
    }
    
    
    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select           $_select current where filter
     * @param  Tinebase_Record_Interface   $_filter the string to search for
     * @return void
     */
    protected function _addFilter(Zend_Db_Select $_select, Tinebase_Record_Interface $_filter)
    {
    }
    
    /**
     * converts a int, string or Tinebase_Record_Interface to a id
     *
     * @param int|string|Tinebase_Record_Interface $_id the id to convert
     * @return int
     */
    protected function _convertId($_id)
    {
        if($_id instanceof $this->_modelName) {
            if(empty($_id->id)) {
                throw new Exception('No id set!');
            }
            $id = $_id->id;
        } else {
            $id = $_id;
        }
        
        if($id === 0) {
            throw new Exception($this->_modelName . '.id can not be 0!');
        }
        
        return $id;
    }
    
}
