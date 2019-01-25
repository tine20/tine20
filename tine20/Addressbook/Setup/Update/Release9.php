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
        $this->createTable('addressbook_list_role', $table, 'Addressbook');
        $this->setApplicationVersion('Addressbook', '9.1');
    }

    /**
     * update to 9.2: just a placeholder
     *
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Addressbook', '9.2');
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
                    <name>adb_list_m_role::list_role_id--adb_list_role::id</name>
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
        $this->createTable('adb_list_m_role', $table, 'Addressbook');
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

    /**
     * update to 9.7
     *
     * - re-run update_3 if table adb_list_m_role does not exist
     *
     * @see 0011848: Tine 2.0 update-script creates table adb_list_m_role but registers table with diffent name
     * @return void
     */
    public function update_6()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Rename table "adb_list_m_role" in application_tables');
        $this->renameTableInAppTables('addressbook_list_member_role', 'adb_list_m_role');

        $this->setApplicationVersion('Addressbook', '9.7');
    }

    /**
     * update to 9.8
     *
     * @see 0011934: show contacts in phone call grid
     *
     * @return void
     */
    public function update_7()
    {
        if ($this->getTableVersion('addressbook') < 19) {
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>tel_assistent_normalized</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>tel_car_normalized</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>tel_cell_normalized</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>tel_cell_private_normalized</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>tel_fax_normalized</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>tel_fax_home_normalized</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>tel_home_normalized</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>tel_other_normalized</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>tel_pager_normalized</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>tel_prefer_normalized</name>
                    <type>text</type>
                    <length>32</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>tel_work_normalized</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);

            $this->setTableVersion('addressbook', 19);

            // fill normalized columns with data
            $db = Tinebase_Core::getDb();
            $select = $db->select();
            $columns = array('id', 'tel_assistent', 'tel_car', 'tel_cell', 'tel_cell_private', 'tel_fax', 'tel_fax_home', 'tel_home', 'tel_pager', 'tel_work', 'tel_other', 'tel_prefer');

            // get all telephone columns
            $select->from(SQL_TABLE_PREFIX . 'addressbook', $columns);
            $result = $db->query($select);
            $data = array();
            array_shift($columns);

            $results = $result->fetchAll(Zend_Db::FETCH_ASSOC);

            foreach ($results as $row) {
                foreach ($columns as $col) {
                    if (!empty($row[$col])) {
                        $data[$col . '_normalized'] = Addressbook_Model_Contact::normalizeTelephoneNum((string)$row[$col]);
                    }
                }
                if (count($data) > 0) {
                    $db->update(SQL_TABLE_PREFIX . 'addressbook', $data, $db->quoteInto('id = ?', $row['id']));
                    $data = array();
                }
            }
        }

        $this->setApplicationVersion('Addressbook', '9.8');
    }

    /**
     * update to 9.9
     *
     * addressbook f_fn length => 255
     *
     * @return void
     */
    public function update_8()
    {
        if ($this->getTableVersion('addressbook') == 19) {

            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>n_fn</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->alterCol('addressbook', $declaration);

            $this->setTableVersion('addressbook', 20);
        }

        $this->setApplicationVersion('Addressbook', '9.9');
    }

    /**
     * add addressbook_industry table and column
     *
     * @return void
     */
    public function update_9()
    {
        if ($this->getTableVersion('addressbook_industry') < 1) {

            if (! $this->_backend->tableExists('addressbook_industry')) {
                $table = Setup_Backend_Schema_Table_Factory::factory('String', '
                <table>
                    <name>addressbook_industry</name>
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
                            <name>name</name>
                            <type>text</type>
                            <length>256</length>
                            <notnull>false</notnull>
                        </field>
                        <field>
                            <name>description</name>
                            <type>text</type>
                            <notnull>false</notnull>
                        </field>
                        <field>
                            <name>created_by</name>
                            <type>text</type>
                            <length>40</length>
                        </field>
                        <field>
                            <name>creation_time</name>
                            <type>datetime</type>
                        </field>
                        <field>
                            <name>last_modified_by</name>
                            <type>text</type>
                            <length>40</length>
                        </field>
                        <field>
                            <name>last_modified_time</name>
                            <type>datetime</type>
                        </field>
                        <field>
                            <name>is_deleted</name>
                            <type>boolean</type>
                            <default>false</default>
                        </field>
                        <field>
                            <name>deleted_by</name>
                            <type>text</type>
                            <length>40</length>
                        </field>
                        <field>
                            <name>deleted_time</name>
                            <type>datetime</type>
                        </field>
                        <field>
                            <name>seq</name>
                            <type>integer</type>
                            <notnull>true</notnull>
                            <default>0</default>
                        </field>
                        <index>
                            <name>id</name>
                            <primary>true</primary>
                            <field>
                                <name>id</name>
                            </field>
                        </index>
                    </declaration>
                </table>
                ');
                $this->createTable('addressbook_industry', $table, 'Addressbook');
            } else {
                $this->setTableVersion('addressbook_industry', 1);
            }
        }
        
        if ($this->getTableVersion('addressbook') == 20) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>industry</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('addressbook', $declaration);
            $this->setTableVersion('addressbook', 21);
        }
        
        $this->setApplicationVersion('Addressbook', '9.10');
    }

    /**
     * update to 9.11
     *
     * add multiple sync backends / ldap implementation
     */
    public function update_10()
    {
        if ($this->getTableVersion('addressbook') < 22) {
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>syncBackendIds</name>
                    <type>text</type>
                    <length>16000</length>
                </field>');
            try {
                $this->_backend->addCol('addressbook', $declaration);
            } catch (Zend_Db_Exception $zde) {
                Tinebase_Exception::log($zde);
            }

            $this->setTableVersion('addressbook', 22);
        }

        $this->setApplicationVersion('Addressbook', '9.11');
    }

    /**
     * update to 10.0
     */
    public function update_11()
    {
        $this->setApplicationVersion('Addressbook', '10.0');
    }
}
