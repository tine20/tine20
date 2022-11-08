<?php
/**
 * class to hold lead data
 * 
 * @package     Crm
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Crm_Model_Lead extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'Lead';
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
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format: 
     *     array('Addressbook_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Addressbook_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     * 
     * @var array
     *
     * @todo still needed with MC?
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User'     => array('created_by', 'last_modified_by'),
        'recursive'               => array('attachments' => 'Tinebase_Model_Tree_Node'),
    );
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        'containerName'     => 'Lead list',
        'containersName'    => 'Leads lists',
        'recordName'        => 'Lead',
        'recordsName'       => 'Leads', // ngettext('Lead', 'Leads', n)
        'hasRelations'      => true,
        'copyRelations'     => true,
        'hasCustomFields'   => true,
        'hasSystemCustomFields' => false,
        'hasNotes'          => true,
        'hasTags'           => true,
        'modlogActive'      => true,
        'hasAttachments'    => true,
        'createModule'      => true,
        'exposeHttpApi'     => true,
        'exposeJsonApi'     => true,
        'containerProperty' => 'container_id',
        'multipleEdit'      => false,

        'titleProperty'     => 'lead_name',
        'appName'           => 'Crm',
        'modelName'         => self::MODEL_NAME_PART, // _('GENDER_Lead')

        'filterModel'       => [
            'showClosed'      => [
                'filter'            => Crm_Model_LeadClosedFilter::class,
                'title'             => 'Show Closed', // _('Show Closed')
                // 'jsConfig'          => ['filtertype' => 'crm...'] // TODO needed?
            ],
            'query'      => [
                'filter'            => Crm_Model_LeadQueryFilter::class,
            ],
            // relation filters
            'contact'        => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
                'related_model'     => 'Addressbook_Model_Contact',
                'filtergroup'    => 'Addressbook_Model_ContactFilter'
            )),
            'product'        => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
                'related_model'     => 'Sales_Model_Product',
                'filtergroup'    => 'Sales_Model_ProductFilter'
            )),
            'task'           => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
                'related_model'     => 'Tasks_Model_Task',
                'filtergroup'    => 'Tasks_Model_TaskFilter'
            )),
        ],
        'fields'            => [
            'lead_name'            => [
                self::LABEL                 => 'Lead name', //_('Lead name')
                self::TYPE                  => self::TYPE_STRING,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                self::INPUT_FILTERS         => [Zend_Filter_StringTrim::class],
            ],
            'leadstate_id'            => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
            ],
            'leadtype_id'            => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
            ],
            'leadsource_id'            => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
            ],
            'start'            => [
                self::LABEL                 => 'Start',
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
            ],
            'end'            => [
                self::LABEL                 => 'End',
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
            ],
            'description'            => [
                self::LABEL                 => 'Description',
                self::TYPE                  => self::TYPE_FULLTEXT,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ],
            'turnover'            => [
                self::TYPE                  => self::TYPE_FLOAT,
                self::NULLABLE              => true,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                self::INPUT_FILTERS          => [Zend_Filter_Empty::class => null],
            ],
            'probableTurnover'            => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ],
            'probability'            => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
            ],
            'end_scheduled'            => [
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
            ],
            'resubmission_date'            => [
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ],
            'mute'            => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
            ],
        ]
    ];

    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        // a lead may have one responsible and/or one customer
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact', 'config' => array(
            array('type' => 'RESPONSIBLE', 'degree' => 'parent', 'text' => 'Responsible', 'max' => '0:0'), // _('Responsible')
            array('type' => 'CUSTOMER', 'degree' => 'parent', 'text' => 'Customer', 'max' => '0:0'),  // _('Customer')
            ),
            'default' => array('type' => 'CUSTOMER', 'related_degree' => 'parent')
        ),
        // a lead may have many tasks, but a task may have one lead, no more
        array('relatedApp' => 'Tasks', 'relatedModel' => 'Task', 'config' => array(
            array('type' => 'TASK', 'degree' => 'sibling', 'text' => 'Task', 'max' => '0:1'), // _('Task')
            ),
        )
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
     * @param   int|string|Crm_Model_Lead $_accountId the lead id to convert
     * @return  int
     * @throws  UnexpectedValueException
     *
     * @refactor remove that
     */
    static public function convertLeadIdToInt($_leadId)
    {
        if($_leadId instanceof Crm_Model_Lead) {
            if(empty($_leadId->id)) {
                throw new UnexpectedValueException('No lead id set.');
            }
            $id = (int) $_leadId->id;
        } else {
            $id = (int) $_leadId;
        }
        
        if($id === 0) {
            throw new UnexpectedValueException('Lead id can not be 0.');
        }
        
        return $id;
    }
    
    /**
     * modify values during setFromJson
     *
     * @param   array $_data the json decoded values
     * @throws  UnexpectedValueException
     * 
     * @todo move to converter
     */
    protected function _setFromJson(array &$_data)
    {
        // TODO move this to a better place (maybe the converter) and check why the client is able to send empty date regardless of validation
        if (empty($_data['start'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                . ' Sanitize empty start date. This should not happen as the client needs to validate the property.');
            $_data['start'] = Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())->toString();
        }
        
        // TODO should be removed as we already have generic relation handling
        if (isset($_data['relations'])) {
            foreach ((array)$_data['relations'] as $key => $relation) {
                if (empty($relation['related_model'])) {
                    // related_model might be missing for contact relations
                    $relation['related_model'] = Addressbook_Model_Contact::class;
                }
                if (! isset($relation['type']) || empty($relation['type']) && isset($relation['related_model'])
                    && $relation['related_model'] === Addressbook_Model_Contact::class)
                {
                    // relation type might be missing for contact relations
                    $relation['type'] = 'CUSTOMER';
                }
                
                if (! isset($relation['id'])) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG) && isset($relation['type'])) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Setting new relation of type ' . $relation['type']);
                    }
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                        . ' ' . print_r($relation, TRUE));
                    
                    $data = array(
                        'own_model'              => 'Crm_Model_Lead',
                        'own_backend'            => 'Sql',
                        'own_id'                 => (isset($_data['id'])) ? $_data['id'] : 0,
                        'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'type'                   => $relation['type'],
                        'related_record'         => (isset($relation['related_record'])) ? $relation['related_record'] : array(),
                        'related_id'             => (isset($relation['related_id'])) ? $relation['related_id'] : NULL,
                        'remark'                 => (isset($relation['remark'])) ? $relation['remark'] : NULL,
                        'related_model'          => (isset($relation['related_model'])) ? $relation['related_model'] : NULL,
                        'related_backend'        => (isset($relation['related_backend'])) ? $relation['related_backend'] : Addressbook_Backend_Factory::SQL
                    );
                    
                    // set id from related record (if it didn't got set in javascript frontend)
                    if ($data['related_id'] === NULL && !empty($relation['related_record']['id'])) {
                        $data['related_id'] = $relation['related_record']['id'];
                    }
                    
                    $relation['type'] = strtoupper($relation['type']);
                    switch ($relation['type']) {
                        case 'RESPONSIBLE':
                        case 'CUSTOMER':
                        case 'PARTNER':
                            $data['related_model'] = 'Addressbook_Model_Contact';
                            $data['related_backend'] = Addressbook_Backend_Factory::SQL;
                            break;
                        case 'TASK':
                            $data['related_model'] = 'Tasks_Model_Task';
                            $data['related_backend'] = Tasks_Backend_Factory::SQL;
                            break;
                        case 'PRODUCT':
                            $data['related_model'] = 'Sales_Model_Product';
                            $data['related_backend'] = 'Sql';
                            break;
                        default:
                            // do nothing
                    }
    
                    // sanitize container id
                    if (isset($relation['related_record']) && $relation['type'] != 'PRODUCT') {
                        if (! isset($relation['related_record']['container_id']) || empty($relation['related_record']['container_id'])) {
                            // use default container for app
                            $data['related_record']['container_id'] = Tinebase_Container::getInstance()->getDefaultContainer(
                                ($relation['type'] == 'TASK') ? Tasks_Model_Task::class : Addressbook_Model_Contact::class,
                                NULL,
                                ($relation['type'] == 'TASK') ? Tasks_Preference::DEFAULTTASKLIST : Addressbook_Preference::DEFAULTADDRESSBOOK
                            )->getId();
                        } elseif (is_array($relation['related_record']['container_id'])) {
                            $data['related_record']['container_id'] = $relation['related_record']['container_id']['id'];
                        }
                    }
                        
                    $_data['relations'][$key] = $data;
                } else {
                    // update relation type
                    if (isset($relation['related_record']['relation_type']) && $relation['type'] !== strtoupper($relation['related_record']['relation_type'])) {
                        $_data['relations'][$key]['type'] = strtoupper($relation['related_record']['relation_type']);
                    }
                }
            }
        }
    }

    /**
     * use probability / end date to determine lead status
     * 
     * @return string
     */
    public function getLeadStatus()
    {
        if (empty($this->end)) {
            $result = 'open';
        } else {
            if ($this->probability == 100) {
                $result = 'won';
            } elseif ($this->probability == 0) {
                $result = 'lost';
            } else {
                // open or unknown/undefined?
                $result = 'open';
            }
        }
        return $result;
    }

    /**
     * old function for Responsibles
     *
     * @return Tinebase_Record_RecordSet
     */
    public function getResponsibles()
    {
        return $this->getRelations(['RESPONSIBLE']);
    }

    /**
     * get all Relation for type.
     *
     * @param $relationTypes array
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    public function getRelations($relationTypes)
    {
        $responsibles = new Tinebase_Record_RecordSet('Addressbook_Model_Contact');

        foreach ($relationTypes as $relationType) {
            foreach ($this->relations as $relation) {
                if ($relation->related_model == 'Addressbook_Model_Contact'
                    && $relation->type == $relationType
                    && is_object($relation->related_record)
                ) {
                    $responsibles->addRecord($relation->related_record);
                }
            }
        }

        return $responsibles;
    }

    /**
     * @return mixed|string
     */
    public function getTitle()
    {
        return $this->lead_name;
    }
}
