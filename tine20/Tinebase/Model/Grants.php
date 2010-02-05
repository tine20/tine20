<?php
/**
 * class to handle grants
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for one application
 * 
 * @package     Tinebase
 * @subpackage  Record
 *  */
class Tinebase_Model_Grants extends Tinebase_Record_Abstract
{
    
    /**
     * constants for default grants
     */
    const READGRANT     = 'readGrant';
    const ADDGRANT      = 'addGrant';
    const EDITGRANT     = 'editGrant';
    const DELETEGRANT   = 'deleteGrant';
    const ADMINGRANT    = 'adminGrant';
    
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
     * this filter get used when validating user generated content with Zend_Filter_Input
     *
     * @var array
     */
    protected $_filters = array(
        //'*'      => 'StringTrim'
    );

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Filter_Input
     *
     * @var array
     */
    protected $_validators = array();

    public function __construct($_data = NULL, $_bypassFilters = FALSE, $_convertDates = NULL)
    {
        $this->_validators = array(
            'id'          => array('Alnum', 'allowEmpty' => TRUE),
            'account_id'   => array('presence' => 'required', 'allowEmpty' => TRUE, 'default' => '0'),
            'account_type' => array('presence' => 'required', 'InArray' => array(Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP)),
            //'account_name' => array('allowEmpty' => TRUE),
            'readGrant'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE,
                'presence' => 'required',
                'allowEmpty' => true
            ),
            'addGrant'    => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE,
                'presence' => 'required',
                'allowEmpty' => true
            ),
            'editGrant'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE,
                'presence' => 'required',
                'allowEmpty' => true
            ),
            'deleteGrant' => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE,
                'presence' => 'required',
                'allowEmpty' => true
            ),
            'adminGrant'  => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE,
                'presence' => 'required',
                'allowEmpty' => true
            )
        );
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
}