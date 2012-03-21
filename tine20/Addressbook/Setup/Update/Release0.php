<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

class Addressbook_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * this function does nothing. It's from the dark ages without setup being functional
     */    
    public function update_1()
    {
        $this->validateTableVersion('addressbook', '1');
        
        $this->setApplicationVersion('Addressbook', '0.2');
    }
    
    /**
     * updates what???
     * 
     * @todo add changed fields
     */    
    public function update_2()
    {
        $this->validateTableVersion('addressbook', '1');
        
        $this->setTableVersion('addressbook', '2');
        $this->setApplicationVersion('Addressbook', '0.3');
    }
    
    /**
     * correct modlog field definitions
     */    
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>integer</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>creation_time</name>
                <type>datetime</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>integer</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_time</name>
                <type>datetime</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>integer</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_time</name>
                <type>datetime</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $this->setApplicationVersion('Addressbook', '0.4');
    }
                
    /**
     * add salutation_id field and table
     * 
     */    
    public function update_4()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>salutation_id</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>');
        try {
            $this->_backend->addCol('addressbook', $declaration);
        } catch (Exception $e) {
            echo "salutation_id already exists.\n";
        }        

        $tableDefinition = ('
        <table>
            <name>addressbook_salutations</name>
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
                    <length>32</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>gender</name>
                    <type>enum</type>
                    <value>male</value>
                    <value>female</value>
                    <value>other</value>
                    <notnull>true</notnull>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <unique>true</unique>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            </declaration>
        </table>        
        ');
    
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition);
        try {
            $this->_backend->createTable($table);
        } catch (Exception $e) {
            echo "salutation table already exists.\n";
        }        
        
        // add initial values
        $backend = new Addressbook_Backend_Salutation();
        $maleSalutation = new Addressbook_Model_Salutation(array(
            'id'        => 1,
            'name'      => 'Mr',
            'gender'    => Addressbook_Model_Salutation::GENDER_MALE
        ));
        $backend->create($maleSalutation);
        $femaleSalutation = new Addressbook_Model_Salutation(array(
            'id'        => 2,
            'name'      => 'Ms',
            'gender'    => Addressbook_Model_Salutation::GENDER_FEMALE
        ));
        $backend->create($femaleSalutation);
        $companySalutation = new Addressbook_Model_Salutation(array(
            'id'        => 3,
            'name'      => 'Company',
            'gender'    => Addressbook_Model_Salutation::GENDER_OTHER
        ));
        $backend->create($companySalutation);
        
        $this->setApplicationVersion('Addressbook', '0.5');
    }
    
    /**
     * rename column owner to container_id in addressbook table
     * 
     */    
    public function update_5()
    {
        try {
            $this->_backend->dropForeignKey('addressbook', 'addressbook_container_id');
        } catch (Exception $e) {
            echo "  Foreign key 'addressbook_container_id' didn't exist.\n";
        }

        try {
            $this->_backend->dropIndex('addressbook', 'owner');
        } catch (Exception $e) {
            echo "  Index 'owner' didn't exist.\n";
        }
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>container_id</name>
                <type>integer</type>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration, 'owner');
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>container_id</name>
                <field>
                    <name>container_id</name>
                </field>
            </index>
        ');
        $this->_backend->addIndex('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>addressbook::container_id--container::id</name>
                <field>
                    <name>container_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>container</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('addressbook', $declaration);
        
        $this->setTableVersion('addressbook', '4');
        $this->setApplicationVersion('Addressbook', '0.6');
    }
    
    /**
     * rename column owner to container_id in addressbook table
     * 
     */    
    public function update_6()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_id</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>');
        
        $this->_backend->alterCol('addressbook', $declaration, 'account_id');
        
        $this->setTableVersion('addressbook', '5');
        $this->setApplicationVersion('Addressbook', '0.7');
    }
    
    /**
     * the formeer update had the wrong field length
     * added foreign key to accounts table
     * 
     */    
    public function update_7()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>');
        
        $this->_backend->alterCol('addressbook', $declaration, 'account_id');
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>addressbook::account_id--accounts::id</name>
                <field>
                    <name>account_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>accounts</table>
                    <field>id</field>
                </reference>
                <onupdate>cascade</onupdate>
                <ondelete>cascade</ondelete>
            </index>   
        ');
        $this->_backend->addForeignKey('addressbook', $declaration);
        
        $this->setTableVersion('addressbook', '6');
        
        $this->setApplicationVersion('Addressbook', '0.8');
    }
    
    /**
     * change all fields which store account ids from integer to string
     * 
     */
    public function update_8()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration, 'created_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration, 'last_modified_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration, 'deleted_by');
                
        $this->setApplicationVersion('Addressbook', '0.9');
    }
    
    /**
     * change all fields which store account ids from integer to string
     * 
     */
    public function update_9()
    {
        $this->_backend->dropForeignKey('addressbook', 'addressbook::account_id--accounts::id');
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>addressbook::account_id--accounts::id</name>
                <field>
                    <name>account_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>accounts</table>
                    <field>id</field>
                    <onupdate>cascade</onupdate>
                    <ondelete>cascade</ondelete>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('addressbook', $declaration);
        
        $this->setTableVersion('addressbook', '7');
        
        $this->setApplicationVersion('Addressbook', '0.10');
    }

    /**
     * give anyone GRANT_READ to internal addressbook
     *
     * @todo remove that
     */
    public function update_10()
    {
//        $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Model_Container::TYPE_INTERNAL);
//        Tinebase_Container::getInstance()->addGrants($internalAddressbook, Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE, '0', array(
//            Tinebase_Model_Grants::GRANT_READ
//        ), TRUE);
        
        $this->setApplicationVersion('Addressbook', '0.11');
    }
    
    /**
     * update to 2.0
     * @return void
     */
    public function update_11()
    {
        $this->setApplicationVersion('Addressbook', '2.0');
    }
}
