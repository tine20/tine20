<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

class Phone_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * just a placeholder
     *
     */
    public function update_1()
    {
/*      $declaration = new Setup_Backend_Schema_Field();
        
        $declaration->name      = 'account_type';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'true';
        $declaration->value     = array('anyone', 'account', 'group');
        
        $this->_backend->addCol('application_rights', $declaration);
        
        
        $declaration = new Setup_Backend_Schema_Field();
        
        $declaration->name      = 'right';
        $declaration->type      = 'text';
        $declaration->length    = 64;
        $declaration->notnull   = 'true';
        
        $this->_backend->alterCol('application_rights', $declaration);
        
        $this->setTableVersion('phone_extensions', '1');
        $this->setApplicationVersion('Phone', '0.2'); */
    }

    /**
     * rename application (Dialer -> Phone)
     *
     */
    public function update_2()
    {
        // rename database table
        $this->_backend->renameTable('dialer_extensions', 'phone_extensions');
        
        $this->setApplicationVersion('Phone', '0.3');
    }    
    
    /**
     * add new table for phone history
     *
     */
    public function update_3()
    {
        $tableDefinition = '
        <table>
            <name>phone_callhistory</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>line_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>phone_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>call_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>start</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>connected</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>disconnected</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>duration</name>
                    <type>integer</type>
                </field>
                <field>
                    <name>ringing</name>
                    <type>integer</type>
                </field>                
                <field>
                    <name>direction</name>
                    <type>enum</type>
                    <value>in</value>
                    <value>out</value>
                    <default>in</default>
                    <notnull>true</notnull>         
                </field>
                <field>
                    <name>source</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>destination</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>call_id-phone_id</name>
                    <unique>true</unique>
                    <field>
                        <name>call_id</name>
                    </field>
                    <field>
                        <name>phone_id</name>
                    </field>
                </index>
            </declaration>
        </table>
        ';
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition);
        $this->_backend->createTable($table);

        $this->setApplicationVersion('Phone', '0.4');
    }
            
    /**
     * remove no longer needed field
     *
     */
    public function update_4()
    {
        $this->_backend->dropPrimaryKey('phone_callhistory');
        $this->_backend->dropIndex('phone_callhistory', 'call_id-phone_id');
        $this->_backend->dropCol('phone_callhistory', 'call_id');
        
        $indexDefinition = '
        <index>
            <name>id</name>
            <primary>true</primary>
            <field>
                <name>id</name>
            </field>
            <field>
                <name>phone_id</name>
            </field>
        </index>';
        
        $index = Setup_Backend_Schema_Index_Factory::factory('String', $indexDefinition);
        $this->_backend->addPrimaryKey('phone_callhistory', $index);
        
        $this->setApplicationVersion('Phone', '0.5');
    }

    /**
     * add column callerid
     *
     */
    public function update_5()
    {
        $fieldDefinition = '
        <field>
            <name>callerid</name>
            <type>text</type>
            <length>80</length>
            <notnull>false</notnull>
        </field>';
        
        $column = Setup_Backend_Schema_Field_Factory::factory('String', $fieldDefinition);
        $this->_backend->addCol('phone_callhistory', $column);
        
        $this->setApplicationVersion('Phone', '0.6');
    }
    
    /**
     * change all fields which store account ids from integer to string
     * 
     */
    public function update_6()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('phone_extensions', $declaration, 'account_id');
        
        $this->setTableVersion('phone_extensions', 2);
        $this->setApplicationVersion('Phone', '0.7');
    }
    
    /**
     * update to 2.0
     * @return void
     */
    public function update_7()
    {
        $this->setApplicationVersion('Phone', '2.0');
    }
}
