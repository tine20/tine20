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
     * Preference for row double click default action
     */
    const DB_CLICK_ACTION = 'dbClickAction';

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
        $preferences[] = self::DB_CLICK_ACTION;

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

        $prefDescriptions = array(
            self::DB_CLICK_ACTION => array(
                'label' => $translate->_('Default behavior when double-clicking on a Row'),
                'description' => $translate->_('Defines which action should be executed by default when double-clicked.'),
            )
        );

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
            case self::DB_CLICK_ACTION:
                $preference->value = 'download';

                $downloadOption = '<option>
                    <value>download</value>
                    <label>' . $translate->_('Download') . '</label>
                </option>';

                $previewOption = '<option>
                    <value>preview</value>
                    <label>' . $translate->_('Preview') . '</label>
                </option>';

                $preference->options = \sprintf('<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                    %s
                    %s
                    </options>', $downloadOption,  $previewOption);
                break;

            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }

        return $preference;
    }
}
