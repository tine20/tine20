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
     * give all useraccounts grants to view free/busy of the account this preference is yes
     */
    const FREEBUSY = 'freeBusy';
    
    /**
     * default calendar all newly created/invited events are placed in
     */
    const DEFAULTCALENDAR = 'defaultCalendar';


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
            self::FREEBUSY,
            self::DEFAULTCALENDAR
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
            self::FREEBUSY  => array(
                'label'         => $translate->_('Publish Free/Busy Information'),
                'description'   => $translate->_('Allow all users to view your free/busy information'),
            ),
            self::DEFAULTCALENDAR  => array(
                'label'         => $translate->_('Default Calendar'),
                'description'   => $translate->_('The defualt calendar for invitations and new events'),
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
    public function getPreferenceDefaults($_preferenceName)
    {
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        
        switch($_preferenceName) {
            case self::FREEBUSY:
                $preference->value      = 0;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::DEFAULTCALENDAR:
                $preference->value      = Calendar_Controller::getInstance()->getDefaultDisplayCalendar(Tinebase_Core::getUser())->getId();
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
                // get all user accounts
                $calendars = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), 'Calendar', Tinebase_Core::getUser(), Tinebase_Model_Container::GRANT_ADD);
                
                foreach ($calendars as $calendar) {
                    $result[] = array($calendar->getId(), $calendar->name);
                }
                break;
            default:
                $result = parent::_getSpecialOptions($_value);
        }
        
        return $result;
    }
}
