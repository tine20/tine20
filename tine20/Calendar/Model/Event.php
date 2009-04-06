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
 *  - deleted recuring exceptions are stored in exdate (array of datetimes)
 *  - modified recuring exceptions have their own event with recurid set the uid-dtstart
 *    of the originators event (@see RFC2445)
 *  - as id is unique, each modified recuring event has its own id
 *  - rrule is stored in RCF2445 format
 *  - the rrule_until is redundat to the rrule until property for fast queries
 *  - we don't use rrule count, they are converted to an until
 *  - like always in tine, we save all dates in UTC, but to correctly compute
 *    recuring events, we also save the timezone of the organizer
 *  - despite RFC2445 we have an expicit isAllDayEvent property
 * 
 * @package Calendar
 */
class Calendar_Model_Event extends Tinebase_Record_Abstract
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
        'created_by'           => array('allowEmpty' => true,  'Int'  ),
        'creation_time'        => array('allowEmpty' => true          ),
        'last_modified_by'     => array('allowEmpty' => true          ),
        'last_modified_time'   => array('allowEmpty' => true          ),
        'is_deleted'           => array('allowEmpty' => true          ),
        'deleted_time'         => array('allowEmpty' => true          ),
        'deleted_by'           => array('allowEmpty' => true          ),
        'seq'                  => array('allowEmpty' => true,  'Int'  ),
        // calendar only fields
        'dtend'                => array('allowEmpty' => true          ),
        'transp'               => array('allowEmpty' => true          ),
        // ical common fields
        'class_id'             => array('allowEmpty' => true, 'Int'   ),
        'description'          => array('allowEmpty' => true          ),
        'geo'                  => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'location'             => array('allowEmpty' => true          ),
        'organizer'            => array('allowEmpty' => true, 'Int'   ),
        'priority'             => array('allowEmpty' => true, 'default' => 1),
        'status_id'            => array('allowEmpty' => true          ),
        'summary'              => array('presence' => 'required'      ),
        'url'                  => array('allowEmpty' => true          ),
        'uid'                  => array('allowEmpty' => true          ),
        // ical common fields with multiple appearance
        //'attach'                => array('allowEmpty' => true         ),
        'attendee'              => array('allowEmpty' => true         ), // RecordSet of Calendar_Model_Attendee
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
        'is_all_day_evnet'      => array('allowEmpty' => true         ),
        'rrule_until'           => array('allowEmpty' => true         ),
        'originator_tz'         => array('allowEmpty' => true         ),
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
     * fill record from json data
     *
     * @param string $_data json encoded data
     * @return void
     */
    public function setFromJson($_data)
    {
        $data = Zend_Json::decode($_data);
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($data, true));
        
        if (empty($data['geo'])) {
            $data['geo'] = NULL;
        }
        
        if (isset($data['container_id']) && is_array($data['container_id'])) {
            $data['container_id'] = $data['container_id']['id'];
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($data, true));
        $this->setFromArray($data);
    }
}