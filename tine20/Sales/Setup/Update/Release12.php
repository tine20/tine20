<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Sales_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.1
     *
     *  - add more contract favorites
     */
    public function update_0()
    {
        self::createDefaultFavoritesForContracts();
        $this->setApplicationVersion('Sales', '12.1');
    }



    /**
     * update to 12.2
     *
     *  - add type to invoice positions
     */
    public function update_1()
    {
        if (!$this->_backend->columnExists('type', 'sales_invoice_positions')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>type</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>');

            $this->_backend->addCol('sales_invoice_positions', $declaration);
        }

        if ($this->getTableVersion('sales_invoice_positions') < 3) {
            $this->setTableVersion('sales_invoice_positions', 3);
        }

        $this->setApplicationVersion('Sales', '12.2');
    }

}
