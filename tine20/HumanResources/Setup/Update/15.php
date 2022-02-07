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

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE015_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
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
}
