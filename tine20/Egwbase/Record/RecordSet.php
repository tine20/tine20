<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id: $
 */

/**
 * class to hold a list of records
 *
 */
class Egwbase_Record_RecordSet implements IteratorAggregate, Countable, ArrayAccess
{
    protected $_listOfRecords = array();
    protected $_recordClass = NULL;


    /**
     * Enter description here...
     *
     * @param array $_records array of record objects
     * @param strin $_className the required classType
     */
    public function __construct(array $_records = array(), $_className = NULL)
    {
        if($this->_recordClass === NULL && $_className !== NULL) {
            $this->_recordClass = $_className;
        }
        
        foreach($_records as $record) {
            if (is_array($record)) {
                if($this->_recordClass === NULL) {
                    throw new UnexpectedValueException('$_recordClass can not be NULL, when adding arrays');
                }
                $record = new $this->_recordClass($record, true);
            }
            
            if($record instanceof $this->_recordClass) {
                $this->_listOfRecords[$record->getId()] = $record;
            } else {
                throw new InvalidArgumentException('invalid datatype for Egwbase_Record_RecordSet');
            }
        }
    }

    /**
     * executes given function in all records
     *
     * @param string $_fname
     * @param array $_arguments
     * @return array array indentifier => return value
     */
    public function __call($_fname, $_arguments)
    {
        $returnValues = array();
        foreach ($this->_listOfRecords as $id => $record) {
            $return[$id] = call_user_func_array(array($record, $_fname), $_arguments);
        }
        
        return $returnValues;
    }
    
    /**
     * add Egwbase_Record_Interface like object to internal list, if an record
     * with the records identifier allready exists, this record will be replaeced
     *
     * @param Egwbase_Record_Interface $_record
     */
    public function addRecord(Egwbase_Record_Interface $_record)
    {
        $this->_listOfRecords[$_record->getId()] = $_record;
    }

    /**
     * converts RecordSet to array
     * 
     * param bool $_withKeys return array with keys when TRUE 
     * @return array identifier => recordarray
     */
    public function toArray($_convertDates = NULL, $_withKeys = FALSE)
    {
        $resultArray = array();
        foreach($this->_listOfRecords as $id => $record) {
            if($_withKeys === TRUE) {
                $resultArray[$id] = $record->toArray($_convertDates);
            } else {
                $resultArray[] = $record->toArray($_convertDates);
            }
        }
         
        return $resultArray;
    }
    
    /**
     * Returns the number of elements in the recordSet.
     *
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
            throw new Egwbase_Record_Exception('Attempt to add/set record of wrong record class');
        } elseif ($_offset !== $_value->getID()) {
            throw new Egwbase_Record_Exception('Attempt to add/set record with wrong identifier');
        }
        return $this->addRecord($_value);
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetUnset($_offset)
    {
        if (array_key_exists($_offset, $this->_listOfRecords)){
            unset($this->_listOfRecords[$_offset]);
        }
    }

}