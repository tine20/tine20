<?php
/**
 * class to hold lead data
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'lead_name'             => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'leadstate_id'          => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'leadtype_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'leadsource_id'         => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'container_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'start'                 => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'end'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'turnover'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'probability'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'end_scheduled'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'products'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
        'end_scheduled',
        'creation_time',
        'last_modified_time',
        'deleted_time'
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
     * fills record from json data
     *
     * @param  string $_data json encoded data
     * @return void
     */
    public function setFromJson($_data)
    {
        $decodedLead = Zend_Json::decode($_data);

        //Zend_Registry::get('logger')->debug("setFromJson:" . print_r($decodedLead,true));
        
        /************* add new relations *******************/
        
        foreach ((array)$decodedLead['relations'] as $key => $relation) {
            
            if (!isset($relation['id'])) {
                $data = array(
                    'own_model'              => 'Crm_Model_Lead',
                    'own_backend'            => Crm_Backend_Factory::SQL,
                    'own_id'                 => (isset($decodedLead['id'])) ? $decodedLead['id'] : 0,
                    'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'type'                   => $relation['type'],
                    'related_record'         => (isset($relation['related_record'])) ? $relation['related_record'] : array(),
                    'related_id'             => (isset($relation['related_id'])) ? $relation['related_id'] : NULL,
                );
                
                switch ($relation['type']) {
                    case 'RESPONSIBLE':                        
                        $data['related_model'] = 'Addressbook_Model_Contact';
                        $data['related_backend'] = Addressbook_Backend_Factory::SQL;
                        break;                    
                    case 'CUSTOMER':
                        $data['related_model'] = 'Addressbook_Model_Contact';
                        $data['related_backend'] = Addressbook_Backend_Factory::SQL;
                        break;                    
                    case 'PARTNER':
                        $data['related_model'] = 'Addressbook_Model_Contact';
                        $data['related_backend'] = Addressbook_Backend_Factory::SQL;
                        break;                    
                    case 'TASK':
                        $data['related_model'] = 'Tasks_Model_Task';
                        $data['related_backend'] = Tasks_Backend_Factory::SQL;
                        break;                    
                    default:
                        throw new Exception('relation type not supported');
                }

                // sanitize container id
                if (isset($relation['related_record']) && is_array($relation['related_record']['container_id'])) {
                    $data['related_record']['container_id'] = $relation['related_record']['container_id']['id'];
                }
                    
                //    $data['related_record']['tags'] = '';
                
                $decodedLead['relations'][$key] = $data;
            } else {
                // update relation type                
                if (isset($relation['related_record']['relation_type']) && $relation['type'] !== strtoupper($relation['related_record']['relation_type'])) {
                    $decodedLead['relations'][$key]['type'] = strtoupper($relation['related_record']['relation_type']);
                }
            }
        }        
        
        /********************** add tags ***********************/
        
        if (isset($decodedLead['tags'])) {
            $decodedLead['tags'] = Zend_Json::decode($decodedLead['tags']);
        }                             

        /********************** add notes ***********************/
        
        if (isset($decodedLead['notes'])) {
            $decodedLead['notes'] = Zend_Json::decode($decodedLead['notes']);
        }                             
        
        /********************** create record ***********************/

        //Zend_Registry::get('logger')->debug("setFromJson (after relation adding):" . print_r($decodedLead,true));
        
        $this->setFromArray($decodedLead);
    }            
}
