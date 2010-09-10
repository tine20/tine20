<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */


/**
 * backend for tinebase preferences
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
        
    /**
     * timezone pref const
     *
     */
    const TIMEZONE = 'timezone';

    /**
     * locale pref const
     *
     */
    const LOCALE = 'locale';
    
    /**
     * default application
     *
     */
    const DEFAULT_APP = 'defaultapp';

    /**
     * preferred window type
     *
     */
    const WINDOW_TYPE = 'windowtype';
    
    /**
     * show logout confirmation
     *
     */
    const CONFIRM_LOGOUT = 'confirmLogout';
    
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = ($this->_application == 'Tinebase') 
            ? array(
                self::TIMEZONE,
                self::LOCALE,
                self::DEFAULT_APP,
                self::WINDOW_TYPE,
                self::CONFIRM_LOGOUT,
            )
            : array();
            
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
            self::TIMEZONE  => array(
                'label'         => $translate->_('Timezone'),
                'description'   => $translate->_('The timezone in which dates are shown in Tine 2.0.'),
            ),
            self::LOCALE  => array(
                'label'         => $translate->_('Language'),
                'description'   => $translate->_('The language of the Tine 2.0 GUI.'),
            ),
            self::DEFAULT_APP  => array(
                'label'         => $translate->_('Default Application'),
                'description'   => $translate->_('The default application to show after login.'),
            ),
            self::WINDOW_TYPE  => array(
                'label'         => $translate->_('Window Type'),
                'description'   => $translate->_('You can choose between ExtJs style windows or normal Browser popup windows.'),
            ),
            self::CONFIRM_LOGOUT  => array(
                'label'         => $translate->_('Confirm Logout'),
                'description'   => $translate->_('Show confirmation dialog on logout.'),
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
            case self::TIMEZONE:
                $preference->value      = 'Europe/Berlin';
                break;
            case self::LOCALE:
                $preference->value      = 'auto';
                break;
            case self::DEFAULT_APP:
                $preference->value      = 'Addressbook';
                break;
            case self::WINDOW_TYPE:
                $preference->value      = 'Browser';
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <label>ExtJs style (experimental)</label>
                            <value>Ext</value>
                        </option>
                        <option>
                            <label>Browser style</label>
                            <value>Browser</value>
                        </option>
                    </options>';
                break;
            case self::CONFIRM_LOGOUT:
                $preference->value      = 1;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            default:
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
    
    /**
     * overwrite this to add more special options for other apps
     *
     * - result array has to have the following format:
     *  array(
     *      array('value1', 'label1'),
     *      array('value2', 'label2'),
     *      ...
     *  )
     *
     * @param string $_value
     * @return array
     *
     * @todo add application title translations?
     * @todo add timezone translations?
     */
    protected function _getSpecialOptions($_value)
    {
        $result = array();

        switch ($_value) {

            /****************** timezone options ************************/
            case Tinebase_Preference::TIMEZONE:
                $locale =  Tinebase_Core::get(Tinebase_Core::LOCALE);

                $availableTimezonesTranslations = Zend_Locale::getTranslationList('citytotimezone', $locale);
                $availableTimezones = DateTimeZone::listIdentifiers();
                //foreach ($availableTimezones as $timezone) {
                //    $result[] = array($timezone, array_key_exists($timezone, $availableTimezonesTranslations) ? $availableTimezonesTranslations[$timezone] : NULL);
                //}

                $result = $availableTimezones;
                break;

                /****************** locale options *************************/
            case Tinebase_Preference::LOCALE:
                $availableTranslations = Tinebase_Translation::getAvailableTranslations();
                foreach ($availableTranslations as $lang) {
                    $region = (!empty($lang['region'])) ? ' / ' . $lang['region'] : '';
                    $result[] = array($lang['locale'], $lang['language'] . $region);
                }
                break;

                /****************** application options ********************/
            case Tinebase_Preference::DEFAULT_APP:
                $applications = Tinebase_Application::getInstance()->getApplications();
                foreach ($applications as $app) {
                    if (
                    $app->status == 'enabled'
                    && $app->name != 'Tinebase'
                    && Tinebase_Core::getUser()->hasRight($app->name, Tinebase_Acl_Rights_Abstract::RUN)
                    ) {
                        $result[] = array($app->name, $app->name);
                    }
                }
                break;

                /****************** default *********************************/
            default:
                $result = parent::_getSpecialOptions($_value);
                break;
        }

        return $result;
    }
    
    /**
     * do some call json functions if preferences name match
     * - every app should define its own special handlers
     *
     * @param Tinebase_Frontend_Json_Abstract $_jsonFrontend
     * @param string $name
     * @param string $value
     * @param string $appName
     */
    public function doSpecialJsonFrontendActions(Tinebase_Frontend_Json_Abstract $_jsonFrontend, $name, $value, $appName)
    {
        if ($appName == $this->_application) {
            switch ($name) {
                case Tinebase_Preference::LOCALE:
                    $_jsonFrontend->setLocale($value, FALSE, TRUE);
                    break;
                case Tinebase_Preference::TIMEZONE:
                    $_jsonFrontend->setTimezone($value, FALSE);
                    break;
            }
        }
    }
}
