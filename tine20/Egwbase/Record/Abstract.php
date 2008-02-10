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
 * Abstract implemetation of  Egwbase_Record_Interface
 */

abstract class Egwbase_Record_Abstract implements Egwbase_Record_Interface//, ArrayAccess, IteratorAggregate
{

    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = NULL;
    
    /**
     * holds properties of record
     * 
     * @var array 
     */
    protected $_properties = array();
    
    /**
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array list of zend inputfilter
     */
    protected $_filters = array();
    
    /**
     * Defintion of properties. All properties of record _must_ be declared here!
     * This validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array list of zend validator
     */
    protected $_validators = array();
    
    /**
     * the validators place there validation errors in this variable
     * 
     * @var array list of validation errors
     */
    protected $_validationErrors = array();
    
    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array();
    
    /**
     * save state if data should be validated on the fly(false) or on demand(false)
     *
     * @var bool
     */
    protected $_bypassFilters = false;
    
    /**
     * save state if datetimeFields should be converted from iso8601 strings to ZendDate objects and back 
     *
     * @var bool
     */
    protected $_convertDates = true;
    
    /**
     * save state if data are validated
     *
     * @var bool
     */
    protected $_isValidated = false;
    
    /**
     * holds instance of Zend_Filter
     * 
     * @var Zend_Filter
     */
    protected $_Zend_Filter = NULL;
   
    /**
     * Default constructor
     * Constructs an object and sets its record related properties.
     * 
     * @todo what happens if not all properties in the datas are set?
     * The default values must also be set, even if no filtering is done!
     * 
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param bool $_convertDates converts ISO 8801 to Zend_Date representation of $this->_datetimeFields
     * @return void
     * @throws Egwbase_Record_Exception
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        if ($this->_identifier === NULL) {
            throw new Egwbase_Record_Exception('$_identifier is not declared');
        }
        
        $this->_bypassFilters = (bool)$_bypassFilters;
        $this->_convertDates = (bool)$_convertDates;
        
        // try to set data only, when $_data is an array
        if(is_array($_data)) {
            $this->setFromArray($_data, $this->_bypassFilters);
        }
    }
    
    /**
     * sets identifier of record
     * 
     * @string identifier
     */
    public function setId($_id, $_bypassFilters = NULL)
    {
        // set internal state to "not validated"
        $this->_isValidated = false;
        
        if($_bypassFilters === NULL) {
            $bypassFilters = $this->_bypassFilters;
        } else {
            $bypassFilters = (bool)$_bypassFilters;
        }
        
        if ($bypassFilters === true) {
            $this->_properties[$this->_identifier] = $_id;
        } else {
            $newData = $this->_properties;
            $newData[$this->_identifier] = $_id;
            
            $this->setFromUserData($newData);
        }
    }
    
    /**
     * gets identifier of record
     * 
     * @return string identifier
     */
    public function getId()
    {
        return $this->_properties[$this->_identifier];
    }
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input is always done
     *
     * @param array $_data the new data to set
     * @throws Egwbase_Record_Exception when content contains invalid or missing data
     */
    public function setFromUserData(array $_data)
    {
        $this->setFromArray($_data, false);
    }
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data the new data to set
     * @param bool $_bypassFilters enabled/disable validation of data. set to NULL to use state set by the constructor 
     * @throws Egwbase_Record_Exception when content contains invalid or missing data
     */
    public function setFromArray(array $_data, $_bypassFilters)
    {
        if($this->_convertDates === true) {
            $this->_convertISO8601ToZendDate($_data);
        }
        
        // set internal state to "not validated"
        $this->_isValidated = false;
        
        if($_bypassFilters === true) {
            // set data without validation
            foreach ($_data as $key => $value) {
                if (array_key_exists ($key, $this->_validators)) {
                    $this->_properties[$key] = $value;
                }
            }
        } else {
            // set data with validation
            $inputFilter = $this->_getFilter();
            $inputFilter->setData($_data);
            
            if ($inputFilter->isValid()) {
                $data = $inputFilter->getUnescaped();
                foreach($data as $key => $value) {
                    $this->_properties[$key] = $value;
                }
                
                // set internal state to "validated"
                $this->_isValidated = true;
            } else {
                foreach($inputFilter->getMessages() as $fieldName => $errorMessages) {
                    $this->_validationErrors[] = array(
                        'id'  => $fieldName,
                        'msg' => $errorMessages[0]
                    );
                }
                $e = new Egwbase_Record_Exception_Validation('some fields have invalid content');
                Zend_Registry::get('logger')->debug(__CLASS__ . ":\n" .
                    print_r($this->_validationErrors,true). $e);
                throw $e;
            }
        }
    }
    
    /**
     * Sets timezone of $this->_datetimeFields
     * 
     * @see Zend_Date::setTimezone()
     * @param string $_timezone
     * @return void
     */
    public function setTimezone($_timezone)
    {
        foreach ($this->_datetimeFields as $field) {
            if (!isset($this->_properties[$field])) continue;
            
            if(!is_array($this->_properties[$field])) {
                $toConvert = array(&$this->_properties[$field]);
            } else {
                $toConvert = &$this->_properties[$field];
            }

            foreach ($toConvert as $field => &$value) {
                if (! $value instanceof Zend_Date) {
                    throw new Exception($toConvert[$field] . 'must be an Zend_Date'); 
                }
                $value->setTimezone($_timezone);
            } 
        }
    }
    
