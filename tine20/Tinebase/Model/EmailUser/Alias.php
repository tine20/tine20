<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * virtual EmailUser Alias model
 *
 * @package     Tinebase
 * @subpackage  Model
 *
 * @property string email
 * @property boolean dispatch_address
 */
class Tinebase_Model_EmailUser_Alias extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'EmailUser_Alias';

    const FLDS_EMAIL = 'email';
    const FLDS_DISPATCH_ADDRESS = 'dispatch_address';

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

        self::FIELDS => [
            self::FLDS_EMAIL => [
                self::TYPE => self::TYPE_STRING,
            ],
            self::FLDS_DISPATCH_ADDRESS => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL => true,
            ]
        ],
    ];
}
