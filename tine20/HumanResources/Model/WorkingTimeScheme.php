<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * working time schema
 *
 * @package     HumanResources
 * @subpackage  Model
 *
 * @property Tinebase_Record_RecordSet      blpipe
 */
class HumanResources_Model_WorkingTimeScheme extends Tinebase_Record_NewAbstract
{
    const MY_VERSION = 4;
    const MODEL_NAME_PART = 'WorkingTimeScheme';

    const TYPES_TEMPLATE = 'template';
    const TYPES_INDIVIDUAL = 'individual';
    const TYPES_SHARED = 'shared';

    const FLDS_TITLE = 'title';
    const FLDS_TYPE = 'type';
    const FLDS_JSON = 'json';
    const FLDS_BLPIPE = 'blpipe';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        self::VERSION               => self::MY_VERSION,
        self::RECORD_NAME           => 'Working time scheme',
        self::RECORDS_NAME          => 'Working time schemes', // ngettext('Working time scheme', 'Working time schemes', n)
        self::MODLOG_ACTIVE         => TRUE,
        self::IS_DEPENDENT          => TRUE,
        self::TITLE_PROPERTY        => self::FLDS_TITLE,
        self::APP_NAME              => HumanResources_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::EXPOSE_JSON_API       => true,

        self::TABLE                 => [
            self::NAME                  => 'humanresources_workingtime',
        ],


        self::FIELDS                => [
            self::FLDS_TITLE            => [
                self::TYPE                  => self::TYPE_STRING,
                self::LABEL                 => 'Title', // _('Title')
                self::QUERY_FILTER          => true,
                self::LENGTH                => 255,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            self::FLDS_TYPE             => [
                self::TYPE                  => self::TYPE_STRING,
                self::LABEL                 => 'Type', // _('Type')
                self::LENGTH                => 40,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::DEFAULT_VALUE    => self::TYPES_TEMPLATE,
                    [Zend_Validate_InArray::class, [self::TYPES_INDIVIDUAL, self::TYPES_TEMPLATE, self::TYPES_SHARED]],
                ],
                self::DEFAULT_VAL           => self::TYPES_TEMPLATE,
            ],
            self::FLDS_JSON             => [
                self::TYPE                  => self::TYPE_JSON,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::DEFAULT_VALUE    => [['days' => [0,0,0,0,0,0,0]]],
                    Tinebase_Record_Validator_Json::class,
                ],
            ],
            self::FLDS_BLPIPE           => [
                self::TYPE                  => self::TYPE_RECORDS,
                self::NULLABLE              => true,
                self::CONFIG                => [
                    self::APP_NAME              => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => HumanResources_Model_BLDailyWTReport_Config::MODEL_NAME_PART,
                    self::STORAGE               => self::TYPE_JSON,
                ],
            ]
        ]
    );
}
