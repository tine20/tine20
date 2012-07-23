<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for Addressbook preferences
 *
 * @package     Addressbook
 */
class Addressbook_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * default addressbook all newly created contacts are placed in
     */
    const DEFAULTADDRESSBOOK = 'defaultAddressbook';

    /**
     * have name of default favorite an a central palce
     * _("All contacts")
     */
    const DEFAULTPERSISTENTFILTER_NAME = "All contacts";
    
    /**
     * @var string application
     */
    protected $_application = 'Addressbook';
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::DEFAULTADDRESSBOOK,
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
            self::DEFAULTADDRESSBOOK  => array(
                'label'         => $translate->_('Default Addressbook'),
                'description'   => $translate->_('The default addressbook for new contacts'),
            ),
            self::DEFAULTPERSISTENTFILTER  => array(
                'label'         => $translate->_('Default Favorite'),
                'description'   => $translate->_('The default favorite which is loaded on addressbook startup'),
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
            case self::DEFAULTADDRESSBOOK:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId);
                break;
            case self::DEFAULTPERSISTENTFILTER:
                $preference->value          = Tinebase_PersistentFilter::getPreferenceValues('Addressbook', $_accountId, self::DEFAULTPERSISTENTFILTER_NAME);
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
}
