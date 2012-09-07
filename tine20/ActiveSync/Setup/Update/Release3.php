<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * updates for major release 3
 *
 * @package     ActiveSync
 * @subpackage  Setup
 */
class ActiveSync_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * add filter columns
     * @return void
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>contactfilter</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>calendarfilter</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>taskfilter</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>emailfilter</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $this->setApplicationVersion('ActiveSync', '3.1');
    }
    
    /**
     * rename filter columns
     * @return void
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>contactfilter_id</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration, 'contactfilter');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>calendarfilter_id</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration, 'calendarfilter');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>taskfilter_id</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration, 'taskfilter');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>emailfilter_id</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration, 'emailfilter');
        
        $this->setApplicationVersion('ActiveSync', '3.2');
    }
    
    /**
     * rename filter columns once more
     * 
     * @return void
     */
    public function update_2()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>contactsfilter_id</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration, 'contactfilter_id');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>tasksfilter_id</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration, 'taskfilter_id');

        $this->setApplicationVersion('ActiveSync', '3.3');
    }
    
    /**
     * add foreign keys
     * 
     * @return void
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>calendarfilter_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>contactsfilter_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>emailfilter_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>tasksfilter_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_device::calendarfilter_id--filter::id</name>
                <field>
                    <name>calendarfilter_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>filter</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_device::contactsfilter_id--filter::id</name>
                <field>
                    <name>contactsfilter_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>filter</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_device::emailfilter_id--filter::id</name>
                <field>
                    <name>emailfilter_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>filter</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_device::tasksfilter_id--filter::id</name>
                <field>
                    <name>tasksfilter_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>filter</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('acsync_device', $declaration);
        
        $this->setApplicationVersion('ActiveSync', '3.4');
    }
    
    /**
     * update foreign keys
     * 
     * @return void
     */
    public function update_4()
    {
        $this->_backend->dropForeignKey('acsync_device', 'acsync_device::calendarfilter_id--filter::id');
        $this->_backend->dropForeignKey('acsync_device', 'acsync_device::contactsfilter_id--filter::id');
        $this->_backend->dropForeignKey('acsync_device', 'acsync_device::emailfilter_id--filter::id');
        $this->_backend->dropForeignKey('acsync_device', 'acsync_device::tasksfilter_id--filter::id');
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_device::calendarfilter_id--filter::id</name>
                <field>
                    <name>calendarfilter_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>filter</table>
                    <field>id</field>
                    <ondelete>set null</ondelete>
                    <onupdate>cascade</onupdate>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_device::contactsfilter_id--filter::id</name>
                <field>
                    <name>contactsfilter_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>filter</table>
                    <field>id</field>
                    <ondelete>set null</ondelete>
                    <onupdate>cascade</onupdate>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_device::emailfilter_id--filter::id</name>
                <field>
                    <name>emailfilter_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>filter</table>
                    <field>id</field>
                    <ondelete>set null</ondelete>
                    <onupdate>cascade</onupdate>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_device::tasksfilter_id--filter::id</name>
                <field>
                    <name>tasksfilter_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>filter</table>
                    <field>id</field>
                    <ondelete>set null</ondelete>
                    <onupdate>cascade</onupdate>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('acsync_device', $declaration);
        
        $this->setTableVersion('acsync_device', 2);
        $this->setApplicationVersion('ActiveSync', '3.5');
    }
    
    /**
     * update to 4.0
     * @return void
     */
    public function update_5()
    {
        $this->setApplicationVersion('ActiveSync', '4.0');
    }
}
