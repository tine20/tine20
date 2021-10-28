<?php

/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2021.11 (ONLY!)
 */
class Sales_Setup_Update_14 extends Setup_Update_Abstract
{
    const RELEASE014_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE014_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE014_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE014_UPDATE003 = __CLASS__ . '::update003';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE014_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ]
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE014_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE014_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE014_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Sales', '14.0', self::RELEASE014_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([Sales_Model_Address::class]);
        $this->addApplicationUpdate('Sales', '14.1', self::RELEASE014_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([Sales_Model_Boilerplate::class]);
        $this->addApplicationUpdate('Sales', '14.2', self::RELEASE014_UPDATE002);
    }

    public function update003()
    {
        // update needs this - it waits for table metadata lock for a very long time otherwise ...
        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . Sales_Model_Product::TABLE_NAME
            . ' SET purchaseprice = 0 WHERE purchaseprice IS NULL');
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . Sales_Model_Product::TABLE_NAME
            . ' SET salesprice = 0 WHERE salesprice IS NULL');
        Setup_SchemaTool::updateSchema([Sales_Model_Product::class]);
        $this->addApplicationUpdate('Sales', '14.3', self::RELEASE014_UPDATE003);
    }
}
