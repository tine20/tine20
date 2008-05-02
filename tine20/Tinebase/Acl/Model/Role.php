<?php
/**
 * model to handle roles
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add role members and rights
 */

/**
 * defines the datatype for roles
 * 
 * @package     Tinebase
 * @subpackage  Acl
 *  */
class Tinebase_Acl_Model_Role extends Tinebase_Record_Abstract
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
     * this filter get used when validating user generated content with Zend_Filter_Input
     *
     * @var array
     */
    protected $_filters = array(
        'name'      => 'StringTrim'
    );

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Filter_Input
     *
     * @var array
     */
    protected $_validators = array(
            'id'                    => array('allowEmpty' => true),
            'name'                  => array('presence' => 'required'),
            'description'           => array('allowEmpty' => true),
            'created_by'            => array('allowEmpty' => true),
            'creation_time'         => array('allowEmpty' => true),
            'last_modified_by'      => array('allowEmpty' => true),
            'last_modified_time'    => array('allowEmpty' => true),
    );
        
    /**
     * @var array
     */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
    );
    
    /**
     * returns role name
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
    
}