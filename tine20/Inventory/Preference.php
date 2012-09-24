<?php
/**
 * Tine 2.0
 * 
 * @package     Inventory
 * @subpackage  Preference
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for Inventory preferences
 *
 * @package     Inventory
 * @subpackage  Preference
 */
class Inventory_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * default container for inventory items
     */
    const DEFAULT_INVENTORYITEM_CONTAINER = 'defaultInventoryItemContainer';
    
    /**
     * @var string application
     */
    protected $_application = 'Inventory';
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::DEFAULT_INVENTORYITEM_CONTAINER,
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
            self::DEFAULT_INVENTORYITEM_CONTAINER  => array(
                'label'         => $translate->_('Default Inventory Item Container'),
                'description'   => $translate->_('The default container for new items'),
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
            case self::DEFAULT_INVENTORYITEM_CONTAINER:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId);
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
}
