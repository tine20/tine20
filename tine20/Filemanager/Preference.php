<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Preference
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * backend for Filemanager preferences
 *
 * @package     Filemanager
 * @subpackage  Preference
 */
class Filemanager_Preference extends Tinebase_Preference_Abstract
{
    /**
     * Filemanager_Preference constructor.
     * @param null $_dbAdapter
     * @param array $_options
     * @throws \Tinebase_Exception_Backend_Database
     */
    public function __construct($_dbAdapter = null, array $_options = array())
    {
        $this->_application = 'Filemanager';
        parent::__construct($_dbAdapter, $_options);
    }

    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $preferences = [];

        return $preferences;
    }

    /**
     * get translated right descriptions
     *
     * @return  array with translated descriptions for this applications preferences
     */
    public function getTranslatedPreferences()
    {
        $translate = Tinebase_Translation::getTranslation($this->_application);

        $prefDescriptions = [
            
        ];

        return $prefDescriptions;
    }

    /**
     * @param string $_preferenceName
     * @param null $_accountId
     * @param string $_accountType
     * @return Tinebase_Model_Preference
     * @throws Tinebase_Exception_NotFound
     */
    public function getApplicationPreferenceDefaults(
        $_preferenceName,
        $_accountId = null,
        $_accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER
    ) {
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        $translate = Tinebase_Translation::getTranslation($this->_application);

        switch ($_preferenceName) {
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }

        return $preference;
    }
}
