<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Sebastian Lenk <s.lenk@metaways.de>
 * @version     $Id$
 * 
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
     * backend type constant
     *
     */
    const TYPE = 'Sql';
    
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
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = FALSE;
    
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
     * the constructor
     *
     * @param string $_tableName
     * @param string $_modelName
     * @param Zend_Db_Adapter_Abstract $_db optional
     */
    public function __construct ($_tableName, $_modelName, $_dbAdapter = NULL)
    {
        $this->_db = ($_dbAdapter instanceof Zend_Db_Adapter_Abstract) ? $_dbAdapter : Tinebase_Core::getDb();
        $this->_tableName = $_tableName;
        $this->_modelName = $_modelName;
    }
    
    /*************************** get/search funcs ************************************/

    /**
     * Gets one entry (by id)
     *
     * @param integer|Tinebase_Record_Interface $_id
     * @param $_getDeleted get deleted records
     * @throws Tinebase_Exception_NotFound
     */
    public function get($_id, $_getDeleted = FALSE) {
        
        $id = $this->_convertId($_id);
        
        $select = $this->_getSelect('*', $_getDeleted);
        $select->where($this->_db->quoteIdentifier($this->_identifier) . ' = ?', $id);

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
                
        if (!$queryResult) {
            throw new Tinebase_Exception_NotFound($this->_modelName . ' record with id ' . $id . ' not found!');
        }
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));        
        $result = new $this->_modelName($queryResult);
               
        // get custom fields
        if ($result->has('customfields')) {
            $this->_getCustomFields($result);
        }
        
        return $result;
    }
    
    /**
     * Get multiple entries
     *
     * @param string|array $_id Ids
     * @return Tinebase_Record_RecordSet
     * 
     * @todo get custom fields here as well
     */
    public function getMultiple($_id) {
        
        if (empty($_id)) {
            return new Tinebase_Record_RecordSet($this->_modelName);
        }

        $select = $this->_getSelect();
        $select->where($this->_db->quoteIdentifier($this->_identifier) . ' in (?)', (array) $_id);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        $result = new Tinebase_Record_RecordSet($this->_modelName, $queryResult, true);
        
        return $result;
    }
    
    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC') {
        if(!in_array($_orderDirection, array('ASC', 'DESC'))) {
            throw new Tinebase_Exception_InvalidArgument('$_orderDirection is invalid');
        }
        
        $select = $this->_getSelect();
        $select->order($_orderBy . ' ' . $_orderDirection);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, true));
        
        $result = new Tinebase_Record_RecordSet($this->_modelName, $queryResult);
        
        return $result;
    }
    
    /**
    * Search for records matching given filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     * 
     * @todo add typehint again when voipmanager refactoring is finished
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)    {
        $result = ($_onlyIds) ? array() : new Tinebase_Record_RecordSet($this->_modelName);
        
        if ($_pagination === NULL) {
            $_pagination = new Tinebase_Model_Pagination();
        }
        
        // build query
        $selectCols = ($_onlyIds) ? 'id' : '*';
        $select = $this->_getSelect($selectCols);
        
        $this->_addFilter($select, $_filter);
        $_pagination->appendPaginationSql($select);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        // get records
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        foreach ($rows as $row) {
            if ($_onlyIds) {
                $result[] = $row[$this->_getRecordIdentifier()];
            } else {
                $record = new $this->_modelName($row, true, true);
                $result->addRecord($record);
            }
        }
        
        return $result;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     * @todo add typehint again when voipmanager refactoring is finished
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {        
        $select = $this->_getSelect(array('count' => 'COUNT(*)'));
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
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_UnexpectedValue
     * 
     * @todo    remove autoincremental ids later
     */
    public function create(Tinebase_Record_Interface $_record) {
    	
    	$identifier = $this->_getRecordIdentifier();
    	
    	if (!$_record instanceof $this->_modelName) {
    		throw new Tinebase_Exception_InvalidArgument('$_record is of invalid model type. Should be instance of ' . $this->_modelName);
    	}
    	
        // set uid if record has hash id and id is empty
    	if ($this->_hasHashId() && empty($_record->$identifier)) {
            $newId = $_record->generateUID();
            $_record->setId($newId);
        }
    	
        $recordArray = $_record->toArray();
        
        // unset id if autoincrement & still empty
        if (empty($_record->$identifier) || $_record->$identifier == 'NULL' ) {
            unset($recordArray['id']);
        }
        
        $tableKeys = $this->_db->describeTable($this->_tableName);
        $recordArray = array_intersect_key($recordArray, $tableKeys);

        $this->_prepareData($recordArray);
        
        $this->_db->insert($this->_tableName, $recordArray);
        
        if (!$this->_hasHashId()) {
            $newId = $this->_db->lastInsertId();
        }

        // if we insert a record without an id, we need to get back one
        if (empty($_record->$identifier) && $newId == 0) {
            throw new Tinebase_Exception_UnexpectedValue("Returned record id is 0.");
        }
        
        // if the record had no id set, set the id now
        if ($_record->$identifier == NULL || $_record->$identifier == 'NULL') {
        	$_record->$identifier = $newId;
        }
        
        // add custom fields
        if ($_record->has('customfields') && !empty($_record->customfields)) {
            $this->_saveCustomFields($_record);
        }
        
        return $this->get($_record->$identifier);
    }
    
    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record) {
        
        $identifier = $this->_getRecordIdentifier();
        
        if (!$_record instanceof $this->_modelName) {
            throw new Tinebase_Exception_InvalidArgument('$_record is of invalid model type');
        }
        
    	if(!$_record->isValid()) {
            throw new Tinebase_Exception_Record_Validation('record object is not valid');
        }
        
        $id = $_record->getId();

        $recordArray = $_record->toArray();
        $tableKeys = $this->_db->describeTable($this->_tableName);
        $recordArray = array_intersect_key($recordArray, $tableKeys);
        
        $this->_prepareData($recordArray);
                
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($identifier) . ' = ?', $id),
        );
        
        $this->_db->update($this->_tableName, $recordArray, $where);
        
        // update custom fields
        if ($_record->has('customfields')) {
            $this->_saveCustomFields($_record);
        }
                
        return $this->get($id, TRUE);
    }
    
    /**
     * Updates multiple entries
     *
     * @param array $_ids to update
     * @param array $_data
     * @return integer number of affected rows
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     */
    public function updateMultiple($_ids, $_data) {

        if (empty($_ids)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No records updated.');
            return;
        }
        $identifier = $this->_getRecordIdentifier();
        
        $recordArray = $_data;
        
        $tableKeys = $this->_db->describeTable($this->_tableName);
        $recordArray = array_intersect_key($recordArray, $tableKeys);
        
        $this->_prepareData($recordArray);
                
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($identifier) . ' IN (?)', $_ids),
        );
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($where, TRUE));
        
        return $this->_db->update($this->_tableName, $recordArray, $where);        
    }
    
    /**
      * Deletes entries
      * 
      * @param string|integer|Tinebase_Record_Interface $_id
      * @return void
      * 
      * @todo   delete custom fields?
      */
    public function delete($_id) {
        $id = $this->_convertId($_id);
        $identifier = $this->_getRecordIdentifier();
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($identifier) . ' = ?', $id)
        );
        
        $this->_db->delete($this->_tableName, $where);
        
        // delete custom fields
        /*
        if ($_record->has('customfields')) {
            $this->_saveCustomFields($_record);
        }
        */
    }
    
    /*************************** other ************************************/
    
    /**
     * returns the db adapter
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDb()
    {
        return $this->_db;
    }
    
    /**
     * get backend type
     *
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }
    
    /*************************** protected helper funcs ************************************/
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     * 
     * @todo    perhaps we need to add the tablename here for filtering with more than 1 table (like that: ->from(array('blabla' => $this->_tableName))
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {        
        $select = $this->_db->select();

        $select->from($this->_tableName, $_cols);
        
        if (!$_getDeleted && $this->_modlogActive) {
            // don't fetch deleted objects
            $select->where($this->_db->quoteIdentifier('is_deleted') . ' = 0');                        
        }
        
        return $select;
    }
    
    
    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select                       $_select current where filter
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter the string to search for
     * @return void
     */
    protected function _addFilter(Zend_Db_Select $_select, /*Tinebase_Model_Filter_FilterGroup */$_filter)
    {
        $_filter->appendFilterSql($_select);
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
            $identifier = $this->_getRecordIdentifier();
        	if(empty($_id->$identifier)) {
                throw new Tinebase_Exception_InvalidArgument('No id set!');
            }
            $id = $_id->$identifier;
        } elseif (is_array($_id)) {
            throw new Tinebase_Exception_InvalidArgument('Id can not be an array!');
        } else {
            $id = $_id;
        }
        
        if($id === 0) {
            throw new Tinebase_Exception_InvalidArgument($this->_modelName . '.id can not be 0!');
        }
        
        return $id;
    }
    
    /**
     * returns true if id is a hash value and false if integer
     *
     * @return  boolean
     * @todo    remove that when all tables use hash ids 
     */
    protected function _hasHashId()
    {
        $fields = $this->_db->describeTable($this->_tableName);
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($fields, true));

        $identifier = $this->_getRecordIdentifier();
        $result = ($fields[$identifier]['DATA_TYPE'] === 'varchar' && $fields[$identifier]['LENGTH'] == 40);
        
        return $result;
    }
    
    /**
     * splits identifier if table name is given (i.e. for joined tables)
     *
     * @return string identifier name
     */
    protected function _getRecordIdentifier()
    {
        if (preg_match("/\./", $this->_identifier)) {
            list($table, $identifier) = explode('.', $this->_identifier);
    	} else {
    		$identifier = $this->_identifier;
    	}
    	
        return $identifier;    
    }

    /**
     * save custom fields of record in its custom fields table
     *
     * @param Tinebase_Record_Interface $_record
     */
    protected function _saveCustomFields(Tinebase_Record_Interface $_record)
    {
        $customFieldsTableName = $this->_tableName . '_' . 'custom';
        
        // delete all custom fields for this record first
        $this->_deleteCustomFields($_record->getId());
        
        // save custom fields
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId();
        $customFields = Tinebase_Config::getInstance()->getCustomFieldsForApplication($applicationId, $this->_modelName)->name;
        foreach ($customFields as $customField) {
            if (!empty($_record->customfields[$customField])) {
                $data = array(
                    'record_id' => $_record->getId(),
                    'name'      => $customField,
                    'value'     => $_record->customfields[$customField]
                );
                $this->_db->insert($customFieldsTableName, $data);
            }
        }
    }

    /**
     * get custom fields and add them to $_record->customfields arraay
     *
     * @param Tinebase_Record_Interface $_record
     */
    protected function _getCustomFields(Tinebase_Record_Interface &$_record)
    {
        $customFieldsTableName = $this->_tableName . '_' . 'custom';

        $select = $this->_db->select()
            ->from($customFieldsTableName)
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('record_id') . ' = ?', $_record->getId()));
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $customFields = array();
        foreach ($rows as $row) {            
            $customFields[$row['name']] = $row['value'];
        }
        $_record->customfields = $customFields; 
    }
    
    /**
     * delete custom fields of record
     *
     * @param string $_recordId
     */
    protected function _deleteCustomFields($_recordId)
    {
        $customFieldsTableName = $this->_tableName . '_' . 'custom';

        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('record_id') . ' = ?', $_recordId)
        );        
        $this->_db->delete($customFieldsTableName, $where);
    }

    /**
     * prepare record data array
     * - replace int and bool values by Zend_Db_Expr
     *
     * @param array &$_recordArray
     * @return array with the prepared data
     */
    protected function _prepareData(&$_recordArray) {
        
        foreach ($_recordArray as $key => $value) {
            if (is_bool($value)) {
                $_recordArray[$key] = ($value) ? new Zend_Db_Expr('1') : new Zend_Db_Expr('0');
            } elseif (is_int($value)) {
                $_recordArray[$key] = new Zend_Db_Expr((string) $value);
            }
        }
    }
}
