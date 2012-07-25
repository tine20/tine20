<?php
/**
 * Tine 2.0
 * 
 * @package     ExampleApplication
 * @subpackage  Preference
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for ExampleApplication preferences
 *
 * @package     ExampleApplication
 * @subpackage  Preference
 */
class ExampleApplication_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * default container for example record
     */
    const DEFAULT_EXAMPLERECORD_CONTAINER = 'defaultExampleRecordContainer';
    
    /**
     * @var string application
     */
    protected $_application = 'ExampleApplication';
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::DEFAULT_EXAMPLERECORD_CONTAINER,
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
            self::DEFAULT_EXAMPLERECORD_CONTAINER  => array(
                'label'         => $translate->_('Default Example Record Container'),
                'description'   => $translate->_('The default container for new records'),
            )
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
            case self::DEFAULT_EXAMPLERECORD_CONTAINER:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId);
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
}
