<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Task-Record Class
 * 
 * @package     Tasks
 * @subpackage    Model
 */
class Tasks_Model_Task extends Tinebase_Record_Abstract
{
    const CLASS_PUBLIC         = 'PUBLIC';
    const CLASS_PRIVATE        = 'PRIVATE';
    //const CLASS_CONFIDENTIAL   = 'CONFIDENTIAL';
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
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
        'Tinebase_Model_User'     => array('created_by', 'last_modified_by', 'organizer'),
        'recursive'               => array('attachments' => 'Tinebase_Model_Tree_Node'),
    );
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tasks';
    
    /**
     * validators
     *
     * @var array
     */
    protected $_validators = array(
        // tine record fields
        'container_id'         => array(Zend_Filter_Input::ALLOW_EMPTY => true,  'Int' ),
        'created_by'           => array(Zend_Filter_Input::ALLOW_EMPTY => true,        ),
        'creation_time'        => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'last_modified_by'     => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'last_modified_time'   => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'is_deleted'           => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'deleted_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'deleted_by'           => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'seq'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        // task only fields
        //'id'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'Alnum'),
        'id'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'percent'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'default' => 0),
        'completed'            => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'due'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        // ical common fields
        'class'                => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            array('InArray', array(self::CLASS_PUBLIC, self::CLASS_PRIVATE, /*self::CLASS_CONFIDENTIAL*/)),
        ),
        'description'          => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'geo'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'location'             => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'organizer'            => array(Zend_Filter_Input::ALLOW_EMPTY => true,        ),
        'originator_tz'        => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'priority'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'default' => 1),
        'status'               => array(Zend_Filter_Input::ALLOW_EMPTY => false        ),
        'summary'              => array(Zend_Filter_Input::PRESENCE => 'required'      ),
        'url'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'uid'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'etag'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        // ical common fields with multiple appearance
        'attach'               => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'attendee'             => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'tags'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true        ), //originally categories
        'comment'              => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'contact'              => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'related'              => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'resources'            => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'rstatus'              => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        // scheduleable interface fields
        'dtstart'              => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'duration'             => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'recurid'              => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        // scheduleable interface fields with multiple appearance
        'exdate'               => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'exrule'               => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'rdate'                => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'rrule'                => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        // tine 2.0 notes, alarms and relations
        'notes'                => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'alarms'               => array(Zend_Filter_Input::ALLOW_EMPTY => true        ), // RecordSet of Tinebase_Model_Alarm
        'relations'            => array(Zend_Filter_Input::ALLOW_EMPTY => true        ),
        'attachments'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'creation_time', 
        'last_modified_time', 
        'deleted_time', 
        'completed', 
        'dtstart', 
        'due', 
        'exdate', 
        'rdate'
    );
    
    /**
     * the constructor
     * it is needed because we have more validation fields in Tasks
     * 
     * @param mixed $_data
     * @param bool $bypassFilters sets {@see this->bypassFilters}
     * @param bool $convertDates sets {@see $this->convertDates}
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_filters['organizer'] = new Zend_Filter_Empty(NULL);
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * sets the record related properties from user generated input.
     *
     * @param   array $_data
     * @return void
     */
    public function setFromArray(array $_data)
    {
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_data, true));
        
        if (empty($_data['geo'])) {
            $_data['geo'] = NULL;
        }
        
        if (empty($_data['class'])) {
            $_data['class'] = self::CLASS_PUBLIC;
        }
        
        if (isset($_data['organizer']) && is_array($_data['organizer'])) {
            $_data['organizer'] = $_data['organizer']['accountId'];
        }
        
        if (isset($_data['alarms']) && is_array($_data['alarms'])) {
            $_data['alarms'] = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', $_data['alarms'], TRUE);
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_data, true));
        parent::setFromArray($_data);
    }
    
    /**
     * create notification message for task alarm
     *
     * @return string
     * 
     * @todo should we get the locale pref for each single user here instead of the default?
     * @todo move lead stuff to Crm(_Model_Lead)?
     * @todo add getSummary to Addressbook_Model_Contact for linked contacts?
     */
    public function getNotificationMessage()
    {
        // get locale from prefs
        $localePref = Tinebase_Core::getPreference()->getValue(Tinebase_Preference::LOCALE);
        $locale = Tinebase_Translation::getLocale($localePref);
        
        $translate = Tinebase_Translation::getTranslation($this->_application, $locale);
        
        // get date strings
        $timezone = ($this->originator_tz) ? $this->originator_tz : Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        $dueDateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat($this->due, $timezone, $locale);
        
        // resolve values
        Tinebase_User::getInstance()->resolveUsers($this, 'organizer', true);
        $status = Tasks_Config::getInstance()->get(Tasks_Config::TASK_STATUS)->records->getById($this->status);
        $organizerName = ($this->organizer) ? $this->organizer->accountDisplayName : '';
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->toArray(), TRUE));
        
        $text = $this->summary . "\n\n" 
            . $translate->_('Due')          . ': ' . $dueDateString                  . "\n" 
            . $translate->_('Organizer')    . ': ' . $organizerName                  . "\n" 
            . $translate->_('Description')  . ': ' . $this->description              . "\n"
            . $translate->_('Priority')     . ': ' . $this->priority                 . "\n"
            . $translate->_('Status')       . ': ' . $translate->_($status['value']) . "\n"
            . $translate->_('Percent')      . ': ' . $this->percent                  . "%\n\n";
            
        // add relations (get with ignore acl)
        $relations = Tinebase_Relations::getInstance()->getRelations(get_class($this), 'Sql', $this->getId(), NULL, array('TASK'), TRUE);
        foreach ($relations as $relation) {
            if ($relation->related_model == 'Crm_Model_Lead') {
                $lead = $relation->related_record;
                $text .= $translate->_('Lead') . ': ' . $lead->lead_name . "\n";
                $leadRelations = Tinebase_Relations::getInstance()->getRelations(get_class($lead), 'Sql', $lead->getId());
                foreach ($leadRelations as $leadRelation) {
                    if ($leadRelation->related_model == 'Addressbook_Model_Contact') {
                        $contact = $leadRelation->related_record;
                        $text .= $leadRelation->type . ': ' . $contact->n_fn . ' (' . $contact->org_name . ')' . "\n"
                            . ((! empty($contact->tel_work)) ?  "\t" . $translate->_('Telephone')   . ': ' . $contact->tel_work   . "\n" : '')
                            . ((! empty($contact->email)) ?     "\t" . $translate->_('Email')       . ': ' . $contact->email      . "\n" : '');
                    }
                }
            }
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $text);
            
        return $text;
    }
    
    /**
     * sets and returns the addressbook entry of the organizer
     * 
     * @return Addressbook_Model_Contact
     */
    public function resolveOrganizer()
    {
        Tinebase_User::getInstance()->resolveUsers($this, 'organizer', true);
        
        if (! empty($this->organizer) && $this->organizer instanceof Tinebase_Model_User) {
            $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($this->organizer->contact_id, TRUE);
            if ($contacts) {
                return $contacts->getFirstRecord();
            }
        }
    }
}
