<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'Task',  // ngettext('GENDER_Task', 'GENDER_Tasks', n)
        'recordsName'       => 'Tasks', // ngettext('Task', 'Tasks', n)
        'hasRelations'      => true,
        'hasCustomFields'   => true, // TODO ?!? yes or no?
        'hasNotes'          => true,
        'hasTags'           => true,
        'modlogActive'      => true,
        'hasAttachments'    => true,
        'hasAlarms'         => true,
        'createModule'      => true,

        'containerProperty' => 'container_id',

        'containerName'     => 'Tasks',
        'containersName'    => 'Tasks',
        'containerUsesFilter' => true,

        'titleProperty'     => 'summary',//array('%s - %s', array('number', 'title')),
        'appName'           => 'Tasks',
        'modelName'         => 'Task',

        'filterModel'       => array(
            'organizer'         => array(
                'filter'            => 'Tinebase_Model_Filter_User',
                'label'             => null,
                'options'           => array(
                    'appName' => 'Tasks', 'modelName' => 'Task'
                ),
            ),
            'queryRelated'      => array(
                'filter'            => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label'             => null,
                'options'           => array(
                    'related_model'     => 'Crm_Model_Lead',
                ),
            ),
        ),
        'fields'            => array(
            'percent'           => array(
                'label'             => 'Percent', //_('Percent')
                'type'              => 'integer',
                'default'           => 0,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'completed'         => array(
                'label'             => 'Completed', //_('Completed')
                'type'              => 'datetime',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'due'               => array(
                'label'             => 'Due', //_('Due')
                'type'              => 'datetime',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'class'             => array(
                'label'             => 'Class', //_('Class')
                'type'              => 'string',
                'validators'        => array(
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    array('InArray', array(self::CLASS_PUBLIC, self::CLASS_PRIVATE, /*self::CLASS_CONFIDENTIAL*/)),
                ),
            ),
            'description'       => array(
                'label'             => 'Description', //_('Description')
                'type'              => 'fulltext',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter'       => true,
            ),
            'geo'               => array(
                'label'             => 'Geo', //_('Geo')
                'type'              => 'float',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'location'          => array(
                'label'             => 'Location', //_('Location')
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'organizer'         => array(
                'label'             => 'Organizer', //_('Organizer')
                'type'              => 'user',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'inputFilters'      => array(Zend_Filter_Empty::class => null),
            ),
            'originator_tz'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'priority'          => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'default'           => Tasks_Model_Priority::NORMAL,
            ),
            'status'            => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false),
            ),
            'summary'           => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::PRESENCE => 'required'),
                'queryFilter'       => true,
            ),
            'url'               => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'uid'               => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'etag'              => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'attach'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'attendee'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'comment'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'contact'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'related'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'resources'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'rstatus'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'dtstart'     => array(
                'label'             => null,
                'type'              => 'datetime',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'duration'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'recurid'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'exdate'     => array(
                'label'             => null,
                'type'              => 'datetime',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'exrule'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'rdate'     => array(
                'label'             => null,
                'type'              => 'datetime',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'rrule'     => array(
                'label'             => null,
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
        ),
    );

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
            $_data['organizer'] = isset($_data['organizer']['account_id']) ?
                $_data['organizer']['account_id'] :
                $_data['organizer']['accountId'];
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
     * @todo what about priority translation here?
     */
    public function getNotificationMessage()
    {
        // get locale from prefs
        $localePref = Tinebase_Core::getPreference()->getValue(Tinebase_Preference::LOCALE);
        $locale = Tinebase_Translation::getLocale($localePref);
        
        $translate = Tinebase_Translation::getTranslation($this->_application, $locale);
        
        // get date strings
        $timezone = ($this->originator_tz) ? $this->originator_tz : Tinebase_Core::getUserTimezone();
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
