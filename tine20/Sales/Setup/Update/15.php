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
 * this is 2022.11 (ONLY!)
 */
class Sales_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE015_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE015_UPDATE003 = __CLASS__ . '::update003';

    static protected $_allUpdates = [
        // this needs to be executed before HR update, so we make it TB prio
        self::PRIO_TINEBASE_STRUCTURE       => [
            self::RELEASE015_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
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
            self::RELEASE015_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([Sales_Model_Product::class, Sales_Model_SubProductMapping::class,
            Sales_Model_Document_Offer::class, Sales_Model_Document_Boilerplate::class,
            Sales_Model_Document_Customer::class, Sales_Model_Document_Address::class,
            Sales_Model_Document_Order::class, Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.1', self::RELEASE015_UPDATE001);
    }

    public function update002()
    {
        if (class_exists('HumanResources_Config') &&
                Tinebase_Application::getInstance()->isInstalled(HumanResources_Config::APP_NAME)) {
            $this->_backend->renameTable('sales_divisions', 'humanresources_division');
            Tinebase_Application::getInstance()->removeApplicationTable(Sales_Config::APP_NAME, 'sales_divisions');
            Tinebase_Application::getInstance()->removeApplicationTable(HumanResources_Config::APP_NAME,
                'humanresources_division');
        } else {
            $this->_backend->dropTable('sales_divisions', Sales_Config::APP_NAME);
        }
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.2', self::RELEASE015_UPDATE002);
    }

    public function update003()
    {
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX .
            'filter where `model` = "Sales_Model_Division" or `model` = "Sales_Model_DivisionFilter"');

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.3', self::RELEASE015_UPDATE003);
    }
}
