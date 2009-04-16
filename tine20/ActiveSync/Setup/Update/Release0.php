<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
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
    
    /**
     * add default policy
     * 
     */    
    public function update_2()
    {
        try {
            $this->_db->insert(SQL_TABLE_PREFIX . 'acsync_policy', array(
                'id'                    => 1,
                'name'                  => 'Default policy',
                'description'           => 'Default policy installed during setup',
                'policykey'             => 1,
                'devicepasswordenabled' => 1 
            ));
        } catch(Zend_Db_Statement_Exception $e) {
            // do nothing! The default policy is already there
        }
        $this->setApplicationVersion('ActiveSync', '0.3');
    }
    
    /**
     * add fields to store device informations
     * 
     */    
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>model</name>
                <type>text</type>
                <length>255</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>imei</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>friendlyname</name>
                <type>text</type>
                <length>255</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>os</name>
                <type>text</type>
                <length>255</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>oslanguage</name>
                <type>text</type>
                <length>128</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>phonenumber</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $this->setApplicationVersion('ActiveSync', '0.4');
    }
    
    /**
     * add table for folders sent to client(foldersync command)
     * 
     */    
    public function update_4()
    {
        $tableDefinition = ('
            <table>
                <name>acsync_folder</name>
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
                        <name>folderid</name>
                        <type>text</type>
                        <length>254</length>
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
                        <name>device_id--class--folderid</name>
                        <unique>true</unique>
                        <field>
                            <name>device_id</name>
                        </field>
                        <field>
                            <name>class</name>
                        </field>
                        <field>
                            <name>folderid</name>
                        </field>
                    </index>
                    <index>
                        <name>acsync_folder::device_id--acsync_device::id</name>
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
                
        $this->setApplicationVersion('ActiveSync', '0.5');
    }    
}
