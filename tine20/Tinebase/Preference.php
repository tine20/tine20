<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 */


/**
 * backend for tinebase preferences
 *
 * @package     Timetracker
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
     * application
     *
     * @var string
     */
    protected $_application = 'Tinebase';    
        
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
                self::LOCALE
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
        );
        
        return $prefDescriptions;
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
