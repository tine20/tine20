<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * virtual AttendanceRecorder BL Config Model
 */
class HumanResources_Model_BLAttendanceRecorder_Config extends Tinebase_Model_BLConfig
{
    const MODEL_NAME_PART = 'BLAttendanceRecorder_Config';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static function inheritModelConfigHook(array &$_defintion)
    {
        $_defintion[self::APP_NAME] = HumanResources_Config::APP_NAME;
        $_defintion[self::MODEL_NAME] = self::MODEL_NAME_PART;
        if (!isset($_defintion[self::FIELDS][self::FLDS_CLASSNAME][self::CONFIG])) {
            $_defintion[self::FIELDS][self::FLDS_CLASSNAME][self::CONFIG] = [];
        }
        $_defintion[self::FIELDS][self::FLDS_CLASSNAME][self::CONFIG][self::AVAILABLE_MODELS] = [
            HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::class,
        ];
    }
}
