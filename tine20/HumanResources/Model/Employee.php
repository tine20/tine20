<?php
/**
 * Tine 2.0

 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Employee data
 * 
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_Employee extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which
     * represents the identifier
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     * @var string
     */
    protected $_application = 'HumanResources';

    /**
     * list of zend validator
     * this validators get used when validating user generated content with Zend_Input_Filter
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'account_id'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'countryname'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'locality'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'postalcode'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'region'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'street'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'street2'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'email'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_home'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'title'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'number'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_fn'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bday'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bank_account_holder' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bank_account_number' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bank_name'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bank_code_number'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'employment_begin'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'employment_end'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'sickness_manager_id' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'vacation_manager_id' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'status'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        
        
    // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // relations (linked HumanResources_Model_Employee records) and other metadata
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contracts'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time',
        'bday',
        'employment_begin',
        'employment_end'
    );
    
    protected $_privateFields = array(
        'countryname',
        'locality',
        'postalcode',
        'region',
        'street',
        'street2',
        'email',
        'tel_home',
        'tel_cell',
        'bday',
        'bank_account_holder',
        'bank_account_number',
        'bank_name',
        'bank_code_number',
        'employment_begin',
        'employment_end',
    );
    
    /**
     * the constructor
     * @see Tinebase_Record_Abstract
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true) {
        $this->_doPrivateCleanup();
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * removes privat information from the Employee
     */
    protected function _doPrivateCleanup()
    {
        if (! Tinebase_Core::getUser()->hasRight('HumanResources', HumanResources_Acl_Rights::EDIT_PRIVATE)) {
            $this->_validators = array_diff_key($this->_validators, array_flip($this->_privateFields));
        }
    }
    
    /**
//      * can be reimplemented by subclasses to modify values during setFromJson
//      * @param array $_data the json decoded values
//      * @return void
//      */
//     protected function _setFromJson(array &$_data)
//     {
//         for ($i = 0; $i < count($_data['contracts']); $i++) {
//             $_data['contracts'][$i]['workingtime_id'] = $_data['contracts'][$i]['workingtime_id']['id'];
//         }
// // //         foreach($_data['workingtime_id']['contracts'] as $contract) {
// // //             = $_data['workingtime_id']['id'];
// // //         }
//         die(var_dump($_data));
//     }
}