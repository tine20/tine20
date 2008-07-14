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

class Voipmanager_Setup_Update_Release1 extends Setup_Update_Abstract
{
    /**
     * add the asterisk_peers table
     */    
    public function update_00()
    {
        $tableDefinition = "
        <table>
            <name>snom_phones_acl</name>
            <engine>InnoDB</engine>
            <charset>utf8</charset>
            <version>1</version>
            <declaration>
                <field>
                    <name>phone_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>                                
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>phone_id</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>                    
                </index>
            </declaration>
        </table>";

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        

        $this->setApplicationVersion('Voipmanager', '1.01');
    }       
   
}