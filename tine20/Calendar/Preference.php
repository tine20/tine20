<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for Calendar preferences
 *
 * @package     Calendar
 */
class Calendar_Preference extends Tinebase_Preference_Abstract
{
    /**
     * where daysview should be scrolled to
     */
    const DAYSVIEW_STARTTIME = 'daysviewstarttime';
    
    /**
     * where daysvoew should be scrolled maximum up to
     */
    const DAYSVIEW_ENDTIME = 'daysviewendtime';
    
    /**
     * where daysview should be scrolled to
     */
    const DAYSVIEW_DEFAULT_STARTTIME = 'daysviewdefaultstarttime';

    /**
     * time in minutes to be visible in dayviews at once
     */
    const DAYSVIEW_TIME_VISIBLE = 'daysviewtimevisible';

    /**
     * default calendar all newly created/invited events are placed in
     */
    const DEFAULTCALENDAR = 'defaultCalendar';

    /**
     * have name of default favorite an a central palce
     * _("All my events")
     */
    const DEFAULTPERSISTENTFILTER_NAME = "All my events";
    
    /**
     * general notification level
     */
    const NOTIFICATION_LEVEL = 'notificationLevel';
    
    /**
     * send notifications of own updates
     */
    const SEND_NOTIFICATION_OF_OWN_ACTIONS = 'sendnotificationsofownactions';

    /**
     * send notifications of tentative events
     */
    const SEND_NOTIFICATION_FOR_TENTATIVE = 'sendnotificationsfortentative';

    /**
     * send alarm notifications
     */
    const SEND_ALARM_NOTIFICATIONS = 'sendalarmnotifications';
    
    /**
     * enable default alarm 
     */
    const DEFAULTALARM_ENABLED = 'defaultalarmenabled';
    
    /**
     * default alarm time in minutes before
     */
    const DEFAULTALARM_MINUTESBEFORE = 'defaultalarmminutesbefore';
    
    /**
     * default alarm time in minutes before
     */
    const DEFAULTATTENDEE_STRATEGY = 'defaultAttendeeStrategy';

    /**
     * default set events to privat
     */
    const DEFAULT_EVENTS_RRIVATE = 'defaultSetEventsToPrivat';
    
    /**
     * timeIncrement
     */
    const DEFAULT_TIMEINCREMENT = 'timeIncrement';
    
    /**
     * firstdayofweek
     */
    const FIRSTDAYOFWEEK = 'firstdayofweek';

    /**
     * default calendar strategy
     */
    const DEFAULT_CALENDAR_STRATEGY = 'defaultCalendarStrategy';

    /**
     * fixedCalendars
     */
    const FIXED_CALENDARS = 'fixedCalendars';

    /**
     * @var string application
     */
    protected $_application = 'Calendar';
        
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $cropDays = Calendar_Config::getInstance()->get(Calendar_Config::CROP_DAYS_VIEW);
        
        $allPrefs = array(
            self::DAYSVIEW_STARTTIME,
            self::DAYSVIEW_ENDTIME,
            self::DAYSVIEW_TIME_VISIBLE,
            self::DEFAULTCALENDAR,
            self::DEFAULTPERSISTENTFILTER,
            self::NOTIFICATION_LEVEL,
            self::SEND_NOTIFICATION_OF_OWN_ACTIONS,
            self::SEND_NOTIFICATION_FOR_TENTATIVE,
            self::SEND_ALARM_NOTIFICATIONS,
            self::DEFAULTALARM_ENABLED,
            self::DEFAULTALARM_MINUTESBEFORE,
            self::DEFAULTATTENDEE_STRATEGY,
            self::DEFAULT_TIMEINCREMENT,
            self::DEFAULTATTENDEE_STRATEGY,
            self::DEFAULT_EVENTS_RRIVATE,
            self::FIRSTDAYOFWEEK,
            self::DEFAULT_CALENDAR_STRATEGY,
            self::FIXED_CALENDARS,
        );
        
        if ($cropDays) {
            array_unshift($allPrefs, self::DAYSVIEW_DEFAULT_STARTTIME);
        }
            
