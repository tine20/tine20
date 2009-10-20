<?php
/**
 * class to hold contract data
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
     * relation type: acount
     *
     */
    const RELATION_TYPE_ACCOUNT = 'ACCOUNT';
    
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
     * fill record from json data
     *
     * @param string $_data json encoded data
     * @return void
     * 
     * @todo    discuss this concept / move to json abstract? / record abstract?
     */
    public function setFromJson($_data)
    {
        $data = Zend_Json::decode($_data);
        
        if (isset($data['container_id']) && is_array($data['container_id'])) {
            $data['container_id'] = $data['container_id']['id'];
        }
        
        /************* add new relations *******************/
        
        if (isset($data['relations'])) {
            foreach ((array)$data['relations'] as $key => $relation) {
                
                if (!isset($relation['id'])) {
                    $relationData = array(
                        'own_model'              => 'Sales_Model_Contract',
                        'own_backend'            => 'Sql',
                        'own_id'                 => (isset($data['id'])) ? $data['id'] : 0,
                        'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'type'                   => $relation['type'],
                        'related_record'         => (isset($relation['related_record'])) ? $relation['related_record'] : array(),
                        'related_id'             => (isset($relation['related_id'])) ? $relation['related_id'] : NULL,
                    );
                    
                    switch ($relation['type']) {
                        case self::RELATION_TYPE_ACCOUNT:                        
                            $relationData['related_model'] = 'Tinebase_Model_User';
                            $relationData['related_backend'] = Tinebase_User::getConfiguredBackend();
                            break;                    
                        case self::RELATION_TYPE_CUSTOMER:
                            $relationData['related_model'] = 'Addressbook_Model_Contact';
                            $relationData['related_backend'] = Addressbook_Backend_Factory::SQL;
                            break;                    
                        default:
                            throw new Sales_Exception_UnexpectedValue('Relation type not supported.');
                    }
    
                    // sanitize container id
                    if (isset($relation['related_record']['container_id']) && is_array($relation['related_record']['container_id'])) {
                        $data['related_record']['container_id'] = $relation['related_record']['container_id']['id'];
                    }
                    
                    $data['relations'][$key] = $relationData;
                }
            }
        }
        
        $this->setFromArray($data);
    }
}
