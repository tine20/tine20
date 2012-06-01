<?php
/**
 * class to hold contract data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        add contract status table
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
    // relations (linked users/groups and customers
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
    );
    
    /**
     * fill record from json data
     *
     * @param  array &$data
     * @return void
     */
    protected function _setFromJson(array &$data)
    {
        /************* add new relations *******************/
        
        if (array_key_exists('relations', $data)) {
            foreach ((array)$data['relations'] as $key => $relation) {
                
                if (!isset($relation['id'])) {
                    $relationData = array(
                        'own_model'              => 'Sales_Model_Contract',
                        'own_backend'            => 'Sql',
                        'own_id'                 => (array_key_exists('id', $data)) ? $data['id'] : 0,
                        'own_degree'             => (array_key_exists('own_degree', $relation)) ? $relation['own_degree'] : 'sibling',
                        'type'                   => (array_key_exists('type', $relation)) ? $relation['type'] : '',
                        'related_record'         => (array_key_exists('related_record', $relation)) ? $relation['related_record'] : array(),
                        'related_id'             => (array_key_exists('related_id', $relation)) ? $relation['related_id'] : NULL,
                    );
                    $relationData['related_model'] = 'Addressbook_Model_Contact';
                    $relationData['related_backend'] = Addressbook_Backend_Factory::SQL;
                    
                    // sanitize container id
                    if (isset($relation['related_record']['container_id']) && is_array($relation['related_record']['container_id'])) {
                        $data['related_record']['container_id'] = $relation['related_record']['container_id']['id'];
                    }
                    
                    $data['relations'][$key] = $relationData;
                }
            }
        }
    }
}
