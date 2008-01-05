<?php
/**
 * class to handle grants
 * 
 * @package     Egwbase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Application.php 390 2007-12-03 15:29:54Z nelius_weiss $
 */

/**
 * defines the datatype for one application
 */
class Egwbase_Record_Grants extends Egwbase_Record_Abstract
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Filter_Input
     *
     * @var array
     */
    protected $_filters = array(
        '*'      => 'StringTrim'
    );

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Filter_Input
     *
     * @var array
     */
    protected $_validators = array();

   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'accountId';
    
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = NULL)
    {
        $this->_validators = array(
            'accountId'   => array('Digits', 'presence' => 'required'),
            'accountName' => array('presence' => 'required'),
            'readGrant'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'addGrant'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'editGrant'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'deleteGrant'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'adminGrant'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            )
        );
        
        return parent::__construct($_data, $_bypassFilters);
    }
}