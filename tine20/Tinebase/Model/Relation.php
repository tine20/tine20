<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        add 'options' field and use it for (crm) remarks (product price/desc/quantity)
 */

/**
 * Tinebase_Model_Relation
 * Model of a record relation
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Relation extends Tinebase_Record_Abstract 
{
    /**
     * degree parent
     */
    const DEGREE_PARENT = 'parent';
    /**
     * degree child
     */
    const DEGREE_CHILD = 'child';
    /**
     * degree sibling
     */
    const DEGREE_SIBLING = 'sibling';
    /**
     * manually created relation
     */
    const TYPE_MANUAL = 'manual';
    /**
     * key to find identifier
     */
	protected $_identifier = 'id';
	/**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
	/**
	 * all valid fields
	 */
    protected $_validators = array(
        'id'                     => array('allowEmpty' => true,  'Alnum'),
	    'own_model'              => array('presence' => 'required', 'allowEmpty' => false),
	    'own_backend'            => array('presence' => 'required', 'allowEmpty' => false),
	    'own_id'                 => array('presence' => 'required', 'allowEmpty' => true, 'Alnum'),
	    'own_degree'             => array('presence' => 'required', 'allowEmpty' => false, 'InArray' => array(
            self::DEGREE_PARENT, 
            self::DEGREE_CHILD, 
            self::DEGREE_SIBLING
        )),
	    'related_model'          => array('presence' => 'required', 'allowEmpty' => false),
	    'related_backend'        => array('presence' => 'required', 'allowEmpty' => false),
	    'related_id'             => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
        'type'                   => array('presence' => 'required', 'allowEmpty' => false),
        'remark'                 => array('allowEmpty' => true          ), // freeform field for manual relations
        'related_record'         => array('allowEmpty' => true          ), // property to store 'resolved' relation record
        'created_by'             => array('allowEmpty' => true,         ),
        'creation_time'          => array('allowEmpty' => true          ),
        'last_modified_by'       => array('allowEmpty' => true,         ),
        'last_modified_time'     => array('allowEmpty' => true          ),
        'is_deleted'             => array('allowEmpty' => true          ),
        'deleted_time'           => array('allowEmpty' => true          ),
        'deleted_by'             => array('allowEmpty' => true,         ),
	);
	/**
	 * fields containing datetime data
	 */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time'
    );
    
    
    /**
     * convert remark to array if json encoded
     * 
     * @see Tinebase_Record_Abstract::setFromArray
     *
     * @param array $_data            the new data to set
     * 
     * @todo    always json::encode remarks? / add options field that is always json encoded
     */
    public function setFromArray(array $_data)
    {
        parent::setFromArray($_data);
        
        if ($this->remark && is_string($this->remark) && strpos('{', $this->remark) === 0) {
            $this->remark = Zend_Json::decode($this->remark);
        }
    }
} // end of Tinebase_Model_Relation
?>
