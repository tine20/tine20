<?php
/**
 * class to hold number data
 * 
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        put this into tinebase, don't use types but model names
 */

/**
 * class to hold number data
 * 
 * @package     Sales
 * @subpackage    Model
 */
class Sales_Model_Number extends Tinebase_Record_Abstract
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
    protected $_application = 'Sales';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'               => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'number'           => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'account_id'       => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'model'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'update_time'      => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'update_time',
    );
}