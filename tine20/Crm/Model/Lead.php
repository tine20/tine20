<?php
/**
 * class to hold lead data
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo remove old fields customer/partner/tasks/... -> they are covered with the relations field
 */

/**
 * class to hold lead data
 * 
 * @package     Crm
 */
class Crm_Model_Lead extends Tinebase_Record_Abstract
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
    protected $_application = 'Crm';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'id'            => 'Digits',
        'lead_name'     => 'StringTrim',
        'probability'   => 'Digits'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'lead_name'     => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'leadstate_id'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'leadtype_id'   => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'leadsource_id' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'container'     => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'start'         => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'description'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'end'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'turnover'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'probability'   => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'end_scheduled' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'products'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'tags'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'relations'     => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        #'leadmodified'       => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        #'leadcreated'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        #'leadmodifier'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        #'leadlastread'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        #'leadlastreader'     => array(Zend_Filter_Input::ALLOW_EMPTY => true)  
    );

    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'start',
        'end',
        'end_scheduled'
    );
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    public function setContactData($_contactData)
    {
        $_key = $this->_properties['id'];
        $_contact = $_contactData[$_key];
        $this->_properties['contacts'] = $_contact;
    }
    
    /**
     * converts a int, string or Crm_Model_Lead to a lead id
     *
     * @param int|string|Crm_Model_Lead $_accountId the lead id to convert
     * @return int
     */
    static public function convertLeadIdToInt($_leadId)
    {
        if($_leadId instanceof Crm_Model_Lead) {
            if(empty($_leadId->id)) {
                throw new Exception('no lead id set');
            }
            $id = (int) $_leadId->id;
        } else {
            $id = (int) $_leadId;
        }
        
        if($id === 0) {
            throw new Exception('lead id can not be 0');
        }
        
        return $id;
    }
    
    /**
     * create new record from json data
     *
     * @param string $_data json encoded data
     * @return Crm_Model_Lead task record
     * 
     * @todo add related records
     * @todo add different backend types?
     * @todo decode tags?
     */
    public static function setFromJson($_data)
    {
        $decodedLead = Zend_Json::decode($_data);
        
        Zend_Registry::get('logger')->debug("setFromJson:" . print_r($decodedLead,true));
        
        /************* add relations *******************/
        /*
        $relationTypes = array(
            'responsible' => array(
                'model'     => 'Addressbook_Model_Contact',
                'backend'   => Addressbook_Backend_Factory::SQL,
                'type'      => 'RESPONSIBLE'
            ),
            'customer' => array(
                'model'     => 'Addressbook_Model_Contact',
                'backend'   => Addressbook_Backend_Factory::SQL,
                'type'      => 'CUSTOMER'
            ), 
            'partner' => array(
                'model'     => 'Addressbook_Model_Contact',
                'backend'   => Addressbook_Backend_Factory::SQL,
                'type'      => 'PARTNER'
            ), 
            'tasks' => array(
                'model'     => 'Tasks_Model_Task',
                'backend'   => Tasks_Backend_Factory::SQL,
                'type'      => 'TASK'
            ), 
        );
            
        // build relation data array
        $relationData = array();
        foreach ($relationTypes as $type => $values) {  
            if (isset($decodedLead[$type])) {          
                foreach ($decodedLead[$type] as $relation) {
                    $data = array(
                        'id'                     => (isset($relation['link_id'])) ? $relation['link_id'] : NULL,
                        'own_model'              => 'Crm_Model_Lead',
                        'own_backend'            => Crm_Backend_Factory::SQL,
                        'own_id'                 => $decodedLead['id'],
                        'own_degree'             => Tinebase_Relation_Model_Relation::DEGREE_SIBLING,
                        'related_model'          => $values['model'],
                        'related_backend'        => $values['backend'],
                        'related_id'             => $relation['id'],
                        'type'                   => $values['type']                    
                    );
                    
                    $relationData[] = $data;
                }
            }
        }
        $decodedLead['relations'] = $relationData;
        */
        
        /********************** add tags ***********************/
        
        if (isset($decodedLead['tags'])) {
            $decodedLead['tags'] = Zend_Json::decode($decodedLead['tags']);
        }                             
        
        /********************** create record ***********************/
        
        $lead = new Crm_Model_Lead($decodedLead);
        
        return $lead;         
    }            
}
