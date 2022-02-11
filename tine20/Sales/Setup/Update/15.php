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
    const RELEASE015_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE015_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE015_UPDATE006 = __CLASS__ . '::update006';

    static protected $_allUpdates = [
        // this needs to be executed before HR update, so we make it TB prio
        self::PRIO_TINEBASE_STRUCTURE       => [
            self::RELEASE015_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            // this one also maybe rather earlier than later, so prio TB
            self::RELEASE015_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE015_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
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
            self::RELEASE015_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
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
        if ($this->getTableVersion('sales_customers') < 4) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
               <field>
                    <name>language</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>
        ');
            $this->_backend->addCol('sales_customers', $declaration, 6);
            $this->setTableVersion('sales_customers', 4);
        }
        $this->addApplicationUpdate('Sales', '15.2', self::RELEASE015_UPDATE002);
    }

    public function update003()
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
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.3', self::RELEASE015_UPDATE003);
    }

    public function update004()
    {
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX .
            'filter where `model` = "Sales_Model_Division" or `model` = "Sales_Model_DivisionFilter"');

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.4', self::RELEASE015_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Delivery::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Delivery::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.5', self::RELEASE015_UPDATE005);
    }

    public function update006()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_ProductLocalization::class,
        ]);

        $lang = Sales_Config::getInstance()->{Sales_Config::LANGUAGES_AVAILABLE}->default;
        $db = $this->getDb();
        $tableName = SQL_TABLE_PREFIX . Sales_Model_ProductLocalization::getConfiguration()->getTableName();
        foreach ($db->query('SELECT id, name, description, is_deleted FROM ' . SQL_TABLE_PREFIX .
                Sales_Model_Product::TABLE_NAME)->fetchAll(Zend_Db::FETCH_NUM) as $row) {
            $db->insert($tableName, [
                'id' => Tinebase_Record_Abstract::generateUID(),
                Tinebase_Record_PropertyLocalization::FLD_RECORD_ID => $row[0],
                Tinebase_Record_PropertyLocalization::FLD_TYPE => 'name',
                Tinebase_Record_PropertyLocalization::FLD_TEXT => $row[1],
                Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => $lang,
                'is_deleted' => $row[3],
            ]);
            $db->insert($tableName, [
                'id' => Tinebase_Record_Abstract::generateUID(),
                Tinebase_Record_PropertyLocalization::FLD_RECORD_ID => $row[0],
                Tinebase_Record_PropertyLocalization::FLD_TYPE => 'description',
                Tinebase_Record_PropertyLocalization::FLD_TEXT => $row[2],
                Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => $lang,
                'is_deleted' => $row[3],
            ]);
        }

        Setup_SchemaTool::updateSchema([
            Sales_Model_Product::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.6', self::RELEASE015_UPDATE006);
    }
}
