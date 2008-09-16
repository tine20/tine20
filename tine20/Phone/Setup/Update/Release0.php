<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:Release0.php 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
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
}
