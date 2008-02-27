<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * class to hold a list of records
 * 
 * records are held as a unsorted set with a autoasigned numeric index.
 * NOTE: the index of an record is _not_ related to the record and/or its identifier!
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
     * Holds validation errors
     * @var array
     */
    protected $_validationErrors = array();


    /**
     * creates new Tinebase_Record_RecordSet
     *
     * @param array $_records array of record objects
     * @param string $_className the required classType
     * @param bool $_bypassFilters {@see Tinebase_Record_Interface::__construct}
     * @param bool $_convertDates {@see Tinebase_Record_Interface::__construct}
     * @return void
     */
    public function __construct(array $_records, $_className,  $_bypassFilters = false, $_convertDates = true)
    {
     
        $this->_recordClass = $_className;

        foreach($_records as $record) {
            if($record instanceof $this->_recordClass) {
                $this->_listOfRecords[] = $record;
            } elseif (is_array($record)) {
                $this->_listOfRecords[] = new $this->_recordClass($record, $_bypassFilters, $_convertDates);
            } else {
            	throw new Tinebase_Record_Exception_NotAllowed('Attempt to add/set record of wrong record class. Should be ' . $this->_recordClass);
            }
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
        return key($this->_listOfRecords);
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
        
        if (empty($_offset)) {
        	$this->addRecord($_value);
        } else {
        	$this->_listOfRecords[$_offset] = $_value;
        }
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetUnset($_offset)
    {
        unset($this->_listOfRecords[$_offset]);
    }

}