<?php
/**
 * class to handle grants
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * read grant to all records of a container / a single record
     */
    const GRANT_READ     = 'readGrant';
    
    /**
     * add grant to a container
     */
    const GRANT_ADD      = 'addGrant';
    
    /**
     * edit grant to all records of a container / a single record
     */
    const GRANT_EDIT     = 'editGrant';
    
    /**
     * delete grant to all records of a container / a single record
     */
    const GRANT_DELETE   = 'deleteGrant';
    
    /**
     * admin grant to a container
     */
    const GRANT_ADMIN    = 'adminGrant';
    
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
        );
        
        foreach ($this->getAllGrants() as $grant) {
            $this->_validators[$grant] = array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE,
                'presence' => 'required',
                'allowEmpty' => true
            );
        }
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * get all possible grants
     *
     * @return  array   all application prefs
     */
    public function getAllGrants()
    {
        $allGrants = array(
            self::GRANT_READ,
            self::GRANT_ADD,
            self::GRANT_EDIT,
            self::GRANT_DELETE,
            self::GRANT_ADMIN,
        );
    
        return $allGrants;
    }
}