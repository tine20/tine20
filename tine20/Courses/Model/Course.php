<?php
/**
 * class to hold Course data
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 */

/**
 * class to hold Course data
 * 
 * @property  string  $group_id
 * @package     Courses
 */
class Courses_Model_Course extends Tinebase_Record_Abstract
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
    protected $_application = 'Courses';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'group_id'              => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'type'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'internet'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'fileserver'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // relations (linked Courses_Model_Course records) and other metadata
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),    
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
     * fills a record from json data
     *
     * @param string $_data json encoded data
     * @return void
     */
    public function setFromJson($_data)
    {
        parent::setFromJson($_data);
    }
}
