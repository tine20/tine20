<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * class Tinebase_Model_PersistentFilter
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_PersistentFilter extends Tinebase_Record_Abstract 
{
    /**
     * @property String $application_id
     */
    
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
    protected $_application = 'Tinebase';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'application_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'account_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'model'                 => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'filters'               => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'presence'=>'required'),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_default'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time'
    );
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     */
    public function setFromArray(array $_data)
    {
        if (isset($_data['filters']) && ! $_data['filters'] instanceof Tinebase_Model_Filter_FilterGroup) {
            $_data['filters'] = $this->getFilterGroup($_data['model'], $_data['filters']);
        }
        
        return parent::setFromArray($_data);
    }
    
    /**
     * wrapper for setFromJason which expects datetimes in array to be in
     * users timezone and converts them to UTC
     *
     * @param  string $_data json encoded data
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     */
    public function setFromJsonInUsersTimezone($_data)
    {
        if (isset($_data['filters']) && ! $_data['filters'] instanceof Tinebase_Model_Filter_FilterGroup) {
            
            $filtersData = $_data['filters'];
            unset($_data['filters']);
        }
        
        parent::setFromJsonInUsersTimezone($_data);
        
        if (isset($filtersData)) {
            $this->filters = $this->getFilterGroup($_data['model'], $filtersData, TRUE);
        }
    }
    
    /**
     * gets filtergroup 
     * 
     * @param  $_filterModel    filtermodel
     * @param  $_filterData     array data of all filters
     * @param  $_fromUserTime   filterData is in user time
     * @return Tinebase_Model_Filter_FilterGroup
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function getFilterGroup($_filterModel, $_filterData, $_fromUserTime = FALSE)
    {
        $filter = new $_filterModel(array());
        
        if (! is_subclass_of($filter, 'Tinebase_Model_Filter_FilterGroup')) {
            throw new Tinebase_Exception_InvalidArgument('Filter Model has to be subclass of Tinebase_Model_Filter_FilterGroup.');
        }
        
        if ($_fromUserTime === TRUE) {
            $filter->setFromArrayInUsersTimezone($_filterData);
        } else {
            $filter->setFromArray($_filterData);
        }
        
        return $filter;
    }
}
