<?php
/**
 * class to hold list data
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Addressbook_List
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        '*'				=> 'StringTrim',
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'list_id'			=> array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
    	'list_name'			=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
    	'list_description'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
    	'list_owner'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
    	'list_members'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );
    
    protected $_validationErrors = array();
    
    public function __construct($_contactData = NULL)
    {
    	if(is_array($_contactData)) {
    		$this->setFromUserData($_contactData);
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
    public function setFromUserData(array $_listData)
    {
    	if(isset($_listData['list_members'])) {
    		$listMembers = (array)$_listData['list_members'];
    		unset($_listData['list_members']);
    	}
    	
    	$inputFilter = new Zend_Filter_Input($this->_filters, $this->_validators, $_listData);
    	
    	if ($inputFilter->isValid()) {
    		$contactData = $inputFilter->getUnescaped();
    		foreach($contactData as $key => $value) {	
    			$this->$key = $value;
    		}
    		if(is_array($listMembers)) {
    			$contactSet = new Addressbook_ContactSet();
    			foreach($listMembers as $listMember) {
    				try {
    					$contact = new Addressbook_Contact();
    					if($listMember['contact_id'] == -1) {
    						// add as new contact
    						unset($listMember['contact_id']);
    					}
    					$contact->setFromUserData($listMember);
    					$contactSet->addContact($contact);
    				} catch (Exception $e) {
    					// just skip the entry for now
    				}
    			}
    			$this->list_members = $contactSet;
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