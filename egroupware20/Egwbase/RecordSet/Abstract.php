<?php
/**
 * class to hold a list of records
 *
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: ContactSet.php 138 2007-09-28 05:18:53Z lkneschke $
 *
 */
abstract class Egwbase_RecordSet_Abstract implements IteratorAggregate, Countable, ArrayAccess
{
    protected $_listOfRecords = array();
    protected $_recordClass = NULL;


    /**
     * Enter description here...
     *
     * @param array $_records array of record objects
     * @param strin $_className the required classType
     */
    public function __construct(array $_records = array())
    {
        if (!$this->_recordClass){
            $classname = substr(get_class($this), 0, -3);
            if (class_exists($classname)) {
                $this->_recordClass = $classname;
            } else {
                throw new Egwbase_Record_Exception('Class of records not set!');
            }
        }
        
        foreach($_records as $record) {
            if (is_array($record)) {
                $record = new $this->_recordClass($record, true);
            }
            
            if($record instanceof $this->_recordClass) {
                $this->_listOfRecords[$record->getId()] = $record;
            } else {
                throw new Exception('invalid datatype for Egwbase_RecordSet_Abstract');
            }
        }
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
     * @return array identifier => recordarray
     */
    public function toArray()
    {
        $resultArray = array();
        foreach($this->_listOfRecords as $id => $record) {
            $resultArray[$id] = $record->toArray();
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