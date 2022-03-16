<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * virtual BL Config Model
 *
 * @package     Tinebase
 * @subpackage  Model
 *
 * @property string                                                         classname
 * @property Tinebase_Record_Interface|Tinebase_BL_ElementConfigInterface   configRecord
 */
class Tinebase_Model_BLConfig extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'BLConfig';

    const FLDS_CLASSNAME = 'classname';
    const FLDS_CONFIG_RECORD = 'configRecord';

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
    protected static $_modelConfiguration = [
        self::APP_NAME      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,


        self::FIELDS        => [
            self::FLDS_CLASSNAME        => [
                self::TYPE                  => self::TYPE_MODEL,
                self::LABEL                 => 'Type', // _('Type)
            ],
            self::FLDS_CONFIG_RECORD    => [
                self::TYPE                  => self::TYPE_DYNAMIC_RECORD,
                self::LABEL                 => 'Config', // _('Config)
                self::CONFIG                => [
                    self::REF_MODEL_FIELD       => self::FLDS_CLASSNAME,
                    self::PERSISTENT            => true,
                ]
            ]
        ],
    ];
}
