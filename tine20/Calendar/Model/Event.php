<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Model of an event
 * 
 * Recuring Notes: 
 *  - deleted recurring exceptions are stored in exdate (array of datetimes)
 *  - modified recurring exceptions have their own event with recurid set the uid-dtstart
 *    of the originators event (@see RFC2445)
 *  - as id is unique, each modified recurring event has its own id
 *  - rrule is stored in RCF2445 format
 *  - the rrule_until is redundat to the rrule until property for fast queries
 *  - we don't use rrule count, they are converted to an until
 *  - like always in tine, we save all dates in UTC, but to correctly compute
 *    recurring events, we also save the timezone of the organizer
 *  - despite RFC2445 we have an expicit isAllDayEvent property
 * 
 * @package Calendar
 */
class Calendar_Model_Event extends Tinebase_Record_Abstract
{
    const TRANSP_TRANSP        = 'TRANSPARENT';
    const TRANSP_OPAQUE        = 'OPAQUE';
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
    protected $_application = 'Calendar';
    
    /**
     * validators
     *
     * @var array
     */
    protected $_validators = array(
        // tine record fields
        'id'                   => array('allowEmpty' => true,  'Alnum'),
        'container_id'         => array('allowEmpty' => true,  'Int'  ),
        'created_by'           => array('allowEmpty' => true,         ),
        'creation_time'        => array('allowEmpty' => true          ),
        'last_modified_by'     => array('allowEmpty' => true          ),
        'last_modified_time'   => array('allowEmpty' => true          ),
        'is_deleted'           => array('allowEmpty' => true          ),
        'deleted_time'         => array('allowEmpty' => true          ),
        'deleted_by'           => array('allowEmpty' => true          ),
        'seq'                  => array('allowEmpty' => true,  'Int'  ),
        // calendar only fields
        'dtend'                => array('allowEmpty' => true          ),
        'transp'               => array('allowEmpty' => true,  'InArray' => array(self::TRANSP_TRANSP, self::TRANSP_OPAQUE)),
        // ical common fields
        'class_id'             => array('allowEmpty' => true, 'Int'   ),
        'description'          => array('allowEmpty' => true          ),
        'geo'                  => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'location'             => array('allowEmpty' => true          ),
        'organizer'            => array('allowEmpty' => true,         ),
        'priority'             => array('allowEmpty' => true, 'Int'   ),
        'status_id'            => array('allowEmpty' => true          ),
        'summary'              => array('allowEmpty' => true          ),
        'url'                  => array('allowEmpty' => true          ),
        'uid'                  => array('allowEmpty' => true          ),
        // ical common fields with multiple appearance
        //'attach'                => array('allowEmpty' => true         ),
        'attendee'              => array('allowEmpty' => true         ), // RecordSet of Calendar_Model_Attender
        'alarms'                => array('allowEmpty' => true         ), // RecordSet of Tinebase_Model_Alarm
        'tags'                  => array('allowEmpty' => true         ), // originally categories handled by Tinebase_Tags
        'notes'                 => array('allowEmpty' => true         ), // originally comment handled by Tinebase_Notes
        //'contact'               => array('allowEmpty' => true         ),
        //'related'               => array('allowEmpty' => true         ),
        //'resources'             => array('allowEmpty' => true         ),
        //'rstatus'               => array('allowEmpty' => true         ),
        // ical scheduleable interface fields
        'dtstart'               => array('allowEmpty' => true         ),
        'recurid'               => array('allowEmpty' => true         ),
        // ical scheduleable interface fields with multiple appearance
        'exdate'                => array('allowEmpty' => true         ), //  array of Zend_Date's
        //'exrule'                => array('allowEmpty' => true         ),
        //'rdate'                 => array('allowEmpty' => true         ),
        'rrule'                 => array('allowEmpty' => true         ),
        // calendar helper fields
        'is_all_day_event'      => array('allowEmpty' => true         ),
        'rrule_until'           => array('allowEmpty' => true         ),
        'originator_tz'         => array('allowEmpty' => true         ),
    
        // grant helper fields
        Tinebase_Model_Container::READGRANT   => array('allowEmpty' => true),
        Tinebase_Model_Container::EDITGRANT   => array('allowEmpty' => true),
        Tinebase_Model_Container::DELETEGRANT => array('allowEmpty' => true),
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
        'dtend', 
        'exdate',
        //'rdate',
        'rrule_until',
    );
    
