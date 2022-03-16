<?php

/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2022.11 (ONLY!)
 */
class HumanResources_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE015_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE015_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE015_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE015_UPDATE005 = __CLASS__ . '::update005';

    static protected $_allUpdates = [
        // we'll do some querys here and we want them done before any schema tool comes along to play
        self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            self::RELEASE015_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE015_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE015_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
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
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('HumanResources', '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . HumanResources_Model_Division::TABLE_NAME . ' SET deleted_time = "1970-01-01 00:00:00" WHERE deleted_time IS NULL');
        Setup_SchemaTool::updateSchema([
            HumanResources_Model_Division::class,
        ]);
        $this->addApplicationUpdate('HumanResources', '15.1', self::RELEASE015_UPDATE001);
    }

    public function update002()
    {
        Tinebase_PersistentFilter::getInstance()->createDuringSetup(new Tinebase_Model_PersistentFilter([
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId(),
            'model'             => HumanResources_Model_Division::class . 'Filter',
            'name'              => "All Divisions",
            'description'       => "All division records",
            'filters'           => [],
        ]));
        $this->addApplicationUpdate('HumanResources', '15.2', self::RELEASE015_UPDATE002);
    }

    // this is a app struct prio task too, we better get it done asap after update001 / division table is right
    // otherwise divisions lack their container, can't have that
    public function update003()
    {
        $divisionCtrl = HumanResources_Controller_Division::getInstance();
        $oldValue = $divisionCtrl->doContainerACLChecks(false);
        try {
            $setContainerMethod = new ReflectionMethod(HumanResources_Controller_Division::class, '_setContainer');
            $setContainerMethod->setAccessible(true);
            foreach ($divisionCtrl->getAll() as $division) {
                $setContainerMethod->invoke($divisionCtrl, $division);
                $divisionCtrl->getBackend()->update($division);
            }
            $this->addApplicationUpdate('HumanResources', '15.3', self::RELEASE015_UPDATE003);
        } finally {
            $divisionCtrl->doContainerACLChecks($oldValue);
        }
    }

    public function update004()
    {
        $this->getDb()->query('ALTER TABLE ' . SQL_TABLE_PREFIX . HumanResources_Model_FreeTime::TABLE_NAME .
            ' CHANGE `status` `' . HumanResources_Model_FreeTime::FLD_TYPE_STATUS . '` varchar(40)');

        $this->addApplicationUpdate('HumanResources', '15.4', self::RELEASE015_UPDATE004);
    }

    public function update005()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        Setup_SchemaTool::updateSchema([
            HumanResources_Model_FreeTime::class,
            HumanResources_Model_FreeTimeType::class,
        ]);

        $this->getDb()->update(SQL_TABLE_PREFIX . HumanResources_Model_FreeTime::TABLE_NAME, [
            HumanResources_Model_FreeTime::FLD_PROCESS_STATUS => HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED
        ]);

        $this->getDb()->update(SQL_TABLE_PREFIX . HumanResources_Model_FreeTime::TABLE_NAME, [
            HumanResources_Model_FreeTime::FLD_TYPE_STATUS => HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED
        ], HumanResources_Model_FreeTime::FLD_TYPE_STATUS . ' = "IN-PROCESS"');

        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . HumanResources_Model_FreeTime::TABLE_NAME . ' SET ' .
            HumanResources_Model_FreeTime::FLD_PROCESS_STATUS . ' = ' . HumanResources_Model_FreeTime::FLD_TYPE_STATUS .
            ' WHERE ' . HumanResources_Model_FreeTime::FLD_TYPE_STATUS . $this->getDb()->quoteInto(' IN (?)', [
                HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED,
                HumanResources_Config::FREE_TIME_PROCESS_STATUS_REQUESTED,
                HumanResources_Config::FREE_TIME_PROCESS_STATUS_DECLINED
            ]));

        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . HumanResources_Model_FreeTime::TABLE_NAME . ' SET ' .
            HumanResources_Model_FreeTime::FLD_TYPE_STATUS . ' = NULL WHERE ' .
            HumanResources_Model_FreeTime::FLD_TYPE_STATUS . $this->getDb()->quoteInto(' IN (?)', [
                HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED,
                HumanResources_Config::FREE_TIME_PROCESS_STATUS_REQUESTED,
                HumanResources_Config::FREE_TIME_PROCESS_STATUS_DECLINED
            ]));

        HumanResources_Setup_Initialize::addFreeTimePersistenFilter();

        $translate = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME);
        $existingFTTs = HumanResources_Controller_FreeTimeType::getInstance()->getAll();
        foreach(HumanResources_Setup_Initialize::$freeTimeTypes as $ftt) {
            foreach([$ftt['name'], $translate->_($ftt['name'])] as $name) {
                $existingFTT = $existingFTTs->filter('name', $name)->getFirstRecord();
                if ($existingFTT) {
                    $existingFTT->color = $ftt['color'];
                    HumanResources_Controller_FreeTimeType::getInstance()->update($existingFTT);
                    continue 2;
                }
            }
        }

        $this->addApplicationUpdate('HumanResources', '15.5', self::RELEASE015_UPDATE005);
    }
}
