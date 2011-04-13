<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold data representing one node in the tree
 * 
 * @property  string             contenttype
 * @property  Tinebase_DateTime  creation_time
 * @property  string             hash
 * @property  string             name
 * @property  Tinebase_DateTime  last_modified_time
 * @property  string             object_id
 * @property  string             size
 * @property  string             type
 */
class Tinebase_Model_Tree_Node extends Tinebase_Record_Abstract
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
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
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
    
        // model specific fields
        'parent_id'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
    	'object_id'      => array('presence' => 'required'),
    	'name'           => array('presence' => 'required'),
    	'islink'         => array(
            Zend_Filter_Input::DEFAULT_VALUE => '0',
            'InArray' => array(true, false)
        ),
        
        // fields from filemanager_objects table (ro)
        'type'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contenttype'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'revision'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'hash'			 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'size'			 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
     * converts a string or Addressbook_Model_List to a list id
     *
     * @param   string|Addressbook_Model_List  $_listId  the contact id to convert
     * 
     * @return  string
     * @throws  UnexpectedValueException  if no list id set 
     */
    static public function convertListIdToInt($_listId)
    {
        if ($_listId instanceof self) {
            if ($_listId->getId() == null) {
                throw new UnexpectedValueException('No identifier set.');
            }
            $id = (string) $_listId->getId();
        } else {
            $id = (string) $_listId;
        }
        
        if (empty($id)) {
            throw new UnexpectedValueException('Identifier can not be empty.');
        }
        
        return $id;
    }    
}
