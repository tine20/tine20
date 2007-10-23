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
abstract class Egwbase_RecordSet_Abstract implements Iterator, Countable
{
	protected $_position = 0;
	
	protected $_listOfRecords = array();
	
	protected $_count = 0;
	
	/**
	 * @param array of record objects
	 */
	public function __construct(array $_records)
	{
		foreach($_records as $record) {
			$this->_listOfRecords[$this->_count] = $record;
			$this->_count++;
		}
	}
	
	/**
	 * add Egwbase_Record_Interface like object to internal list
	 *
	 * @param Egwbase_Record_Interface $_record
	 */
	public function addRecord(Egwbase_Record_Interface $_record)
	{
		$this->_listOfRecords[$this->_count] = $_record;
		$this->_count++;
	}
	
    /**
     * Returns the number of elements in the collection.
     *
     * required by interface Countable
     *
     * @return int
     */
    public function count()
    {
        return $this->_count;
    }

	/**
     * get the current element.
     * 
     * required by interface Iterator.
     *
     * @return Egwbase_Record_Interface current element from the collection
     */
    public function current()
    {
        if ($this->valid() === FALSE) {
            return null;
        }

        // return the Egwbase_Accounts_User_UserData object
        return $this->_listOfRecords[$this->_position];
    }

    /**
     * return the identifying key of the current element.
     * 
     * required by interface Iterator.
     *
     * @return int
     */
    public function key()
    {
        return $this->_position;
    }

    /**
     * move forward to next element.
     * 
     * required by interface Iterator.
     *
     * @return void
     */
    public function next()
    {
        ++$this->_position;
    }
    
    /**
     * rewind the iterator to the first element.
     * 
     * required by interface Iterator.
     *
     * @return void
     */
    public function rewind()
    {
        $this->_position = 0;
    }
    
    /**
     * check if there is a current element after calls to rewind() or next().
     * used to check if we've iterated to the end of the collection.
     * 
     * required by interface Iterator.
     *
     * @return bool False if there's nothing more to iterate over
     */
    public function valid()
    {
        return $this->_position < $this->_count;
    }
    
    public function toArray()
    {
    	$resultArray = array();
    	foreach($this->_listOfRecords as $record) {
    		$resultArray[] = $record->toArray();
    	}
    	
    	return $resultArray;
    }
    
}