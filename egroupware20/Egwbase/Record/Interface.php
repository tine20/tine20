<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Egwbase_Record_Interface
 * 
 * This a the abstract interface of an record.
 * A record is e.g. a single address or or single event.
 * The idea behind is that we can have metaoperation over differnt apps by 
 * having a common interface.
 * A record is identified by a identifier. As we are a Webapp and want to 
 * deal with the objects in the browser, identifier should be a string!
 */
interface Egwbase_Record_Interface extends ArrayAccess, IteratorAggregate 
{
    /**
     * Default constructor
     * Constructs an object and sets its record related properties.
     *
     * @param mixed $_contactData
     * @param bool $_bypassFilters Bypass filters at object creation with data
     * this is usefull when datas are for shure valid, e.g. after database query
     * 
     * @return void
     * @throws Egwbase_Record_Exception
     */
    public function __construct($_data = NULL, $_bypassFilters = false);
    
    /**
     * sets identifier of record
     * 
     * @string identifier
     * @bool bypass Filter
     */
    public function setId($_id, $_bypassFilter = false);
    
    /**
     * gets identifier of record
     * 
     * @return string identifier
     */
    public function getId();
    
    /**
     * sets record related properties
     * 
     * @param string name of property
     * @param mixed value of property
     */
    public function __set($_name, $_value);
    
    /**
     * gets record related properties
     * 
     * @param string name of property
     * @return mixed value of property
     */
    public function __get($_name);
    
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
    

    
}