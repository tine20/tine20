<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * Dynamic Record Wrapper Model
 *
 * @package     Tinebase
 * @subpackage  Model
 *
 * @property string                             $model_name
 * @property Tinebase_Record_Interface|string   $record
 */
class Tinebase_Model_DynamicRecordWrapper extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'DynamicRecordWrapper';

    const FLD_MODEL_NAME = 'model_name';
    const FLD_RECORD = 'record';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,


        self::FIELDS        => [
            self::FLD_MODEL_NAME        => [
                self::TYPE                  => self::TYPE_MODEL,
            ],
            self::FLD_RECORD            => [
                self::TYPE                  => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                => [
                    self::REF_MODEL_FIELD       => self::FLD_MODEL_NAME,
                    self::PERSISTENT            => Tinebase_Model_Converter_DynamicRecord::REFID,
                ]
            ]
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
