<?php declare(strict_types=1);
/**
 * Division controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class HumanResources_Model_WTRCorrection extends Tinebase_Record_NewAbstract
{
    const TABLE_NAME = 'wtr_correction';
    const MODEL_NAME_PART = 'WTRCorrection';

    const FLD_CORRECTION = 'correction';
    const FLD_DESCRIPTION = 'description';
    const FLD_EMPLOYEE_ID = 'employee_id';
    const FLD_STATUS = 'status';
    const FLD_TITLE = 'title';
    const FLD_WTR_MONTHLY = 'wtr_monthly';
    const FLD_WTR_DAILY = 'wtr_daily';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION               => 1,
        self::RECORD_NAME           => 'Working time correction', // gettext('GENDER_Working time correction')
        self::RECORDS_NAME          => 'Working time corrections', // ngettext('Working time corrections', 'Working time corrections', n)
        self::MODLOG_ACTIVE         => true,
        self::HAS_ATTACHMENTS       => true,
        self::TITLE_PROPERTY        => self::FLD_TITLE,
        self::APP_NAME              => HumanResources_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::CREATE_MODULE         => false,
        self::EXPOSE_JSON_API       => true,
        self::DEFAULT_SORT_INFO     => ['field' => 'creation_time', 'direction' => 'DESC'],
        self::DELEGATED_ACL_FIELD   => self::FLD_EMPLOYEE_ID,

        self::TABLE                 => [
            self::NAME                  => self::TABLE_NAME,
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_EMPLOYEE_ID => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'division_id' => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES => [
                                Tinebase_Record_Expander::PROPERTY_CLASS_ACCOUNT_GRANTS => [],
                            ]
                        ]
                    ]
                ]
            ]
        ],

        self::FIELDS                => [
            self::FLD_EMPLOYEE_ID       => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LABEL                 => 'Employee', // _('Employee')
                self::CONFIG                => [
                    self::APP_NAME              => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => HumanResources_Model_Employee::MODEL_NAME_PART,
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_WTR_DAILY         => [
                self::TYPE                  => self::TYPE_RECORD,
                self::CONFIG                => [
                    self::APP_NAME              => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => HumanResources_Model_DailyWTReport::MODEL_NAME_PART,
                ],
                self::DISABLED              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE              => true,
            ],
            self::FLD_WTR_MONTHLY       => [
                self::TYPE                  => self::TYPE_RECORD,
                self::CONFIG                => [
                    self::APP_NAME              => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => HumanResources_Model_MonthlyWTReport::MODEL_NAME_PART,
                ],
                self::DISABLED              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE              => true,
            ],
            self::FLD_TITLE             => [
                self::TYPE                  => self::TYPE_STRING,
                self::LABEL                 => 'Title', // _('Title')
                self::QUERY_FILTER          => true,
                self::LENGTH                => 255,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE              => true,
            ],
            self::FLD_DESCRIPTION       => [
                self::TYPE                  => self::TYPE_TEXT,
                self::LABEL                 => 'Description', // _('Description')
                self::QUERY_FILTER          => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE              => true,
            ],
            self::FLD_CORRECTION        => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_DURATION_SEC,
                self::LABEL                 => 'Correction', // _('Correction')
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_STATUS            => [
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::LABEL                 => 'Status', // _('Status')
                self::NAME                  => HumanResources_Config::WTR_CORRECTION_STATUS,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::INPUT_FILTERS         => [
                    Zend_Filter_Empty::class => HumanResources_Config::WTR_CORRECTION_STATUS_REQUESTED,
                ]
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
