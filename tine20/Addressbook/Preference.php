<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
            self::DEFAULTADDRESSBOOK
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
            case self::DEFAULTADDRESSBOOK:
                $accountId          = $_accountId ? $_accountId : Tinebase_Core::getUser()->getId();
                $addressbooks       = Tinebase_Container::getInstance()->getPersonalContainer($accountId, 'Addressbook', $accountId, 0, true);
                $preference->value  = $addressbooks->getFirstRecord()->getId();
                $preference->personal_only = TRUE;
                
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
                // get all user accounts
                $addressbooks = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), 'Addressbook', Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_ADD);
                $addressbooks->merge(Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), 'Addressbook', Tinebase_Model_Grants::GRANT_ADD));
                
                foreach ($addressbooks as $addressbook) {
                    $result[] = array($addressbook->getId(), $addressbook->name);
                }
                break;
            default:
                $result = parent::_getSpecialOptions($_value);
        }
        
        return $result;
    }
}
