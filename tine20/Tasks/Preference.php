<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Preference
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tasks preferences
 *
 * @package     Tasks
 * @subpackage  Preference
 */
class Tasks_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * have name of default favorite on a central place
     * _("All my tasks")
     */
    const DEFAULTPERSISTENTFILTER_NAME = "All my tasks";
    
    /**
     * default task list where all new tasks are placed in
     */
    const DEFAULTTASKLIST = 'defaultTaskList';
    
    /**
     * @var string application
     */
    protected $_application = 'Tasks';
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::DEFAULTPERSISTENTFILTER,
            self::DEFAULTTASKLIST,
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
            self::DEFAULTPERSISTENTFILTER  => array(
                'label'         => $translate->_('Default Favorite'),
                'description'   => $translate->_('The default favorite which is loaded on Tasks startup'),
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
     * @param string|Tinebase_Model_User $_accountId
     * @param string $_accountType
     * @return Tinebase_Model_Preference
     */
    public function getApplicationPreferenceDefaults($_preferenceName, $_accountId = NULL, $_accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        
        switch($_preferenceName) {
            case self::DEFAULTPERSISTENTFILTER:
                $preference->value          = Tinebase_PersistentFilter::getPreferenceValues('Tasks', $_accountId, self::DEFAULTPERSISTENTFILTER_NAME);
                break;
            case self::DEFAULTTASKLIST:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId);
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
}
