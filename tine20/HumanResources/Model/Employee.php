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
     * if foreign Id fields should be resolved on search and get from json
     * should have this format: 
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     * 
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User'           => array('created_by', 'last_modified_by', 'account_id'),
        'Sales_Model_Division'          => array('division_id'),
        'HumanResources_Model_Employee' => array('supervisor_id')
    );

    /**
     * list of zend validator
     * this validators get used when validating user generated content with Zend_Input_Filter
     * @var array
     */
    protected $_validators = array(
        'id'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
        'salutation'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'number'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_family'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_given'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_fn'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bday'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bank_account_holder' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bank_account_number' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bank_name'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bank_code_number'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'employment_begin'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'employment_end'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'supervisor_id'       => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'division_id'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'health_insurance'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'profession'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),

        // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // relations
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),

        'contracts'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
        'contracts'
    );

    /**
     * returns the foreignId fields (used in Tinebase_Convert_Json)
     * @return array
     */
    public static function getResolveForeignIdFields()
    {
        $rf = static::$_resolveForeignIdFields;
        if (! Tinebase_Application::getInstance()->isInstalled('Sales', true)) {
            unset($rf['Sales_Model_Division']);
        }
        
        return $rf;
    }
    
    /**
     * the constructor
     * @see Tinebase_Record_Abstract
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true) {
        $this->_doPrivateCleanup();
        $this->_filters['division_id'] = new Zend_Filter_Empty(NULL);
        
        if (Tinebase_Application::getInstance()->isInstalled('Sales', true)) {
            $this->_validators['costcenters'] = array(Zend_Filter_Input::ALLOW_EMPTY => true);
        }
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }

    /**
     * removes privat information from the Employee if user is no admin and has no EDIT_PRIVATE rights on this application
     */
    protected function _doPrivateCleanup()
    {
        $user = Tinebase_Core::getUser();
        if ($user instanceof Tinebase_Model_FullUser) {
            // no private cleanup with admin rights
            if ($user->hasRight('HumanResources', HumanResources_Acl_Rights::ADMIN) ||
                $user->hasRight('Tinebase', Tinebase_Acl_Rights_Abstract::ADMIN) ||
                $user->hasRight('HumanResources', HumanResources_Acl_Rights::EDIT_PRIVATE)) {
                return;
            } else {
                $this->_validators = array_diff_key($this->_validators, array_flip($this->_privateFields));
            }
        }
    }
}