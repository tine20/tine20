<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for one application
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Application extends Tinebase_Record_Abstract
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
    protected $_application = 'Tinebase';
    
	/**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'name'      => 'StringTrim',
        'version'   => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array();

    /**
     * @see Tinebase_Record_Abstract
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_validators = array(
            'id'        => array('Digits', 'allowEmpty' => true),
            'name'      => array('presence' => 'required'),
            'status'    => array(new Zend_Validate_InArray(array('enabled', 'disabled'))),
            'order'     => array('Digits', 'presence' => 'required'),
            'tables'    => array('allowEmpty' => true),
            'version'   => array('presence' => 'required')
        );
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
}