<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        think about removing the appendForeignRecord* functions
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
     * placeholder for id column for search()/_getSelect()
     */
    const IDCOL             = '_id_';
    
    /**
     * fetch single column with db query
     */
    const FETCH_MODE_SINGLE = 'fetch_single';

    /**
     * fetch two columns (id + X) with db query
     */
    const FETCH_MODE_PAIR   = 'fetch_pair';
    
    /**
     * fetch all columns with db query
     */
    const FETCH_ALL         = 'fetch_all';

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
     * @var Tinebase_Backend_Sql_Command_Interface
     */
    protected $_dbCommand;
    
    /**
     * schema of the table
     *
     * @var array
     */
    protected $_schema = NULL;
    
    /**
     * foreign tables 
     * name => array(table, joinOn, field)
     *
     * @var array
     */
    protected $_foreignTables = array();
    
    /**
     * additional search count columns
     * 
     * @var array
     */
    protected $_additionalSearchCountCols = array();
    
    /**
     * default secondary sort criteria
     * 
     * @var string
     */
    protected $_defaultSecondarySort = NULL;
    
    /**
     * default column(s) for count
     * 
     * @var string
     */
    protected $_defaultCountCol = '*';
    
    /**
     * the constructor
     * 
     * allowed options:
     *  - modelName
     *  - tableName
     *  - tablePrefix
     *  - modlogActive
     *  
     * @param Zend_Db_Adapter_Abstract $_db (optional)
     * @param array $_options (optional)
     * @throws Tinebase_Exception_Backend_Database
     */
    public function __construct($_dbAdapter = NULL, $_options = array())
    {
        $this->_db        = ($_dbAdapter instanceof Zend_Db_Adapter_Abstract) ? $_dbAdapter : Tinebase_Core::getDb();
        $this->_dbCommand = Tinebase_Backend_Sql_Command::factory($this->_db);
        
        $this->_modelName            = (isset($_options['modelName']) || array_key_exists('modelName', $_options))            ? $_options['modelName']    : $this->_modelName;
        $this->_tableName            = (isset($_options['tableName']) || array_key_exists('tableName', $_options))            ? $_options['tableName']    : $this->_tableName;
        $this->_tablePrefix          = (isset($_options['tablePrefix']) || array_key_exists('tablePrefix', $_options))          ? $_options['tablePrefix']  : $this->_db->table_prefix;
        $this->_modlogActive         = (isset($_options['modlogActive']) || array_key_exists('modlogActive', $_options))         ? $_options['modlogActive'] : $this->_modlogActive;
        
        if (! ($this->_tableName && $this->_modelName)) {
            throw new Tinebase_Exception_Backend_Database('modelName and tableName must be configured or given.');
        }
        if (! $this->_db) {
            throw new Tinebase_Exception_Backend_Database('Database adapter must be configured or given.');
        }
        
        try {
            $this->_schema = Tinebase_Db_Table::getTableDescriptionFromCache($this->_tablePrefix . $this->_tableName, $this->_db);
        } catch (Zend_Db_Adapter_Exception $zdae) {
            throw new Tinebase_Exception_Backend_Database('Connection failed: ' . $zdae->getMessage());
        }
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
    public function getModlogActive()
    {
        return $this->_modlogActive;
    }
    
    /**
     * returns the db schema
     * 
     * @return array
     */
    public function getSchema()
    {
        return $this->_schema;
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
        if (empty($_id)) {
            throw new Tinebase_Exception_NotFound('$_id can not be empty');
        }
        
        $id = Tinebase_Record_Abstract::convertId($_id, $this->_modelName);
        
        return $this->getByProperty($id, $this->_identifier, $_getDeleted);
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
     * Gets one entry (by property)
     *
     * @param  mixed  $value
     * @param  string $property
     * @param  bool   $getDeleted
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotFound
     */
    public function getByProperty($value, $property = 'name', $getDeleted = FALSE) 
    {
        $select = $this->_getSelect('*', $getDeleted)
            ->limit(1);
        
        if ($value !== NULL) {
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . $property) . ' = ?', $value);
        } else {
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . $property) . ' IS NULL');
        }
        
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
        
        if (!$queryResult) {
            $messageValue = ($value !== NULL) ? $value : 'NULL';
            throw new Tinebase_Exception_NotFound($this->_modelName . " record with $property = $messageValue not found!");
        }
        
        $result = $this->_rawDataToRecord($queryResult);
        
        return $result;
    }
    
    /**
     * fetch a single property for all records defined in array of $ids
     * 
     * @param array|string $ids
     * @param string $property
     * @return array (key = id, value = property value)
     */
    public function getPropertyByIds($ids, $property)
    {
        $select = $this->_getSelect(array($property, $this->_identifier));
        $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . $this->_identifier) . ' IN (?)', (array) $ids);
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        $this->_checkTracing($select);
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        $stmt->closeCursor();
        
        $result = array();
        foreach($queryResult as $row) {
            $result[$row[$this->_identifier]] = $row[$property];
        }
        return $result;
    }
    
    /**
     * converts raw data from adapter into a single record
     *
     * @param  array $_rawData
     * @return Tinebase_Record_Abstract
     */
    protected function _rawDataToRecord(array $_rawData)
    {
        $result = new $this->_modelName($_rawData, true);
        
        $this->_explodeForeignValues($result);
        
        return $result;
    }
    
    /**
     * explode foreign values
     * 
     * @param Tinebase_Record_Interface $_record
     */
    protected function _explodeForeignValues(Tinebase_Record_Interface $_record)
    {
        foreach (array_keys($this->_foreignTables) as $field) {
            $isSingleValue = ((isset($this->_foreignTables[$field]['singleValue']) || array_key_exists('singleValue', $this->_foreignTables[$field])) && $this->_foreignTables[$field]['singleValue']);
            if (! $isSingleValue) {
                $_record->{$field} = (! empty($_record->{$field})) ? explode(',', $_record->{$field}) : array();
            }
        }
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
        if (! empty($_value)) {
            $value = (array)$_value;
            $orderBy = $this->_tableName . '.' . ($_orderBy ? $_orderBy : $_property);

            $select = $this->_getSelect('*', $_getDeleted)
                ->where($columnName . 'IN (?)', $value)
                ->order($orderBy . ' ' . $_orderDirection);

                Tinebase_Backend_Sql_Abstract::traitGroup($select);
        } else {
                $select = $this->_getSelect('*', $_getDeleted)->where('1=0');
        }
        
        $this->_checkTracing($select);
        
        $stmt = $this->_db->query($select);
        
        $resultSet = $this->_rawDataToRecordSet($stmt->fetchAll());
        $resultSet->addIndices(array($_property));
        
        return $resultSet;
    }
    
    /**
     * converts raw data from adapter into a set of records
     *
     * @param  array $_rawDatas of arrays
     * @return Tinebase_Record_RecordSet
     */
    protected function _rawDataToRecordSet(array $_rawDatas)
    {
        $result = new Tinebase_Record_RecordSet($this->_modelName, $_rawDatas, true);
        
        if (! empty($this->_foreignTables)) {
            foreach ($result as $record) {
                $this->_explodeForeignValues($record);
            }
        }
        
        return $result;
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
        // filter out any emtpy values
        $ids = array_filter((array) $_id, function($value) {
            return !empty($value);
        });
        
        if (empty($ids)) {
            return new Tinebase_Record_RecordSet($this->_modelName);
        }

        // replace objects with their id's
        foreach ($ids as &$id) {
            if ($id instanceof Tinebase_Record_Interface) {
                $id = $id->getId();
            }
        }
        
        $select = $this->_getSelect();
        $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . $this->_identifier) . ' IN (?)', $ids);
        
        if ($_containerIds !== NULL && isset($this->_schema['container_id'])) {
            if (empty($_containerIds)) {
                $select->where('1=0 /* insufficient grants */');
            } else {
                $select->where($this->_db->quoteIdentifier($this->_tableName . '.container_id') . ' IN (?) /* add acl in getMultiple */', (array) $_containerIds);
            }
        }
        
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        $this->_checkTracing($select);
        
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
        
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, true));
        
        $result = $this->_rawDataToRecordSet($queryResult);
        
        return $result;
    }
    
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @param  array|string|boolean                 $_cols columns to get, * per default / use self::IDCOL or TRUE to get only ids
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_cols = '*')
    {
        if ($_pagination === NULL) {
            $_pagination = new Tinebase_Model_Pagination(NULL, TRUE);
        }
        
        // legacy: $_cols param was $_onlyIds (boolean) ...
        if ($_cols === TRUE) {
            $_cols = self::IDCOL;
        } else if ($_cols === FALSE) {
            $_cols = '*';
        }
        
        // (1) get ids or id/value pair
        list($colsToFetch, $getIdValuePair) = $this->_getColumnsToFetch($_cols, $_filter, $_pagination);
        $select = $this->_getSelect($colsToFetch);
        if ($_filter !== NULL) {
            $this->_addFilter($select, $_filter);
        }
        $this->_addSecondarySort($_pagination);
        $_pagination->appendPaginationSql($select);

        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        if ($getIdValuePair) {
            return $this->_fetch($select, self::FETCH_MODE_PAIR);
        } else {
            $ids = $this->_fetch($select);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Fetched ' . count($ids) .' ids.');
        
        if ($_cols === self::IDCOL) {
            return $ids;
        } else if (empty($ids)) {
            return new Tinebase_Record_RecordSet($this->_modelName);
        } else {
            // (2) get other columns and do joins
            $select = $this->_getSelect($_cols);
            $this->_addWhereIdIn($select, $ids);
            $_pagination->appendSort($select);
            
            $rows = $this->_fetch($select, self::FETCH_ALL);
            
            return $this->_rawDataToRecordSet($rows);
        }
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
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int|array
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        $defaultCountCol = $this->_defaultCountCol == '*' ?  '*' : $this->_db->quoteIdentifier($this->_defaultCountCol);
        
        $searchCountCols = array('count' => 'COUNT(' . $defaultCountCol . ')');
        foreach ($this->_additionalSearchCountCols as $column => $select) {
            $searchCountCols['sum_' . $column] = new Zend_Db_Expr('SUM(' . $this->_db->quoteIdentifier($column) . ')');
        }
        
        list($subSelectColumns, $getIdValuePair) = $this->_getColumnsToFetch(self::IDCOL, $_filter);
        if (!empty($this->_additionalSearchCountCols)) {
            $subSelectColumns = array_merge($subSelectColumns, $this->_additionalSearchCountCols);
        }
        
        $subSelect = $this->_getSelect($subSelectColumns);
        $this->_addFilter($subSelect, $_filter);
        
        Tinebase_Backend_Sql_Abstract::traitGroup($subSelect);
        
        $countSelect = $this->_db->select()->from($subSelect, $searchCountCols);
        
        if (!empty($this->_additionalSearchCountCols)) {
            $result = $this->_db->fetchRow($countSelect);
        } else {
            $result = $this->_db->fetchOne($countSelect);
        }
        
        return $result;
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
        $getIdValuePair = FALSE;

        if ($_cols === '*') {
            $colsToFetch = array('id' => self::IDCOL);
        } else {
            $colsToFetch = (array) $_cols;
            
            if (in_array(self::IDCOL, $colsToFetch) && count($colsToFetch) == 2) {
                // id/value pair requested
                $getIdValuePair = TRUE;
            } else if (! in_array(self::IDCOL, $colsToFetch) && count($colsToFetch) == 1) {
                // only one non-id column was requested -> add id and treat it like id/value pair
                array_push($colsToFetch, self::IDCOL);
                $getIdValuePair = TRUE;
            } else {
                $colsToFetch = array('id' => self::IDCOL);
            }
        }
        
        if ($_filter !== NULL) {
            $colsToFetch = $this->_addFilterColumns($colsToFetch, $_filter);
        }
        
        if ($_pagination instanceof Tinebase_Model_Pagination) {
            foreach((array) $_pagination->sort as $sort) {
                if (! (isset($colsToFetch[$sort]) || array_key_exists($sort, $colsToFetch))) {
                    $colsToFetch[$sort] = (substr_count($sort, $this->_tableName) === 0) ? $this->_tableName . '.' . $sort : $sort;
                }
            }
        }
        
        return array($colsToFetch, $getIdValuePair);
    }
    
    /**
     * add columns from filter
     * 
     * @param array $_colsToFetch
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return array
     */
    protected function _addFilterColumns($_colsToFetch, Tinebase_Model_Filter_FilterGroup $_filter)
    {
        // need to ask filter if it needs additional columns
        $filterCols = $_filter->getRequiredColumnsForSelect();
        foreach ($filterCols as $key => $filterCol) {
            if (! (isset($_colsToFetch[$key]) || array_key_exists($key, $_colsToFetch))) {
                $_colsToFetch[$key] = $filterCol;
            }
        }
        
        return $_colsToFetch;
    }
    
    /**
     * add default secondary sort criteria
     * 
     * @param Tinebase_Model_Pagination $_pagination
     */
    protected function _addSecondarySort(Tinebase_Model_Pagination $_pagination)
    {
        if (! empty($this->_defaultSecondarySort)) {
            if (! is_array($_pagination->sort) || ! in_array($this->_defaultSecondarySort, $_pagination->sort)) {
                $_pagination->sort = array_merge((array)$_pagination->sort, array($this->_defaultSecondarySort));
            }
        }
    }
    
    /**
     * adds 'id in (...)' where stmt
     * 
     * @param Zend_Db_Select $_select
     * @param string|array $_ids
     * @return Zend_Db_Select
     */
    protected function _addWhereIdIn(Zend_Db_Select $_select, $_ids)
    {
        $_select->where($this->_db->quoteInto($this->_db->quoteIdentifier($this->_tableName . '.' . $this->_identifier) . ' in (?)', (array) $_ids));
        
        return $_select;
    }
    
    /**
     * Checks if backtrace and query should be logged
     * 
     * For enabling this feature, you must add a key in config.inc.php:
     * 
     *     'logger' => 
     *         array(
     *             // logger stuff
     *             'traceQueryOrigins' => true,
     *             'priority' => 8
     *         ),
     *
     * @param Zend_Db_Select $select
     */
    protected function _checkTracing(Zend_Db_Select $select)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE) && $config = Tinebase_Core::getConfig()->logger) {
            if ($config->traceQueryOrigins) {
                $e = new Exception();
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "\n" . 
                    "BACKTRACE: \n" . $e->getTraceAsString() . "\n" . 
                    "SQL QUERY: \n" . $select);
            }
        }
    }
    
    /**
     * fetch rows from db
     * 
     * @param Zend_Db_Select $_select
     * @param string $_mode
     * @return array
     */
    protected function _fetch(Zend_Db_Select $_select, $_mode = self::FETCH_MODE_SINGLE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $_select->__toString());
        
        Tinebase_Backend_Sql_Abstract::traitGroup($_select);
        
        $this->_checkTracing($_select);
        
        $stmt = $this->_db->query($_select);
        
        if ($_mode === self::FETCH_ALL) {
            $result = (array) $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        } else {
            $result = array();
            while ($row = $stmt->fetch(Zend_Db::FETCH_NUM)) {
                if ($_mode === self::FETCH_MODE_SINGLE) {
                    $result[] = $row[0];
                } else if ($_mode === self::FETCH_MODE_PAIR) {
                    $result[$row[0]] = $row[1];
                }
            }
        }
        
        return $result;
    }
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        if ($_cols !== '*' ) {
            $cols = array();
            // make sure cols is an array, prepend tablename and fix keys
            foreach ((array) $_cols as $id => $col) {
                $key = (is_numeric($id)) ? ($col === self::IDCOL) ? $this->_identifier : $col : $id;
                $cols[$key] = ($col === self::IDCOL) ? $this->_tableName . '.' . $this->_identifier : $col;
            }
        } else {
            $cols = '*';
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($cols, TRUE));
        
        $select = $this->getAdapter()->select();
        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName), $cols);
        
        if (!$_getDeleted && $this->_modlogActive) {
            // don't fetch deleted objects
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.is_deleted') . ' = 0');
        }
        
        $this->_addForeignTableJoins($select, $cols);
        
        return $select;
    }
    
    /**
     * add foreign table joins
     * 
     * @param Zend_Db_Select $_select
     * @param array|string $_cols columns to get, * per default
     * 
     * @todo joining the same table twice with same name but different "on"'s is not possible currently
     */
    protected function _addForeignTableJoins(Zend_Db_Select $_select, $_cols, $_groupBy = NULL)
    {
        if (! empty($this->_foreignTables)) {
            $groupBy = ($_groupBy !== NULL) ? $_groupBy : $this->_tableName . '.' . $this->_identifier;
            $_select->group($groupBy);
            
            $cols = (array) $_cols;
            foreach ($this->_foreignTables as $foreignColumn => $join) {
                // only join if field is in cols
                if (in_array('*', $cols) || (isset($cols[$foreignColumn]) || array_key_exists($foreignColumn, $cols))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' foreign column: ' . $foreignColumn);
                    
                    $selectArray = ((isset($join['select']) || array_key_exists('select', $join)))
                        ? $join['select'] 
                        : (((isset($join['field']) || array_key_exists('field', $join)) && (! (isset($join['singleValue']) || array_key_exists('singleValue', $join)) || ! $join['singleValue']))
                            ? array($foreignColumn => $this->_dbCommand->getAggregate($join['table'] . '.' . $join['field']))
                            : array($foreignColumn => $join['table'] . '.id'));
                    $joinId = isset($join['joinId']) ? $join['joinId'] : $this->_identifier;
                    
                    // avoid duplicate columns => will be added again in the next few lines of code
                    $this->_removeColFromSelect($_select, $foreignColumn);
                    
                    $from = $_select->getPart(Zend_Db_Select::FROM);
                    
                    if (!isset($from[$join['table']])) {
                        $_select->joinLeft(
                            /* table  */ array($join['table'] => $this->_tablePrefix . $join['table']), 
                            /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.' . $joinId) . ' = ' . $this->_db->quoteIdentifier($join['table'] . '.' . $join['joinOn']),
                            /* select */ $selectArray
                        );
                    } else {
                        // join is defined already => just add the column
                        $_select->columns($selectArray, $join['table']);
                    }
                }
            }
        }
    }
    
    /**
     * remove column from select to avoid duplicates 
     * 
     * @param Zend_Db_Select $_select
     * @param string $_column
     * @todo remove $_cols parameter
     */
    protected function _removeColFromSelect(Zend_Db_Select $_select, $_column)
    {
        $columns = $_select->getPart(Zend_Db_Select::COLUMNS);
        
        foreach ($columns as $id => $column) {
            if ($column[2] == $_column) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
                    __METHOD__ . '::' . __LINE__ . ' Removing ' . $_column . ' from columns.');
                
                unset($columns[$id]);
                
                // reset all all columns and add as again
                $_select->reset(Zend_Db_Select::COLUMNS);
                foreach ($columns as $newColumn) {
                    $_select->columns(!empty($newColumn[2]) ? array($newColumn[2] => $newColumn[1]) : $newColumn[1], $newColumn[0]);
                }
                
                break;
            }
        }
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
            throw new Tinebase_Exception_InvalidArgument('invalid model type: $_record is instance of "' . get_class($_record) . '". but should be instance of ' . $this->_modelName);
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
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . " Prepared data for INSERT: " . print_r($recordArray, true)
        );
        
        $this->_db->insert($this->_tablePrefix . $this->_tableName, $recordArray);
        
        if (!$this->_hasHashId()) {
            $newId = $this->_db->lastInsertId($this->getTablePrefix() . $this->getTableName(), $identifier);
            if(!$newId && isset($_record[$identifier])){
                $newId = $_record[$identifier];
            }
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
        
        $this->_updateForeignKeys('create', $_record);
        
        $result = $this->get($_record->$identifier);
        
        $this->_inspectAfterCreate($result, $_record);
        
        return $result;
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
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Abstract $_record
     * @return array
     */
    protected function _recordToRawData($_record)
    {
        $readOnlyFields = $_record->getReadOnlyFields();
        $raw = $_record->toArray(FALSE);
        foreach ($raw as $key => $value) {
            if ($value instanceof Tinebase_Record_Interface) {
                $raw[$key] = $value->getId();
            }
            if (in_array($key, $readOnlyFields)) {
                unset($raw[$key]);
            }
        }
        
        return $raw;
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
            } elseif (is_null($value)) {
                $_recordArray[$key] = new Zend_Db_Expr('NULL');
            } elseif (is_int($value)) {
                $_recordArray[$key] = new Zend_Db_Expr((string) $value);
            }
        }
    }
    
    /**
     * update foreign key values
     * 
     * @param string $_mode create|update
     * @param Tinebase_Record_Abstract $_record
     */
    protected function _updateForeignKeys($_mode, Tinebase_Record_Abstract $_record)
    {
        if (! empty($this->_foreignTables)) {
            
            foreach ($this->_foreignTables as $modelName => $join) {
                
                if (! (isset($join['field']) || array_key_exists('field', $join))) {
                    continue;
                }
                
                $idsToAdd    = array();
                $idsToRemove = array();
                
                if (!empty($_record->$modelName)) {
                    $idsToAdd = $this->_getIdsFromMixed($_record->$modelName);
                }
                
                $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                
                if ($_mode == 'update') {
                    $select = $this->_db->select();
        
                    $select->from(array($join['table'] => $this->_tablePrefix . $join['table']), array($join['field']))
                        ->where($this->_db->quoteIdentifier($join['table'] . '.' . $join['joinOn']) . ' = ?', $_record->getId());
                    
                    Tinebase_Backend_Sql_Abstract::traitGroup($select);
                    
                    $stmt = $this->_db->query($select);
                    $currentIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                    $stmt->closeCursor();
                    
                    $idsToRemove = array_diff($currentIds, $idsToAdd);
                    $idsToAdd    = array_diff($idsToAdd, $currentIds);
                }
                
                if (!empty($idsToRemove)) {
                    $where = '(' . 
                        $this->_db->quoteInto($this->_db->quoteIdentifier($this->_tablePrefix . $join['table'] . '.' . $join['joinOn']) . ' = ?', $_record->getId()) .
                        ' AND ' . 
                        $this->_db->quoteInto($this->_db->quoteIdentifier($this->_tablePrefix . $join['table'] . '.' . $join['field']) . ' IN (?)', $idsToRemove) .
                    ')';
                        
                    $this->_db->delete($this->_tablePrefix . $join['table'], $where);
                }
                
                foreach ($idsToAdd as $id) {
                    $recordArray = array (
                        $join['joinOn'] => $_record->getId(),
                        $join['field']  => $id
                    );
                    $this->_db->insert($this->_tablePrefix . $join['table'], $recordArray);
                }
                    
                
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            }
        }
    }

    /**
     * convert recordset, array of ids or records to array of ids
     * 
     * @param  mixed  $_mixed
     * @return array
     */
    protected function _getIdsFromMixed($_mixed)
    {
        if ($_mixed instanceof Tinebase_Record_RecordSet) { // Record set
            $ids = $_mixed->getArrayOfIds();
            
        } elseif (is_array($_mixed)) { // array
            foreach ($_mixed as $mixed) {
                if ($mixed instanceof Tinebase_Record_Abstract) {
                    $ids[] = $mixed->getId();
                } else {
                    $ids[] = $mixed;
                }
            }
            
        } else { // string
            $ids[] = $_mixed instanceof Tinebase_Record_Abstract ? $_mixed->getId() : $_mixed;
        }
        
        return $ids;
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
            throw new Tinebase_Exception_InvalidArgument('invalid model type: $_record is instance of "' . get_class($_record) . '". but should be instance of ' . $this->_modelName);
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
        
        $this->_updateForeignKeys('update', $_record);
        
        $result = $this->get($id, true);
        
        return $result;
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
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No records updated.');
            return 0;
        }
        
        // separate CustomFields
        
        $myFields = array();
        $customFields = array();
        
        foreach($_data as $key => $value) {
            if(stristr($key, '#')) $customFields[substr($key,1)] = $value;
            else $myFields[$key] = $value;
        }
        
        // handle CustomFields
        
        if(count($customFields)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' CustomFields found.');
            Tinebase_CustomField::getInstance()->saveMultipleCustomFields($this->_modelName, $_ids, $customFields);
        }
        
        // handle StdFields
        
        if(!count($myFields)) { return 0; } 

        $identifier = $this->_getRecordIdentifier();
        
        $recordArray = $myFields;
        $recordArray = array_intersect_key($recordArray, $this->_schema);
        
        $this->_prepareData($recordArray);
                
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($identifier) . ' IN (?)', $_ids),
        );
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($where, TRUE));
        
        return $this->_db->update($this->_tablePrefix . $this->_tableName, $recordArray, $where);
    }
    
    /**
      * Deletes entries
      * 
      * @param string|integer|Tinebase_Record_Interface|array $_id
      * @return void
      * @return int The number of affected rows.
      */
    public function delete($_id) 
    {
        if (empty($_id)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No records deleted.');
            return 0;
        }
        
        $idArray = (! is_array($_id)) ? array(Tinebase_Record_Abstract::convertId($_id, $this->_modelName)) : $_id;
        $identifier = $this->_getRecordIdentifier();
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($identifier) . ' IN (?)', $idArray)
        );
        
        return $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
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
        if (! (isset($this->_schema[$_property]) || array_key_exists($_property, $this->_schema))) {
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
     * appends foreign recordSet (1:n relation) to given recordSet
     *
     * @param Tinebase_Record_RecordSet     $_recordSet         Records to append the foreign records to
     * @param string                        $_appendTo          Property in the records where to append the foreign records to
     * @param string                        $_recordKey         Property in the records where the foreign key value is in
     * @param string                        $_foreignKey        Key property in foreign table of the records to append
     * @param Tinebase_Backend_Sql_Abstract $_foreignBackend    Foreign table backend 
     */
    public function appendForeignRecordSetToRecordSet($_recordSet, $_appendTo, $_recordKey, $_foreignKey, $_foreignBackend)
    {
        $idxRecordKeyMap = $_recordSet->$_recordKey;
        $recordKeyIdxMap = array_flip($idxRecordKeyMap);
        $allForeignRecords = $_foreignBackend->getMultipleByProperty($idxRecordKeyMap, $_foreignKey);
        $foreignRecordsClassName = $allForeignRecords->getRecordClassName();

        foreach ($_recordSet as $record) {
            $record->$_appendTo = new Tinebase_Record_RecordSet($foreignRecordsClassName);
        }

        foreach($allForeignRecords as $foreignRecord) {
            $record = $_recordSet->getByIndex($recordKeyIdxMap[$foreignRecord->$_foreignKey]);
            $foreignRecordSet = $record->$_appendTo;

            $foreignRecordSet->addRecord($foreignRecord);
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
     * get foreign table information
     *
     * @return array
     */
    public function getForeignTables()
    {
        return $this->_foreignTables;
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
     * get table identifier
     * 
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }
    
    /**
     * get db adapter
     *
     * @return Zend_Db_Adapter_Abstract
     * @throws Tinebase_Exception_Backend_Database
     */
    public function getAdapter()
    {
        if (! $this->_db instanceof Zend_Db_Adapter_Abstract) {
            throw new Tinebase_Exception_Backend_Database('Could not fetch database adapter');
        }
        
        return $this->_db;
    }
    
    /**
     * get dbCommand class
     *
     * @return Tinebase_Backend_Sql_Command_Interface
     * @throws Tinebase_Exception_Backend_Database
     */
    public function getDbCommand()
    {
        if (! $this->_dbCommand instanceof Tinebase_Backend_Sql_Command_Interface) {
            throw new Tinebase_Exception_Backend_Database('Could not fetch database command class');
        }
        
        return $this->_dbCommand;
    }
    
    /**
     * Public service for grouping treatment
     * 
     * @param string $tablePrefix
     * @param Zend_Db_Select $select
     */
    public static function traitGroup(Zend_Db_Select $select)
    {
        // not needed for MySQL backends
        if ($select->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mysql) {
            return;
        }
        
        $group = $select->getPart(Zend_Db_Select::GROUP);
        
        if (empty($group)) {
            return;
        }
        
        $columns        = $select->getPart(Zend_Db_Select::COLUMNS);
        $updatedColumns = array();
        
        //$column is an array where 0 is table, 1 is field and 2 is alias
        foreach ($columns as $key => $column) {
            if ($column[1] instanceof Zend_Db_Expr) {
                if (preg_match('/^\(.*\)/', $column[1])) {
                    $updatedColumns[] = array($column[0], new Zend_Db_Expr("MIN(" . $column[1] . ")"), $column[2]);
                } else {
                    $updatedColumns[] = $column;
                }
                
                continue;
            }
            
            if (preg_match('/^\(.*\)/', $column[1])) {
                $updatedColumns[] = array($column[0], new Zend_Db_Expr("MIN(" . $column[1] . ")"), $column[2]);
                
                continue;
            }
            
            // resolve * to single columns
            if ($column[1] == '*') {

                $tableFields = Tinebase_Db_Table::getTableDescriptionFromCache(SQL_TABLE_PREFIX . $column[0], $select->getAdapter());
                foreach ($tableFields as $columnName => $schema) {
                    
                    // adds columns into group by clause (table.field)
                    // checks if field has a function (that must be an aggregation)
                    $fieldName = "{$column[0]}.$columnName";
                    
                    if (in_array($fieldName, $group)) {
                        $updatedColumns[] = array($column[0], $fieldName, $columnName);
                    } else {
                        // any selected field which is not in the group by clause must have an aggregate function
                        // we choose MIN() as default. In practice the affected columns will have only one value anyways.
                        $updatedColumns[] = array($column[0], new Zend_Db_Expr("MIN(" . $select->getAdapter()->quoteIdentifier($fieldName) . ")"), $columnName);
                    }
                }
                
                continue;
            }
            
            $fieldName = $column[0] . '.' . $column[1];
            
            if (in_array($fieldName, $group)) {
                $updatedColumns[] = $column;
            } else {
                // any selected field which is not in the group by clause must have an aggregate function
                // we choose MIN() as default. In practice the affected columns will have only one value anyways.
                $updatedColumns[] = array($column[0], new Zend_Db_Expr("MIN(" . $select->getAdapter()->quoteIdentifier($fieldName) . ")"), $column[2] ? $column[2] : $column[1]);
            }
        }
        
        $select->reset(Zend_Db_Select::COLUMNS);
        
        foreach ($updatedColumns as $column) {
            $select->columns(!empty($column[2]) ? array($column[2] => $column[1]) : $column[1], $column[0]);
        }

        // add order by columns to group by
        $order = $select->getPart(Zend_Db_Select::ORDER);
        
        foreach($order as $column) {
            $field = $column[0];
            
            if (preg_match('/.*\..*/',$field) && !in_array($field,$group)) {
                // adds column into group by clause (table.field)
                $group[] = $field;
            }
        }
        
        $select->reset(Zend_Db_Select::GROUP);
        
        $select->group($group);
    }
}
