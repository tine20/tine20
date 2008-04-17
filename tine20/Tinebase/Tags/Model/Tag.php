<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for one tag
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Tags_Model_Tag extends Tinebase_Record_Abstract
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
        'name'              => 'StringTrim'
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
        'type'                   => array('InArray' => array(Tinebase_Tags::TYPE_PERSONAL, Tinebase_Tags::TYPE_SHARED) ),
        'owner'                  => array('allowEmpty' => true),
        'name'                   => array('presence' => 'required'),
        'description'            => array('allowEmpty' => true),
        'color'                  => array('allowEmpty' => true),
        'occurrence'             => array('allowEmpty' => true),
        'account_grants'         => array('allowEmpty' => true),
        'created_by'             => array('allowEmpty' => true),
        'creation_time'          => array('allowEmpty' => true),
        'last_modified_by'       => array('allowEmpty' => true),
        'last_modified_time'     => array('allowEmpty' => true),
        'is_deleted'             => array('allowEmpty' => true),
        'deleted_time'           => array('allowEmpty' => true),
        'deleted_by'             => array('allowEmpty' => true),
    );
    
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time',
    );
    
    /**
     * returns containername
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}