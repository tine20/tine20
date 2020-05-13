<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  BL
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * @package     HumanResources
 * @subpackage  BL
 */
class HumanResources_Model_WorkingTimeSchemeTest extends TestCase
{

    /**
     * @return HumanResources_Model_WorkingTimeScheme
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _getData()
    {
        return new HumanResources_Model_WorkingTimeScheme([
            HumanResources_Model_WorkingTimeScheme::FLDS_JSON   => ['days' => [8*3600,8*3600,8*3600,8*3600,(int)(5.5*3600),0,0]],
            HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE => [
                [
                    Tinebase_Model_BLConfig::FLDS_CLASSNAME     =>
                        HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::class,
                    Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD => [
                        HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::FLDS_START_TIME    => '07:30:00',
                        HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::FLDS_END_TIME      => '16:25:00'
                    ]
                ]
            ]
        ], true);
    }

    public function testConvertToRecord()
    {
        $workingTimeScheme = $this->_getData();
        $workingTimeScheme->json = json_encode($workingTimeScheme->json);

        $workingTimeScheme->runConvertToRecord();
        static::assertTrue(is_array($workingTimeScheme->json), 'json is not an array');
        static::assertTrue($workingTimeScheme->blpipe instanceof Tinebase_Record_RecordSet, 'blpipe is not a RS');
        static::assertTrue($workingTimeScheme->blpipe->getFirstRecord() instanceof Tinebase_Model_BLConfig,
            'blpipes first record is not instance of ' . Tinebase_Model_BLConfig::class);
        static::assertTrue($workingTimeScheme->blpipe->getFirstRecord()->configRecord instanceof
            HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig,
            'blconfig records configRecord is not instance of ' .
            HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::class);
    }
}