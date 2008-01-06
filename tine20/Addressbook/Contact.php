<?php
/**
 * class to hold contact data
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Addressbook_Contact
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        '*'                     => 'StringTrim',
        'adr_one_countryname'   => array('StringTrim', 'StringToUpper'),
        'adr_two_countryname'   => array('StringTrim', 'StringToUpper'),
    	'contact_email'         => array('StringTrim', 'StringToLower'),
        'contact_email_home'    => array('StringTrim', 'StringToLower'),
        'contact_url'           => array('StringTrim', 'StringToLower'),
        'contact_url_home'      => array('StringTrim', 'StringToLower'),
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        //'contact_created'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        //'contact_creator'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_modified'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        //'contact_modifier'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_countryname'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_locality'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_postalcode'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_region'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_street'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_street2'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_countryname'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_locality'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_postalcode'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_region'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_street'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_street2'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_assistent'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_bday'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_email'     => array(
            array(
                'Regex', 
                '/^[^0-9][a-z0-9_]+([.][a-z0-9_]+)*[@][a-z0-9_]+([.][a-z0-9_]+)*[.][a-z]{2,4}$/'
            ), 
            Zend_Filter_Input::ALLOW_EMPTY => true
        ),
        'contact_email_home'     => array(
            array(
                'Regex', 
                '/^[^0-9][a-z0-9_]+([.][a-z0-9_]+)*[@][a-z0-9_]+([.][a-z0-9_]+)*[.][a-z]{2,4}$/'
            ), 
            Zend_Filter_Input::ALLOW_EMPTY => true
        ),
        'contact_id'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
    	'contact_note'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_owner'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
    	'contact_role'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_title'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_url'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_url_home'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_family'		=> array(),
        'n_given'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_middle'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_prefix'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_suffix'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_name'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_unit'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_assistent'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_car'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell_private'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax_home'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_home'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_pager'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_work'              => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );
    
    protected $_validationErrors = array();
    
    public function __construct($_contactData = NULL)
    {
    	if(is_array($_contactData)) {
    		foreach($_contactData as $key => $value) {
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
     * sets the contactdata from user generated input. data get filtered by Zend_Filter_Input
     *
     * @param array $_contactData
     * @throws Execption when content contains invalid or missing data
     */
    public function setFromUserData(array $_contactData)
    {
    	$inputFilter = new Zend_Filter_Input($this->_filters, $this->_validators, $_contactData);
    	
    	if ($inputFilter->isValid()) {
    		$contactData = $inputFilter->getUnescaped();
    		foreach($contactData as $key => $value) {
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