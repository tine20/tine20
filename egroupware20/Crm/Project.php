<?php
/**
 * class to hold project data
 * 
 * @package     CRM
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Event.php 200 2007-11-16 10:50:03Z twadewitz $
 *
 */
class Crm_Project
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
        'pj_id' 				    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'pj_name'                   => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'pj_distributionphase_id'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_customertype_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'pj_leadsource_id'          => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'pj_owner'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_modifier'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_start'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_modified'               => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'pj_description'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_end'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_turnover'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_probability'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_end_scheduled'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_lastread'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_lastreader'             => array(Zend_Filter_Input::ALLOW_EMPTY => true)  
        
    );
    
    protected $_validationErrors = array();
    
    public function __construct($_eventData = NULL)
    {
    	if(is_array($_eventData)) {
    		foreach($_eventData as $key => $value) {
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
     * sets the eventdata from user generated input. data get filtered by Zend_Filter_Input
     *
     * @param array $_eventData
     * @throws Execption when content contains invalid or missing data
     */
    public function setFromUserData(array $_eventData)
    {
    	$inputFilter = new Zend_Filter_Input($this->_filters, $this->_validators, $_eventData);
    	
    	if ($inputFilter->isValid()) {
    		$eventData = $inputFilter->getUnescaped();
    		foreach($eventData as $key => $value) {
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