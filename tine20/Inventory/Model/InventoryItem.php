<?php
/**
 * class to hold InventoryItem data
 * 
 * @package     Inventory
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold InventoryItem data
 * 
 * @package     Inventory
 * @subpackage  Model
 */
class Inventory_Model_InventoryItem extends Tinebase_Record_Abstract
{  
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Inventory';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'type'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'container_id'          => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
    // @todo add more fields
        'inventory_id'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'location'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'add_time'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'total_number'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'active_number'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // relations (linked Inventory_Model_InventoryItem records) and other metadata
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time',
        'add_time'
    );
    
    /**
     * overwrite constructor to add more filters
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     * @return void
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        // do something here if you like (add default empty values, ...)
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase/Record/Tinebase_Record_Abstract::isValid()
     */
    public function isValid($_throwExceptionOnInvalidData = false)
    {
        //$isValid = parent::isValid($_throwExceptionOnInvalidData);
        
        $isValid = (int) $this->active_number > (int) $this->total_number;
        
        if ($isValid && $_throwExceptionOnInvalidData) {
            $e = new Tinebase_Exception_Record_Validation('active number must be equal or less than total number');
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ":\n" .
               print_r($this->_validationErrors,true). $e);
            throw $e;
        }
        return $isValid;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase/Record/Tinebase_Record_Abstract::setFromArray()
     */
    /*
    public function setFromArray(array $_data)
    {
    
        if (isset($_data['total_number']) && ! is_int($_data['total_number'])) {
            unset($_data['total_number']);
        }
        
        if (isset($_data['active_number']) && ! is_int($_data['active_number'])) {
            unset($_data['active_number']);
        }
        
        return parent::setFromArray($_data);
    }
    */
    /**
     * fills a record from json data
     *
     * @param string $_data json encoded data
     * @return void
     */
    public function setFromJson($_data)
    {
    
        if (isset($_data['total_number']) && ! is_int($_data['total_number'])) {
            unset($_data['total_number']);
        }
        
        if (isset($_data['active_number']) && ! is_int($_data['active_number'])) {
            unset($_data['active_number']);
        }
        
       return parent::setFromJson($_data);
        
        // do something here if you like
    }
}
