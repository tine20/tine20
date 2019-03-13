<?php
/**
 * class to hold WageType data
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold WageType data
 *
 * @package     HumanResources
 * @subpackage  Model
 *
 * @property    string                          $number                 a 10 chars alnum string
 * @property    string                          $name
 * @property    string                          $description
 * @property    bool                            $system                 this is a system wage type which could not be deleted
 * @property    int                             $wage_factor            just informal - no wage computation here atm.
 * @property    bool                            $additional_wage        if set blpipe adds a additional wage
 *  example: actual workingtime from 14:00 to 21:00. WorkingTimePrototype defines a wage_type with 'additional_wage' set from 20:00
 *           blpipe will compute 7 hours of base wage and 1 additional hour of the extra wage
 */
class HumanResources_Model_WageType extends Tinebase_Record_Abstract
{
    const MODEL_NAME_PART = 'WageType';
    const TABLE_NAME = 'humanresources_wagetypes';

    const ID_FEAST      = '30';
    const ID_SALARY     = '31';
    const ID_VACATION   = '32';
    const ID_SICK       = '33';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::RECORD_NAME               => 'Wage type',
        self::RECORDS_NAME              => 'Wage types', // ngettext('Wage type', 'Wage types', n)
        self::TITLE_PROPERTY            => 'id',
        self::HAS_CUSTOM_FIELDS         => true,
        self::HAS_NOTES                 => true,
        self::HAS_TAGS                  => true,
        self::MODLOG_ACTIVE             => true,
        self::EXPOSE_JSON_API           => true,

        self::SINGULAR_CONTAINER_MODE   => true,
        self::HAS_PERSONAL_CONTAINER    => false,

        self::CREATE_MODULE             => false,
        self::APP_NAME                  => HumanResources_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
        ],

        self::FIELDS => [
            'number' => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 10,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Abbreviation', // _('Abbreviation')
            ],
            'name' => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 255,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Name', // _('Name')
            ],
            'description' => [
                self::TYPE              => self::TYPE_FULLTEXT,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                self::LABEL             => 'Description', // _('Description')
                self::NULLABLE          => true,
            ],
            'system' => [
                self::TYPE              => self::TYPE_BOOLEAN,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                self::DEFAULT_VAL       => false,
                self::LABEL             => 'System', // _('System')
            ],
            'wage_factor' => [
                // @TODO: should be type percent!
                self::TYPE              => self::TYPE_INTEGER,
                'specialType'           => 'percent',
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false,],
                self::DEFAULT_VAL       => 100,
                self::LABEL             => 'Factor (%)', // _('Factor (%)')
            ],
            'additional_wage' => [
                self::TYPE              => self::TYPE_BOOLEAN,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                self::DEFAULT_VAL       => false,
                self::LABEL             => 'Additional Wage', // _('Additional Wage')
            ],
        ]
    ];
}
