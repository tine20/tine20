<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Sales_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     * - 0012358: purchase invoice description column too short
     */
    public function update_0()
    {
        if ($this->getTableVersion('sales_purchase_invoices') < 4) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
               <field>
                    <name>description</name>
                    <type>text</type>
                </field>
        ');
            $this->_backend->alterCol('sales_purchase_invoices', $declaration);
            $this->setTableVersion('sales_purchase_invoices', 4);
        }
        $this->setApplicationVersion('Sales', '10.1');
    }
}
