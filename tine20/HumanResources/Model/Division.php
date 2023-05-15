<?php declare(strict_types=1);
/**
 * class to hold Division data
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Division data
 *
 * @package     Sales
 */
class HumanResources_Model_Division extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART    = 'Division';
    public const TABLE_NAME         = 'humanresources_division';

    public const FLD_FREE_TIME_CAL  = 'free_time_cal';
    public const FLD_TITLE          = 'title';
    public const FLD_WORKING_TIME_TIMEACCOUNT_ID = 'wt_ta_id';


    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 2,
        self::APP_NAME                  => HumanResources_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME               => 'Division', // gettext('GENDER_Division')
        self::RECORDS_NAME              => 'Divisions', // ngettext('Division', 'Divisions', n)
        self::CONTAINER_NAME            => 'Division',
        self::CONTAINERS_NAME           => 'Divisions',
        self::HAS_RELATIONS             => true,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::CREATE_MODULE             => true,
        self::EXPOSE_JSON_API           => true,
        self::TITLE_PROPERTY            => self::FLD_TITLE,
        self::EXTENDS_CONTAINER         => self::FLD_CONTAINER_ID,
        self::GRANTS_MODEL              => HumanResources_Model_DivisionGrants::class,
        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_WORKING_TIME_TIMEACCOUNT_ID          => [],
            ],
            Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES => [
                Tinebase_Record_Expander::PROPERTY_CLASS_GRANTS         => [],
                Tinebase_Record_Expander::PROPERTY_CLASS_ACCOUNT_GRANTS => [],
            ]
        ],

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_TITLE                 => [
                    self::COLUMNS                   => [self::FLD_TITLE, self::FLD_DELETED_TIME],
                ],
            ],
        ],
        self::FIELDS                    => [
            self::FLD_TITLE                 => [
                self::LABEL                     => 'Title', // _('Title')
                self::TYPE                      => self::TYPE_STRING,
                self::QUERY_FILTER              => true,
            ],
            self::FLD_WORKING_TIME_TIMEACCOUNT_ID => [
                self::LABEL                         => 'Timetracker time account for working time tracking of this division', // _('Timetracker time account for working time tracking of this division')
                self::TYPE                          => self::TYPE_RECORD,
                self::NULLABLE                      => true,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY          => true,
                ],
                self::CONFIG                        => [
                    self::APP_NAME                      => Timetracker_Config::APP_NAME,
                    self::MODEL_NAME                    => Timetracker_Model_Timeaccount::MODEL_NAME_PART,
                ],
            ],
            self::FLD_FREE_TIME_CAL         => [
                self::TYPE                      => self::TYPE_CONTAINER,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Free Time Calendar', // _('Free Time Calendar')
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_Container::class,
                    self::OPTIONS                   => [
                        self::MODEL_NAME                => Calendar_Model_Event::class,
                    ]
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