        return $allPrefs;
    }
    
    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications preferences
     */
    public function getTranslatedPreferences()
    {
        $translate = Tinebase_Translation::getTranslation($this->_application);

        $prefDescriptions = array(
            self::DAYSVIEW_STARTTIME => array(
                'label'         => $translate->_('Start Time'),
                'description'   => $translate->_('Position on the left time axis, day and week view should start with'),
            ),
            self::DAYSVIEW_ENDTIME => array(
                'label'         => $translate->_('End Time'),
                'description'   => $translate->_('Position on the left time axis, day and week view should end with'),
            ),
            self::DAYSVIEW_DEFAULT_STARTTIME => array(
                'label'         => $translate->_('Default Start Time'),
                'description'   => $translate->_('Scroll position on the left time axis, day and week view should start with'),
            ),
            self::DAYSVIEW_TIME_VISIBLE => array(
                'label'         => $translate->_('Visible Time'),
                'description'   => $translate->_('Time to be displayed at once'),
            ),
            self::DEFAULTCALENDAR  => array(
                'label'         => $translate->_('Default Calendar'),
                'description'   => $translate->_('The default calendar for invitations and new events'),
            ),
            self::DEFAULTPERSISTENTFILTER  => array(
                'label'         => $translate->_('Default Favorite'),
                'description'   => $translate->_('The default favorite which is loaded on calendar startup'),
            ),
            self::NOTIFICATION_LEVEL => array(
                'label'         => $translate->_('Get Notification Emails'),
                'description'   => $translate->_('The level of actions you want to be notified about. Please note that organizers will get notifications for all updates including attendee answers unless this preference is set to "Never"'),
            ),
            self::SEND_NOTIFICATION_OF_OWN_ACTIONS => array(
                'label'         => $translate->_('Send Notifications Emails of own Actions'),
                'description'   => $translate->_('Get notifications emails for actions you did yourself'),
            ),
            self::SEND_NOTIFICATION_FOR_TENTATIVE => array(
                'label'         => $translate->_('Send Notifications Emails of tentative events'),
                'description'   => $translate->_('Get notifications emails of tentative events'),
            ),
            self::SEND_ALARM_NOTIFICATIONS => array(
                'label'         => $translate->_('Send Alarm Notifications Emails'),
                'description'   => $translate->_('Get event alarms via email'),
            ),
            self::DEFAULTALARM_ENABLED => array(
                'label'         => $translate->_('Enable Standard Alarm'),
                'description'   => $translate->_('New events get a standard alarm as defined below'),
            ),
            self::DEFAULTALARM_MINUTESBEFORE => array(
                'label'         => $translate->_('Standard Alarm Time'),
                'description'   => $translate->_('Minutes before the event starts'),
            ),
            self::DEFAULTATTENDEE_STRATEGY => array(
                    'label'         => $translate->_('Default Attendee Strategy'),
                    'description'   => $translate->_('Default Attendee Strategy for new events'),
            ),
            self::DEFAULT_TIMEINCREMENT => array(
                'label'         => $translate->_('Time Increment'),
                'description'   => $translate->_('Increment of event time steps'),
            ),
            self::DEFAULT_EVENTS_RRIVATE => array(
                'label'         => $translate->_('Default set Events to privat'),
                'description'   => $translate->_('If enabled every created event is always privat'),
            ),
            self::FIRSTDAYOFWEEK => array(
                'label'         => $translate->_('First Day of Week'),
                'description'   => $translate->_('On what day the week should be starting'),
            ),
            self::DEFAULT_CALENDAR_STRATEGY => array(
                'label'         => $translate->_('Default calendar strategy'),
                'description'   => $translate->_('The calendar for new events if no container is selected'),
            ),
            self::FIXED_CALENDARS => array(
                'label'         => $translate->_('Fixed Calendars'),
                'description'   => $translate->_('Calendars always selected regardless of all filter parameters.'),
            ),
        );
        
        return $prefDescriptions;
    }
    
    /**
     * Creates XML Data for a combobox
     *
     * Hours: 0 to 24
     *
     * @param string $default
     * @return string
     */
    protected function _createTimespanDataXML($start=0, $end=24)
    {
        $doc = new DomDocument('1.0');
        $options = $doc->createElement('options');
        $doc->appendChild($options);
        
        $time = new Tinebase_DateTime('@0');
        for ($i=$start; $i<=$end; $i++) {
            $time->setHour($i);
            $timeString = $time->format('H:i');
            if ($i == $end && $timeString == '00:00') {
                $timeString = '24:00';
            }
            $value  = $doc->createElement('value');
            $value->appendChild($doc->createTextNode($timeString));
            $label  = $doc->createElement('label');
            $label->appendChild($doc->createTextNode($timeString)); // @todo l10n
            
            $option = $doc->createElement('option');
            $option->appendChild($value);
            $option->appendChild($label);
            $options->appendChild($option);
        }
        
        return $doc->saveXML();
    }

    /**
     * get preference defaults if no default is found in the database
     *
     * @param string $_preferenceName
     * @param string|Tinebase_Model_User $_accountId
     * @param string $_accountType
     * @return Tinebase_Model_Preference
     */
    public function getApplicationPreferenceDefaults($_preferenceName, $_accountId = NULL, $_accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Get default value for ' . $_preferenceName . ' of account id '. $_accountId);
        
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        
        switch ($_preferenceName) {
            case self::DAYSVIEW_STARTTIME:
                $preference->value      = '08:00';
                $preference->options = $this->_createTimespanDataXML(0, 23);
                break;
            case self::DAYSVIEW_ENDTIME:
                $preference->value      = '18:00';
                $preference->options = $this->_createTimespanDataXML(1, 24);
                break;
            case self::DAYSVIEW_DEFAULT_STARTTIME:
                $preference->value      = '08:00';
                $preference->options = $this->_createTimespanDataXML(0, 23);
                break;
            case self::DAYSVIEW_TIME_VISIBLE:
                $preference->value      = '10:00';
                $preference->options = $this->_createTimespanDataXML(1, 24);
                break;
            case self::DEFAULTCALENDAR:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId);
                break;
            case self::DEFAULTPERSISTENTFILTER:
                $preference->value          = Tinebase_PersistentFilter::getPreferenceValues('Calendar', $_accountId, "All my events");
                break;
            case self::NOTIFICATION_LEVEL:
                $translate = Tinebase_Translation::getTranslation($this->_application);
                // need to put the translations strings here because they were not found in the xml below :/
                // _('Never') _('On invitation and cancellation only') _('On time changes') _('On all updates but attendee responses') _('On attendee responses too')
                $preference->value      = Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_EVENT_RESCHEDULE;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <value>'. Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_NONE . '</value>
                            <label>'. $translate->_('Never') . '</label>
                        </option>
                        <option>
                            <value>'. Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_INVITE_CANCEL . '</value>
                            <label>'. $translate->_('On invitation and cancellation only') . '</label>
                        </option>
                        <option>
                            <value>'. Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_EVENT_RESCHEDULE . '</value>
                            <label>'. $translate->_('On time changes') . '</label>
                        </option>
                        <option>
                            <value>'. Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_EVENT_UPDATE . '</value>
                            <label>'. $translate->_('On all updates but attendee responses') . '</label>
                        </option>
                        <option>
                            <value>'. Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE . '</value>
                            <label>'. $translate->_('On attendee responses too') . '</label>
                        </option>
                    </options>';
                break;
            case self::SEND_NOTIFICATION_OF_OWN_ACTIONS:
                $preference->value      = 0;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::SEND_NOTIFICATION_FOR_TENTATIVE:
                $preference->value      = 1;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::SEND_ALARM_NOTIFICATIONS:
                $preference->value      = 1;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::DEFAULTALARM_ENABLED:
                $preference->value      = 0;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::DEFAULTALARM_MINUTESBEFORE:
                $preference->value      = 15;
                $preference->options    = '';
                break;
            case self::DEFAULTATTENDEE_STRATEGY:
                $translate = Tinebase_Translation::getTranslation($this->_application);
                $preference->value      = 'me';
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <label>' . $translate->_('Me') . '</label>
                            <value>me</value>
                        </option>
                        <option>
                            <label>' . $translate->_('Intelligent') . '</label>
                            <value>intelligent</value>
                        </option>
                        <option>
                            <label>' . $translate->_('Calendar owner') . '</label>
                            <value>calendarOwner</value>
                        </option>
                        <option>
                            <label>' . $translate->_('Filtered attendee') . '</label>
                            <value>filteredAttendee</value>
                        </option>
                        <option>
                            <label>' . $translate->_('None') . '</label>
                            <value>none</value>
                        </option>
                    </options>';
                break;
            case self::DEFAULT_TIMEINCREMENT:
                $preference->value      = 15;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <label>5</label>
                            <value>5</value>
                        </option>
                        <option>
                            <label>10</label>
                            <value>10</value>
                        </option>
                        <option>
                            <label>15</label>
                            <value>15</value>
                        </option>
                        <option>
                            <label>20</label>
                            <value>20</value>
                        </option>
                        <option>
                            <label>30</label>
                            <value>30</value>
                        </option>
                        <option>
                            <label>60</label>
                            <value>60</value>
                        </option>
                    </options>';
                break;
            case self::DEFAULT_EVENTS_RRIVATE:
                $preference->value      = 0;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::FIRSTDAYOFWEEK:
                $translate = Tinebase_Translation::getTranslation($this->_application);
                $preference->value = 1;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <label>' . $translate->_('Sunday'). '</label>
                            <value>0</value>
                        </option>
                        <option>
                            <label>' . $translate->_('Monday') . '</label>
                            <value>1</value>
                        </option>
                    </options>';
                break;
            case self::DEFAULT_CALENDAR_STRATEGY:
                $translate = Tinebase_Translation::getTranslation($this->_application);
                $preference->value = 'default';
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <label>' . $translate->_('Default Calendar') . '</label>
                            <value>default</value>
                        </option>
                        <option>
                            <label>' . $translate->_('None') . '</label>
                            <value>none</value>
                        </option>
                    </options>';
                break;
            case self::FIXED_CALENDARS:
                $preference->value = array();
                // TODO set better (?) options / maybe this could be removed
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::DEFAULTCONTAINER_OPTIONS . '</special>
                    </options>';

                $preference->uiconfig = array(
                    'xtype'       => 'containerspicker',
                    'appName'     => 'Calendar',
                    'model'       => 'Event',
                );
                $preference->personal_only = true;
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }

    /**
     * overwrite this in concrete classes if needed
     *
     * @param $_preferenceName
     * @return array
     */
    public function _getPrefRecordConfig($_preferenceName)
    {
        switch ($_preferenceName) {
            case self::FIXED_CALENDARS:
                return array(
                    'modelName'      => 'Tinebase_Model_Container',
                );
                break;
            default:
                return parent::_getPrefRecordConfig($_preferenceName);
        }
    }
}
