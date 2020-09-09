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
 * virtual EmailUser Forward model
 *
 * @package     Tinebase
 * @subpackage  Model
 *
 * @property string email
 */
class Tinebase_Model_EmailUser_Forward extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'EmailUser_Forward';

    const FLDS_EMAIL = 'email';

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
            self::FLDS_EMAIL        => [
                self::TYPE                  => self::TYPE_STRING,
            ],
        ],
    ];
}
