<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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
}
