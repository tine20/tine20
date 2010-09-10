<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 * @todo        add default account settings ?
 * @todo        make UPDATEINTERVAL a free form preference
 */


/**
 * backend for Felamimail preferences
 *
 * @package     Felamimail
 * @subpackage  Backend
 */
class Felamimail_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * default email account to use
     *
     */
    const DEFAULTACCOUNT = 'defaultEmailAccount';

    /**
     * email folder update interval
     *
     */
    const UPDATEINTERVAL = 'updateInterval';

    /**
     * use felamimail in addressbook
     *
     */
    const USEINADB = 'useInAdb';

   
    /**
     * use for default note
     *
     */
    const AUTOATTACHNOTE = 'autoAttachNote';
    
    /**
     * show delete confirmation
     *
     * @todo add this to more apps?
     */
    const CONFIRM_DELETE = 'confirmDelete';
    
    /**
     * application
     *
     * @var string
     */
    protected $_application = 'Felamimail';    
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::DEFAULTACCOUNT,
            self::UPDATEINTERVAL,
            self::USEINADB,
            self::AUTOATTACHNOTE,
            self::CONFIRM_DELETE,
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
            self::DEFAULTACCOUNT  => array(
                'label'         => $translate->_('Default Email Account'),
                'description'   => $translate->_('The default email account to use when sending mails.'),
            ),
            self::UPDATEINTERVAL  => array(
                'label'         => $translate->_('Email Update Interval'),
                'description'   => $translate->_('How often should Felamimail check for new Emails (in minutes). "0" means never.'),
            ),
            self::USEINADB  => array(
                'label'         => $translate->_('Use in Addressbook'),
                'description'   => $translate->_('Compose Emails from the Addressbook with Felamimail.'),
            ),
            self::AUTOATTACHNOTE  => array(
                'label'         => $translate->_('Use for NOTES'),
                'description'   => $translate->_('Save Note default Value.'),
            ),
            self::CONFIRM_DELETE  => array(
                'label'         => $translate->_('Confirm Delete'),
                'description'   => $translate->_('Show confirmation dialog when deleting mails.'),
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
            case self::USEINADB:
                $preference->value      = 1;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::DEFAULTACCOUNT:
                $preference->value      = '';
                break;
            case self::UPDATEINTERVAL:
                $preference->value      = 5;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>';
                for ($i = 1; $i <= 20; $i++) {
                    $preference->options .= '<option>
                        <label>'. $i . '</label>
                        <value>'. $i . '</value>
                    </option>';
                }
                $preference->options    .= '</options>';
                break;
            case self::AUTOATTACHNOTE:
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::CONFIRM_DELETE:
                $preference->value      = 1;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
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
            case self::DEFAULTACCOUNT:
                // get all user accounts
                $accounts = Felamimail_Controller_Account::getInstance()->search();
                foreach ($accounts as $account) {
                    $result[] = array($account->getId(), $account->name);
                }
                break;
            default:
                $result = parent::_getSpecialOptions($_value);
        }
        
        return $result;
    }
}
