<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        use const for type (set in constructor)
 * @todo        move custom fields handling to controller?
 */

/**
 * Abstract class for a Tine 2.0 sql backend
 * 
 * @package     Tinebase
 * @subpackage  Backend
 */
abstract class Tinebase_Backend_Sql_Abstract extends Tinebase_Backend_Abstract implements Tinebase_Backend_Sql_Interface
{
    /**
     * backend type
     *
     * @var string
     */
    protected $_type = 'Sql';
    
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = NULL;
    
    /**
     * Table prefix
     *
     * @var string
     */
    protected $_tablePrefix = NULL;
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = FALSE;
    
    /**
     * use subselect in searchCount fn
     *
     * @var boolean
     * @todo this should be TRUE by default / need to check if child classes overwrite _getSelect or searchCount()
     */
    protected $_useSubselectForCount = FALSE;
    
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
     * schema of the table
     *
     * @var array
     */
    protected $_schema = NULL;
    
    /**
     * the constructor
     *
     * @param Zend_Db_Adapter_Abstract $_db (optional)
     * @param string $_modelName (optional)
     * @param string $_tableName (optional)
     * @param string $_tablePrefix (optional)
     * @param boolean $_modlogActive (optional)
     * @param boolean $_useSubselectForCount (optional)
     */
    public function __construct ($_dbAdapter = NULL, $_modelName = NULL, $_tableName = NULL, $_tablePrefix = NULL, $_modlogActive = NULL, $_useSubselectForCount = NULL)
    {
        $this->_db = ($_dbAdapter instanceof Zend_Db_Adapter_Abstract) ? $_dbAdapter : Tinebase_Core::getDb();
        $this->_modelName = $_modelName ? $_modelName : $this->_modelName;
        $this->_tableName = $_tableName ? $_tableName : $this->_tableName;
        $this->_tablePrefix = $_tablePrefix ? $_tablePrefix : $this->_db->table_prefix;
        $this->_modlogActive = ($_modlogActive !== NULL) ? $_modlogActive : $this->_modlogActive;
        $this->_useSubselectForCount = ($_useSubselectForCount !== NULL) ? $_useSubselectForCount : $this->_useSubselectForCount;
        
        if (! ($this->_tableName && $this->_modelName)) {
            throw new Tinebase_Exception_Backend('modelName and tableName must be configured or given.');
        }
        if (! $this->_db) {
            throw new Tinebase_Exception_Backend('Database adapter must be configured or given.');
        }
        
        $this->_schema = $this->_db->describeTable($this->_tablePrefix . $this->_tableName);
    }
    
    /*************************** getters and setters *********************************/
    
    /**
     * sets modlog active flag
     * 
     * @param $_bool
     * @return Tinebase_Backend_Sql_Abstract
     */
    public function setModlogActive($_bool)
    {
        $this->_modlogActive = (bool) $_bool;
        return $this;
    }
    
    /**
     * checks if modlog is active or not
     * 
     * @return bool
     */
    public function ModlogActive()
    {
        return $this->_modlogActive;
    }
    
    /*************************** get/search funcs ************************************/

    /**
     * Gets one entry (by id)
     *
     * @param integer|Tinebase_Record_Interface $_id
     * @param $_getDeleted get deleted records
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotFound
     */
    public function get($_id, $_getDeleted = FALSE) 
    {
        $id = $this->_convertId($_id);
        
        return $this->getByProperty($id, $this->_identifier, $_getDeleted);
    }

