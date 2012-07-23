<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @subpackage  Preference
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for Crm preferences
 *
 * @package     Crm
 * @subpackage  Preference
 * 
 * @todo add & implement NOTIFICATION_LEVEL
 */
class Crm_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * default list all created leads are placed in
     */
    const DEFAULTLEADLIST = 'defaultLeadList';

    /**
     * general notification level
     */
    //const NOTIFICATION_LEVEL = 'notificationLevel';

    /**
     * have name of default favorite an a central palce
     * _("All leads")
     */
    const DEFAULTPERSISTENTFILTER_NAME = "All leads";
    
    /**
     * send notifications of own updates
     */
    const SEND_NOTIFICATION_OF_OWN_ACTIONS = 'sendnotificationsofownactions';
    
    /**
     * @var string application
     */
    protected $_application = 'Crm';
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::DEFAULTLEADLIST,
            //self::NOTIFICATION_LEVEL,
            self::SEND_NOTIFICATION_OF_OWN_ACTIONS,
            self::DEFAULTPERSISTENTFILTER,
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
            self::DEFAULTLEADLIST  => array(
                'label'         => $translate->_('Default Lead List'),
                'description'   => $translate->_('The default list for new leads'),
            ),
            /*
            self::NOTIFICATION_LEVEL => array(
                'label'         => $translate->_('Get Notification Emails'),
                'description'   => $translate->_('The level of actions you want to be notified about.'),
            ),
            */
            self::SEND_NOTIFICATION_OF_OWN_ACTIONS => array(
                'label'         => $translate->_('Send Notifications Emails for own actions'),
                'description'   => $translate->_('Get notifications emails for actions you did yourself'),
            ),
            self::DEFAULTPERSISTENTFILTER  => array(
                'label'         => $translate->_('Default Favorite'),
                'description'   => $translate->_('The default favorite which is loaded on crm startup'),
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
            case self::DEFAULTLEADLIST:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId);
                break;
            /*
            case self::NOTIFICATION_LEVEL:
                $translate = Tinebase_Translation::getTranslation($this->_application);
                
                $preference->value      = Crm_Controller_EventNotifications::NOTIFICATION_LEVEL_EVENT_RESCHEDULE;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <value>'. Crm_Controller_EventNotifications::NOTIFICATION_LEVEL_NONE . '</value>
                            <label>'. $translate->_('Never') . '</label>
                        </option>
                        <option>
                            <value>'. Crm_Controller_EventNotifications::NOTIFICATION_LEVEL_INVITE_CANCEL . '</value>
                            <label>'. $translate->_('On invitaion and cancelation only') . '</label>
                        </option>
                        <option>
                            <value>'. Crm_Controller_EventNotifications::NOTIFICATION_LEVEL_EVENT_RESCHEDULE . '</value>
                            <label>'. $translate->_('On time changes') . '</label>
                        </option>
                        <option>
                            <value>'. Crm_Controller_EventNotifications::NOTIFICATION_LEVEL_EVENT_UPDATE . '</value>
                            <label>'. $translate->_('On all updates but attendee responses') . '</label>
                        </option>
                        <option>
                            <value>'. Crm_Controller_EventNotifications::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE . '</value>
                            <label>'. $translate->_('On attendee responses too') . '</label>
                        </option>
                    </options>';
                break;
            */
            case self::SEND_NOTIFICATION_OF_OWN_ACTIONS:
                $preference->value      = 1;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::DEFAULTPERSISTENTFILTER:
                $preference->value          = Tinebase_PersistentFilter::getPreferenceValues('Crm', $_accountId, self::DEFAULTPERSISTENTFILTER_NAME);
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
}
