<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
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
     * creates new Tinebase_Record_RecordSet
     *
     * @param string $_className the required classType
     * @param array $_records array of record objects
     * @param bool $_bypassFilters {@see Tinebase_Record_Interface::__construct}
     * @param bool $_convertDates {@see Tinebase_Record_Interface::__construct}
     * @return void
     */
    public function __construct($_className, array $_records = array(),  $_bypassFilters = false, $_convertDates = true)
    {
        $this->_recordClass = $_className;

        foreach($_records as $record) {
        	$toAdd = is_array($record) ? new $this->_recordClass($record, $_bypassFilters, $_convertDates) : $record;
        	$this->addRecord($toAdd);
        }
    }
    
    /**
     * add Tinebase_Record_Interface like object to internal list
     *
     * @param Tinebase_Record_Interface $_record
     * @return int index in set of inserted record
     */
    public function addRecord(Tinebase_Record_Interface $_record)
    {
        if (! $_record instanceof $this->_recordClass) {
            throw new Tinebase_Record_Exception_NotAllowed('Attempt to add/set record of wrong record class. Should be ' . $this->_recordClass);
        }
        $this->_listOfRecords[] = $_record;
        end($this->_listOfRecords);
        $index = key($this->_listOfRecords);
        
        $recordId = $_record->getId();
        if ($recordId) {
            $this->_idMap[$recordId] = $index;
        } else {
            $this->_idLess[] = $index;
        }
		
        return $index;
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
     * 
     * @return array identifier => recordarray
     */
    public function toArray()
    {
        $resultArray = array();
        foreach($this->_listOfRecords as $index => $record) {
            $resultArray[$index] = $record->toArray();
        }
         
        return $resultArray;
    }
    
    /**
     * returns index of record identified by its id
     * 
     * @param  string $_id id of record
     * @return int|bool    index of record or false if not in set
     */
    public function getIndexById($_id)
    {
        return array_key_exists($_id, $this->_idMap) ? $this->_idMap[$_id] : false;
    }
    
    /**
     * returns array of ids
     */
    public function getArrayOfIds()
    {
        return array_keys($this->_idMap);
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
     * sets given property in all member records of this set
     * 
     * @param string $_name
     * @param mixed $_value
     * @return void
     */
    public function __set($_name, $_value)
    {
    	foreach ($this->_listOfRecords as $record) {
    		$record->$_name = $_value;
    	}
    }
    
    /**
     * returns an array with the properties of all records in this set
     * 
     * @param  string $_name property
     * @return array index => property
     */
    public function __get($_name)
    {
        $propertiesArray = array();
        foreach ($this->_listOfRecords as $index => $record) {
            $propertiesArray[$index] = $record->$_name;
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
        return $this->_listOfRecords[$_offset];
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetSet($_offset, $_value)
    {
        if (! $_value instanceof $this->_recordClass) {
            throw new Tinebase_Record_Exception_NotAllowed('Attempt to add/set record of wrong record class. Should be ' . $this->_recordClass);
        }
        
        if (!is_int($_offset)) {
        	$this->addRecord($_value);
        } else {
            if (!array_key_exists($_offset, $this->_listOfRecords)) {
                throw new Tinebase_Record_Exception_NotAllowed('adding a record is only allowd via the addRecord method');
            }
        	$this->_listOfRecords[$_offset] = $_value;
        	$id = $_value->getId();
        	if ($id) {
        	    if(! array_key_exists($id, $this->_idMap)) {
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
     * filter recordset and return subset
     *
     * @param string $_field
     * @param string $_value
     * @return Tinebase_Record_RecordSet
     * 
     * @todo add regular expressions as $_value
     */
    public function filter($_field, $_value)
    {
        $result = clone($this);
        
        // remove all entries that don't match the filter
        foreach ($result as $index => $record) {
            if ($record->$_field !== $_value) {
                unset($result[$index]);
            }
        }
        
        return $result;
    }
    
}