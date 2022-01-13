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

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE015_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Sales', '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([Sales_Model_Product::class, Sales_Model_SubProductMapping::class,
            Sales_Model_Document_Offer::class, Sales_Model_Document_Boilerplate::class,
            Sales_Model_Document_Customer::class, Sales_Model_Document_Address::class,
            Sales_Model_Document_Order::class, Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class]);
        $this->addApplicationUpdate('Sales', '15.1', self::RELEASE015_UPDATE001);
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
}
