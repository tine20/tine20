<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Task-Record Class
 * @package Tasks
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
        'container_id'         => array('allowEmpty' => true,  'Int' ),
        'created_by'           => array('allowEmpty' => true,        ),
        'creation_time'        => array('allowEmpty' => true         ),
        'last_modified_by'     => array('allowEmpty' => true         ),
        'last_modified_time'   => array('allowEmpty' => true         ),
        'is_deleted'           => array('allowEmpty' => true         ),
        'deleted_time'         => array('allowEmpty' => true         ),
        'deleted_by'           => array('allowEmpty' => true         ),
        // task only fields
        'id'                   => array('allowEmpty' => true, 'Alnum'),
        'percent'              => array('allowEmpty' => true, 'default' => 0),
        'completed'            => array('allowEmpty' => true         ),
        'due'                  => array('allowEmpty' => true         ),
        // ical common fields
        'class'                => array('allowEmpty' => true,  'InArray' => array(self::CLASS_PUBLIC, self::CLASS_PRIVATE, /*self::CLASS_CONFIDENTIAL*/)),
        'description'          => array('allowEmpty' => true         ),
        'geo'                  => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'location'             => array('allowEmpty' => true         ),
        'organizer'            => array('allowEmpty' => true,        ),
        'originator_tz'        => array('allowEmpty' => true         ),
        'priority'             => array('allowEmpty' => true, 'default' => 1),
        'status_id'            => array('allowEmpty' => true         ),
        'summary'              => array('presence' => 'required'     ),
        'url'                  => array('allowEmpty' => true         ),
        // ical common fields with multiple appearance
        'attach'                => array('allowEmpty' => true        ),
        'attendee'              => array('allowEmpty' => true        ),
        'tags'                  => array('allowEmpty' => true        ), //originally categories
        'comment'               => array('allowEmpty' => true        ),
        'contact'               => array('allowEmpty' => true        ),
        'related'               => array('allowEmpty' => true        ),
        'resources'             => array('allowEmpty' => true        ),
        'rstatus'               => array('allowEmpty' => true        ),
        // scheduleable interface fields
        'dtstart'               => array('allowEmpty' => true        ),
        'duration'              => array('allowEmpty' => true        ),
        'recurid'               => array('allowEmpty' => true        ),
        // scheduleable interface fields with multiple appearance
        'exdate'                => array('allowEmpty' => true        ),
        'exrule'                => array('allowEmpty' => true        ),
        'rdate'                 => array('allowEmpty' => true        ),
        'rrule'                 => array('allowEmpty' => true        ),
        // tine 2.0 notes, alarms and relations
        'notes'                 => array('allowEmpty' => true        ),
        'alarms'                => array('allowEmpty' => true        ), // RecordSet of Tinebase_Model_Alarm
        'relations'             => array('allowEmpty' => true        ),
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
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_data, true));
        
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
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_data, true));
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
        $status = Tasks_Controller_Status::getInstance()->getTaskStatus($this->status_id);
        $organizerName = ($this->organizer) ? $this->organizer->accountDisplayName : '';
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->toArray(), TRUE));
        
        $text = $this->summary . "\n\n" 
            . $translate->_('Due')          . ': ' . $dueDateString         . "\n" 
            . $translate->_('Organizer')    . ': ' . $organizerName         . "\n" 
            . $translate->_('Description')  . ': ' . $this->description     . "\n"
            . $translate->_('Priority')     . ': ' . $this->priority        . "\n"
            . $translate->_('Status')       . ': ' . $status['status_name'] . "\n"
            . $translate->_('Percent')      . ': ' . $this->percent         . "%\n\n";
            
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
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $text);
            
        return $text;
    }
}
