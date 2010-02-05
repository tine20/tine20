<?php
/**
 * grants model of a container
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * grants model of a container
 * 
 * @package     Tinebase
 * @subpackage  Record
 *  
 */
class Tinebase_Model_Grants extends Tinebase_Record_Abstract
{
    
    /**
     * grant to read all records of a container / a single record
     */
    const GRANT_READ     = 'readGrant';
    
    /**
     * grant to add a record to a container
     */
    const GRANT_ADD      = 'addGrant';
    
    /**
     * grant to edit all records of a container / a single record
     */
    const GRANT_EDIT     = 'editGrant';
    
    /**
     * grant to delete  all records of a container / a single record
     */
    const GRANT_DELETE   = 'deleteGrant';
    
    /**
     * grant to _access_ records marked as private (GRANT_X = GRANT_X * GRANT_PRIVATE)
     */
    const GRANT_PRIVATE = 'privateGrant';
    
    /**
     * grant to export all records of a container / a single record
     */
    const GRANT_EXPORT = 'exportGrant';
    
    /**
     * grant to sync all records of a container / a single record
     */
    const GRANT_SYNC = 'syncGrant';
    
    /**
     * grant to administrate a container
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
     * @return  array   all container grants
     */
    public function getAllGrants()
    {
        $allGrants = array(
            self::GRANT_READ,
            self::GRANT_ADD,
            self::GRANT_EDIT,
            self::GRANT_DELETE,
            self::GRANT_PRIVATE,
            self::GRANT_EXPORT,
            self::GRANT_SYNC,
            self::GRANT_ADMIN,
        );
    
        return $allGrants;
    }
    
    /**
     * get translated grants
     * 
     * @return  array with translated descriptions for the containers grants
     *
    public function getTranslatedGrants()
    {
        $translate = Tinebase_Translation::getTranslation($this->_application);

        $descriptions = array(
            self::GRANT_READ  => array(
                'label'         => $translate->_/('Read'),
                'description'   => $translate->_/('The grant to read records of this container'),
            ),
            self::GRANT_ADD  => array(
                'label'         => $translate->_/('Add'),
                'description'   => $translate->_/('The grant to add records to this container'),
            ),
            self::GRANT_EDIT => array(
                'label'         => $translate->_/('Edit'),
                'description'   => $translate->_/('The grant to edit records in this container'),
            ),
            self::GRANT_DELETE => array(
                'label'         => $translate->_/('Delete'),
                'description'   => $translate->_/('The grant to delete records in this container'),
            ),
            self::GRANT_PRIVATE => array(
                'label'         => $translate->_/('Private'),
                'description'   => $translate->_/('The grant to access records marked as private in this container'),
            ),
            self::GRANT_EXPORT => array(
                'label'         => $translate->_/('Export'),
                'description'   => $translate->_/('The grant to export records from this container'),
            ),
            self::GRANT_SYNC => array(
                'label'         => $translate->_/('Sync'),
                'description'   => $translate->_/('The grant to synchronise records with this container'),
            ),
            self::GRANT_ADMIN => array(
                'label'         => $translate->_/('Admin'),
                'description'   => $translate->_/('The grant to administrate this container'),
            ),
        );
        
        return $descriptions;
    }
    */
    
}