    /**
     * returns array of fields with validation errors 
     *
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->_validationErrors;
    }
    
    /**
     * returns array with record related properties 
     *
     * @param bool $_convertDates set to NULL to use value set by the constructor
     * @return array
     */
    public function toArray($_convertDates = NULL)
    {
        if($_convertDates === NULL) {
            $convertDates = $this->_convertDates;
        } else {
            $convertDates = (bool)$_convertDates;
        }

        $recordArray = $this->_properties;
        if ($convertDates === true) {
            $this->_convertZendDateToISO8601($recordArray);
        }
        return $recordArray;
    }
    
    /**
     * validate the the internal data
     *
     * @return bool
     */
    public function isValid()
    {
        if($this->_isValidated === false) {
            $inputFilter = $this->_getFilter();
            $inputFilter->setData($this->_properties);
            
            $this->_isValidated = $inputFilter->isValid();
            
            // still invalid? let's store the fields with problems
            if($this->_isValidated === false) {
                $this->_validationErrors = array();
                
                foreach($inputFilter->getMessages() as $fieldName => $errorMessages) {
                    $this->_validationErrors[] = array(
                        'id'  => $fieldName,
                        'msg' => $errorMessages[0]
                    );
                }
            }
        }
        
        return $this->_isValidated;
    }
    
    /**
     * @todo implement a usefull __toString()
     *
     */
    public function __toString(){
        foreach ($this->_properties as $key => $value) {
        }
    }
    
    /**
     * sets record related properties
     * 
     * @param string _name of property
     * @param mixed _value of property
     * @return void
     */
    public function __set($_name, $_value)
    {
        if (!array_key_exists ($_name, $this->_validators)) {
            throw new UnexpectedValueException($_name . ' is no property of $this->_properties');
        }
        
        // set internal state to "not validated"
        $this->_isValidated = false;
        
        if ($this->_bypassFilters === true) {
            $this->_properties[$_name] = $_value;
        } else {
            $newData = $this->_properties;
            $newData[$_name] = $_value;
            
            $this->setFromUserData($newData);
        }
    }
    
    /**
     * checkes if an propertiy is set
     * 
     * @param string _name name of property
     * @return bool property is set or not
     */
    public function __isset($_name)
    {
        return isset($this->_properties[$_name]);
    }
    
    /**
     * gets record related properties
     * 
     * @param string _name of property
     * @return mixed value of property
     */
    public function __get($_name)
    {
        if (!array_key_exists ($_name, $this->_validators)) {
            throw new UnexpectedValueException($_name . ' is no property of $this->_properties');
        }
        
        return $this->_properties[$_name];
    }
    
    /**
     * returns a Zend_Filter for the $_filters and $_validators of this record class.
     * we just create an instance of Filter if we really need it.
     * 
     * @retrun Zend_Filter
     */
    protected function _getFilter()
    {
        if ($this->_Zend_Filter == NULL) {
           $this->_Zend_Filter = new Zend_Filter_Input( $this->_filters, $this->_validators);
        }
        return $this->_Zend_Filter;
    }
    
    /**
     * Converts Zend_Dates into ISO8601 representation
     *
     * @param array &$_toConvert
     * @return 
     */
    protected function _convertZendDateToISO8601(&$_toConvert)
    {
        foreach ($_toConvert as $field => $value) {
            if ($value instanceof Zend_Date) {
                $_toConvert[$field] = $value->get(Zend_Date::ISO_8601);
            } elseif (is_array($value)) {
                $_toConvert[$field] = $this->_convertZendDateToISO8601($value);
            }
        }
    }
    
    /**
     * Converts dates into Zend_Date representation
     *
     * @param array &$_data
     * @return void
     */
    protected function _convertISO8601ToZendDate(array &$_data)
    {
        foreach ($this->_datetimeFields as $field) {
            if (!isset($_data[$field]) || $_data[$field] instanceof Zend_Date) continue;
            
            if(is_array($_data[$field])) {
                foreach($_data[$field] as $dataKey => $dataValue) {
                    $_data[$field][$dataKey] =  (int)$dataValue == 0 ? NULL : new Zend_Date($dataValue, Zend_Date::ISO_8601);
                }
            } else {
                $_data[$field] = (int)$_data[$field] == 0 ? NULL : new Zend_Date($_data[$field], Zend_Date::ISO_8601);
            }
        }
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetExists($_offset)
    {
        return isset($this->_properties[$offset]);
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetGet($_offset)
    {
        return $this->_properties[$_offset];
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetSet($_offset, $_value)
    {
        return $this->__set($_offset, $_value);
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetUnset($_offset)
    {
        throw new Egwbase_Record_Exception('Unsetting of properties is not allowed');
    }
    
    /**
     * required by IteratorAggregate interface
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_properties);    
    }
    
}