    /**
     * Gets one entry (by property)
     *
     * @param  mixed  $_value
     * @param  string $_property
     * @param  bool   $_getDeleted
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotFound
     * 
     * @todo move resolveRecordCustomFields to abstract record controller get() fn
     */
    public function getByProperty($_value, $_property = 'name', $_getDeleted = FALSE) 
    {
        $select = $this->_getSelect('*', $_getDeleted);
        $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . $_property) . ' = ?', $_value)
               ->limit(1);

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
                
        if (!$queryResult) {
            throw new Tinebase_Exception_NotFound($this->_modelName . " record with $_property " . $_value . ' not found!');
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));        
        $result = $this->_rawDataToRecord($queryResult);
               
        // get custom fields
        if ($result->has('customfields')) {
            Tinebase_CustomField::getInstance()->resolveRecordCustomFields($result);
        }
        
        return $result;
    }
    
    /**
     * gets multiple entries (by property)
     *
     * @param  mixed  $_value
     * @param  string $_property
     * @param  bool   $_getDeleted
     * @param  string $_orderBy        defaults to $_property
     * @param  string $_orderDirection defaults to 'ASC'
     * @return Tinebase_Record_RecordSet
     */
    public function getMultipleByProperty($_value, $_property='name', $_getDeleted = FALSE, $_orderBy = NULL, $_orderDirection = 'ASC')
    {
        $columnName = $this->_db->quoteIdentifier($this->_tableName . '.' . $_property);
        $value = empty($_value) ? array('') : (array)$_value;
        $orderBy = $this->_tableName . '.' . ($_orderBy ? $_orderBy : $_property);
        
        $select = $this->_getSelect('*', $_getDeleted)
                       ->where($columnName . 'IN (?)', $value)
                       ->order($orderBy . ' ' . $_orderDirection);
        
        $stmt = $this->_db->query($select);
        
        $resultSet = $this->_rawDataToRecordSet($stmt->fetchAll());
        $resultSet->addIndices(array($_property));
        
        return $resultSet;
    }
    
    /**
     * Get multiple entries
     *
     * @param string|array $_id Ids
     * @param array $_containerIds all allowed container ids that are added to getMultiple query
     * @return Tinebase_Record_RecordSet
     * 
     * @todo get custom fields here as well
     */
    public function getMultiple($_id, $_containerIds = NULL) 
    {
        if (empty($_id)) {
            return new Tinebase_Record_RecordSet($this->_modelName);
        }

        $select = $this->_getSelect();
        $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . $this->_identifier) . ' in (?)', (array) $_id);
        
        if ($_containerIds !== NULL && isset($this->_schema['container_id'])) {
            if (empty($_containerIds)) {
                $select->where('1=0 /* insufficient grants */');
            } else {
                $select->where($this->_db->quoteIdentifier($this->_tableName . '.container_id') . ' in (?) /* add acl in getMultiple */', (array) $_containerIds);
            }
        }
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        $result = $this->_rawDataToRecordSet($queryResult);
        
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
    public function getAll($_orderBy = NULL, $_orderDirection = 'ASC') 
    {
        $orderBy = $_orderBy ? $_orderBy : $this->_tableName . '.' . $this->_identifier;
        
        if(!in_array($_orderDirection, array('ASC', 'DESC'))) {
            throw new Tinebase_Exception_InvalidArgument('$_orderDirection is invalid');
        }
        
        $select = $this->_getSelect();
        $select->order($orderBy . ' ' . $_orderDirection);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, true));
        
        $result = $this->_rawDataToRecordSet($queryResult);
        
        return $result;
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
        if ($_pagination === NULL) {
            $_pagination = new Tinebase_Model_Pagination();
        }
        
        // build query
        $selectCols = ($_onlyIds) ? $this->_tableName . '.id' : '*';
        $select = $this->_getSelect($selectCols);
        
        $this->_addFilter($select, $_filter);
        $_pagination->appendPaginationSql($select);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        // get records
        $stmt = $this->_db->query($select);
        $rows = (array)$stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        if ($_onlyIds) {
            $result = array();
            foreach ($rows as $row) {
                $result[] = $row[$this->_getRecordIdentifier()];
            }
        } else {
            $result = $this->_rawDataToRecordSet($rows);
        }
        
        return $result;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {   
        if ($this->_useSubselectForCount) {
            // use normal search query as subselect to get count -> select count(*) from (select [...]) as count
            $select = $this->_getSelect();
            $this->_addFilter($select, $_filter);
            $countSelect = $this->_db->select()->from($select, array('count' => 'COUNT(*)'));
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $countSelect->__toString());
            
            $result = $this->_db->fetchOne($countSelect);
        } else {
            $select = $this->_getSelect(array('count' => 'COUNT(*)'));
            $this->_addFilter($select, $_filter);
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

            $result = $this->_db->fetchOne($select);
        }
        
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
    public function create(Tinebase_Record_Interface $_record) 
    {
    	$identifier = $_record->getIdProperty();
    	
    	if (!$_record instanceof $this->_modelName) {
    		throw new Tinebase_Exception_InvalidArgument('$_record is of invalid model type. Should be instance of ' . $this->_modelName);
    	}
    	
        // set uid if record has hash id and id is empty
    	if ($this->_hasHashId() && empty($_record->$identifier)) {
            $newId = $_record->generateUID();
            $_record->setId($newId);
        }
    	
        $recordArray = $this->_recordToRawData($_record);
        
        // unset id if autoincrement & still empty
        if (empty($_record->$identifier) || $_record->$identifier == 'NULL' ) {
            unset($recordArray['id']);
        }
        
        $recordArray = array_intersect_key($recordArray, $this->_schema);

        $this->_prepareData($recordArray);
        
        $this->_db->insert($this->_tablePrefix . $this->_tableName, $recordArray);
        
        if (!$this->_hasHashId()) {
            $newId = $this->_db->lastInsertId($this->getTablePrefix() . $this->getTableName());
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
            Tinebase_CustomField::getInstance()->saveRecordCustomFields($_record);
        }
        
        $result = $this->get($_record->$identifier);
        $this->_inspectAfterCreate($result, $_record);
        
        return $result;
    }
    
    /**
     * Creates new entry/entries with prepared statement
     *  - perhaps this could be faster than creating new entries with create() but first tests could not prove that
     *  - this has to be further tested and improved or removed if we don't need it 
     *
     * @param   Tinebase_Record_Abstract|Tinebase_Record_RecordSet $_record
     * @return  Tinebase_Record_Abstract|Tinebase_Record_RecordSet
     * @throws  Tinebase_Exception_InvalidArgument
     * 
     * @todo    check if we need this
     * @todo    support custom fields
     */
    public function createPrepared($_records) 
    {
        // only do this for records with hash ids
        if (! $this->_hasHashId()) {
            throw new Tinebase_Exception_InvalidArgument('Autoincremental ids are not supported (yet).');
        } 
        
        // sanitize param
        if ($_records instanceof Tinebase_Record_Abstract) {
            $records = new Tinebase_Record_RecordSet($this->_modelName);
            $records->addRecord($_records);
            $single = TRUE;
        } else if (! $_records instanceof Tinebase_Record_RecordSet) {
            throw new Tinebase_Exception_InvalidArgument('Recordset or single Record expected');
        } else if (count($_records) == 0) {
            return $_records;
        } else {
            $records = $_records;
            $single = FALSE;
        }
        
        // use first record to determine fields (sorted by fieldname) and quote identifiers
        $first = $records->getFirstRecord();
        $identifier = $first->getIdProperty();
        $firstRecordArray = array_intersect_key($this->_recordToRawData($first), $this->_schema);
        if (! array_key_exists($first->getIdProperty(), $firstRecordArray)) {
            $firstRecordArray[$identifier] = '';
        }
        ksort($firstRecordArray);
        $fields = array_keys($firstRecordArray);
        foreach ($fields as &$field) {
            $field = $this->_db->quoteIdentifier($field);
        }
        $placeholders = array_fill(0, count($fields), '?'); 
        
        $stmt = $this->_db->prepare('INSERT INTO ' . SQL_TABLE_PREFIX . $this->_tableName 
            . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')'
        );
        
        // insert records
        $ids = array();
        foreach ($records as $record) {
            $recordArray = array_intersect_key($this->_recordToRawData($record), $this->_schema);
            if (! array_key_exists($first->getIdProperty(), $recordArray) || empty($recordArray[$identifier])) {
                // add identifier
                $recordArray[$identifier] = $record->generateUID();
                $record->setId($recordArray[$identifier]);
            }
            
            // sort data and execute!
            ksort($recordArray);
            if (array_keys($recordArray) === array_keys($firstRecordArray)) {
                $stmt->execute(array_values($recordArray));
                $ids[] = $recordArray[$identifier];
            } else {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Fields mismatch.');
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r(array_keys($firstRecordArray), TRUE));
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r(array_keys($recordArray), TRUE));
            }
        }
        
        // get and return inserted record(s)
        if ($single) {
            $result = $this->get($ids[0]);
        } else {
            $result = $this->getMultiple($ids);
        }
        return $result;
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
        
        $identifier = $_record->getIdProperty();
        
        if (!$_record instanceof $this->_modelName) {
            throw new Tinebase_Exception_InvalidArgument('$_record is of invalid model type');
        }
        
    	$_record->isValid(TRUE);
        
        $id = $_record->getId();

        $recordArray = $this->_recordToRawData($_record);
        $recordArray = array_intersect_key($recordArray, $this->_schema);
        
        $this->_prepareData($recordArray);
                
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($identifier) . ' = ?', $id),
        );
        
        $this->_db->update($this->_tablePrefix . $this->_tableName, $recordArray, $where);
        
        // update custom fields
        if ($_record->has('customfields')) {
            Tinebase_CustomField::getInstance()->saveRecordCustomFields($_record);
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
    public function updateMultiple($_ids, $_data) 
    {

        if (empty($_ids)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No records updated.');
            return 0;
        }
        $identifier = $this->_getRecordIdentifier();
        
        $recordArray = $_data;
        $recordArray = array_intersect_key($recordArray, $this->_schema);
        
        $this->_prepareData($recordArray);
                
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($identifier) . ' IN (?)', $_ids),
        );
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($where, TRUE));
        
        return $this->_db->update($this->_tablePrefix . $this->_tableName, $recordArray, $where);        
    }
    
    /**
      * Deletes entries
      * 
      * @param string|integer|Tinebase_Record_Interface|array $_id
      * @return void
      * 
      * @todo   delete custom fields?
      */
    public function delete($_id) 
    {
        if (is_array($_id)) {
            foreach ($_id as $id) {
                $this->delete($id);
            }
            return;
        }
        
        $id = $this->_convertId($_id);
        $identifier = $this->_getRecordIdentifier();
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($identifier) . ' = ?', $id)
        );
        
        $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }
    
    /**
     * delete rows by property
     * 
     * @param string|array $_value
     * @param string $_property
     * @param string $_operator (equals|in)
     * @return integer The number of affected rows.
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function deleteByProperty($_value, $_property, $_operator = 'equals')
    {
        if (! array_key_exists($_property, $this->_schema)) {
            throw new Tinebase_Exception_InvalidArgument('Property ' . $_property . ' does not exist in table ' . $this->_tableName);
        }
        
        switch ($_operator) {
            case 'equals':
                $op = ' = ?';
                break;
            case 'in':
                $op = ' IN (?)';
                $_value = (array) $_value; 
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('Invalid operator: ' . $_operator);
        }
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($_property) . $op, $_value)
        );
        
        return $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }
    
    /*************************** foreign record fetchers *******************************/
    
    /**
     * appends foreign record (1:1 relation) to given record
     *
     * @param Tinebase_Record_Abstract      $_record            Record to append the foreign record to
     * @param string                        $_appendTo          Property in the record where to append the foreign record to
     * @param string                        $_recordKey         Property in the record where the foreign key value is in
     * @param string                        $_foreignKey        Key property in foreign table of the record to append
     * @param Tinebase_Backend_Sql_Abstract $_foreignBackend    Foreign table backend 
     */
    public function appendForeignRecordToRecord($_record, $_appendTo, $_recordKey, $_foreignKey, $_foreignBackend)
    {
        try { 
            $_record->$_appendTo = $_foreignBackend->getByProperty($_record->$_recordKey, $_foreignKey);
        } catch (Tinebase_Exception_NotFound $e) {
            $_record->$_appendTo = NULL;
        }
    }
    
    /**
     * appends foreign recordSet (1:n relation) to given record
     *
     * @param Tinebase_Record_Abstract      $_record            Record to append the foreign records to
     * @param string                        $_appendTo          Property in the record where to append the foreign records to
     * @param string                        $_recordKey         Property in the record where the foreign key value is in
     * @param string                        $_foreignKey        Key property in foreign table of the records to append
     * @param Tinebase_Backend_Sql_Abstract $_foreignBackend    Foreign table backend 
     */
    public function appendForeignRecordSetToRecord($_record, $_appendTo, $_recordKey, $_foreignKey, $_foreignBackend)
    {
        $_record->$_appendTo = $_foreignBackend->getMultipleByProperty($_record->$_recordKey, $_foreignKey);
    }
    
    /**
     * appends foreign record (1:1/n:1 relation) to given recordSet
     *
     * @param Tinebase_Record_RecordSet     $_recordSet         Records to append the foreign record to
     * @param string                        $_appendTo          Property in the records where to append the foreign record to
     * @param string                        $_recordKey         Property in the records where the foreign key value is in
     * @param string                        $_foreignKey        Key property in foreign table of the record to append
     * @param Tinebase_Backend_Sql_Abstract $_foreignBackend    Foreign table backend 
     */
    public function appendForeignRecordToRecordSet($_recordSet, $_appendTo, $_recordKey, $_foreignKey, $_foreignBackend)
    {
        $allForeignRecords = $_foreignBackend->getMultipleByProperty($_recordSet->$_recordKey, $_foreignKey);
        foreach ($_recordSet as $record) {
            $record->$_appendTo = $allForeignRecords->filter($_foreignKey, $record->$_recordKey)->getFirstRecord();
        }
    }
    
    /**
     * appends foreign recordSet (1:n/m:n relation) to given recordSet
     *
     * @param Tinebase_Record_RecordSet     $_recordSet         Records to append the foreign records to
     * @param string                        $_appendTo          Property in the records where to append the foreign records to
     * @param string                        $_recordKey         Property in the records where the foreign key value is in
     * @param string                        $_foreignKey        Key property in foreign table of the records to append
     * @param Tinebase_Backend_Sql_Abstract $_foreignBackend    Foreign table backend 
     */
    public function appendForeignRecordSetToRecordSet($_recordSet, $_appendTo, $_recordKey, $_foreignKey, $_foreignBackend)
    {
        $allForeignRecords = $_foreignBackend->getMultipleByProperty($_recordSet->$_recordKey, $_foreignKey);
        foreach ($_recordSet as $record) {
            $record->$_appendTo = $allForeignRecords->filter($_foreignKey, $record->$_recordKey);
        }
    }
    
    /*************************** other ************************************/
    
    /**
     * get table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->_tableName;
    }
    
    /**
     * get table prefix
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->_tablePrefix;
    }
    
    /**
     * get db adapter
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        return $this->_db;
    }
    
    /*************************** protected helper funcs ************************************/
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     * 
     * @todo think about adding custom fields here
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {        
        $select = $this->_db->select();

        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName), $_cols);
        
        if (!$_getDeleted && $this->_modlogActive) {
            // don't fetch deleted objects
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.is_deleted') . ' = 0');                        
        }
        
        return $select;
    }
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Abstract $_record
     * @return array
     */
    protected function _recordToRawData($_record)
    {
        return $_record->toArray();
    }
    
    /**
     * converts raw data from adapter into a single record
     *
     * @param  array $_rawData
     * @return Tinebase_Record_Abstract
     */
    protected function _rawDataToRecord(array $_rawData)
    {
        return new $this->_modelName($_rawData, true);
    }
    
    /**
     * converts raw data from adapter into a set of records
     *
     * @param  array $_rawDatas of arrays
     * @return Tinebase_Record_RecordSet
     */
    protected function _rawDataToRecordSet(array $_rawDatas)
    {
        return new Tinebase_Record_RecordSet($this->_modelName, $_rawDatas, true);
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
        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($_select, $_filter, $this);
        //$_filter->appendFilterSql($_select);
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
        $identifier = $this->_getRecordIdentifier();
        $result = (in_array($this->_schema[$identifier]['DATA_TYPE'], array('varchar', 'VARCHAR2')) && $this->_schema[$identifier]['LENGTH'] == 40);
        
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
     * prepare record data array
     * - replace int and bool values by Zend_Db_Expr
     *
     * @param array &$_recordArray
     * @return array with the prepared data
     */
    protected function _prepareData(&$_recordArray) 
    {
        
        foreach ($_recordArray as $key => $value) {
            if (is_bool($value)) {
                $_recordArray[$key] = ($value) ? new Zend_Db_Expr('1') : new Zend_Db_Expr('0');
            } elseif (is_int($value)) {
                $_recordArray[$key] = new Zend_Db_Expr((string) $value);
            }
        }
    }
    
    /**
     * do something after creation of record
     * 
     * @param Tinebase_Record_Abstract $_newRecord
     * @param Tinebase_Record_Abstract $_recordToCreate
     * @return void
     */
    protected function _inspectAfterCreate(Tinebase_Record_Abstract $_newRecord, Tinebase_Record_Abstract $_recordToCreate)
    {
    }
}
