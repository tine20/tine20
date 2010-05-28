<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Tinebase_Setup_Update_Release1 extends Setup_Update_Abstract
{
    /**
     * update to 1.1
     *
     */    
    public function update_0()
    {
        $this->setApplicationVersion('Tinebase', '1.1');
    }

    /**
     * update to 1.2
     * - add window style
     */
    public function update_1()
    {
        // add window type preference
        $windowStylePref = new Tinebase_Model_Preference(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'name'              => Tinebase_Preference::WINDOW_TYPE,
            'value'             => 'Browser',
            'account_id'        => 0,
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'type'              => Tinebase_Model_Preference::TYPE_DEFAULT,
            'options'           => '<?xml version="1.0" encoding="UTF-8"?>
                <options>
                    <option>
                        <label>ExtJs style</label>
                        <value>Ext</value>
                    </option>
                    <option>
                        <label>Browser style</label>
                        <value>Browser</value>
                    </option>
                </options>'
        ));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($windowStylePref->toArray(), TRUE));
        
        Tinebase_Core::getPreference()->create($windowStylePref);
        
        $this->setApplicationVersion('Tinebase', '1.2');
    }

    /**
     * update to 1.3
     * - add alarm table
     */
    public function update_2()
    {
        $tableDefinition = '
        <table>
            <name>alarm</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>record_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>model</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>alarm_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>sent_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>sent_status</name>
                    <type>enum</type>
                    <value>pending</value>
                    <value>failure</value>
                    <value>success</value>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>sent_message</name>
                    <type>text</type>
                </field>
                <field>
                    <name>options</name>
                    <type>text</type>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>record_id-model</name>
                    <unique>true</unique>
                    <field>
                        <name>record_id</name>
                    </field>
                    <field>
                        <name>model</name>
                    </field>
                </index>
            </declaration>
        </table>';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);
        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase'), 
            'alarm', 
            1
        );
        
        $this->setApplicationVersion('Tinebase', '1.3');
    }

    /**
     * update to 1.4
     * - add async events table
     */
    public function update_3()
    {
        $tableDefinition = '
        <table>
            <name>async_job</name>
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
                </field>
                <field>
                    <name>start_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>end_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>status</name>
                    <type>enum</type>
                    <value>running</value>
                    <value>failure</value>
                    <value>success</value>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>message</name>
                    <type>text</type>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            </declaration>
        </table>';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);
        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase'), 
            'async_job', 
            1
        );
        
        $this->setApplicationVersion('Tinebase', '1.4');
    }
    
    /**
     * update to 1.5
     * - account ids are strings now 
     */
    public function update_4()
    {
        /*
         * drop old indexes
         */
        $this->_backend->dropForeignKey('accounts',      'accounts::primary_group_id--groups::id');
        $this->_backend->dropForeignKey('group_members', 'group_members::account_id--accounts::id');
        $this->_backend->dropForeignKey('group_members', 'group_members::group_id--groups::id');        
        /*
         * update column definition
         */
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('accounts', $declaration, 'id');
        $this->_backend->alterCol('groups', $declaration, 'id');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>primary_group_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('accounts', $declaration, 'primary_group_id');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>group_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('group_members', $declaration, 'group_id');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('group_members', $declaration, 'account_id');
        
        /*
         * readd foreign keys
         */        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>accounts::primary_group_id--groups::id</name>
                <field>
                    <name>primary_group_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>groups</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('accounts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>group_members::group_id--groups::id</name>
                <field>
                    <name>group_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>groups</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('group_members', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>group_members::account_id--accounts::id</name>
                <field>
                    <name>account_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>accounts</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('group_members', $declaration);
        
        $this->setApplicationVersion('Tinebase', '1.5');
    }
    
    /**
     * update to 1.6
     * - change all fields which store account id's to string type
     */
    public function update_5()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>name</name>
                <type>text</type>
                <length>100</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('preferences', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('roles', $declaration, 'created_by');
        $this->_backend->alterCol('record_persistentobserver', $declaration, 'created_by');
        $this->_backend->alterCol('tags', $declaration, 'created_by');
        $this->_backend->alterCol('notes', $declaration, 'created_by');
        $this->_backend->alterCol('filter', $declaration, 'created_by');
        $this->_backend->alterCol('importexport_definitions', $declaration, 'created_by');
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('relations', $declaration, 'created_by');

        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('roles', $declaration, 'last_modified_by');
        $this->_backend->alterCol('tags', $declaration, 'last_modified_by');
        $this->_backend->alterCol('notes', $declaration, 'last_modified_by');
        $this->_backend->alterCol('filter', $declaration, 'last_modified_by');
        $this->_backend->alterCol('importexport_definitions', $declaration, 'last_modified_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('record_persistentobserver', $declaration, 'last_modified_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('relations', $declaration, 'last_modified_by');
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('record_persistentobserver', $declaration, 'deleted_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('relations', $declaration, 'deleted_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('tags', $declaration, 'deleted_by');
        $this->_backend->alterCol('notes', $declaration, 'deleted_by');
        $this->_backend->alterCol('filter', $declaration, 'deleted_by');
        $this->_backend->alterCol('importexport_definitions', $declaration, 'deleted_by');

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>modification_account</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('timemachine_modlog', $declaration, 'modification_account');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>owner</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('tags', $declaration, 'owner');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('role_accounts', $declaration, 'account_id');
        $this->_backend->alterCol('access_log', $declaration, 'account_id');
        $this->_backend->alterCol('preferences', $declaration, 'account_id');
        $this->_backend->alterCol('filter', $declaration, 'account_id');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('container_acl', $declaration, 'account_id');
        $this->_backend->alterCol('tags_acl', $declaration, 'account_id');
        
        $this->setApplicationVersion('Tinebase', '1.6');
    }

    /**
     * update to 1.7
     * - add modlog info to container table
     */
    public function update_6()
    {
        $this->validateTableVersion('container', '1');        

        $newFields = array(
            '<field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>',
            '<field>
                <name>creation_time</name>
                <type>datetime</type>
            </field>',
            '<field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>',
            '<field>
                <name>last_modified_time</name>
                <type>datetime</type>
            </field>',
            '<field>
                <name>is_deleted</name>
                <type>boolean</type>
                <default>false</default>
            </field>',
            '<field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>',            
            '<field>
                <name>deleted_time</name>
                <type>datetime</type>
            </field>'
        );

        foreach ($newFields as $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->addCol('container', $declaration);
        }

        $this->setTableVersion('container', '2');
        $this->setApplicationVersion('Tinebase', '1.7');
    }
    
    /**
     * update to 1.8
     * - add state data table
     */
    public function update_7()
    {
        $tableDefinition = '
        <table>
            <name>state</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>user_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>data</name>
                    <type>text</type>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>user_id</name>
                    <unique>true</unique>
                    <field>
                        <name>user_id</name>
                    </field>
                </index>
            </declaration>
        </table>';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);
        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase'), 
            'state', 
            1
        );
        
        $this->setApplicationVersion('Tinebase', '1.8');
    }
    
    /**
     * update to 1.9
     * - set UUID type for existing ldap backends to uidnumber and gidnumber
     */
    public function update_8()
    {
        $configSettings = array(
            'groupUUIDAttribute' => 'gidnumber',              
            'userUUIDAttribute'  => 'uidnumber'
        );
        
        foreach ($configSettings as $name => $value) {
            Tinebase_Config::getInstance()->setConfigForApplication($name, $value);
        }
        $this->setApplicationVersion('Tinebase', '1.9');
    }
    
    /**
     * update to 1.10
     * - fix for signed / unsigned problem
     */
    public function update_9()
    {
        // fix for signed / unsigned problem
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>integer</type>
                <autoincrement>true</autoincrement>
            </field>
        ');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $this->setApplicationVersion('Tinebase', '1.10');
    }
    
    /**
     * update to 1.11
     * - import accounts from ldap to sql if needed
     */
    public function update_10()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>password</name>
                <type>text</type>
                <length>100</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('accounts', $declaration);
        
        $groupBackend = Tinebase_Group::getInstance();
        
        // check if ldap backend is enabled
        if($groupBackend instanceof Tinebase_Group_Ldap) {
            // empty user and group tables
            $this->_db->delete(SQL_TABLE_PREFIX . 'group_members');
            $this->_db->delete(SQL_TABLE_PREFIX . 'accounts');
            $this->_db->delete(SQL_TABLE_PREFIX . 'groups');
            $this->_db->delete(SQL_TABLE_PREFIX . 'addressbook', array('account_id IS NOT NULL'));
                        
            // import groups from ldap
            Tinebase_Group::getInstance()->importGroups();
            
            // import users from ldap
            Tinebase_User::getInstance()->importUsers();
            
            // import group memberships from ldap
            Tinebase_Group::getInstance()->importGroupMembers();            
        }
        $this->setApplicationVersion('Tinebase', '1.11');
    }
    
    /**
     * update to 1.12
     * - import accounts from ldap to sql again if needed
     */
    public function update_11()
    {
        $groupBackend = Tinebase_Group::getInstance();
        
        // check if ldap backend is enabled
        if($groupBackend instanceof Tinebase_Group_Ldap) {
            // empty suer and group tables
            #$this->_db->delete(SQL_TABLE_PREFIX . 'group_members');
            #$this->_db->delete(SQL_TABLE_PREFIX . 'accounts');
            #$this->_db->delete(SQL_TABLE_PREFIX . 'groups');
                        
            // import groups from ldap
            #Tinebase_Group::getInstance()->importGroups();
            
            // import users from ldap
            Tinebase_User::getInstance()->importUsers();
            
            // import group memberships from ldap
            #Tinebase_Group::getInstance()->importGroupMembers();            
        }
        
        $this->setApplicationVersion('Tinebase', '1.12');
    }
    
    /**
     * update to 1.13
     * - change the lenght of the name field to 100, because index gets to long on some MySQL versions
     */
    public function update_12()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>name</name>
                <type>text</type>
                <length>100</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('preferences', $declaration);
        
        $this->setApplicationVersion('Tinebase', '1.13');
    }

    /**
     * update tinebase to 2.0
     * - add two more fields (group, order) to custom fields table
     */
    public function update_13()
    {
        // $this->validateTableVersion('config_customfields', '1');        

        $newFields = array(
            '<field>
                <name>group</name>
                <type>text</type>
                <length>100</length>
            </field>',                
            '<field>
                <name>order</name>
                <type>integer</type>
            </field>'
        );
        
        foreach ($newFields as $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->addCol('config_customfields', $declaration);
        }

        $this->setTableVersion('config_customfields', '2');
                
        $this->setApplicationVersion('Tinebase', '2.0');
    }
}
