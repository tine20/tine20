<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
class Sales_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     * @see: 0009048: sometimes the status of sales contract has an icon, sometimes not
     *       https://forge.tine20.org/mantisbt/view.php?id=9048
     */
    public function update_0()
    {
        $quotedTableName = $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "sales_contracts");
        
        $sql = "UPDATE " . $quotedTableName . " SET " . $this->_db->quoteIdentifier('status') . " = 'OPEN' WHERE " . $this->_db->quoteIdentifier('status') . " = 'open';";
        $this->_db->query($sql);
        $sql =  "UPDATE " . $quotedTableName . " SET " . $this->_db->quoteIdentifier('status') . " = 'CLOSED' WHERE " . $this->_db->quoteIdentifier('status') . " = 'closed';";
        $this->_db->query($sql);
        $sql =  "UPDATE " . $quotedTableName . " SET " . $this->_db->quoteIdentifier('cleared') . " = 'CLEARED' WHERE " . $this->_db->quoteIdentifier('cleared') . " = 'cleared';";
        $this->_db->query($sql);
        $sql =  "UPDATE " . $quotedTableName . " SET " . $this->_db->quoteIdentifier('cleared') . " = 'TO_CLEAR' WHERE " . $this->_db->quoteIdentifier('cleared') . " = 'to clear';";
        $this->_db->query($sql);
        $sql =  "UPDATE " . $quotedTableName . " SET " . $this->_db->quoteIdentifier('cleared') . " = 'NOT_YET_CLEARED' WHERE " . $this->_db->quoteIdentifier('cleared') . " = 'not yet cleared';";
        $this->_db->query($sql);
        
        $this->setApplicationVersion('Sales', '8.1');
    }

    /**
     * update to 8.2
     *   - add modlog to costcenter model
     */
    public function update_1()
    {
        $fields = array('<field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>creation_time</name>
                <type>datetime</type>
            </field> ','
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>last_modified_time</name>
                <type>datetime</type>
            </field>','
            <field>
                <name>is_deleted</name>
                <type>boolean</type>
                <default>false</default>
            </field>','
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>deleted_time</name>
                <type>datetime</type>
            </field>','
            <field>
                <name>seq</name>
                <type>integer</type>
                <notnull>true</notnull>
                <default>0</default>
            </field>');
        
        foreach($fields as $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->addCol('sales_cost_centers', $declaration);
        }
        
        $this->setTableVersion('sales_cost_centers', 2);;
        $this->setApplicationVersion('Sales', '8.2');
    }
}
