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
        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '12.6', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        // force closed transaction
        Tinebase_TransactionManager::getInstance()->rollBack();

        // make sure, workingtime_json exists
        if (! $this->_backend->columnExists('workingtime_json', 'humanresources_contract')) {
            $this->_backend->addCol('humanresources_contract', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>workingtime_json</name>
                    <type>text</type>
                    <length>1024</length>
                    <notnull>false</notnull>
                </field>'));
        }

        $rows = Tinebase_Core::getDb()->query('SELECT id, workingtime_json, employee_id, start_date FROM ' . SQL_TABLE_PREFIX .
            'humanresources_contract')->fetchAll();
        if (Tinebase_Core::isLogLevel(Zend_Log::WARN) && count($rows) > 0) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' .
                'found these deprecated HR contract data:' . print_r($rows, true));
        }

        $this->_backend->dropTable('humanresources_wt_dailyreport', HumanResources_Config::APP_NAME);
        $this->_backend->dropTable('humanresources_breaks', HumanResources_Config::APP_NAME);

        Setup_SchemaTool::updateSchema([
            HumanResources_Model_Contract::class,
            HumanResources_Model_WorkingTimeScheme::class,
            HumanResources_Model_DailyWTReport::class,
            HumanResources_Model_FreeTimeType::class,
            HumanResources_Model_WageType::class,
        ]);

        HumanResources_Setup_Initialize::addCORSystemCustomField();

        $workingTimeSchemeCtrl = HumanResources_Controller_WorkingTimeScheme::getInstance();
        foreach ($rows as $row) {
            if (!is_array($wtData = json_decode($row['workingtime_json'], true)) || !isset($wtData['days']) ||
                    count($wtData['days']) !== 7) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' .
                    'bad row: ' . print_r($row, true));
                continue;
            }

            /*************** !!!
             * we take the old data here, update003 will update the data itself
             */
            /*foreach ($wtData['days'] as &$val) {
                $val = (int)($val * 3600);
            } unset($val);*/

            try {
                $employee = HumanResources_Controller_Employee::getInstance()->get($row['employee_id']);
                $title = $employee['number'] . ' ' . $employee['n_fn'] . ' ';
                try {
                    $date = new Tinebase_DateTime($row['start_date']);
                    $title .= $date->setTimezone(Tinebase_Core::getUserTimezone())->format('Y-m-d ');
                } catch (Exception $e) {}
                $title .= $row['id'];
                $title = mb_substr($title, 0, 255);
            } catch (Exception $e) {
                $title = 'contract ' . $row['id'];
            }

            $wts = new HumanResources_Model_WorkingTimeScheme([
                HumanResources_Model_WorkingTimeScheme::FLDS_TITLE => $title,
                HumanResources_Model_WorkingTimeScheme::FLDS_TYPE =>
                    HumanResources_Model_WorkingTimeScheme::TYPES_INDIVIDUAL,
                HumanResources_Model_WorkingTimeScheme::FLDS_JSON => $wtData,
            ]);
            try {
                $wts = $workingTimeSchemeCtrl->create($wts);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                continue;
            }
            Tinebase_Core::getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'humanresources_contract SET ' .
                'working_time_scheme = "' . $wts->getId() . '" WHERE id = "' . $row['id'] . '"');

        }

        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '12.7', self::RELEASE012_UPDATE002);
    }

    public function update003()
    {
        // force closed transaction
        Tinebase_TransactionManager::getInstance()->rollBack();
        
        $workingTimeSchemeCtrl = HumanResources_Controller_WorkingTimeScheme::getInstance();
        /** @var HumanResources_Model_WorkingTimeScheme $workingTimeScheme */
        foreach ($workingTimeSchemeCtrl->getAll() as $workingTimeScheme) {
            if (is_array($data = $workingTimeScheme->jsonData('json')) && isset($data['days']) &&
                    count($data['days']) === 7) {
                foreach ($data['days'] as &$val) {
                    $val = (int)($val * 3600);
                }
                unset($val);
                $workingTimeScheme->json = $data;
            } else {
                $workingTimeScheme->json = ['days' => [0, 0, 0, 0, 0, 0, 0]];
            }
            if ($workingTimeScheme->isDirty()) {
                try {
                    $workingTimeSchemeCtrl->update($workingTimeScheme);
                } catch (Tinebase_Exception_Record_Validation $terv) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN) && count($rows) > 0) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' .
                            ' ' . $terv);
                    }
                }
            }
        }

        HumanResources_Setup_Initialize::createReportTemplatesFolder();
        HumanResources_Setup_Initialize::createtWageTypes(false);
        HumanResources_Setup_Initialize::createFreeTimeTypes(false);
        try {
            HumanResources_Setup_Initialize::createWorkingTimeModels();
        } catch (Tinebase_Exception_Duplicate $ted) {
            // already there
        }

        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '12.8', self::RELEASE012_UPDATE003);
    }

    public function update004()
    {
        Tinebase_Core::getDb()->update(SQL_TABLE_PREFIX . HumanResources_Model_FreeTimeType::TABLE_NAME, [
                'id' => 'sickness'
            ], 'id = "01"');
        Tinebase_Core::getDb()->update(SQL_TABLE_PREFIX . HumanResources_Model_FreeTimeType::TABLE_NAME, [
            'id' => 'vacation'
        ], 'id = "03"');

        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '12.9', self::RELEASE012_UPDATE004);
    }
}
