<?php
/**
 * Abstract implemetation of  Egwbase_Record_Interface
 * 
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Lists.php 121 2007-09-24 19:42:55Z lkneschke $
 *
 */

abstract class Egwbase_Record_Abstract implements Egwbase_Record_Interface
{
    public $bypassFilter = false;
    
    /**
     * defintion of record related properties with filters and validators
     * 
     * @var array record related properties: nameOfProperty => DefaultValue
     */
    protected $_properties = array();
    
    /**
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array list of zend inputfilter
     */
    protected $_filters = array();
    
    /**
     * this validators get used when validating user generated content with Zend_Input_Filter
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
     * holds instance of Zend_Filter
     * 
     * @var Zend_Filter
     */
    protected $_Zend_Filter = NULL;
   
    /**
     * Default constructor
     * Constructs an object and sets its record related properties.
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @return void
     * @throws Egwbase_Record_Exception
     */
    public function __construct($_data = NULL, $_bypassFilters = false)
    {
        if ($_bypassFilters) {
            foreach ($_data as $key => $value) {
                if (array_key_exists ($key, $this->_properties)) {
                    $this->_properties[$key] = $value;
                }
            }
        } else {
            $this->setFromUserData($_data);
        }
    }
    
    /**
     * sets the record related properties from user generated input.
     * Input-filtering and validation is to be done here e.g. by Zend_Filter_Input
     *
     * @param array $_data
     * @throws Egwbase_Record_Exception when content contains invalid or missing data
     */
    public function setFromUserData(array $_data)
    {
        $inputFilter = $this->getFilter();
        $inputFilter->setData(data);
    	
    	if ($inputFilter->isValid()) {
    		$data = $inputFilter->getUnescaped();
    		foreach($data as $key => $value) {
    			$this->$_prperties['key'] = $value;
    		}
    	} else {
            foreach($inputFilter->getMessages() as $fieldName => $errorMessages) {
                $this->_validationErrors[] = array('id'  => $fieldName,
                                  'msg' => $errorMessages[0]);
            }
    		throw new Egwbase_Record_Exception('some fields have invalid content');
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
     * @return array
     */
    public function toArray()
    {
        return $this->_properties;
    }
    
    /**
     * sets record related properties
     * 
     * @param string name of property
     * @param mixed value of property
     */
    public function __set($_name, $_value)
    {
        if (array_key_exists ($_name, $this->_properties)) {
            if (!$this->bypassFilter) {
                return $this->setFromUserData(array($_name => $_value));
            }
            return $this->_properties[$_name] = $_value;
        }
        throw new Egwbase_Record_Exception($_name . ' is no property of $this->_properties');
    }
    
    /**
     * gets record related properties
     * 
     * @param string name of property
     * @return mixed value of property
     */
    public function __get($_name)
    {
        if (array_key_exists ($_name, $this->_properties)) {
            return $this->_properties[$_name];
        }
        throw new Egwbase_Record_Exception($_name . ' is no property of $this->_properties');
    }
    
    /**
     * returns a Zend_Filter for the $_filters and $_validators of this record class.
     * we just create an instance of Filter if we really need it.
     * 
     * @retrun Zend_Filter
     */
    protected function getFilter()
    {
        if ($this->_Zend_Filter == NULL) {
           $this->_Zend_Filter = new Zend_Filter( $this->_filters, $this->_validators);
        }
        return $this->_Zend_Filter;
    }
    
}