<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * class to hold a list of records
 * 
 * records are held as a unsorted set with a autoasigned numeric index.
 * NOTE: the index of an record is _not_ related to the record and/or its identifier!
 * 
 * @package     Tinebase
 * @subpackage  Record
 *
 */
class Tinebase_Record_RecordSet implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * class name of records this instance can hold
     * @var string
     */
    protected $_recordClass;
    
    /**
     * Holds records
     * @var array
     */
    protected $_listOfRecords = array();
    
    /**
     * holds mapping id -> offset in $_listOfRecords
     * @var array
     */
    protected $_idMap = array();
    
    /**
     * holds offsets of idless (new) records in $_listOfRecords
     * @var array
     */
    protected $_idLess = array();
    
    /**
     * Holds validation errors
     * @var array
     */
    protected $_validationErrors = array();

    /**
     * Holds indices
     *
     * @var array indicesname => indicesarray
     */
    protected $_indices = array();
    
    /**
     * creates new Tinebase_Record_RecordSet
     *
     * @param string $_className the required classType
     * @param array|Tinebase_Record_RecordSet $_records array of record objects
     * @param bool $_bypassFilters {@see Tinebase_Record_Interface::__construct}
     * @param bool $_convertDates {@see Tinebase_Record_Interface::__construct}
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __construct($_className, $_records = array(), $_bypassFilters = false, $_convertDates = true)
    {
        if (! class_exists($_className)) {
            throw new Tinebase_Exception_InvalidArgument('Class ' . $_className . ' does not exist');
        }
        $this->_recordClass = $_className;
        
        foreach ($_records as $record) {
            $toAdd = $record instanceof Tinebase_Record_Abstract ? $record : new $this->_recordClass($record, $_bypassFilters, $_convertDates);
            $this->addRecord($toAdd);
        }
    }
    
    /**
     * clone records
     */
    public function __clone()
    {
        foreach ($this->_listOfRecords as $key => $record) {
            $this->_listOfRecords[$key] = clone $record;
        }
        $this->_buildIndices();
    }
    
    /**
     * returns name of record class this recordSet contains
     * 
     * @returns string
     */
    public function getRecordClassName()
    {
        return $this->_recordClass;
    }
    
    /**
     * add Tinebase_Record_Interface like object to internal list
     *
     * @param Tinebase_Record_Interface $_record
     * @param integer $_index
     * @return int index in set of inserted record
     */
    public function addRecord(Tinebase_Record_Interface $_record, $_index = NULL)
    {
        if (! $_record instanceof $this->_recordClass) {
            throw new Tinebase_Exception_Record_NotAllowed('Attempt to add/set record of wrong record class. Should be ' . $this->_recordClass);
        }
        $this->_listOfRecords[] = $_record;
        end($this->_listOfRecords);
        $index = ($_index !== NULL) ? $_index : key($this->_listOfRecords);
        
        // maintain indices
        $recordId = $_record->getId();
        if ($recordId) {
            $this->_idMap[$recordId] = $index;
        } else {
            $this->_idLess[] = $index;
        }
        foreach ($this->_indices as $name => &$propertyIndex) {
            $propertyIndex[$index] = $_record->$name;
        }
        
        return $index;
    }
    
    /**
     * removes all records from this set
     */
    public function removeAll()
    {
        foreach($this->_listOfRecords as $record) {
            $this->removeRecord($record);
        }
    }
    
    /**
     * remove record from set
     * 
     * @param Tinebase_Record_Interface $_record
     */
    public function removeRecord(Tinebase_Record_Interface $_record)
    {
        $idx = $this->indexOf($_record);
        if ($idx !== false) {
            $this->offsetUnset($idx);
        }
    }

    /**
     * remove records from set
     * 
     * @param Tinebase_Record_RecordSet $_records
     */
    public function removeRecords(Tinebase_Record_RecordSet $_records)
    {
        foreach ($_records as $record) {
            $this->removeRecord($record);
        }
    }
    
    /**
     * get index of given record
     * 
     * @param Tinebase_Record_Interface $_record
     * @return (int) index of record of false if not found
     */
    public function indexOf(Tinebase_Record_Interface $_record)
    {
        return array_search($_record, $this->_listOfRecords);
    }
    
    /**
     * checks if each member record of this set is valid
     * 
     * @return bool
     */
    public function isValid()
    {
        foreach ($this->_listOfRecords as $index => $record) {
            if (!$record->isValid()) {
                $this->_validationErrors[$index] = $record->getValidationErrors();
            }
        }
        return !(bool)count($this->_validationErrors);
    }
    
    /**
     * returns array of array of fields with validation errors 
     *
     * @return array index => validationErrors
     */
    public function getValidationErrors()
    {
        return $this->_validationErrors;
    }
    
    /**
     * converts RecordSet to array
     * NOTE: keys of the array are numeric and have _noting_ to do with internal indexes or identifiers
     * 
     * @return array 
     */
    public function toArray()
    {
        $resultArray = array();
        foreach($this->_listOfRecords as $index => $record) {
            $resultArray[$index] = $record->toArray();
        }
         
        return array_values($resultArray);
    }
    
    /**
     * returns index of record identified by its id
     * 
     * @param  string $_id id of record
     * @return int|bool    index of record or false if not in set
     */
    public function getIndexById($_id)
    {
        return (isset($this->_idMap[$_id]) || array_key_exists($_id, $this->_idMap)) ? $this->_idMap[$_id] : false;
    }
    
    /**
     * returns record identified by its id
     * 
     * @param  string $_id id of record
     * @return Tinebase_Record_Abstract::|bool    record or false if not in set
     */
    public function getById($_id)
    {
        $idx = $this->getIndexById($_id);
        
        return $idx !== false ? $this[$idx] : false;
    }

    /**
     * returns record identified by its id
     * 
     * @param  integer $index of record
     * @return Tinebase_Record_Abstract::|bool    record or false if not in set
     */
    public function getByIndex($index)
    {
        return (isset($this->_listOfRecords[$index])) ? $this->_listOfRecords[$index] : false;
    }
    
    /**
     * returns array of ids
     */
    public function getArrayOfIds()
    {
        return array_keys($this->_idMap);
    }
    
    /**
     * returns array of ids
     */
    public function getArrayOfIdsAsString()
    {
        $ids = array_keys($this->_idMap);
        foreach($ids as $key => $id) {
            $ids[$key] = (string) $id;
        }
        return $ids;
    }

    /**
     * returns array with idless (new) records in this set
     * 
     * @return array
     */
    public function getIdLessIndexes()
    {
        return array_values($this->_idLess);
    }
    
    /**
     * sets given property in all records with data from given values identified by their indices
     *
     * @param string $_name property name
     * @param array  $_values index => property value
     * @throws Tinebase_Exception_Record_NotDefined
     */
    public function setByIndices($_name, array $_values)
    {
        foreach ($_values as $index => $value) {
            if (! (isset($this->_listOfRecords[$index]) || array_key_exists($index, $this->_listOfRecords))) {
                throw new Tinebase_Exception_Record_NotDefined('Could not find record with index ' . $index);
            }
            $this->_listOfRecords[$index]->$_name = $value;
        }
    }
    
    /**
     * Sets timezone of $this->_datetimeFields
     * 
     * @see Tinebase_DateTime::setTimezone()
     * @param  string $_timezone
     * @param  bool   $_recursive
     * @return  void
     * @throws Tinebase_Exception_Record_Validation
     */
    public function setTimezone($_timezone, $_recursive = TRUE)
    {
        $returnValues = array();
        foreach ($this->_listOfRecords as $index => $record) {
            $returnValues[$index] = $record->setTimezone($_timezone, $_recursive);
        }
        
        return $returnValues;
    }
    
    /**
     * sets given property in all member records of this set
     * 
     * @param string $_name
     * @param mixed $_value
     * @return void
     * 
     * @todo reactivate indices (@see 0007558: reactivate indices in Tinebase_Record_RecordSet)
     */
    public function __set($_name, $_value)
    {
        foreach ($this->_listOfRecords as $record) {
            $record->$_name = $_value;
        }
        if (FALSE && (isset($this->_indices[$_name]) || array_key_exists($_name, $this->_indices))) {
            foreach ($this->_indices[$_name] as $key => $oldvalue) {
                $this->_indices[$_name][$key] = $_value;
            }
        }
    }
    
    /**
     * returns an array with the properties of all records in this set
     * 
     * @param  string $_name property
     * @return array index => property
     * 
     * @todo reactivate indices (@see 0007558: reactivate indices in Tinebase_Record_RecordSet)
     */
    public function __get($_name)
    {
        // NOTE: indices may lead to wrong results if a record is changed after build of indices
        if (FALSE && (isset($this->_indices[$_name]) || array_key_exists($_name, $this->_indices))) {
            $propertiesArray = $this->_indices[$_name];
        } else {
            $propertiesArray = array();
            foreach ($this->_listOfRecords as $index => $record) {
                $propertiesArray[$index] = $record->$_name;
            }
        }
        return $propertiesArray;
    }
    
    /**
     * executes given function in all records
     *
     * @param string $_fname
     * @param array $_arguments
     * @return array array index => return value
     */
    public function __call($_fname, $_arguments)
    {
        $returnValues = array();
        foreach ($this->_listOfRecords as $index => $record) {
            $returnValues[$index] = call_user_func_array(array($record, $_fname), $_arguments);
        }
        
        return $returnValues;
    }
    
   /** convert this to string
    *
    * @return string
    */
    public function __toString()
    {
       return print_r($this->toArray(), TRUE);
    }
    
    /**
     * Returns the number of elements in the recordSet.
     * required by interface Countable
     *
     * @return int
     */
    public function count()
    {
        return count($this->_listOfRecords);
    }

    /**
     * required by IteratorAggregate interface
     * 
     * @return iterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_listOfRecords);
    }

    /**
     * required by ArrayAccess interface
     */
    public function offsetExists($_offset)
    {
        return isset($this->_listOfRecords[$_offset]);
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetGet($_offset)
    {
        if (! is_int($_offset)) {
            throw new Tinebase_Exception_UnexpectedValue("index must be of type integer (". gettype($_offset) .") " . $_offset .  ' given');
        }
        if (! (isset($this->_listOfRecords[$_offset]) || array_key_exists($_offset, $this->_listOfRecords))) {
            throw new Tinebase_Exception_NotFound("No such entry with index $_offset in this record set");
        }
        
        return $this->_listOfRecords[$_offset];
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetSet($_offset, $_value)
    {
        if (! $_value instanceof $this->_recordClass) {
            throw new Tinebase_Exception_Record_NotAllowed('Attempt to add/set record of wrong record class. Should be ' . $this->_recordClass);
        }
        
        if (!is_int($_offset)) {
            $this->addRecord($_value);
        } else {
            if (!(isset($this->_listOfRecords[$_offset]) || array_key_exists($_offset, $this->_listOfRecords))) {
                throw new Tinebase_Exception_Record_NotAllowed('adding a record is only allowd via the addRecord method');
            }
            $this->_listOfRecords[$_offset] = $_value;
            $id = $_value->getId();
            if ($id) {
                if(! (isset($this->_idMap[$id]) || array_key_exists($id, $this->_idMap))) {
                    $this->_idMap[$id] = $_offset;
                    $idLessIdx = array_search($_offset, $this->_idLess);
                    unset($this->_idLess[$idLessIdx]);
                }
            } else {
                if (array_search($_offset, $this->_idLess) === false) {
                    $this->_idLess[] = $_offset;
                    $idMapIdx = array_search($_offset, $this->_idMap);
                    unset($this->_idMap[$idMapIdx]);
                }
            }
        }
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetUnset($_offset)
    {
        $id = $this->_listOfRecords[$_offset]->getId();
        if ($id) {
            unset($this->_idMap[$id]);
        } else {
            $idLessIdx = array_search($_offset, $this->_idLess);
            unset($this->_idLess[$idLessIdx]);
        }
        
        unset($this->_listOfRecords[$_offset]);
    }
    
    /**
     * Returns an array with ids of records to delete, to create or to update
     *
     * @param array $_toCompareWithRecordsIds Array to compare this record sets ids with
     * @return array An array with sub array indices 'toDeleteIds', 'toCreateIds' and 'toUpdateIds'
     * 
     * @deprecated please use diff() as this returns wrong result when idless records have been added
     * @see 0007492: replace getMigration() with diff() when comparing Tinebase_Record_RecordSets
     */
    public function getMigration(array $_toCompareWithRecordsIds)
    {
        $existingRecordsIds = $this->getArrayOfIds();
        
        $result = array();
        
        $result['toDeleteIds'] = array_diff($existingRecordsIds, $_toCompareWithRecordsIds);
        $result['toCreateIds'] = array_diff($_toCompareWithRecordsIds, $existingRecordsIds);
        $result['toUpdateIds'] = array_intersect($existingRecordsIds, $_toCompareWithRecordsIds);
        
        return $result;
    }

    /**
     * adds indices to this record set
     *
     * @param array $_properties
     * @return $this
     */
    public function addIndices(array $_properties)
    {
        if (! empty($_properties)) {
            foreach ($_properties as $property) {
                if (! (isset($this->_indices[$property]) || array_key_exists($property, $this->_indices))) {
                    $this->_indices[$property] = array();
                }
            }
            
            $this->_buildIndices();
        }
        
        return $this;
    }
    
    /**
     * build all indices of this set
     *
     */
    protected function _buildIndices()
    {
        foreach ($this->_indices as $name => $propertyIndex) {
            unset($this->_indices[$name]);
            $this->_indices[$name] = $this->__get($name);
        }
    }
    
    /**
     * filter recordset and return subset
     *
     * @param string $_field
     * @param string $_value
     * @return Tinebase_Record_RecordSet
     */
    public function filter($_field, $_value = NULL, $_valueIsRegExp = FALSE)
    {
        $matchingRecords = $this->_getMatchingRecords($_field, $_value, $_valueIsRegExp);
        
        $result = new Tinebase_Record_RecordSet($this->_recordClass, $matchingRecords);
        $result->addIndices(array_keys($this->_indices));
        
        return $result;
    }
    
    /**
     * Finds the first matching record in this store by a specific property/value.
     *
     * @param string $_field
     * @param string $_value
     * @return Tinebase_Record_Abstract
     */
    public function find($_field, $_value, $_valueIsRegExp = FALSE)
    {
        $matchingRecords = array_values($this->_getMatchingRecords($_field, $_value, $_valueIsRegExp));
        return count($matchingRecords) > 0 ? $matchingRecords[0] : NULL;
    }
    
    /**
     * filter recordset and return matching records
     *
     * @param string|function $_field
     * @param string $_value
     * @param boolean $_valueIsRegExp
     * @return array
     * 
     * @todo reactivate indices (@see 0007558: reactivate indices in Tinebase_Record_RecordSet)
     */
    protected function _getMatchingRecords($_field, $_value, $_valueIsRegExp = FALSE)
    {
        if (is_callable($_field)) {
            $matchingRecords = array_filter($this->_listOfRecords, $_field);
        } else {
            // NOTE: indices may lead to wrong results if a record is changed after build of indices
            if (FALSE && (isset($this->_indices[$_field]) || array_key_exists($_field, $this->_indices))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Filtering with indices, expecting fast results ;-)');
                $valueMap = $this->_indices[$_field];
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " Filtering field '$_field' of '{$this->_recordClass}' without indices, expecting slow results");
                $valueMap = $this->$_field;
            }
            
            if ($_valueIsRegExp) {
                $matchingMap = preg_grep($_value,  $valueMap);
            } else {
                $matchingMap = array_flip((array)array_keys($valueMap, $_value));
            }
            
            $matchingRecords = array_intersect_key($this->_listOfRecords, $matchingMap);
        }
        return $matchingRecords;
    }
    
    /**
     * returns first record of this set
     *
     * @return Tinebase_Record_Abstract|NULL
     */
    public function getFirstRecord()
    {
        if (count($this->_listOfRecords) > 0) {
            foreach ($this->_listOfRecords as $idx => $record) {
                return $record;
            }
        } else {
            return NULL;
        }
    }
    
    /**
     * compares two recordsets / only compares the ids / returns all records that are different in an array:
     *  - removed  -> all records that are in $this but not in $_recordSet
     *  - added    -> all records that are in $_recordSet but not in $this
     *  - modified -> array of diffs  for all different records that are in both record sets
     * 
     * @param Tinebase_Record_RecordSet $recordSet
     * @return Tinebase_Record_RecordSetDiff
     */
    public function diff($recordSet)
    {
        if (! $recordSet instanceof Tinebase_Record_RecordSet) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Did not get Tinebase_Record_RecordSet, skipping diff(' . $this->_recordClass . ')');
            return new Tinebase_Record_RecordSetDiff(array(
                'model'    => $this->getRecordClassName()
            ));
        }
        
        if ($this->getRecordClassName() !== $recordSet->getRecordClassName()) {
            throw new Tinebase_Exception_InvalidArgument('can only compare recordsets with the same type of records');
        }
        
        $existingRecordsIds = $this->getArrayOfIds();
        $toCompareWithRecordsIds = $recordSet->getArrayOfIds();
        
        $removedIds = array_diff($existingRecordsIds, $toCompareWithRecordsIds);
        $addedIds = array_diff($toCompareWithRecordsIds, $existingRecordsIds);
        $modifiedIds = array_intersect($existingRecordsIds, $toCompareWithRecordsIds);
        
        $removed = new Tinebase_Record_RecordSet($this->getRecordClassName());
        $added = new Tinebase_Record_RecordSet($this->getRecordClassName());
        $modified = new Tinebase_Record_RecordSet('Tinebase_Record_Diff');
        
        foreach ($addedIds as $id) {
            $added->addRecord($recordSet->getById($id));
        }
        // consider records without id, too
        foreach ($recordSet->getIdLessIndexes() as $index) {
            $added->addRecord($recordSet->getByIndex($index));
        }
        foreach ($removedIds as $id) {
            $removed->addRecord($this->getById($id));
        }
        // consider records without id, too
        foreach ($this->getIdLessIndexes() as $index) {
            $removed->addRecord($this->getByIndex($index));
        }
        foreach ($modifiedIds as $id) {
            $diff = $this->getById($id)->diff($recordSet->getById($id));
            if (! $diff->isEmpty()) {
                $modified->addRecord($diff);
            }
        }
        
        $result = new Tinebase_Record_RecordSetDiff(array(
            'model'    => $this->getRecordClassName(),
            'added'    => $added,
            'removed'  => $removed,
            'modified' => $modified,
        ));
        
        return $result;
    }
    
    /**
     * merges records from given record set
     * 
     * @param Tinebase_Record_RecordSet $_recordSet
     * @return void
     */
    public function merge(Tinebase_Record_RecordSet $_recordSet)
    {
        foreach ($_recordSet as $record) {
            if (! in_array($record, $this->_listOfRecords, true)) {
                $this->addRecord($record);
            }
        }
        
        return $this;
    }
    
    /**
     * sorts this recordset
     *
     * @param string $_field
     * @param string $_direction
     * @param string $_sortFunction
     * @param int $_flags sort flags for asort/arsort
     * @return $this
     */
    public function sort($_field, $_direction = 'ASC', $_sortFunction = 'asort', $_flags = SORT_REGULAR)
    {
        $offsetToSortFieldMap = $this->__get($_field);
        
        switch ($_sortFunction) {
            case 'asort':
                $fn = $_direction == 'ASC' ? 'asort' : 'arsort';
                $fn($offsetToSortFieldMap, $_flags);
                break;
            case 'natcasesort':
                natcasesort($offsetToSortFieldMap);
                if ($_direction == 'DESC') {
                    // @todo check if this is working
                    $offsetToSortFieldMap = array_reverse($offsetToSortFieldMap);
                }
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('Sort function unknown.');
        }
        
        // tmp records
        $oldListOfRecords = $this->_listOfRecords;
        
        // reset indexes and records
        $this->_idLess        = array();
        $this->_idMap         = array();
        $this->_listOfRecords = array();
        $namedIndices = array_keys($this->_indices);
        $this->_indices = array();
        $this->addIndices($namedIndices);
        
        foreach (array_keys($offsetToSortFieldMap) as $oldOffset) {
            $this->addRecord($oldListOfRecords[$oldOffset]);
        }
        
        return $this;
    }

    /**
    * sorts this recordset by pagination sort info
    *
    * @param Tinebase_Model_Pagination $_pagination
    * @return $this
    */
    public function sortByPagination($_pagination)
    {
        if ($_pagination !== NULL && $_pagination->sort) {
            $sortField = is_array($_pagination->sort) ? $_pagination->sort[0] : $_pagination->sort;
            $this->sort($sortField, ($_pagination->dir) ? $_pagination->dir : 'ASC');
        }
        
        return $this;
    }
    
    /**
     * limits this recordset by pagination
     * sorting should always be applied before to get the desired sequence
     * @param Tinebase_Model_Pagination $_pagination
     * @return $this
     */
    public function limitByPagination($_pagination)
    {
        if ($_pagination !== NULL && $_pagination->limit) {
            $indices = range($_pagination->start, $_pagination->start + $_pagination->limit - 1);
            foreach($this as $index => &$record) {
                if(! in_array($index, $indices)) {
                    $this->offsetUnset($index);
                }
            }
        }
        return $this;
    }
    
    /**
     * translate all member records of this set
     * 
     */
    public function translate()
    {
        foreach ($this->_listOfRecords as $record) {
            $record->translate();
        }
    }    
}
