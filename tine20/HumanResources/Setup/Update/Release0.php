<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class HumanResources_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update 0.1 -> 0.2
     * - add fileserver (access) to timeaccounts
     *
     */
    public function update_1()
    {
        $field = '<field>
            <name>cost_center_id</name>
            <type>text</type>
            <length>40</length>
            <notnull>true</notnull>
        </field>';

        $declaration = new Setup_Backend_Schema_Field_Xml($field);

        $this->_backend->addCol('humanresources_contract', $declaration);
        $this->_backend->dropCol('humanresources_contract', 'cost_centre');

        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>contract::cost_center_id--sales_cost_centers::id</name>
                <field>
                    <name>cost_center_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>sales_cost_centers</table>
                    <field>id</field>
                </reference>
            </index>
            ');

        $this->_backend->addForeignKey('humanresources_contract', $declaration);
        $this->setTableVersion('humanresources_contract', '2');
        $this->setApplicationVersion('HumanResources', '0.2');
    }
}
