<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Addressbook_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1: add list_roles table
     *
     * @return void
     */
    public function update_0()
    {
        $table = Setup_Backend_Schema_Table_Factory::getSimpleRecordTable('addressbook_list_role');
        $this->_backend->createTable($table, 'Addressbook', 'addressbook_list_role');
        $this->setApplicationVersion('Addressbook', '9.1');
    }

    /**
     * update to 9.2: just a placeholder
     *
     * @return void
     */
    public function update_1()
    {
        // do nothing
    }

    /**
     * drop index 'name' from lists table
     *
     * @return void
     */
    public function update_2()
    {
        $this->_backend->dropIndex('addressbook_lists', 'name');
        $this->setTableVersion('addressbook_lists', 3);
        $this->setApplicationVersion('Addressbook', '9.3');
    }

    /**
     * add addressbook_list_member_role table
     *
     * @return void
     */
    public function update_3()
    {
        $table = Setup_Backend_Schema_Table_Factory::factory('String', '
        <table>
            <name>adb_list_m_role</name>
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
                    <name>list_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>list_role_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>contact_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <index>
                    <name>adb_list_m_role::list_id--addressbook_lists::id</name>
                    <field>
                        <name>list_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>addressbook_lists</table>
                        <field>id</field>
                        <ondelete>CASCADE</ondelete>
                        <onupdate>CASCADE</onupdate>
                    </reference>
                </index>
                <index>
                    <name>adb_list_m_role::contact_id--addressbook::id</name>
                    <field>
                        <name>contact_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>addressbook</table>
                        <field>id</field>
                        <ondelete>CASCADE</ondelete>
                        <onupdate>CASCADE</onupdate>
                    </reference>
                </index>
                <index>
                    <name>adb_list_m_role::list_role_id--addressbook_list_role::id</name>
                    <field>
                        <name>list_role_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>addressbook_list_role</table>
                        <field>id</field>
                        <ondelete>CASCADE</ondelete>
                        <onupdate>CASCADE</onupdate>
                    </reference>
                </index>
            </declaration>
        </table>
        ');
        $this->_backend->createTable($table, 'Addressbook', 'addressbook_list_member_role');
        $this->setApplicationVersion('Addressbook', '9.4');
    }
    
    /**
     * update to 9.5
     *
     * @return void
     */
    public function update_4()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>list_type</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->addCol('addressbook_lists', $declaration);
        
        $this->setTableVersion('addressbook_lists', 4);
        $this->setApplicationVersion('Addressbook', '9.5');
    }

    /**
     * update to 9.6
     *
     * @return void
     */
    public function update_5()
    {
        if ($this->getTableVersion('addressbook_lists') < 5) {

            $declaration = new Setup_Backend_Schema_Field_Xml('
               <field>
                    <name>seq</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>');
            $this->_backend->addCol('addressbook_lists', $declaration);

            $this->setTableVersion('addressbook_lists', 5);
        }

        $this->setApplicationVersion('Addressbook', '9.6');
    }
}
