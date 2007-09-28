<?php
/**
 * class to hold a list of contacts
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Addressbook_ContactSet implements Iterator, Countable
{
	protected $_position = 0;
	
	protected $_listOfContacts = array();
	
	protected $_count = 0;
	
	public function __construct($_contacts = NULL)
	{
		if(is_array($_contacts)) {
			foreach($_contacts as $contact) {
				$this->_listOfContacts[$this->_count] = new Addressbook_Contact($contact);
				$this->_count++;
			}
		}
	}
	
	/**
	 * add Addressbook_Contact object to internal list
	 *
	 * @param Addressbook_Contact $_contact the object holding all contact informations
	 */
	public function addContact(Addressbook_Contact $_contact)
	{
		$this->_listOfContacts[$this->_count] = $_contact;
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
     * @return Egwbase_Accounts_User_UserData current element from the collection
     */
    public function current()
    {
        if ($this->valid() === FALSE) {
            return null;
        }

        // return the Egwbase_Accounts_User_UserData object
        return $this->_listOfContacts[$this->_position];
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
    	foreach($this->_listOfContacts as $contact) {
    		$resultArray[] = $contact->toArray();
    	}
    	
    	return $resultArray;
    }
    
}