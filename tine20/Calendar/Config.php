<?php
/**
 * @package     Calendar
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * calendar config class
 * 
 * @package     Calendar
 * @subpackage  Config
 */
class Calendar_Config extends Tinebase_Config_Abstract
{
    /**
     * Fixed Calendars
     * 
     * @var string
     */
    const FIXED_CALENDARS = 'fixedCalendars';

    /**
     * Event Status Available
     *
     * @var string
     */
    const EVENT_STATUS = 'eventStatus';

    /**
     * Event Transparencies Available
     *
     * @var string
     */
    const EVENT_TRANSPARENCIES = 'eventTransparencies';

    /**
     * Event Classes Available
     *
     * @var string
     */
    const EVENT_CLASSES = 'eventClasses';

    /**
     * Attendee Status Available
     * 
     * @var string
     */
    const ATTENDEE_STATUS = 'attendeeStatus';
    
    /**
     * Attendee Roles Available
     * 
     * @var string
     */
    const ATTENDEE_ROLES = 'attendeeRoles';

    /**
     * Resource Types Available
     *
     * @var string
     */
    const RESOURCE_TYPES = 'resourceTypes';

    /**
     * FreeBusy Types Available
     *
     * @var string
     */
    const FREEBUSY_TYPES = 'freebusyTypes';

    /**
     * which information does the grant freebusy reveal
     *
     * @var string
     */
    const FREEBUSY_INFO_ALLOWED = 'freebusyInfoAllowed';

    /**
     * Crop days view
     *
     * @var string
     */
    const CROP_DAYS_VIEW = 'daysviewcroptime';

    /**
     * Days view mouse wheel increment
     *
     * @var integer
     */
    const DAYS_VIEW_MOUSE_WHEEL_INCREMENT = 'daysviewwheelincrement';
    
    /**
     * Allow events outside the definition created by the edit dialog
     * 
     * @var string
     */
    const CROP_DAYS_VIEW_ALLOW_ALL_EVENTS = 'daysviewallowallevents';

    /**
     * MAX_FILTER_PERIOD_CALDAV
     * 
     * @var string
     */
    const MAX_FILTER_PERIOD_CALDAV = 'maxfilterperiodcaldav';

    /**
     * MAX_FILTER_PERIOD_CALDAV_SYNCTOKEN
     *
     * @var string
     */
    const MAX_FILTER_PERIOD_CALDAV_SYNCTOKEN = 'maxfilterperiodcaldavsynctoken';

    /**
     * MAX_NOTIFICATION_PERIOD_FROM
     * 
     * @var string
     */
    const MAX_NOTIFICATION_PERIOD_FROM = 'maxnotificationperiodfrom';
    
    /**
     * MAX_JSON_DEFAULT_FILTER_PERIOD_FROM
     * 
     * @var string
     */
    const MAX_JSON_DEFAULT_FILTER_PERIOD_FROM = 'maxjsondefaultfilterperiodfrom';
    
    /**
     * MAX_JSON_DEFAULT_FILTER_PERIOD_UNTIL
     * 
     * @var string
     */
    const MAX_JSON_DEFAULT_FILTER_PERIOD_UNTIL = 'maxjsondefaultfilterperioduntil';
    
    /**
     * DISABLE_EXTERNAL_IMIP
     *
     * @var string
     */
    const DISABLE_EXTERNAL_IMIP = 'disableExternalImip';
    
    /**
     * SKIP_DOUBLE_EVENTS
     *
     * @var string
     */
    const SKIP_DOUBLE_EVENTS = 'skipdoubleevents';

    /**
     * Send attendee mails to users with edit permissions to the added resource
     */
    const RESOURCE_MAIL_FOR_EDITORS = 'resourcemailforeditors';

    /**
     * FEATURE_SPLIT_VIEW
     *
     * @var string
     */
    const FEATURE_SPLIT_VIEW = 'featureSplitView';

    /**
     * FEATURE_YEAR_VIEW
     *
     * @var string
     */
    const FEATURE_YEAR_VIEW = 'featureYearView';

    /**
     * FEATURE_EXTENDED_EVENT_CONTEXT_ACTIONS
     *
     * @var string
     */
    const FEATURE_EXTENDED_EVENT_CONTEXT_ACTIONS = 'featureExtendedEventContextActions';

