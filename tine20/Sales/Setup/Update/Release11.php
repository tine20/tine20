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
class Sales_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 11.1
     *
     *  - add customer shorthand for use in filename
     *
     * @throws Setup_Exception_NotFound
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>name_shorthand</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
        ');
        
        try {
            $this->_backend->addCol('sales_customers', $declaration);   
        } catch (Exception $e) {
            // We already have it, it's O.K.
        }
        
        $this->setTableVersion('sales_customers', 3);
        $this->setApplicationVersion('Sales', '11.1');
    }
}
