<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * updates for major release 5
 *
 * @package     ActiveSync
 * @subpackage  Setup
 */
class ActiveSync_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     * - fix cascade statements
     * - extend primary key to counter
     * - add new column pendingdata
     * 
     * @return void
     */
    public function update_0()
    {
        $this->validateTableVersion('acsync_synckey', '1');
        
        $this->_backend->dropForeignKey('acsync_synckey', 'acsync_synckey::device_id--acsync_device::id');
        $this->_backend->dropPrimaryKey('acsync_synckey');
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>device_id--type--counter</name>
                <primary>true</primary>
                <field>
                    <name>device_id</name>
                </field>
                <field>
                    <name>type</name>
                </field>
                <field>
                    <name>counter</name>
                </field>
            </index>
        ');
        $this->_backend->addPrimaryKey('acsync_synckey', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_synckey::device_id--acsync_device::id</name>
                <field>
                    <name>device_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>acsync_device</table>
                    <field>id</field>
                    <ondelete>cascade</ondelete>
                    <onupdate>cascade</onupdate>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('acsync_synckey', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>pendingdata</name>
                <type>blob</type>
            </field>
        ');
        $this->_backend->addCol('acsync_synckey', $declaration);
        
        
        $this->setTableVersion('acsync_synckey', '2');
        $this->setApplicationVersion('ActiveSync', '5.1');
    }
    
    /**
     * update to 5.2
     * - fix cascade statements
     * - added new column is_deleted
     * 
     * @return void
     */
    public function update_1()
    {
        $this->validateTableVersion('acsync_content', '1');
        
        $this->_backend->dropForeignKey('acsync_content', 'acsync_content::device_id--acsync_device::id');
        
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
                    <ondelete>cascade</ondelete>
                    <onupdate>cascade</onupdate>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('acsync_content', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>is_deleted</name>
                <type>boolean</type>
                <default>false</default>
            </field>
        ');
        $this->_backend->addCol('acsync_content', $declaration);
        
        $this->setTableVersion('acsync_content', '2');
        $this->setApplicationVersion('ActiveSync', '5.2');
    }
    
    /**
     * update to 5.3
     * - added new columns to folder table
     * 
     * @return void
     */
    public function update_2()
    {
        $this->validateTableVersion('acsync_folder', '1');
        
        $this->_backend->truncateTable('acsync_folder');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>parentid</name>
                <type>text</type>
                <length>254</length>
            </field>
        ');
        $this->_backend->addCol('acsync_folder', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>displayname</name>
                <type>text</type>
                <length>254</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_folder', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>type</name>
                <type>integer</type>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_folder', $declaration);
        
        $this->setTableVersion('acsync_folder', '2');
        $this->setApplicationVersion('ActiveSync', '5.3');
    }
    
    /**
     * update to 5.4
     * - added id column to synckey table
     * 
     * @return void
     */
    public function update_3()
    {
        $this->validateTableVersion('acsync_synckey', '2');
        
        $this->_backend->truncateTable('acsync_synckey');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_synckey', $declaration);
        
        $this->setTableVersion('acsync_synckey', '3');
        $this->setApplicationVersion('ActiveSync', '5.4');
    }
    
    /**
     * update to 5.5
     * - added folder_id column and drop class and collectionid column
     * - set some columns to NOT NULL
     * 
     * @return void
     */
    public function update_4()
    {
        $this->validateTableVersion('acsync_content', '2');
        
        $this->_backend->truncateTable('acsync_content');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>folder_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_content', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>device_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_content', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>contentid</name>
                <type>text</type>
                <length>64</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_content', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>creation_time</name>
                <type>datetime</type>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_content', $declaration);
        
        $this->_backend->dropCol('acsync_content', 'class');
        $this->_backend->dropCol('acsync_content', 'collectionid');
        
        $this->setTableVersion('acsync_content', '3');
        $this->setApplicationVersion('ActiveSync', '5.5');
    }
    
    /**
     * update to 5.6
     * - fix acsync_content keys
     * 
     * @return void
     */
    public function update_5()
    {
        $this->validateTableVersion('acsync_content', '3');
        
        $this->_backend->dropForeignKey('acsync_content', 'acsync_content::device_id--acsync_device::id');
        $this->_backend->dropIndex('acsync_content', 'device_id--class--collectionid--contentid');
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>device_id--folder_id--contentid</name>
                <unique>true</unique>
                <length>40</length>
                <field>
                    <name>device_id</name>
                </field>
                <field>
                    <name>folder_id</name>
                </field>
                <field>
                    <name>contentid</name>
                </field>
            </index>
        ');
        $this->_backend->addIndex('acsync_content', $declaration);
        
        $this->_addContentDeviceFK();
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_content::folder_id--acsync_folder::id</name>
                <field>
                    <name>folder_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>acsync_folder</table>
                    <field>id</field>
                    <ondelete>cascade</ondelete>
                    <onupdate>cascade</onupdate>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('acsync_content', $declaration);
                
        $this->setTableVersion('acsync_content', '4');
        $this->setApplicationVersion('ActiveSync', '5.6');
    }
    
    /**
     * add acsync_content::device_id--acsync_device::id to content table
     */
    protected function _addContentDeviceFK()
    {
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
                    <ondelete>cascade</ondelete>
                    <onupdate>cascade</onupdate>
                </reference>
            </index>
        ');
        $this->_backend->addForeignKey('acsync_content', $declaration);
    }
    
    /**
     * update to 5.6
     * - fix cascade statements
     * - added new unique key for deviceid and owner_id
     * 
     * @return void
     */
    public function update_6()
    {
        $this->validateTableVersion('acsync_device', '2');
        
        $this->_dropDeviceFKs();
        
        $this->_backend->truncateTable('acsync_device');
        
        $this->_addDeviceFKs();
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>deviceid--owner_id</name>
                <unique>true</unique>
                <length>40</length>
                <field>
                    <name>deviceid</name>
                </field>
                <field>
                    <name>owner_id</name>
                </field>
            </index>
        ');
        $this->_backend->addIndex('acsync_device', $declaration);
        
        $this->setTableVersion('acsync_device', '3');
        $this->setApplicationVersion('ActiveSync', '5.7');
    }
    
    /**
     * drop FKs before truncating acsync_device -> @see 0005942: problems with update script 5.6 -> 5.7
     */
    protected function _dropDeviceFKs()
    {
        $keysToDrop = array(
            'acsync_content' => 'acsync_content::device_id--acsync_device::id',
            'acsync_folder'  => 'acsync_folder::device_id--acsync_device::id',
            'acsync_synckey' => 'acsync_synckey::device_id--acsync_device::id',
        );
        foreach ($keysToDrop as $table => $name) {
            try {
                $this->_backend->dropForeignKey($table, $name);
            } catch (Zend_Db_Statement_Exception $zdse) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                    " Error when dropping $name ->" . $zdse);
            }
        }
    }
    
    /**
     * add FKs to acsync_device -> @see 0005942: problems with update script 5.6 -> 5.7
     */
    protected function _addDeviceFKs()
    {
        $this->_addFolderFK();
        $this->_addContentDeviceFK();
        $this->_addSyncKeyFK();
    }
    
    /**
     * add acsync_synckey::device_id--acsync_device::id
     */
    protected function _addSyncKeyFK()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
        <index>
            <name>acsync_synckey::device_id--acsync_device::id</name>
            <field>
                <name>device_id</name>
            </field>
            <foreign>true</foreign>
            <reference>
                <table>acsync_device</table>
                <field>id</field>
                <ondelete>cascade</ondelete>
                <onupdate>cascade</onupdate>
            </reference>
        </index> 
        ');
        $this->_backend->addForeignKey('acsync_synckey', $declaration);
    }

    /**
     * add acsync_folder::device_id--acsync_device::id
     */
    protected function _addFolderFK()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_folder::device_id--acsync_device::id</name>
                <field>
                    <name>device_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>acsync_device</table>
                    <field>id</field>
                    <ondelete>cascade</ondelete>
                    <onupdate>cascade</onupdate>
                </reference>
            </index> 
        ');
        $this->_backend->addForeignKey('acsync_folder', $declaration);
    }
    
    /**
     * update to 5.7
     * - fix cascade statements
     * - added new unique key for deviceid and owner_id
     * 
     * @return void
     */
    public function update_7()
    {
        $this->validateTableVersion('acsync_content', '4');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>creation_synckey</name>
                <type>integer</type>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_content', $declaration);
        
        $this->setTableVersion('acsync_content', '5');
        
        $this->_dropDeviceFKs();
        
        $this->_backend->truncateTable('acsync_device');
        
        $this->_addDeviceFKs();
        
        $this->setApplicationVersion('ActiveSync', '5.8');
    }
    
    /**
    * update to 6.0
    *
    * @return void
    */
    public function update_8()
    {
        $this->setApplicationVersion('ActiveSync', '6.0');
    }
}