    /**
     * FEATURE_COLOR_BY
     *
     * @var string
     */
    const FEATURE_COLOR_BY = 'featureColorBy';

    /**
     * FEATURE_POLLS
     *
     * @var string
     */
    const FEATURE_POLLS = 'featurePolls';


    /**
     * EVENT_VIEW
     *
     * @var string
     */
    const EVENT_VIEW = 'eventView';
    
    /**
     * FEATURE_RECUR_EXCEPT
     *
     * @var string
     */
    const FEATURE_RECUR_EXCEPT = 'featureRecurExcept';

    /**
     * @var string
     */
    const TENTATIVE_NOTIFICATIONS = 'tentativeNotifications';

    /**
     * @var string
     */
    const TENTATIVE_NOTIFICATIONS_ENABLED = 'enabled';

    /**
     * @var string
     */
    const TENTATIVE_NOTIFICATIONS_DAYS = 'days';

    /**
     * @var string
     */
    const TENTATIVE_NOTIFICATIONS_FILTER = 'filter';

    const ASSIGN_ORGANIZER_MEETING_STATUS_ON_EDIT_GRANT = 'assignOrgMeetingStatusOnEditGrant';

    /**
     * @var string
     */
    const POLL_MUTE_ALTERNATIVES_NOTIFICATIONS = 'pollMuteAlternativesNotifications';

    /**
     * @var string
     */
    const POLL_GTC = 'pollGTC';

    const SEARCH_ATTENDERS_FILTER = 'searchAttendersFilter';
    const SEARCH_ATTENDERS_FILTER_USER = 'user';
    const SEARCH_ATTENDERS_FILTER_GROUP = 'group';
    const SEARCH_ATTENDERS_FILTER_RESOURCE = 'resource';

