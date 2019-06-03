<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Preference
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */


/**
 * backend for tinebase preferences
 *
 * @package     Tinebase
 * @subpackage  Preference
 */
class Tinebase_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * page size in grids
     * 
     */
    const PAGE_SIZE = 'pageSize';
    
    /**
     * strip rows in grids
     * 
     */
    const GRID_STRIPE_ROWS = 'gridStripeRows';
    
    /**
     * show load mask in grids
     * 
     */
    const GRID_LOAD_MASK = 'gridLoadMask';

    /**
     * auto search on filter change
     * 
     */
    const FILTER_CHANGE_AUTO_SEARCH = 'filterChangeAutoSearch';
   
    /**
     *  timezone pref const
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

    /**
     * advanced search through relations and so on
     */
    const ADVANCED_SEARCH = 'advancedSearch';
    
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
                self::PAGE_SIZE,
                self::GRID_STRIPE_ROWS,
                self::GRID_LOAD_MASK,
                self::FILTER_CHANGE_AUTO_SEARCH,
                self::ADVANCED_SEARCH
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
            self::PAGE_SIZE  => array(
                'label'         => $translate->_('Page size'),
                'description'   => $translate->_('Page size in grids'),
            ),
            self::GRID_STRIPE_ROWS  => array(
                'label'         => $translate->_('Grid stripe rows'),
                'description'   => $translate->_('Stripe rows in grids'),
            ),
            self::GRID_LOAD_MASK  => array(
                'label'         => $translate->_('Grid load mask'),
                'description'   => $translate->_('Show load mask in grids'),
            ),
            self::FILTER_CHANGE_AUTO_SEARCH  => array(
                'label'         => $translate->_('Auto search on filter change'),
                'description'   => $translate->_('Perform auto search when filter is changed'),
            ),
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
                'description'   => $translate->_('You can choose between modal windows or normal browser popup windows.'),
            ),
            self::CONFIRM_LOGOUT  => array(
                'label'         => $translate->_('Confirm Logout'),
                'description'   => $translate->_('Show confirmation dialog on logout.'),
            ),
            self::ADVANCED_SEARCH => array(
                'label'         => $translate->_('Enable advanced search'),
                'description'   => $translate->_('If enabled quickfilter searches through relations as well.')
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
            case self::PAGE_SIZE:
                $preference->value  = 50; 
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <label>15</label>
                            <value>15</value>
                        </option>
                        <option>
                            <label>30</label>
                            <value>30</value>
                        </option>
                        <option>
                            <label>50</label>
                            <value>50</value>
                        </option>
                        <option>
                            <label>100</label>
                            <value>100</value>
                        </option>
                    </options>';
                $preference->personal_only = FALSE;
                break;

            case self::GRID_STRIPE_ROWS:
                $preference->value      = 0;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                  $preference->personal_only = FALSE;
                break;
            case self::GRID_LOAD_MASK:
                $preference->value      = 0;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                  $preference->personal_only = FALSE;
                break;
            case self::FILTER_CHANGE_AUTO_SEARCH:
                $preference->value      = 1;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                $preference->personal_only = FALSE;
                break;
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
                $preference->value      = 'autodetect';
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <label>Autodetect</label>
                            <value>autodetect</value>
                        </option>
                        <option>
                            <label>Native windows</label>
                            <value>Browser</value>
                        </option>
                        <option>
                            <label>Overlay windows</label>
                            <value>Ext</value>
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
            case self::ADVANCED_SEARCH:
                $preference->value      = 0;
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
     */
    protected function _getSpecialOptions($_value)
    {
        $result = array();

        switch ($_value) {

            case Tinebase_Preference::TIMEZONE:
                $locale =  Tinebase_Core::getLocale();

                $availableTimezonesTranslations = Zend_Locale::getTranslationList('citytotimezone', $locale);
                $availableTimezones = DateTimeZone::listIdentifiers();
                foreach ($availableTimezones as $timezone) {
                    $result[] = array($timezone, $timezone);
                }
                break;

            case Tinebase_Preference::LOCALE:
                $availableTranslations = Tinebase_Translation::getAvailableTranslations();
                foreach ($availableTranslations as $lang) {
                    $region = (!empty($lang['region'])) ? ' / ' . $lang['region'] : '';
                    $result[] = array($lang['locale'], $lang['language'] . $region);
                }
                break;

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
            // get default prefs if value = use default
            if ($value == Tinebase_Model_Preference::DEFAULT_VALUE) {
                $value = $this->{$name};
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Using default (' . $value . ') for ' . $name);
            }
            
            $session = Tinebase_Core::get(Tinebase_Session::SESSION);
            
            switch ($name) {
                case Tinebase_Preference::LOCALE:
                    unset($session->userLocale);
                    $_jsonFrontend->setLocale($value, FALSE, TRUE);
                    break;
                case Tinebase_Preference::TIMEZONE:
                    unset($session->timezone);
                    $_jsonFrontend->setTimezone($value, FALSE);
                    break;
            }
        }
    }
}
