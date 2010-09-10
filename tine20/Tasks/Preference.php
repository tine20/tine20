<?php
/**
 * Tine 2.0
 * 
 * @package     Task
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Preference.php 14231 2010-05-07 06:54:28Z g.ciyiltepe@metaways.de $
 */


/**
 * backend for Calendar preferences
 *
 * @package     Calendar
 */
class Tasks_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * default tasks filter
     */
    const DEFAULTPERSISTENTFILTER = 'defaultpersistentfilter';
    
    /**
     * have name of default favorite an a central palce
     * _("All my tasks")
     */
    const DEFAULTPERSISTENTFILTER_NAME = "All my tasks";
    
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
            self::DEFAULTPERSISTENTFILTER
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
            )
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
            case self::DEFAULTPERSISTENTFILTER:
                $preference->value          = Tinebase_PersistentFilter::getPreferenceValues('Tasks', $_accountId, self::DEFAULTPERSISTENTFILTER_NAME);
                $preference->personal_only  = TRUE;
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
            case self::DEFAULTPERSISTENTFILTER:
                $result = Tinebase_PersistentFilter::getPreferenceValues('Tasks');
                break;
            default:
                $result = parent::_getSpecialOptions($_value);
        }
        
        return $result;
    }
}
