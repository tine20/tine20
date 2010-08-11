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
 * Tinebase_Record_Interface
 * 
 * This a the abstract interface of an record.
 * A record is e.g. a single address or or single event.
 * The idea behind is that we can have metaoperation over differnt apps by 
 * having a common interface.
 * A record is identified by a identifier. As we are a Webapp and want to 
 * deal with the objects in the browser, identifier should be a string!
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
interface Tinebase_Record_Interface extends ArrayAccess, IteratorAggregate 
{
    /**
     * Default constructor
     * Constructs an object and sets its record related properties.
     *
     * @param mixed $_contactData
     * @param bool $_bypassFilters Bypass filters at object creation with data
     * this is usefull when datas are for sure valid, e.g. after database query
     * @param array $_convertDates array with Zend_Date constructor parameters part and locale
     * 
     * @return void
     * @throws Tinebase_Record_Exception
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true);
    
    /**
     * sets identifier of record
     * 
     * @param string identifier
     */
    public function setId($_id);
    
    /**
     * gets identifier of record
     * 
     * @return string identifier
     */
    public function getId();
    
    /**
     * gets application the records belongs to
     * 
     * @return string application
     */
    public function getApplication();
    
    /**
     * sets record related properties
     * 
     * @param string name of property
     * @param mixed value of property
     */
    public function __set($_name, $_value);
    
    /**
     * unsets record related properties
     * 
     * @param string name of property
     */
    public function __unset($_name);
    
    /**
     * gets record related properties
     * 
     * @param string name of property
     * @return mixed value of property
     */
    public function __get($_name);
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     */
    public function setFromArray(array $_data);
    
    /**
     * Sets timezone of $this->_datetimeFields
     * 
     * @see Zend_Date::setTimezone()
     * @param string $_timezone
     * @throws Tinebase_Exception_Record_Validation
     * @return void
     */
    public function setTimezone($_timezone);
    
    /**
     * validate the the internal data
     *
     * @return bool
     */
    public function isValid();
    
    /**
     * returns array of fields with validation errors 
     *
     * @return array
     */
    public function getValidationErrors();
    
    /**
     * returns array with record related properties 
     *
     * @return array
     */
    public function toArray();
    
    /**
     * returns an array with differences to the given record
     * 
     * @param  Tinebase_Record_Interface $_record record for comparism
     * @return array with differences field => different value
     */
    public function diff($_record);
    
    /**
     * check if two records are equal
     * 
     * @param  Tinebase_Record_Interface $_record record for comparism
     * @param  array                     $_toOmit fields to omit
     * @return bool
     */
    public function isEqual($_record, array $_toOmit = array());
     
    /**
     * translate this records' fields
     *
     */
    public function translate();
    
    /**
     * check if the model has a specific field (container_id for example)
     *
     * @param string $_field
     * @return boolean
     */
    public function has($_field);    
}