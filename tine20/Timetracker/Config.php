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
    const APP_NAME = 'Timetracker';

    /**
     * Feature bookmark for timeaccounts
     */
    const FEATURE_TIMEACCOUNT_BOOKMARK = 'featureTimeaccountBookmark';

    /**
     * deadline
     * 
     * @var string
     */
    const DEADLINE = 'deadline';

    /**
     * status
     *
     * @var string
     */
    const STATUS ='status';

    const TS_PROCESS_STATUS = 'tsProcessStatus';
    const TS_PROCESS_STATUS_REQUESTED = 'REQUESTED';
    const TS_PROCESS_STATUS_ACCEPTED = 'ACCEPTED';
    const TS_PROCESS_STATUS_DECLINED = 'DECLINED';

    const ACCOUNTING_TIME_ROUNDING_MINUTES = 'accountingTimeRoundingMinutes';
    const ACCOUNTING_TIME_ROUNDING_METHOD = 'accountingTimeRoundingMethod';
    const ACCOUNTING_TIME_ROUNDING_METHOD_CEIL = 'ceil';
    const ACCOUNTING_TIME_ROUNDING_METHOD_FLOOR = 'floor';
    const ACCOUNTING_TIME_ROUNDING_METHOD_ROUND = 'round';

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
                    self::DEFAULT_STR                   => false,
                ],
            ],
            self::DEFAULT_STR => [],
        ],
        self::DEADLINE => array(
            'label' => 'Booking deadline',
            'description' => 'Dealine',
            'type' => 'keyFieldConfig',
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => Timetracker_Model_Timeaccount::DEADLINE_NONE,    'value' => 'none'), // _('none')
                    array('id' => Timetracker_Model_Timeaccount::DEADLINE_LASTWEEK,  'value' => 'last week'), // _('last week')
                ),
                'default' => Timetracker_Model_Timeaccount::DEADLINE_NONE,
            )            
        ),
        self::STATUS => array(
            'label' => 'status',
            'description' => 'Status',
            'type' => 'keyFieldConfig',
            'clientRegistryInclude' => TRUE,
            'default' => array(
                'records' => array(
                    array('id' => Timetracker_Model_Timeaccount::STATUS_NOT_YET_BILLED, 'value' => 'not yet billed'), //_('not yet billed')
                    array('id' => Timetracker_Model_Timeaccount::STATUS_TO_BILL, 'value' => 'to bill'), //_('status to bill')
                    array('id' => Timetracker_Model_Timeaccount::STATUS_BILLED, 'value' => 'billed'), //_('billed')
                ),
                'default' => Timetracker_Model_Timeaccount::STATUS_NOT_YET_BILLED,
            )
        ),
        self::TS_PROCESS_STATUS => [
            self::LABEL                 => 'Timesheet Process Status',
            self::DESCRIPTION           => 'Timesheet Process Status',
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    array('id' => self::TS_PROCESS_STATUS_REQUESTED,  'value' => 'Requested',  'icon' => 'images/icon-set/icon_invite.svg', 'system' => TRUE),  //_('Requested')
                    array('id' => self::TS_PROCESS_STATUS_ACCEPTED,   'value' => 'Accepted',   'icon' => 'images/icon-set/icon_ok.svg', 'system' => TRUE),  //_('Accepted')
                    array('id' => self::TS_PROCESS_STATUS_DECLINED,   'value' => 'Declined',   'icon' => 'images/icon-set/icon_stop.svg', 'system' => TRUE),  //_('Declined')
                ],
                self::DEFAULT_STR           => self::TS_PROCESS_STATUS_ACCEPTED,
            ],
        ],
        self::ACCOUNTING_TIME_ROUNDING_MINUTES => [
            self::TYPE                  => self::TYPE_INT,
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => 15
        ],
        self::ACCOUNTING_TIME_ROUNDING_METHOD => [
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => self::ACCOUNTING_TIME_ROUNDING_METHOD_CEIL,
        ],
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
