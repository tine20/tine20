<?php

/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class HumanResources_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE012_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE012_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE012_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE012_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE012_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE012_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
    ];

    public function update001()
    {
        $scheduler = Tinebase_Core::getScheduler();
        if (!$scheduler->hasTask('HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask')) {
            HumanResources_Scheduler_Task::addCalculateDailyWorkingTimeReportsTask($scheduler);
        }
        $this->addApplicationUpdate('HumanResources', '12.6', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        $this->_backend->dropTable('humanresources_breaks', 'HumanResources');
        Setup_SchemaTool::updateSchema([
            HumanResources_Model_WorkingTimeScheme::class,
            HumanResources_Model_DailyWTReport::class,
            HumanResources_Model_FreeTimeType::class,
            HumanResources_Model_WageType::class,
        ]);

        HumanResources_Setup_Initialize::addCORSystemCustomField();

        $this->addApplicationUpdate('HumanResources', '12.7', self::RELEASE012_UPDATE002);
    }

    public function update003()
    {
        $workingTimeSchemeCtrl = HumanResources_Controller_WorkingTimeScheme::getInstance();
        /** @var HumanResources_Model_WorkingTimeScheme $workingTimeScheme */
        foreach ($workingTimeSchemeCtrl->getAll() as $workingTimeScheme) {
            if (is_array($data = $workingTimeScheme->jsonData('json')) && isset($data['days']) &&
                count($data['days']) === 7) {
                foreach ($data['days'] as &$val) {
                    $val = (int)($val * 3600);
                } unset($val);
                $workingTimeScheme->json = $data['days'];
            } elseif (!is_array($workingTimeScheme->jsonData('json')) ||
                count($workingTimeScheme->jsonData('json')) !== 7) {
                $workingTimeScheme->json = [0, 0, 0, 0, 0, 0, 0];
            }
            if ($workingTimeScheme->isDirty()) {
                $workingTimeSchemeCtrl->update($workingTimeScheme);
            }
        }

        // TODO ! update workingtime_json (in 2 tables at least) and convert hour floats to second ints
        throw new Tinebase_Exception_NotImplemented('TODO fix me');

        $this->addApplicationUpdate('HumanResources', '12.8', self::RELEASE012_UPDATE003);
    }

    public function update004()
    {
        Tinebase_Core::getDb()->update(SQL_TABLE_PREFIX . HumanResources_Model_FreeTimeType::TABLE_NAME, [
                'id' => 'sickness'
            ], 'id = "01"');
        Tinebase_Core::getDb()->update(SQL_TABLE_PREFIX . HumanResources_Model_FreeTimeType::TABLE_NAME, [
            'id' => 'vacation'
        ], 'id = "03"');

        $this->addApplicationUpdate('HumanResources', '12.9', self::RELEASE012_UPDATE004);
    }
}