    const FREEBUSY_INFO_ALLOW_DATETIME = 10;
    const FREEBUSY_INFO_ALLOW_ORGANIZER = 20;
    const FREEBUSY_INFO_ALLOW_RESOURCE_ATTENDEE = 30;
    const FREEBUSY_INFO_ALLOW_CALENDAR = 40;
    const FREEBUSY_INFO_ALLOW_ALL_ATTENDEE = 50;


    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::FIXED_CALENDARS => array(
            //_('Fixed Calendars')
            'label'                 => 'Fixed Calendars',
            //_('Calendars always selected regardless of all filter parameters. A valid use case might be to force the display of an certain holiday calendar.')
            'description'           => 'Calendars always selected regardless of all filter parameters. A valid use case might be to force the display of an certain holiday calendar.',
            'type'                  => 'array',
            'contents'              => 'string', // in fact this are ids of Tinebase_Model_Container of app Calendar and we might what to have te ui to autocreate pickers panel here? x-type? -> later
            'clientRegistryInclude' => TRUE
        ),
        self::CROP_DAYS_VIEW => array(
                                   //_('Crop Days')
            'label'                 => 'Crop Days',
                                   //_('Crop calendar view configured start and endtime.')
            'description'           => 'Crop calendar view configured start and endtime.',
            'type'                  => Tinebase_Config_Abstract::TYPE_BOOL,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'setBySetupModule'      => false,
            'default'               => false
        
        ),
        self::CROP_DAYS_VIEW_ALLOW_ALL_EVENTS => array(
                                   //_('Crop Days Limit Override')
            'label'                 => 'Crop Days Limit Override',
                                   //_('Allow events outside start and endtime.')
            'description'           => 'Allow events outside start and endtime.',
            'type'                  => Tinebase_Config_Abstract::TYPE_BOOL,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'setBySetupModule'      => false,
            'default'               => false
        
        ),
        self::DAYS_VIEW_MOUSE_WHEEL_INCREMENT => array(
                                    //_('Week View Mouse Wheel Increment')
            'label'                 => 'Week View Mouse Wheel Increment',
            //_('Crop calendar view configured start and endtime.')
            'description'           => 'Number of pixels to scroll per mouse wheel',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'setBySetupModule'      => false,
            'default'               => 50

        ),
        self::EVENT_VIEW => array(
            //_('Default View for Events')
            'label'                 => 'Default View for Events',
            //_('Default View for Events')
            'description'           => 'Default View for Events ("organizer" or "attendee")',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD,
            'options'               => array(
                'records' => array(
                    array('id' => 'attendee',  'value' => 'Attendee'), //_('Attendee')
                    array('id' => 'organizer', 'value' => 'Organizer'), //_('Organizer')
                ),
            ),
            'clientRegistryInclude' => true,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => FALSE,
            'default'               => 'attendee',
        ),
        self::EVENT_STATUS => [
            //_('Event Status Available')
            'label'                 => 'Event Status Available',
            //_('Possible event status. Please note that additional event status might impact other calendar systems on export or synchronisation.')
            'description'           => 'Possible event status. Please note that additional event status might impact other calendar systems on export or synchronisation.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'default'               => [
                'records' => [
                    ['id' => 'CONFIRMED',    'value' => 'Confirmed',   'icon' => 'images/icon-set/icon_ok.svg',                          'system' => true], //_('Confirmed')
                    ['id' => 'TENTATIVE',    'value' => 'Tentative',   'icon' => 'images/icon-set/icon_calendar_attendee_tentative.svg', 'system' => true], //_('Tentative')
                    ['id' => 'CANCELED',     'value' => 'Canceled',    'icon' => 'images/icon-set/icon_calendar_attendee_cancle.svg',    'system' => true], //_('Canceled')
                ],
                'default' => 'CONFIRMED'
            ]
        ],
        self::EVENT_CLASSES => [
            //_('Event Classes Available')
            'label'                 => 'Event Classes Available',
            //_('Possible event classes. Please note that additional event classes might impact other calendar systems on export or synchronisation.')
            'description'           => 'Possible event classes. Please note that additional event classes might impact other calendar systems on export or synchronisation.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'default'               => [
                'records' => [
                    ['id' => 'PUBLIC',       'value' => 'Public',       'system' => true], //_('Public')
                    ['id' => 'PRIVATE',      'value' => 'Private',      'system' => true], //_('Private')
//                    ['id' => 'CONFIDENTIAL', 'value' => 'Confidential', 'system' => true], //_('Confidential')
                ],
                'default' => 'PUBLIC'
            ]
        ],
        self::EVENT_TRANSPARENCIES => [
            //_('Event Transparencies Available')
            'label'                 => 'Event Transparencies Available',
            //_('Possible event transparencies. Please note that additional event transparencies might impact other calendar systems on export or synchronisation.')
            'description'           => 'Possible event transparencies. Please note that additional event transparencies might impact other calendar systems on export or synchronisation.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'default'               => [
                'records' => [
                    ['id' => 'TRANSPARENT', 'value' => 'Transparent', 'system' => true], //_('Transparent')
                    ['id' => 'OPAQUE',      'value' => 'Opaque',      'system' => true], //_('Opaque')
                ],
                'default' => 'OPAQUE'
            ]
        ],
        self::ATTENDEE_STATUS => array(
                                   //_('Attendee Status Available')
            'label'                 => 'Attendee Status Available',
                                   //_('Possible event attendee status. Please note that additional attendee status might impact other calendar systems on export or synchronisation.')
            'description'           => 'Possible event attendee status. Please note that additional attendee status might impact other calendar systems on export or synchronisation.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'options'               => array('recordModel' => 'Calendar_Model_AttendeeStatus'),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'NEEDS-ACTION', 'value' => 'No response', 'icon' => 'images/icon-set/icon_invite.svg',                      'system' => true), //_('No response')
                    array('id' => 'ACCEPTED',     'value' => 'Accepted',    'icon' => 'images/icon-set/icon_calendar_attendee_accepted.svg',                          'system' => true), //_('Accepted')
                    array('id' => 'DECLINED',     'value' => 'Declined',    'icon' => 'images/icon-set/icon_calendar_attendee_cancle.svg',    'system' => true), //_('Declined')
                    array('id' => 'TENTATIVE',    'value' => 'Tentative',   'icon' => 'images/icon-set/icon_calendar_attendee_tentative.svg', 'system' => true), //_('Tentative')
                ),
                'default' => 'NEEDS-ACTION'
            )
        ),
        self::ATTENDEE_ROLES => array(
                                   //_('Attendee Roles Available')
            'label'                 => 'Attendee Roles Available',
                                   //_('Possible event attendee roles. Please note that additional attendee roles might impact other calendar systems on export or synchronisation.')
            'description'           => 'Possible event attendee roles. Please note that additional attendee roles might impact other calendar systems on export or synchronisation.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'options'               => array('recordModel' => Calendar_Model_AttendeeRole::class),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'REQ', 'value' => 'Required', 'system' => true, 'order' => 0), //_('Required')
                    array('id' => 'OPT', 'value' => 'Optional', 'system' => true, 'order' => 1), //_('Optional')
                ),
                'default' => 'REQ'
            )
        ),
        self::RESOURCE_TYPES => array(
            //_('Resource Types Available')
            'label'                 => 'Resource Types Available',
            //_('Possible resource types. Please note that additional free/busy types might impact other calendar systems on export or synchronisation.')
            'description'           => 'Possible resource types. Please note that additional free/busy types might impact other calendar systems on export or synchronisation.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'options'               => array('recordModel' => Calendar_Model_ResourceType::class),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'RESOURCE', 'is_location' => 0, 'value' => 'Resource', 'icon' => 'images/icon-set/icon_resource.svg', 'system' => true), //_('Resource') @todo default resource icon!
                    array('id' => 'ROOM', 'is_location' => 1, 'value' => 'Room', 'icon' => 'images/icon-set/icon_resource_room1.svg', 'system' => true), //_('Room')
                    array('id' => 'BOARD', 'is_location' => 0, 'value' => 'Board', 'icon' => 'images/icon-set/icon_resource_board.svg', 'system' => true), //_('Board')
                    array('id' => 'CABLE', 'is_location' => 0, 'value' => 'Cable', 'icon' => 'images/icon-set/icon_resource_cable.svg', 'system' => true), //_('Cable')
                    array('id' => 'CAR', 'is_location' => 1, 'value' => 'Car', 'icon' => 'images/icon-set/icon_resource_car.svg', 'system' => true), //_('Car')
                    array('id' => 'COFFEE', 'is_location' => 0, 'value' => 'Coffee', 'icon' => 'images/icon-set/icon_resource_coffee.svg', 'system' => true), //_('Coffee')
                    array('id' => 'HOMEOFFICE', 'is_location' => 1, 'value' => 'Homeoffice', 'icon' => 'images/icon-set/icon_resource_homeoffice.svg', 'system' => true), //_('Homeoffice')
                    array('id' => 'LAMP', 'is_location' => 0, 'value' => 'Lamp', 'icon' => 'images/icon-set/icon_resource_lamp.svg', 'system' => true), //_('Lamp')
                    array('id' => 'LAPTOP', 'is_location' => 0, 'value' => 'Laptop', 'icon' => 'images/icon-set/icon_resource_laptop.svg', 'system' => true), //_('Laptop')
                    array('id' => 'MATERIAL', 'is_location' => 0, 'value' => 'Material', 'icon' => 'images/icon-set/icon_resource_material.svg', 'system' => true), //_('Material')
                    array('id' => 'PROJECTOR', 'is_location' => 0, 'value' => 'Projector', 'icon' => 'images/icon-set/icon_resource_projector.svg', 'system' => true), //_('Projector')
                    array('id' => 'TRAVEL', 'is_location' => 1, 'value' => 'Travel', 'icon' => 'images/icon-set/icon_resource_trolly.svg', 'system' => true), //_('Travel')
                ),
                'default' => 'RESOURCE'
            )
        ),
        self::FREEBUSY_TYPES => array(
            //_('Free/Busy Types Available')
            'label'                 => 'Free/Busy Types Available',
            //_('Possible free/busy types. Please note that additional free/busy types might impact other calendar systems on export or synchronisation.')
            'description'           => 'Possible free/busy types. Please note that additional free/busy types might impact other calendar systems on export or synchronisation.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => Calendar_Model_FreeBusy::FREEBUSY_FREE, 'value' => 'Free', 'system' => true), //_('Free')
                    array('id' => Calendar_Model_FreeBusy::FREEBUSY_BUSY, 'value' => 'Busy', 'system' => true), //_('Busy')
                    array('id' => Calendar_Model_FreeBusy::FREEBUSY_BUSY_TENTATIVE, 'value' => 'Tentative', 'system' => true), //_('Tentative')
                    array('id' => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE, 'value' => 'Unavailable', 'system' => true), //_('Unavailable')
                ),
                'default' => Calendar_Model_FreeBusy::FREEBUSY_BUSY
            )
        ),
        self::MAX_FILTER_PERIOD_CALDAV => array(
        //_('Filter timeslot for CalDAV events')
            'label'                 => 'Filter timeslot for events',
        //_('For how long in the past (in months) the events should be synchronized.')
            'description'           => 'For how long in the past (in months) the events should be synchronized.',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => 2,
        ),
        self::MAX_FILTER_PERIOD_CALDAV_SYNCTOKEN => array(
            //_('Filter timeslot for CalDAV events with SyncToken')
            'label'                 => 'Filter timeslot for CalDAV events with SyncToken',
            //_('For how long in the past (in months) the events should be synchronized.')
            'description'           => 'For how long in the past (in months) the events should be synchronized.',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => 100,
        ),
        self::MAX_NOTIFICATION_PERIOD_FROM => array(
        //_('Timeslot for event notifications')
            'label'                 => 'Timeslot for event notifications',
        //_('For how long in the past (in weeks) event notifications should be sent.')
            'description'           => 'For how long in the past (in weeks) event notifications should be sent.',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => 1, // 1 week is default
        ),
        self::MAX_JSON_DEFAULT_FILTER_PERIOD_FROM => array(
        //_('Default filter period (from) for events fetched via JSON API')
            'label'                 => 'Default filter period (from) for events fetched via JSON API',
        //_('For how long in the past (in months) the events should be fetched.')
            'description'           => 'For how long in the past (in months) the events should be fetched.',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => 0,
        ),
        self::MAX_JSON_DEFAULT_FILTER_PERIOD_UNTIL => array(
        //_('Default filter period (until) for events fetched via JSON API')
            'label'                 => 'Default filter period (until) for events fetched via JSON API',
        //_('For how long in the future (in months) the events should be fetched.')
            'description'           => 'For how long in the future (in months) the events should be fetched.',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => 1,
        ),
        self::DISABLE_EXTERNAL_IMIP => array(
        //_('Disable iMIP for external organizers')
            'label'                 => 'Disable iMIP for external organizers',
        //_('Disable iMIP for external organizers')
            'description'           => 'Disable iMIP for external organizers',
            'type'                  => Tinebase_Config_Abstract::TYPE_BOOL,
            'clientRegistryInclude' => false,
            'setByAdminModule'      => true,
            'setBySetupModule'      => true,
            'default'               => false,
        ),
        self::SKIP_DOUBLE_EVENTS => array(
            //_('(CalDAV) Skip double events from personal or shared calendar')
            'label'                 => '(CalDAV) Skip double events from personal or shared calendar',
            //_('(CalDAV) Skip double events from personal or shared calendar ("personal" > Skip events from personal calendar or "shared" > Skip events from shared calendar)')
            'description'           => '(CalDAV) Skip double events from personal or shared calendar ("personal" > Skip events from personal calendar or "shared" > Skip events from shared calendar)',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
            'default'               => '',
        ),
        self::RESOURCE_MAIL_FOR_EDITORS => array(
            //_('Send notifications to every user with edit permissions of the added resources')
            'label'                 => 'Send notifications to every user with edit permissions of the added resources',
            'description'           => 'Send notifications to every user with edit permissions of the added resources',
            'type'                  => Tinebase_Config_Abstract::TYPE_BOOL,
            'clientRegistryInclude' => false,
            'setBySetupModule'      => false,
            'setByAdminModule'      => true,
            'default'               => false
        ),
        self::ENABLED_FEATURES => [
            //_('Enabled Features')
            self::LABEL                 => 'Enabled Features',
            //_('Enabled Features in Calendar Application.')
            self::DESCRIPTION           => 'Enabled Features in Calendar Application.',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [

                self::FEATURE_SPLIT_VIEW => [
                    self::LABEL             => 'Calendar Split View',
                    //_('Calendar Split View')
                    self::DESCRIPTION       => 'Split day and week views by attendee',
                    //_('Split day and week views by attendee')
                    self::TYPE              => self::TYPE_BOOL,
                    self::DEFAULT_STR       => true,
                ],
                self::FEATURE_YEAR_VIEW => [
                    self::LABEL             => 'Calendar Year View',
                    //_('Calendar Year View')
                    self::DESCRIPTION       => 'Adds year view to Calendar',
                    //_('Adds year view to Calendar')
                    self::TYPE              => self::TYPE_BOOL,
                    self::DEFAULT_STR       => false,
                ],
                self::FEATURE_EXTENDED_EVENT_CONTEXT_ACTIONS => [
                    self::LABEL             => 'Calendar Extended Context Menu Actions',
                    //_('Calendar Extended Context Menu Actions')
                    self::DESCRIPTION       => 'Adds extended actions to event context menus',
                    //_('Adds extended actions to event context menus')
                    self::TYPE              => self::TYPE_BOOL,
                    self::DEFAULT_STR       => true,
                ],
                self::FEATURE_COLOR_BY => [
                    self::LABEL             => 'Color Events By',
                    //_('Color Events By')
                    self::DESCRIPTION       => 'Choose event color by different criteria',
                    //_('Choose event color by different criteria')
                    self::TYPE              => self::TYPE_BOOL,
                    self::DEFAULT_STR       => true,
                ],
                self::FEATURE_RECUR_EXCEPT => [
                    self::LABEL             => 'Recur Events Except',
                    //_('Recur Events Except')
                    self::DESCRIPTION       => 'Recur Events except on certain dates',
                    //_('Recur Events except on certain dates')
                    self::TYPE              => self::TYPE_BOOL,
                    self::DEFAULT_STR       => false,
                ],
                self::FEATURE_POLLS => array(
                    self::LABEL             => 'Activate Poll for Events',
                    //_('Activate Poll for Events')
                    self::DESCRIPTION       =>
                        'Create alternative Events and let users as well as externals vote for the best option.',
                    //_('Create alternative Events and let users as well as externals vote for the best option.')
                    self::TYPE              => self::TYPE_BOOL,
                    self::DEFAULT_STR       => true,
                ),
            ],
            self::DEFAULT_STR => [],
        ],
        self::TENTATIVE_NOTIFICATIONS => array(
            'label'                 => 'Send Tentative Notifications', //_('Send Tentative Notifications')
            'description'           => 'Send notifications to event organiziers of events that are tentative certain days before event is due', //_('Send notifications to event organiziers of events that are tentative certain days before event is due')
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => TRUE,
            'setBySetupModule'      => false,
            'setByAdminModule'      => true,
            'content'               => array(
                self::TENTATIVE_NOTIFICATIONS_ENABLED   => array(
                    'label'         => 'Enabled', //_('Enabled')
                    'description'   => 'Enabled', //_('Enabled')
                    'type'          => Tinebase_Config_Abstract::TYPE_BOOL,
                    'default'       => false,
                ),
                self::TENTATIVE_NOTIFICATIONS_DAYS      => array(
                    'label'         => 'Days Before Due Date', //_('Days Before Due Date')
                    'description'   => 'How many days before the events due date to start send notifications.', //_('How many days before the events due date to start send notifications.')
                    'type'          => Tinebase_Config_Abstract::TYPE_INT,
                    'default'       => 5,
                ),
                self::TENTATIVE_NOTIFICATIONS_FILTER    => array(
                    'label'         => 'Additional Filter', //_('Additional Filter')
                    'description'   => 'Additional filter to limit events notifications should be send for.', //_('Additional filter to limit events notifications should be send for.')
                    'type'          => Tinebase_Config_Abstract::TYPE_ARRAY,
                    'default'       => [],
                ),
            ),
            'default'               => array(),
        ),
        self::POLL_MUTE_ALTERNATIVES_NOTIFICATIONS => array(
            //_('Mute Event Notifications for Polls')
            'label'                 => 'Mute Event Notifications for Polls',
            //_('Do not send invitations notifications for alternative events in an active poll.')
            'description'           => 'Do not send invitations notifications for alternative events in an active poll.',
            'type'                  => Tinebase_Config_Abstract::TYPE_BOOL,
            'clientRegistryInclude' => false,
            'setBySetupModule'      => false,
            'setByAdminModule'      => true,
            'default'               => true
        ),
        self::POLL_GTC => array(
            //_('GTCs for polls')
            'label'                 => 'GTCs for polls',
            //_('GTCs for polls')
            'description'           => 'GTCs for polls',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => false,
            'setBySetupModule'      => false,
            'setByAdminModule'      => true,
            'default'               => '',
        ),
        self::FREEBUSY_INFO_ALLOWED => [
            //_('Freebusy Info')
            self::LABEL             => 'Freebusy Info',
            //_('What data the freebusy grant reveals')
            self::DESCRIPTION       => 'What data the freebusy grant reveals',
            self::TYPE              => self::TYPE_KEYFIELD,
                self::OPTIONS           => [
                    'records'               => [
                        ['id' => Calendar_Config::FREEBUSY_INFO_ALLOW_DATETIME,  'value' => 'only date & time'], //_('only date & time')
                        ['id' => Calendar_Config::FREEBUSY_INFO_ALLOW_ORGANIZER,  'value' => 'and organizer'], //_('and organizer')
                        ['id' => Calendar_Config::FREEBUSY_INFO_ALLOW_RESOURCE_ATTENDEE,  'value' => 'and resources'], //_('and resources')
                        ['id' => Calendar_Config::FREEBUSY_INFO_ALLOW_CALENDAR,  'value' => 'and calendar'], //_('and calendar')
                        ['id' => Calendar_Config::FREEBUSY_INFO_ALLOW_ALL_ATTENDEE,  'value' => 'and other attendees'], //_('and other attendees')
                    ],
                ],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => 10,
        ],
        self::SEARCH_ATTENDERS_FILTER => [
            //_('Search Attenders Additional Filters')
            self::LABEL             => 'Search Attenders Additional Filters',
            //_('Search Attenders Additional Filters')
            self::DESCRIPTION       => 'Search Attenders Additional Filters',
            self::TYPE              => self::TYPE_OBJECT,
            self::CLASSNAME         => Tinebase_Config_Struct::class,
            self::CONTENT           => [
                self::SEARCH_ATTENDERS_FILTER_USER      => [
                    //_('User Additional Filters')
                    self::LABEL                 => 'User Additional Filters',
                    //_('User Additional Filters')
                    self::DESCRIPTION           => 'User Additional Filters',
                    self::TYPE                  => self::TYPE_ARRAY,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => false,
                    self::DEFAULT_STR           => [],
                ],
                self::SEARCH_ATTENDERS_FILTER_GROUP     => [
                    //_('Group Additional Filters')
                    self::LABEL                 => 'Group Additional Filters',
                    //_('Group Additional Filters')
                    self::DESCRIPTION           => 'Group Additional Filters',
                    self::TYPE                  => self::TYPE_ARRAY,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => false,
                    self::DEFAULT_STR           => [],
                ],
                self::SEARCH_ATTENDERS_FILTER_RESOURCE  => [
                    //_('Resource Additional Filters')
                    self::LABEL                 => 'Resource Additional Filters',
                    //_('Resource Additional Filters')
                    self::DESCRIPTION           => 'Resource Additional Filters',
                    self::TYPE                  => self::TYPE_ARRAY,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => false,
                    self::DEFAULT_STR           => [],
                ],
            ],
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [],
        ],
        self::ASSIGN_ORGANIZER_MEETING_STATUS_ON_EDIT_GRANT => [
            self::LABEL                 => 'Assign organizer meeting status on editGrant',
            //_('Assign organizer meeting status on editGrant')
            self::DESCRIPTION           => 'Assign organizer meeting status on editGrant',
            //_('Assign organizer meeting status on editGrant')
            self::TYPE                  => self::TYPE_BOOL,
            self::DEFAULT_STR           => false,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYSETUPMODULE      => false,
            self::SETBYADMINMODULE      => false,
        ],
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Calendar';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() {}
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __clone() {}
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
