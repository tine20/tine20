<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class ActiveSync_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * add table synchronized content
     * 
     */    
    public function update_1()
    {
        $tableDefinition = ('
            <table>
                <name>acsync_content</name>
                <engine>InnoDB</engine>
                <charset>utf8</charset>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>device_id</name>
                        <type>text</type>
                        <length>64</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>class</name>
                        <type>text</type>
                        <length>64</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>contentid</name>
                        <type>text</type>
                        <length>64</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>creation_time</name>
                        <type>datetime</type>
                    </field> 
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                    <index>
                        <name>device_id--class--contentid</name>
                        <unique>true</unique>
                        <field>
                            <name>device_id</name>
                        </field>
                        <field>
                            <name>class</name>
                        </field>
                        <field>
                            <name>contentid</name>
                        </field>
                    </index>
                    <index>
                        <name>acsync_content::device_id--acsync_device::id</name>
                        <field>
                            <name>device_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>acsync_device</table>
                            <field>id</field>
                        </reference>
                        <ondelete>cascade</ondelete>
                        <onupdate>cascade</onupdate>
                    </index>   
                </declaration>
            </table>        
        ');
    
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition); 
        $this->_backend->createTable($table);
                
        $this->setApplicationVersion('ActiveSync', '0.2');
    }    
}
