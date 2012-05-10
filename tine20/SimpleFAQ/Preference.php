<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * faq preferences
 *
 * @package     SimpleFAQ
 */
class SimpleFAQ_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * default faq filter
     */
    const DEFAULTPERSISTENTFILTER = 'defaultpersistentfilter';
    
    /**
     * have name of default favorite an a central palce
     * _("All my FAQs")
     */
    const DEFAULTPERSISTENTFILTER_NAME = "All my FAQs";
    
    /**
     * default faq list where all new faqs are placed in
     */
    const DEFAULTFAQLIST = 'defaultFAQList';
    
    /**
     * @var string application
     */
    protected $_application = 'SimpleFAQ';
        
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
            self::DEFAULTFAQLIST,
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
                'description'   => $translate->_('The default favorite which is loaded on FAQ startup'),
            ),
            self::DEFAULTFAQLIST  => array(
                'label'         => $translate->_('Default FAQ List'),
                'description'   => $translate->_('The default FAQ list to create new FAQ in.'),
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
                $preference->value          = Tinebase_PersistentFilter::getPreferenceValues('SimpleFAQ', $_accountId, self::DEFAULTPERSISTENTFILTER_NAME);
                $preference->personal_only  = TRUE;
                break;
            case self::DEFAULTFAQLIST:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId);
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
}
