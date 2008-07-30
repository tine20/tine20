<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

class Voipmanager_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * add the asterisk_peers table
     */    
    public function update_18()
    {
        $tableDefinition = "
        <table>
            <name>snom_phones_acl</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>snom_phone_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_type</name>
                    <type>enum</type>
                    <value>anyone</value>
                    <value>user</value>
                    <value>group</value>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_id</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>read_right</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>
                <field>
                    <name>write_right</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>
                <field>
                    <name>dial_right</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>                

                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <unique>true</unique>
                    <name>snom_phone_id-account_type-account_id</name>
                    <field>
                        <name>snom_phone_id</name>
                    </field>
                    <field>
                        <name>account_type</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                </index>
                <index>
                    <name>snom_phone_acl::snome_phone_id--snome_phones::id</name>
                    <field>
                        <name>snom_phone_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>snom_phones</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>                    
                    </reference>
                </index>
            </declaration>
        </table>";

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        

        $this->setApplicationVersion('Voipmanager', '0.20');
    }       
   
}