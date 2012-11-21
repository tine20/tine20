<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * defines the datatype for one department
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Department extends Tinebase_Record_Abstract
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
        'name'        => 'StringTrim',
        'description' => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                     => array('Alnum', 'allowEmpty' => true),
        'name'                   => array('presence' => 'required', 'allowEmpty' => false),
        'description'            => array('allowEmpty' => true),
    
        'created_by'             => array('allowEmpty' => true),
        'creation_time'          => array('allowEmpty' => true),
        'last_modified_by'       => array('allowEmpty' => true),
        'last_modified_time'     => array('allowEmpty' => true),
        'is_deleted'             => array('allowEmpty' => true),
        'deleted_time'           => array('allowEmpty' => true),
        'deleted_by'             => array('allowEmpty' => true),
        'seq'                    => array('allowEmpty' => true),
    );
    
    /**
     * returns applicationname
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }        
}
