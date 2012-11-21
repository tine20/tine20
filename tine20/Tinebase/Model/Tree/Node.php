<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold data representing one node in the tree
 * 
 * @package     Tinebase
 * @subpackage  Model
 * @property    string             contenttype
 * @property    Tinebase_DateTime  creation_time
 * @property    string             hash
 * @property    string             name
 * @property    Tinebase_DateTime  last_modified_time
 * @property    string             object_id
 * @property    string             size
 * @property    string             type
 */
class Tinebase_Model_Tree_Node extends Tinebase_Record_Abstract
{
    /**
     * type for personal containers
     */
    const TYPE_FILE = 'file';
    
    /**
     * type for personal containers
     */
    const TYPE_FOLDER = 'folder';
    
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
        'Tinebase_Model_User' => array('created_by', 'last_modified_by')
    ); 
    
    /**
     * list of zend validator
     * 
     * these validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array (
    // tine 2.0 generic fields
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // model specific fields
        'parent_id'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'object_id'      => array('presence' => 'required'),
        'name'           => array('presence' => 'required'),
        'islink'         => array(
            Zend_Filter_Input::DEFAULT_VALUE => '0',
            array('InArray', array(true, false))
        ),
        
        'relations' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notes' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tags' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'customfields' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        
    // fields from filemanager_objects table (ro)
        'type'           => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            array('InArray', array(self::TYPE_FILE, self::TYPE_FOLDER)),
        ),
        'description'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contenttype'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'revision'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'hash'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'size'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // not persistent
        'container_name' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // this is needed for ACL handling and should be sent by / delivered to client (not persistent in db atm)
        'path'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'account_grants' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time'
    );
    
    /**
    * overwrite constructor to add more filters
    *
    * @param mixed $_data
    * @param bool $_bypassFilters
    * @param mixed $_convertDates
    * @return void
    */
    public function __construct($_data = NULL, $_bypassFilters = FALSE, $_convertDates = TRUE)
    {
        $this->_filters['size'] = new Zend_Filter_Empty(0);
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
}
