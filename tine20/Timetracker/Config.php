<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <M.Spahn@bitExpert.de>
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * Type
     *
     * @var string
     */
    const TYPE = 'type';

    /**
     * @var array
     */
    protected static $_properties = [
        self::ENABLED_FEATURES => [
            //_('Enabled Features')
            self::LABEL                 => 'Enabled Features',
            //_('Enabled Features in Timetracker Application.')
            self::DESCRIPTION           => 'Enabled Features in Timetracker Application.',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [
                self::FEATURE_TIMEACCOUNT_BOOKMARK  => [
                    self::LABEL                         => 'Timeaccount Bookmarks',
                    //_('Timeaccount Bookmarks')
                    self::DESCRIPTION                   =>
                        'Add timeaccounts as favorite to speedup timesheet creation.',
                    //_('Add timeaccounts as favorite to speedup timesheet creation.)
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => true,
                ],
            ],
            self::DEFAULT_STR => [],
        ],
        self::TYPE => [
            //_('Type')
            'label' => 'Type',
            //_('Type')
            'description' => 'Possible types',
            'type' => 'keyFieldConfig',
            'clientRegistryInclude' => true,
            'setByAdminModule' => true,
            'setBySetupModule' => false,
            'default' => [
                'records' => [
                    ['id' => 'AZ', 'value' => 'Working time', 'system' => true], //_('Working time')
                    ['id' => 'PZ', 'value' => 'Project time', 'system' => true], //_('Project time')
                ]
            ]
        ]
    ];
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
