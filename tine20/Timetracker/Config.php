<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <M.Spahn@bitExpert.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Sales config class
 *
 * @package     Timetracker
 * @subpackage  Config
 *
 */
class Timetracker_Config extends Tinebase_Config_Abstract
{
    /**
     * Feature bookmark for timeaccounts
     */
    const FEATURE_TIMEACCOUNT_BOOKMARK = 'featureTimeaccountBookmark';

    /**
     * @var array
     */
    protected static $_properties = array(
        self::ENABLED_FEATURES => array(
            //_('Enabled Features')
            'label' => 'Enabled Features',
            //_('Enabled Features in Timetracker Application.')
            'description' => 'Enabled Features in Timetracker Application.',
            'type' => 'object',
            'class' => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => true,
            'content' => array(
                self::FEATURE_TIMEACCOUNT_BOOKMARK => array(
                    'label' => 'Timeaccount Bookmarks', //_('Timeaccount Bookmarks')
                    'description' => 'Add timeaccounts as favorite to speedup timesheet creation.', //_('Add timeaccounts as favorite to speedup timesheet creation.)
                ),
            ),
            'default' => array(
                self::FEATURE_TIMEACCOUNT_BOOKMARK => true,
            ),
        )
    );
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = null;
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Timetracker';

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
    }

    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __clone()
    {
    }
}
