<?php declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Model_BankHoliday extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'BankHoliday';
    const TABLE_NAME = 'bankholiday';

    const FLD_NAME = 'name';
    const FLD_DESCRIPTION = 'description';
    const FLD_DATE = 'date';
    const FLD_CALENDAR_ID = 'calendar_id';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION       => 1,
        self::MODLOG_ACTIVE => true,
        self::IS_DEPENDENT  => true,

        self::RECORD_NAME    => 'Bank Holiday', // _('GENDER_Bank Holiday')
        self::RECORDS_NAME   => 'Bank Holidays', // ngettext('Bank Holiday', 'Bank Holidays', n)
        self::TITLE_PROPERTY => "{{ date |localizeddate('short', 'none', app.request.locale) }} - {{ name}}",

        self::APP_NAME      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,

        self::TABLE         => [
            self::NAME          => self::TABLE_NAME,
            self::INDEXES       => [
                self::FLD_CALENDAR_ID   => [
                    self::COLUMNS           => [self::FLD_CALENDAR_ID],
                ],
            ],
        ],

        self::ASSOCIATIONS => [
            ClassMetadataInfo::MANY_TO_ONE  => [
                self::FLD_CALENDAR_ID           => [
                    self::TARGET_ENTITY             => Tinebase_Model_BankHolidayCalendar::class,
                    self::FIELD_NAME                => self::FLD_CALENDAR_ID,
                    self::JOIN_COLUMNS              => [[
                        self::NAME                      => self::FLD_CALENDAR_ID,
                        self::REFERENCED_COLUMN_NAME    => 'id',
                        self::ON_DELETE                 => 'CASCADE',
                    ]],
                ],
            ],
        ],

        self::FIELDS        => [
            self::FLD_CALENDAR_ID       => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LABEL                 => 'Bank Holiday Calendar', // _('Bank Holiday Calendar')
                self::CONFIG                => [
                    self::APP_NAME              => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME            => Tinebase_Model_BankHolidayCalendar::MODEL_NAME_PART,
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::DISABLED              => true,
            ],
            self::FLD_DATE              => [
                self::TYPE                  => self::TYPE_DATE,
                self::LABEL                 => 'Date', // _('Date')
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_NAME              => [
                self::TYPE                  => self::TYPE_STRING,
                self::LABEL                 => 'Name', // _('Name')
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_DESCRIPTION       => [
                self::TYPE                  => self::TYPE_TEXT,
                self::LABEL                 => 'Description', // _('Description')
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