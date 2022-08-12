<?php

/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2022.11 (ONLY!)
 */
class Timetracker_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE015_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE015_UPDATE003 = __CLASS__ . '::update003';
    
    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE015_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE015_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Timetracker', '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            Timetracker_Model_Timesheet::class,
        ]);

        $this->addApplicationUpdate('Timetracker', '15.1', self::RELEASE015_UPDATE001);
    }

    public function update002()
    {
        Timetracker_Setup_Initialize::addTSRequestedFavorite();
        $this->addApplicationUpdate('Timetracker', '15.2', self::RELEASE015_UPDATE002);
    }

    public function update003()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $db = Tinebase_Core::getDb();
        $taBackend = new Timetracker_Backend_Timeaccount();

        $timeaccounts = $taBackend->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timeaccount::class, [
                    ['field' => 'status', 'operator' => 'equals', 'value' => 'billed'],
                ]
            ));
        
        foreach ($timeaccounts as $timeaccount) {
            $db->query('update ' . SQL_TABLE_PREFIX . 'timetracker_timesheet set is_cleared = 1 where timeaccount_id = "' . $timeaccount->getId() . '" and is_cleared = 0');

            if (!empty($timeaccount['invoice_id'])) {
                $db->query('update ' . SQL_TABLE_PREFIX . 'timetracker_timesheet set invoice_id = "' . $timeaccount['invoice_id'] . '" where timeaccount_id = "' . $timeaccount->getId() . '" and invoice_id IS NULL');
            }
        }
        
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'filter SET filters=REPLACE(filters, "is_cleared_combined", "is_cleared") WHERE model = "Timetracker_Model_TimesheetFilter"');
        $this->addApplicationUpdate('Timetracker', '15.3', self::RELEASE015_UPDATE003);
    }
}
