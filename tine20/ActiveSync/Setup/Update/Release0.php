<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * updates for major release 0
 *
 * @package     ActiveSync
 * @subpackage  Setup
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
      
    /**
     * add field to store collectionid
     * 
     */    
    public function update_5()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>collectionid</name>
                <type>text</type>
                <length>254</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_content', $declaration);
        
        $this->setApplicationVersion('ActiveSync', '0.6');
    }
    
    /**
     * update unique index
     * 
     */    
    public function update_6()
    {
        $this->_backend->dropForeignKey('acsync_content', 'acsync_content::device_id--acsync_device::id');
        $this->_backend->dropIndex('acsync_content', 'device_id--class--contentid');
        
        // add unique key constraint(app_id-name-account_id-account_type)
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>device_id--class--collectionid--contentid</name>
                <unique>true</unique>
                <field>
                    <name>device_id</name>
                </field>
                <field>
                    <name>class</name>
                </field>
                <field>
                    <name>collectionid</name>
                </field>
                <field>
                    <name>contentid</name>
                </field>
            </index>
        ');
        $this->_backend->addIndex('acsync_content', $declaration);
        
        // add foreign key again
        $declaration = new Setup_Backend_Schema_Index_Xml('
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
        ');
        $this->_backend->addForeignKey('acsync_content', $declaration);
                     
        $this->setApplicationVersion('ActiveSync', '0.7');
    }
    
    /**
     * add field to store devicetype
     * 
     */    
    public function update_7()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>devicetype</name>
                <type>text</type>
                <length>64</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $this->setApplicationVersion('ActiveSync', '0.8');
    }
    
    /**
     * change all fields which store account ids from integer to string
     * 
     */
    public function update_8()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>owner_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('acsync_device', $declaration, 'owner_id');
        
        $this->setApplicationVersion('ActiveSync', '0.9');
    }
    
    /**
     * make some fields not null and add new field to stor last FilterType
     * 
     */
    public function update_9()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_folder', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>device_id</name>
                <type>text</type>
                <length>64</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_folder', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>class</name>
                <type>text</type>
                <length>64</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_folder', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>folderid</name>
                <type>text</type>
                <length>254</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_folder', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>creation_time</name>
                <type>datetime</type>
                <notnull>true</notnull>
            </field> 
        ');
        $this->_backend->alterCol('acsync_folder', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lastfiltertype</name>
                <type>integer</type>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_folder', $declaration);
        
        $this->setApplicationVersion('ActiveSync', '0.10');
    }
    
    /**
     * make pingfolder a blob, as it contains serialized data
     * 
     */
    public function update_10()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>pingfolder</name>
                <type>blob</type>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration);
        
        $this->setApplicationVersion('ActiveSync', '0.11');
    }
    
    /**
     * update to 2.0
     * @return void
     */
    public function update_11()
    {
        $this->setApplicationVersion('ActiveSync', '2.0');
    }
}
