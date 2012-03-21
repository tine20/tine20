<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

class Addressbook_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * update from 3.0 -> 3.1
     * - add new import definition
     * 
     * @return void
     */
    public function update_0()
    {
        // get import export definitions and save them in db
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'));
        
        $this->setApplicationVersion('Addressbook', '3.1');
    }
    
    /**
     * create default persistent filters
     */
    public function update_1()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $myEventsPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => Addressbook_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All contacts I have read grants for", // _("All contacts I have read grants for")
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => 'Addressbook_Model_ContactFilter',
            'filters'           => array(),
        )));
        
        $this->setApplicationVersion('Addressbook', '3.2');
    }
    
    /**
     * lat & lon can be negative (change fields to unsigned float)
     */
    public function update_2()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lon</name>
                <type>float</type>
                <unsigned>false</unsigned>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lat</name>
                <type>float</type>
                <unsigned>false</unsigned>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);

        $this->setTableVersion('addressbook', '8');
        $this->setApplicationVersion('Addressbook', '3.3');
    }
    
    /**
     * add salutation image path
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>image_path</name>
                <type>text</type>
                <length>255</length>
            </field>');
        $this->_backend->addCol('addressbook_salutations', $declaration);
        $this->setTableVersion('addressbook_salutations', '2', TRUE);
        
        // update image paths
        $salutationTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'addressbook_salutations'));
        
        $where = $salutationTable->getAdapter()->quoteInto('gender = ?', 'male');
        $salutationTable->update(array('image_path' => 'images/empty_photo_male.png'), $where);
        
        $where = $salutationTable->getAdapter()->quoteInto('gender = ?', 'female');
        $salutationTable->update(array('image_path' => 'images/empty_photo_female.png'), $where);
        
        $where = $salutationTable->getAdapter()->quoteInto('gender = ?', 'other');
        $salutationTable->update(array('image_path' => 'images/empty_photo_company.png'), $where);
        
        $this->setApplicationVersion('Addressbook', '3.4');
    }

    /**
     * update to 3.5
     * - convert internal container into shared container
     */
    public function update_4()
    {
        $this->_db->query("UPDATE " . SQL_TABLE_PREFIX . "container SET type='shared' WHERE type='internal'");
        
        $this->setApplicationVersion('Addressbook', '3.5');
    }
    
    /**
     * update to 3.6
     * - add contact_type column and index
     */
    public function update_5()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>type</name>
                <type>text</type>
                <length>128</length>
                <notnull>true</notnull>
                <default>contact</default>
            </field>
        ');
        $this->_backend->addCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>type</name>
                <field>
                    <name>type</name>
                </field>
            </index>
        ');
        $this->_backend->addIndex('addressbook', $declaration);
        
        $this->_db->query("UPDATE " . SQL_TABLE_PREFIX . "addressbook SET type='user' WHERE account_id IS NOT NULL");
        
        $this->setTableVersion('addressbook', '9');
        $this->setApplicationVersion('Addressbook', '3.6');
    }
    
    /**
     * update to 3.7
     * - create table addressbook_lists and addressbook_list_members
     */
    public function update_6()
    {
        $table = Setup_Backend_Schema_Table_Factory::factory('String', '
            <table>
                <name>addressbook_lists</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>container_id</name>
                        <type>integer</type>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>name</name>
                        <type>text</type>
                        <length>128</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>description</name>
                        <type>text</type>
                        <length>255</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>email</name>
                        <type>text</type>
                        <length>64</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>type</name>
                        <type>enum</type>
                        <value>list</value>
                        <value>group</value>
                        <notnull>true</notnull>
                        <default>list</default>
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
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                    <index>
                        <name>name</name>
                        <unique>true</unique>
                        <field>
                            <name>name</name>
                        </field>
                    </index>
                </declaration>
            </table>
        ');
        $this->_backend->createTable($table);
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', '
            <table>
                <name>addressbook_list_members</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>list_id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>contact_id</name>
                        <type>integer</type>
                        <notnull>true</notnull>
                    </field>
    
                    <index>
                        <name>list_id-contact_id</name>
                        <primary>true</primary>
                        <field>
                            <name>list_id</name>
                        </field>
                        <field>
                            <name>contact_id</name>
                        </field>
                    </index>
                    <index>
                        <name>contact_id</name>
                        <field>
                            <name>contact_id</name>
                        </field>
                    </index>
                    <index>
                        <name>adressbook_list_members::list_id--addressbook_lists::id</name>
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
                        <name>addressbook_list_members::contact_id--addressbook::id</name>
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
                </declaration>
            </table>
        ');
        $this->_backend->createTable($table);

        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), 
            'addressbook_lists', 
            1
        );
        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), 
            'addressbook_list_members', 
            1
        );
        
        $this->setApplicationVersion('Addressbook', '3.7');
    }
    
    /**
     * update to 3.8
     * - populate list table with internal groups
     */
    public function update_7()
    {
        $select = $this->_db->select()
            ->from(
                array('container' => SQL_TABLE_PREFIX . 'container'),
                array('id' => 'container.id')
            )
            ->joinLeft(
                /* table  */ array('applications' => SQL_TABLE_PREFIX . 'applications'), 
                /* on     */ $this->_db->quoteIdentifier('applications.id') . ' = ' . $this->_db->quoteIdentifier('container.application_id'),
                /* select */ array()
            )
            ->where("container.name='Internal Contacts' and type='shared' and applications.name='Addressbook'");
            
        $result = $this->_db->fetchRow($select);
        
        $containerId = $result['id'];
        
        $select = $this->_db->select()
            ->from(
                array('groups' => SQL_TABLE_PREFIX . 'groups') 
            )
            ->group('groups' . '.id')
            ->joinLeft(
                /* table  */ array('group_members' => SQL_TABLE_PREFIX . 'group_members'), 
                /* on     */ $this->_db->quoteIdentifier('groups' . '.id') . ' = ' . $this->_db->quoteIdentifier('group_members' . '.' . 'group_id'),
                /* select */ array()
            )
            ->joinLeft(
                /* table  */ array('accounts' => SQL_TABLE_PREFIX . 'accounts'), 
                /* on     */ $this->_db->quoteIdentifier('group_members' . '.account_id') . ' = ' . $this->_db->quoteIdentifier('accounts' . '.' . 'id'),
                /* select */ array('members' => 'GROUP_CONCAT(' . $this->_db->quoteIdentifier('accounts' . '.' . 'contact_id') . ')')
            )
            ->where("groups.visibility='displayed' and list_id IS NULL");
            
        $result = $this->_db->fetchAll($select);
        
        foreach ($result as $row) {
            // populate list table
            $listId = Tinebase_Record_Abstract::generateUID();
            
            $data = array(
                'id'            => $listId,
                'name'          => $row['name'],
                'description'   => $row['description'],
                'type'          => Addressbook_Model_List::LISTTYPE_GROUP,
                'container_id'  => $containerId
            );
            $this->_db->insert(SQL_TABLE_PREFIX . 'addressbook_lists', $data);
            
            if (!empty($row['members'])) {
                foreach (explode(',', $row['members']) as $member) {
                    $data = array(
                        'list_id'    => $listId,
                        'contact_id' => $member
                    );
                    $this->_db->insert(SQL_TABLE_PREFIX . 'addressbook_list_members', $data);
                }
            }
            // update list_id
            $data = array(
                'list_id'   => $listId
            );
            $this->_db->update(SQL_TABLE_PREFIX . 'groups', $data, $this->_db->quoteInto("id = ?", $row['id']));
        }
        
        $this->setApplicationVersion('Addressbook', '3.8');
    }
    
    /**
     * update to 3.9
     * - drop column account_id
     */
    public function update_8()
    {
        // in earlier releases we added the table prefix to the foreign key names
        try {
            $this->_backend->dropForeignKey('addressbook', 'addressbook::account_id--accounts::id');
        } catch (Zend_Db_Statement_Exception $zdse) {
            try {
                $this->_backend->dropForeignKey('addressbook', SQL_TABLE_PREFIX . 'addressbook::account_id--accounts::id');
            } catch (Zend_Db_Statement_Exception $zdse) {
                // do nothing
            }
        }
        $this->_backend->dropCol('addressbook', 'account_id');
        
        $this->setTableVersion('addressbook', '10');
        $this->setApplicationVersion('Addressbook', '3.9');
    }
    
    /**
     * update to 3.10
     * - change id column of addressbook table to varchar(40)
     */
    public function update_9()
    {
        try {
            $this->_backend->dropForeignKey('addressbook_image', 'addressbook_image::contact_id-addressbook::id');
        } catch (Zend_Db_Statement_Exception $zdse) {
            try {
                // try it again with table prefix
                $this->_backend->dropForeignKey('addressbook_image', SQL_TABLE_PREFIX . 'addressbook_image::contact_id-addressbook::id');
            } catch (Zend_Db_Statement_Exception $zdse) {
            }
        }
        try {
            $this->_backend->dropForeignKey('addressbook_list_members', 'addressbook_list_members::contact_id--addressbook::id');
        } catch (Zend_Db_Statement_Exception $zdse) {
            try {
                // try it again with table prefix
                $this->_backend->dropForeignKey('addressbook_list_members', SQL_TABLE_PREFIX . 'addressbook_list_members::contact_id--addressbook::id');
            } catch (Zend_Db_Statement_Exception $zdse) {
            }
        }
            
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        $this->setTableVersion('addressbook', '11');
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>contact_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('addressbook_image', $declaration);
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>addressbook_image::contact_id-addressbook::id</name>
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
                <ondelete>cascade</ondelete>
            </index>');
        $this->_backend->addForeignKey('addressbook_image', $declaration);
        $this->setTableVersion('addressbook_image', '2', true);
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>contact_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('addressbook_list_members', $declaration);
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>addressbook_list_members::contact_id--addressbook::id</name>
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
            </index>');
        $this->_backend->addForeignKey('addressbook_list_members', $declaration);
        
        $this->setTableVersion('addressbook_list_members', '2');
        
        $this->setApplicationVersion('Addressbook', '3.10');
    }
    
    /**
     * update to 3.11
     * - add more default favorites
     */
    public function update_10()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => 'Addressbook_Model_ContactFilter',
        );
        
        try {
            $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);
            $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
                'name'              => "My company",
                'description'       => "All coworkers in my company",
                'filters'           => array(array(
                    'field'     => 'container_id',
                    'operator'  => 'in',
                    'value'     => array(
                        'id'    => $internalAddressbook->getId(),
                        // @todo use placeholder here (as this can change later)?
                        'path'  => '/shared/' . $internalAddressbook->getId(),
                    )
                )),
            ))));
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do not create filter
        }
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My contacts",
            'description'       => "All contacts in my Addressbooks",
            'filters'           => array(array(
                'field'     => 'container_id',
                'operator'  => 'in',
                'value'     => array(
                    'id'    => 'personal',
                    'path'  => '/personal/' . Tinebase_Model_User::CURRENTACCOUNT,
                )
            )),
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Last modified by me",
            'description'       => "All contacts that I have last modified",
            'filters'           => array(array(
                'field'     => 'last_modified_by',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            )),
        ))));
        
        $this->setApplicationVersion('Addressbook', '3.11');
    }
    
    /**
     * update to 4.0
     * @return void
     */
    public function update_11()
    {
        $this->setApplicationVersion('Addressbook', '4.0');
    }
    
    /**
     * update from 3.0 -> 4.0
     * 
     * Neele release received updates up to 3.12 after branching
     * 
     * @return void
     */
    public function update_12()
    {
        $this->setApplicationVersion('Addressbook', '4.0');
    }
}
