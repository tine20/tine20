<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * backend for Calendar preferences
 *
 * @package     Calendar
 */
class Calendar_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * default calendar all newly created/invited events are placed in
     */
    const DEFAULTCALENDAR = 'defaultCalendar';
    
    /**
     * default calendar filter
     */
    const DEFAULTPERSISTENTFILTER = 'defaultpersistentfilter';
    
    /**
     * general notification level
     */
    const NOTIFICATION_LEVEL = 'notificationLevel';
    
    /**
     * send notifications of own updates
     */
    const SEND_NOTIFICATION_OF_OWN_ACTIONS = 'sendnotificationsofownactions';
    
    
    /**
     * @var string application
     */
    protected $_application = 'Calendar';    
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::DEFAULTCALENDAR,
            self::DEFAULTPERSISTENTFILTER,
            self::NOTIFICATION_LEVEL,
            self::SEND_NOTIFICATION_OF_OWN_ACTIONS,
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
        );
        
        return $prefDescriptions;
    }
    
    /**
     * get preference defaults if no default is found in the database
     *
     * @param string $_preferenceName
     * @return Tinebase_Model_Preference
     */
    public function getPreferenceDefaults($_preferenceName, $_accountId=NULL, $_accountType=Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        
        switch($_preferenceName) {
            case self::DEFAULTCALENDAR:
                $accountId = $_accountId ? $_accountId : Tinebase_Core::getUser()->getId();
                $calendars          = Tinebase_Container::getInstance()->getPersonalContainer($accountId, 'Calendar', $accountId, 0, true);
                $preference->value  = $calendars->getFirstRecord()->getId();
                $preference->personal_only = TRUE;
                break;
            case self::DEFAULTPERSISTENTFILTER:
                $preference->value          = Tinebase_PersistentFilter::getPreferenceValues('Calendar', $_accountId, "All my events");
                $preference->personal_only  = TRUE;
                break;
            case self::NOTIFICATION_LEVEL:
                $translate = Tinebase_Translation::getTranslation($this->_application);
                
                $preference->value      = Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_EVENT_RESCHEDULE;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <value>'. Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_NONE . '</value>
                            <label>'. $translate->_('Never') . '</label>
                        </option>
                        <option>
                            <value>'. Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_INVITE_CANCEL . '</value>
                            <label>'. $translate->_('On invitaion and cancelation only') . '</label>
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
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
    
    /**
     * get special options
     *
     * @param string $_value
     * @return array
     */
    protected function _getSpecialOptions($_value)
    {
        $result = array();
        switch($_value) {
            case self::DEFAULTCALENDAR:
                // get all containers of current user
                $calendars = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), 'Calendar', Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_ADD);
                
                foreach ($calendars as $calendar) {
                    $result[] = array($calendar->getId(), $calendar->name);
                }
                break;
            case self::DEFAULTPERSISTENTFILTER:
                $result = Tinebase_PersistentFilter::getPreferenceValues('Calendar');
                break;
            default:
                $result = parent::_getSpecialOptions($_value);
        }
        
        return $result;
    }
}
