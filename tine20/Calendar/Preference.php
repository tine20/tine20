<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * enable default alarm 
     */
    const DEFAULTALARM_ENABLED = 'defaultalarmenabled';
    
    /**
     * default alarm time in minutes before
     */
    const DEFAULTALARM_MINUTESBEFORE = 'defaultalarmminutesbefore';
    
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
        $allPrefs = array(
            self::DAYSVIEW_STARTTIME,
            self::DEFAULTCALENDAR,
            self::DEFAULTPERSISTENTFILTER,
            self::NOTIFICATION_LEVEL,
            self::SEND_NOTIFICATION_OF_OWN_ACTIONS,
            self::DEFAULTALARM_ENABLED,
            self::DEFAULTALARM_MINUTESBEFORE,
        );
            
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
            self::DEFAULTALARM_ENABLED => array(
                'label'         => $translate->_('Enable Standard Alarm'),
                'description'   => $translate->_('New events get a standard alarm as defined below'),
            ),
            self::DEFAULTALARM_MINUTESBEFORE => array(
                'label'         => $translate->_('Standard Alarm Time'),
                'description'   => $translate->_('Minutes before the event starts'),
            ),
        );
        
        return $prefDescriptions;
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
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        
        switch($_preferenceName) {
            case self::DAYSVIEW_STARTTIME:
                $doc = new DomDocument('1.0');
                $options = $doc->createElement('options');
                $doc->appendChild($options);
                
                $time = new Tinebase_DateTime('@0');
                for ($i=0; $i<48; $i++) {
                    $time->addMinute($i ? 30 : 0);
                    $timeString = $time->format('H:i');
                    
                    $value  = $doc->createElement('value');
                    $value->appendChild($doc->createTextNode($timeString));
                    $label  = $doc->createElement('label');
                    $label->appendChild($doc->createTextNode($timeString)); // @todo l10n
                    
                    $option = $doc->createElement('option');
                    $option->appendChild($value);
                    $option->appendChild($label);
                    $options->appendChild($option);
                }
                
                $preference->value      = '08:00';
                $preference->options = $doc->saveXML();
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
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
}
