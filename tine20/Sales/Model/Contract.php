<?php
/**
 * class to hold contract data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold contract data
 *
 * @package     Sales
 */
class Sales_Model_Contract extends Tinebase_Record_Abstract
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
    protected $_application = 'Sales';
    
    /**
     * relation type: customer
     *
     */
    const RELATION_TYPE_CUSTOMER = 'CUSTOMER';
    
    /**
     * relation type: responsible
     *
     */
    const RELATION_TYPE_RESPONSIBLE = 'RESPONSIBLE';
    
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
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'container_id'          => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'parent_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'number'                => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'title'                 => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'status'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cleared'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cleared_in'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // tags, notes, relations
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
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
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact', 'config' => array(
            array('type' => 'RESPONSIBLE', 'degree' => 'sibling', 'text' => 'Responsible', 'max' => '1:0'), // _('Responsible')
            array('type' => 'CUSTOMER', 'degree' => 'sibling', 'text' => 'Customer', 'max' => '1:0'),  // _('Customer')
            array('type' => 'PARTNER', 'degree' => 'sibling', 'text' => 'Partner', 'max' => '0:0'),  // _('Partner')
            )
        ),
        array('relatedApp' => 'Tasks', 'relatedModel' => 'Task', 'config' => array(
            array('type' => 'TASK', 'degree' => 'sibling', 'text' => 'Task', 'max' => '0:0'),
            )
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Product', 'config' => array(
            array('type' => 'PRODUCT', 'degree' => 'sibling', 'text' => 'Product', 'max' => '0:0'),
            )
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'CostCenter', 'config' => array(
            array('type' => 'LEAD_COST_CENTER', 'degree' => 'sibling', 'text' => 'Lead Cost Center', 'max' => '0:0'), // _('Lead Cost Center')
            )
        ),
        array('relatedApp' => 'Timetracker', 'relatedModel' => 'Timeaccount', 'config' => array(
            array('type' => 'TIME_ACCOUNT', 'degree' => 'sibling', 'text' => 'Time Account', 'max' => '0:1'), // _('Time Account')
            )
        ),
    );
}
