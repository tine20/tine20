<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

class Sales_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * update from 6.0 -> 6.1
     * - add division table
     *
     * @return void
     */
    public function update_0()
    {
        $tableDefinition = '<table>
                <name>sales_divisions</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>title</name>
                        <type>text</type>
                        <length>128</length>
                        <notnull>true</notnull>
                    </field>
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                </declaration>
            </table>';
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition);
        $this->_backend->createTable($table);
        
        $this->setApplicationVersion('Sales', '6.1');
    }

    /**
     * update from 6.1 -> 6.2
     * - add cost_centers table (if not installed already)
     *
     * @return void
     */
    public function update_1()
    {
        if ($this->_backend->tableVersionQuery('sales_cost_centers') === FALSE) {
            $release5 = new Sales_Setup_Update_Release5($this->_backend);
            $release5->update_5();
        }
        $this->setApplicationVersion('Sales', '6.2');
    }
    
    /**
    * update to 7.0
    *
    * @return void
    */
    public function update_2()
    {
        $this->setApplicationVersion('Sales', '7.0');
    }
}
