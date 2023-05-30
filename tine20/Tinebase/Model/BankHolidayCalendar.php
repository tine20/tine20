<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Model_BankHolidayCalendar extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'BankHolidayCalendar';
    const TABLE_NAME = 'bankholiday_calendar';

    const FLD_BANKHOLIDAYS = 'bank_holidays';
    const FLD_DATA_SOURCE = 'data_source';
    const FLD_DESCRIPTION = 'description';
    const FLD_NAME = 'name';
    const FLD_TARGET_CAL_ID = 'target_cal_id';


    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION       => 1,
        self::MODLOG_ACTIVE => true,
        self::EXPOSE_JSON_API => true,
        self::RECORD_NAME   => 'Bank Holiday Calendar', // _('GENDER_Bank Holiday Calendar')
        self::RECORDS_NAME  => 'Bank Holiday Calendars', // ngettext('Bank Holiday Calendar', 'Bank Holiday Calendars', n)

        self::APP_NAME      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,

        self::TITLE_PROPERTY => self::FLD_NAME,

        self::TABLE         => [
            self::NAME          => self::TABLE_NAME,
        ],

        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_BANKHOLIDAYS          => [],
            ],
        ],

        self::FIELDS        => [
            self::FLD_NAME              => [
                self::TYPE                  => self::TYPE_STRING,
                self::LABEL                 => 'Name', // _('Name')
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::QUERY_FILTER          => true,
            ],
            self::FLD_DESCRIPTION       => [
                self::TYPE                  => self::TYPE_TEXT,
                self::LABEL                 => 'Description', // _('Description')
                self::NULLABLE              => true,
            ],
            self::FLD_BANKHOLIDAYS      => [
                self::TYPE                  => self::TYPE_RECORDS,
                self::LABEL                 => 'Bank Holidays', // _('Bank Holidays')
                self::CONFIG                => [
                    self::DEPENDENT_RECORDS     => true,
                    self::APP_NAME              => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME            => Tinebase_Model_BankHoliday::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => Tinebase_Model_BankHoliday::FLD_CALENDAR_ID,
                ],
            ],
            self::FLD_DATA_SOURCE       => [
                self::TYPE                  => self::TYPE_STRING,
                self::LABEL                 => 'Data Source Url', // _('Data Source Url')
                self::LENGTH                => 1000,
                self::NULLABLE              => true,
            ],
            self::FLD_TARGET_CAL_ID     => [
                self::TYPE                  => self::TYPE_CONTAINER,
                self::LABEL                 => 'Target Calendar', // _('Target Calendar')
                self::FILTER_DEFINITION     => [
                    self::FILTER                => Tinebase_Model_Filter_Container::class,
                    self::OPTIONS               => [
                        self::MODEL_NAME            => Calendar_Model_Event::class,
                    ],
                ],
                self::NULLABLE              => true,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}