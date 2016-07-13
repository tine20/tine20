<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Sales_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     * - Add price_gross2 and price_total to purchase invoice
     */
    public function update_0()
    {
        if ($this->getTableVersion('sales_purchase_invoices') < 3) {
            $release8 = new Sales_Setup_Update_Release8($this->_backend);
            $release8->update_30();
        }
        $this->setApplicationVersion('Sales', '9.1');
    }

    /**
     * update to 9.2
     * - Add json attributes to product aggregate
     */
    public function update_1()
    {
        if ($this->getTableVersion('sales_product_agg') < 5) {
            $release8 = new Sales_Setup_Update_Release8($this->_backend);
            $release8->update_32();
        }
        $this->setApplicationVersion('Sales', '9.2');
    }
    /**
     * update to 9.3
     * - Add Best. Verdg. FE/UE
     */
    public function update_2()
    {
        if ($this->getTableVersion('sales_sales_invoice') < 6) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>inventory_change</name>
                    <type>float</type>
                    <unsigned>false</unsigned>
                    <notnull>false</notnull>
                </field>
        ');
            $this->_backend->addCol('sales_sales_invoice', $declaration);
            $this->setTableVersion('sales_sales_invoice', 6);
        }
        $this->setApplicationVersion('Sales', '9.3');
    }
}