    /**
     * sets record related properties
     * 
     * @param string _name of property
     * @param mixed _value of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @return void
     */
    public function __set($_name, $_value)
    {
        // ensure exdate as array
        if ($_name == 'exdate' && ! empty($_value) && ! is_array($_value)) {
            $_value = array($_value);
        }
        
        if ($_name == 'attendee' && is_array($_value)) {
            $_value = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $_value);
        }
        
        parent::__set($_name, $_value);
    }
    
    /**
     * the constructor
     * it is needed because we have more validation fields in Calendars
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
     * gets translated field name
     * 
     * NOTE: this has to be done explicitly as our field names are technically 
     *       and have no translations
     *       
     * @param string         $_field
     * @param Zend_Translate $_translation
     * @return string
     */
    public static function getTranslatedFieldName($_field, $_translation)
    {
        $t = $_translation;
        switch ($_field) {
            case 'dtstart':           return $t->_('Start');
            case 'dtend':             return $t->_('End');
            case 'transp':            return $t->_('Blocking');
            case 'class_id':          return $t->_('Classification');
            case 'description':       return $t->_('Description');
            case 'location':          return $t->_('Location');
            case 'organizer':         return $t->_('Organizer');
            case 'priority':          return $t->_('Priority');
            case 'status_id':         return $t->_('Status');
            case 'summary':           return $t->_('Summary');
            case 'url':               return $t->_('Url');
            case 'rrule':             return $t->_('Recurrance rule');
            case 'is_all_day_event':  return $t->_('Is all day event');
            case 'originator_tz':     return $t->_('Organizer timezone');
            default:                  return $_field;
        }
    }
    
    /**
     * gets translated value
     * 
     * NOTE: This is needed for values like Yes/No, Datetimes, etc.
     * 
     * @param  string           $_field
     * @param  mixed            $_value
     * @param  Zend_Translate   $_translation
     * @param  string           $_timezone
     * @return string
     */
    public static function getTranslatedValue($_field, $_value, $_translation, $_timezone)
    {
        if ($_value instanceof Zend_Date) {
            $locale = new Zend_Locale($_translation->getAdapter()->getLocale());
            return Tinebase_Translation::dateToStringInTzAndLocaleFormat($_value, $_timezone, $locale);
        }
        
        switch ($_field) {
            case 'transp':
                return $_value ? $_translation->_('Yes') : $_translation->_('No');
            default:
                return $_value;
        }
    }
    
    /**
     * sets recurId of this model
     * 
     * @return string recurid which was set
     */
    public function setRecurId()
    {
        if (! ($this->uid && $this->dtstart)) {
            throw new Exception ('uid _and_ dtstart must be set to generate recurid');
        }
        
        $this->recurid = $this->uid . '-' . $this->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        return $this->recurid;
    }
    
    /**
     * cleans up data to only contain freebusy infos
     * removes all fields except dtstart/dtend/id/modlog fields
     * 
     * @return void
     */
    public function doFreeBusyCleanup()
    {
    	if ($this->readGrant || $this->editGrant) {
    	   return;
    	}
    	
        $this->_properties = array_intersect_key($this->_properties, array_flip(array(
            'id', 
            'dtstart', 
            'dtend', 
            'is_all_day_event',
            'rrule',
            'rrule_until',
            'attendee', // if we remove this, we need to adopt attendee resolveing
            'container_id',
            'created_by',
            'creation_time',
            'last_modified_by',
            'last_modified_time',
            'is_deleted',
            'deleted_time',
            'deleted_by',
        )));
    }
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     */
    public function setFromArray(array $_data)
    {
        if (empty($_data['geo'])) {
            $_data['geo'] = NULL;
        }
        
        if (empty($_data['class_id'])) {
            $_data['class_id'] = NULL;
        }
        
        if (empty($_data['priority'])) {
            $_data['priority'] = NULL;
        }
        
        if (empty($_data['status_id'])) {
            $_data['status_id'] = NULL;
        }
        
        if (isset($_data['container_id']) && is_array($_data['container_id'])) {
            $_data['container_id'] = $_data['container_id']['id'];
        }
        
        if (isset($_data['organizer']) && is_array($_data['organizer'])) {
            $_data['organizer'] = $_data['organizer']['id'];
        }
        
        if (isset($_data['attendee']) && is_array($_data['attendee'])) {
            $_data['attendee'] = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $_data['attendee']);
        }
        
        if (isset($_data['rrule']) && is_array($_data['rrule'])) {
            $_data['rrule'] = new Calendar_Model_Rrule($_data['rrule']);
        }
        
        if (isset($_data['alarms']) && is_array($_data['alarms'])) {
            $_data['alarms'] = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', $_data['alarms'], TRUE);
        }
        
        parent::setFromArray($_data);
    }
}
