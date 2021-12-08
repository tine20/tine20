<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2020-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this ist 2020.11 (ONLY!)
 */
class HumanResources_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE013_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE013_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE013_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE013_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE013_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE013_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        try {
            $this->addApplicationUpdate('HumanResources', '13.0', self::RELEASE013_UPDATE001);
        } catch (Setup_Exception $se) {
            // ... version was already increased to 13.0 in 12.php ...
        }
    }

    public function update002()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $this->getDb()->update(SQL_TABLE_PREFIX . 'humanresources_wt_dailyreport',
            ['working_time_target' => 0], 'working_time_target IS NULL');
        $this->getDb()->update(SQL_TABLE_PREFIX . 'humanresources_wt_dailyreport',
            ['working_time_target_correction' => 0], 'working_time_target_correction IS NULL');
        $this->getDb()->update(SQL_TABLE_PREFIX . 'humanresources_wt_dailyreport',
            ['break_time_net' => 0], 'break_time_net IS NULL');
        $this->getDb()->update(SQL_TABLE_PREFIX . 'humanresources_wt_dailyreport',
            ['break_time_deduction' => 0], 'break_time_deduction IS NULL');
        $this->getDb()->update(SQL_TABLE_PREFIX . 'humanresources_wt_dailyreport',
            ['working_time_actual' => 0], 'working_time_actual IS NULL');
        $this->getDb()->update(SQL_TABLE_PREFIX . 'humanresources_wt_dailyreport',
            ['working_time_correction' => 0], 'working_time_correction IS NULL');
        $this->getDb()->update(SQL_TABLE_PREFIX . 'humanresources_wt_dailyreport',
            ['working_time_total' => 0], 'working_time_total IS NULL');

        Setup_SchemaTool::updateSchema([
            HumanResources_Model_DailyWTReport::class,
        ]);
        
        $this->addApplicationUpdate('HumanResources', '13.1', self::RELEASE013_UPDATE002);
    }

    public function update003()
    {
        Setup_SchemaTool::updateSchema([
            HumanResources_Model_MonthlyWTReport::class,
        ]);
        $this->addApplicationUpdate('HumanResources', '13.2', self::RELEASE013_UPDATE003);
    }

    public function update004()
    {
        Setup_SchemaTool::updateSchema([
            HumanResources_Model_Employee::class,
        ]);
        $this->addApplicationUpdate('HumanResources', '13.3', self::RELEASE013_UPDATE004);
    }
}
