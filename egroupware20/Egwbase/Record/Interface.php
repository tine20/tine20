<?php
/**
 * Interface for records
 * 
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Lists.php 121 2007-09-24 19:42:55Z lkneschke $
 *
 */

/**
 * class Egwbase_Record_Interface
 * This a the abstract interface of an record.
 * A record is e.g. a single address or or single event.
 * The idea behind is that we can have metaoperation over differnt apps by 
 * having a common interface.
 * A record is identified by a identifier. As we are a Webapp and want to 
 * deal with the objects in the browser, identifier should be a string!
 */
interface Egwbase_Record_Interface
{
    /**
     * Default constructor
     * Constructs an object and sets its record related properties.
     *
     * @param mixed $_contactData
     * @return void
     * @throws Egwbase_Record_Exception
     */
    public function __construct($_data = NULL, $_bypassFilters = false);
    
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
    
}