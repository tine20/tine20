<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * backend for ActiveSync preferences
 *
 * @package     ActiveSync
 */
class ActiveSync_Preference extends Tinebase_Preference_Abstract
{
    /**
     * default addressbook to create new synced contacts in
     *
     */
    const DEFAULTADDRESSBOOK = 'defaultAddressbook';

    /**
     * default calendar to create new synced events in
     *
     */
    const DEFAULTCALENDAR = 'defaultCalendar';
    
    /**
     * default container to create new synced tasks in
     * 
     */
    const DEFAULTTASKLIST = 'defaultTaskList';
    
    /**
     * application
     *
     * @var string
     */
    protected $_application = 'ActiveSync';
        
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::DEFAULTADDRESSBOOK,
        );
        
        if (Tinebase_Application::getInstance()->isInstalled('Calendar', TRUE)) {
            $allPrefs[] = self::DEFAULTCALENDAR;
        }
        if (Tinebase_Application::getInstance()->isInstalled('Tasks', TRUE)) {
            $allPrefs[] = self::DEFAULTTASKLIST;
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
            self::DEFAULTADDRESSBOOK  => array(
                'label'         => $translate->_('Default Addressbook'),
                'description'   => $translate->_('The default addressbook to create new contacts in.'),
            ),
            self::DEFAULTCALENDAR  => array(
                'label'         => $translate->_('Default Calendar'),
                'description'   => $translate->_('The default calendar to create new events in.'),
            ),
            self::DEFAULTTASKLIST  => array(
                'label'         => $translate->_('Default Task List'),
                'description'   => $translate->_('The default task list to create new tasks in.'),
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
    public function getApplicationPreferenceDefaults($_preferenceName, $_accountId=NULL, $_accountType=Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        
        switch($_preferenceName) {
            case self::DEFAULTADDRESSBOOK:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId, 'Addressbook', self::DEFAULTADDRESSBOOK);
                break;
            case self::DEFAULTCALENDAR:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId, 'Calendar', self::DEFAULTCALENDAR);
                break;
            case self::DEFAULTTASKLIST:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId, 'Tasks', self::DEFAULTTASKLIST);
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
            case self::DEFAULTADDRESSBOOK:
                $result = $this->_getDefaultContainerOptions('Addressbook');
                break;
            case self::DEFAULTCALENDAR:
                $result = $this->_getDefaultContainerOptions('Calendar');
                break;
            case self::DEFAULTTASKLIST:
                $result = $this->_getDefaultContainerOptions('Tasks');
                break;
            default:
                $result = parent::_getSpecialOptions($_value);
        }
        
        return $result;
    }
}
