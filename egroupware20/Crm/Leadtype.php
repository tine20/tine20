<?php
/**
 * class to hold option data
 * 
 * @package     CRM
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: option.php 200 2007-11-16 10:50:03Z twadewitz $
 *
 */
class Crm_Leadtype
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        '*'                     => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'pj_leadtype_id' 	  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'pj_leadtype'         => array(Zend_Filter_Input::ALLOW_EMPTY => false)
    );
    
    protected $_validationErrors = array();
    
    public function __construct($_optionData = NULL)
    {
    	if(is_array($_optionData)) {
    		foreach($_optionData as $key => $value) {
    			if(isset($this->_validators[$key])) {
    				$this->$key = $value;
    			}
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
     * sets the optiondata from user generated input. data get filtered by Zend_Filter_Input
     *
     * @param array $_optionData
     * @throws Execption when content contains invalid or missing data
     */
    public function setFromUserData(array $_optionData)
    {
    	$inputFilter = new Zend_Filter_Input($this->_filters, $this->_validators, $_optionData);
    	
    	if ($inputFilter->isValid()) {
    		$optionData = $inputFilter->getUnescaped();
    		foreach($optionData as $key => $value) {
    			$this->$key = $value;
    		}
    	} else {
            foreach($inputFilter->getMessages() as $fieldName => $errorMessages) {
                $this->_validationErrors[] = array('id'  => $fieldName,
                                  'msg' => $errorMessages[0]);
            }
    		throw new Exception("some fields have invalid content");
    	}
    }
    
    /**
     * return public fields as array
     *
     * @param $_table  name of table to reorder array to
     * @return array
     */
    public function toArray()
    {
    	$resultArray = array();
    	foreach($this->_validators as $key => $name) {
    		if(isset($this->$key)) {
    			$resultArray[$key] = $this->$key;
    		}
    	}
    	return $resultArray;
    }
    
    private function __set($_name, $_value)
    {
    	if(isset($this->_validators[$_name])) {
    		$this->$_name = $_value;
    	} else {
    		throw new Exception("$_name is not a valid resource name");
    	}
    }
}