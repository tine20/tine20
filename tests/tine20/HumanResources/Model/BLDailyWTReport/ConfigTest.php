<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_BLDailyWTReport_ConfigTest extends TestCase
{
    public function testClientModelConfiguration()
    {
        $features = HumanResources_Config::getInstance()->get(HumanResources_Config::ENABLED_FEATURES);
        $features[HumanResources_Config::FEATURE_WORKING_TIME_ACCOUNTING] = true;
        HumanResources_Config::getInstance()->set(HumanResources_Config::ENABLED_FEATURES, $features);

        $tbJFE = new Tinebase_Frontend_Json();
        $registryData = $tbJFE->getAllRegistryData();
        static::assertSame(HumanResources_Config::APP_NAME, $registryData[HumanResources_Config::APP_NAME]['models']
            [HumanResources_Model_BLDailyWTReport_Config::MODEL_NAME_PART][TMCC::APP_NAME]);
        static::assertSame(HumanResources_Model_BLDailyWTReport_Config::MODEL_NAME_PART,
            $registryData[HumanResources_Config::APP_NAME]['models']
            [HumanResources_Model_BLDailyWTReport_Config::MODEL_NAME_PART][TMCC::MODEL_NAME]);
        static::assertTrue(is_array($registryData[HumanResources_Config::APP_NAME]['models']
            [HumanResources_Model_BLDailyWTReport_Config::MODEL_NAME_PART][TMCC::FIELDS]
            [Tinebase_Model_BLConfig::FLDS_CLASSNAME][TMCC::CONFIG][TMCC::AVAILABLE_MODELS]));
    }
}