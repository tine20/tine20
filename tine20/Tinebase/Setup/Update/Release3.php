<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase updates for version 3.x
 *
 * @package     Tinebase
 * @subpackage  Setup
 */
class Tinebase_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * update to 3.1
     * - add value_search option field to customfield_config
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>value_search</name>
                <type>boolean</type>
            </field>');
        $this->_backend->addCol('customfield_config', $declaration);
        
        $this->setTableVersion('customfield_config', '4', TRUE);
        $this->setApplicationVersion('Tinebase', '3.1');
    }    

    /**
     * update to 3.2
     * - add personal_only field to preference
     * - remove all admin/default prefs with this setting
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>personal_only</name>
                <type>boolean</type>
            </field>');
        try {
            $this->_backend->addCol('preferences', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // field already exists
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
        }
        
        $this->setTableVersion('preferences', '5', TRUE);
        
        // remove all personal only prefs for anyone
        $this->_db->query("DELETE FROM " . SQL_TABLE_PREFIX . "preferences WHERE account_type LIKE 'anyone' AND name IN ('defaultCalendar', 'defaultAddressbook')");
        
        $this->setApplicationVersion('Tinebase', '3.2');
    }    
    
    /**
     * update to 3.3
     * - change key of import export definitions table
     */
    public function update_2()
    {
        // we need to drop the foreign key and the index first
        try {
            $this->_backend->dropForeignKey('importexport_definition', 'importexport_definitions::app_id--applications::id');
        } catch (Zend_Db_Statement_Exception $zdse) {
            try {
                // try it again with table prefix
                $this->_backend->dropForeignKey('importexport_definition', SQL_TABLE_PREFIX . 'importexport_definitions::app_id--applications::id');
            } catch (Zend_Db_Statement_Exception $zdse) {
                // already dropped
            }
        }
        $this->_backend->dropIndex('importexport_definition', 'application_id-name-type');
        
        // add index and foreign key again
        $this->_backend->addIndex('importexport_definition', new Setup_Backend_Schema_Index_Xml('<index>
                <name>model-name-type</name>
                <unique>true</unique>
                <field>
                    <name>model</name>
                </field>
                <field>
                    <name>name</name>
                </field>
                <field>
                    <name>type</name>
                </field>
            </index>')
        );
        $this->_backend->addForeignKey('importexport_definition', new Setup_Backend_Schema_Index_Xml('<index>
                <name>importexport_definitions::app_id--applications::id</name>
                <field>
                    <name>application_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>applications</table>
                    <field>id</field>
                </reference>
            </index>')
        );
        
        // increase versions
        $this->setTableVersion('importexport_definition', '3', TRUE);
        $this->setApplicationVersion('Tinebase', '3.3');
    }
    
    /**
     * update to 3.4
     * - add filename field to import/export definitions
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                    <name>filename</name>
                    <type>text</type>
                    <length>40</length>
                </field>');
        $this->_backend->addCol('importexport_definition', $declaration);
        
        $this->setTableVersion('importexport_definition', '4', TRUE);
        $this->setApplicationVersion('Tinebase', '3.4');
    }    

    /**
     * update to 3.5
     * - set filename field in export definitions (name + .xml)
     */
    public function update_4()
    {
        $this->_db->query("UPDATE " . SQL_TABLE_PREFIX . "importexport_definition SET filename=CONCAT(name,'.xml') WHERE type = 'export'");
        $this->setApplicationVersion('Tinebase', '3.5');
    }
    
    /**
     * update to 3.6
     * - container_acl -> int to string
     */
    public function update_5()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
         <field>
            <name>account_grant</name>
            <type>text</type>
            <length>40</length>
            <notnull>true</notnull>
        </field>');
        
        $this->_backend->alterCol('container_acl', $declaration);
        
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='readGrant' WHERE `account_grant` = '1'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='addGrant' WHERE `account_grant` = '2'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='editGrant' WHERE `account_grant` = '4'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='deleteGrant' WHERE `account_grant` = '8'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='adminGrant' WHERE `account_grant` = '16'");
        
        $this->setTableVersion('container_acl', '2', TRUE);
        $this->setApplicationVersion('Tinebase', '3.6');
    }
    
    /**
     * update to 3.7
     * - container_acl -> add EXPORT/SYNC grants
     */
    public function update_6()
    {
        // get timetracker app id
        try {
            $tt = Tinebase_Application::getInstance()->getApplicationByName('Timetracker');
            $select = $this->_db->select()
                ->from(array('container_acl' => SQL_TABLE_PREFIX . 'container_acl'), array('container_acl.container_id', 'container_acl.account_type', 'container_acl.account_id'))
                ->join(array('container' => SQL_TABLE_PREFIX . 'container'), 'container.id = container_acl.container_id', '')
                ->where('account_grant = ?', 'readGrant')
                ->where('application_id <> ?', $tt->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            $select = $this->_db->select()
                ->from(array('container_acl' => SQL_TABLE_PREFIX . 'container_acl'), array('container_acl.container_id', 'container_acl.id', 'container_acl.account_type', 'container_acl.account_id'))
                ->where('account_grant = ?', 'readGrant');
        }
        
        $result = $this->_db->fetchAll($select);
        foreach ($result as $row) {
            // insert new grants
            foreach (array(Tinebase_Model_Grants::GRANT_EXPORT, Tinebase_Model_Grants::GRANT_SYNC) as $grant) {
                $row['account_grant'] = $grant;
                $row['id'] = Tinebase_Record_Abstract::generateUID();
                $this->_db->insert(SQL_TABLE_PREFIX . 'container_acl', $row);
            }
        }
        
        $this->setApplicationVersion('Tinebase', '3.7');
    } 

    /**
     * update to 3.8
     * - schedulers
     */
    public function update_7()
    {
        $declaration = new Setup_Backend_Schema_Table_Xml('
         <table>
            <name>scheduler</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>integer</type>
                    <length>11</length>
                    <autoincrement>true</autoincrement>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>name</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>data</name>
                    <type>text</type>
                    <notnull>true</notnull>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            </declaration>
        </table>');
        
        try {
            $this->_backend->createTable($declaration);
            Tinebase_Application::getInstance()->addApplicationTable(
                Tinebase_Application::getInstance()->getApplicationByName('Tinebase'), 
                'scheduler', 
                1
            );
        } catch (Zend_Db_Statement_Exception $zdse) {
            // do nothing
        }
        
        $request = new Zend_Controller_Request_Simple();
        $request->setControllerName('Tinebase_Alarm');
        $request->setActionName('sendPendingAlarms');
        $request->setParam('eventName', 'Tinebase_Event_Async_Minutely');
        
        $task = new Tinebase_Scheduler_Task();
        $task->setMonths("Jan-Dec");
        $task->setWeekdays("Sun-Sat");
        $task->setDays("1-31");
        $task->setHours("0-23");
        $task->setMinutes("0/1");
        $task->setRequest($request);
        
        $scheduler = Tinebase_Core::getScheduler();
        $scheduler->addTask('Tinebase_Alarm', $task);
        $scheduler->saveTask();
        
        $this->setApplicationVersion('Tinebase', '3.8');
    }    
    
    /**
     * update to 3.9
     * - manage shared favorites
     */
    public function update_8()
    {
        $appsWithFavorites = array(
            'Addressbook',
            'Calendar',
            'Crm',
            'Tasks',
            'Timetracker',
        );
        
        try {
            $roles = Tinebase_Acl_Roles::getInstance();
            $adminRole = $roles->getRoleByName('admin role');
            
            foreach($appsWithFavorites as $appName) {
                try {
                    $app = Tinebase_Application::getInstance()->getApplicationByName($appName);
                    $roles->addSingleRight(
                        $adminRole->getId(), 
                        $app->getId(), 
                        Tinebase_Acl_Rights::MANAGE_SHARED_FAVORITES
                    );
                } catch (Exception $nfe) {
                    // app is not installed
                }
            }
        } catch (Exception $nfe) {
            Tinebase_Core::getLogger()->NOTICE(__METHOD__ . '::' . __LINE__ . " default admin role not found -> MANAGE_SHARED_FAVORITES right is not assigned");
        }
        
        $this->setApplicationVersion('Tinebase', '3.9');
    }
    
    /**
     * update to 3.10
     * - add missing indexes for notes table
     */
    public function update_9()
    {
        // add index and foreign key again
        $this->_backend->addIndex('notes', new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>record_id</name>
                <field>
                    <name>record_id</name>
                </field>
            </index>
        '));
         
        $this->_backend->addIndex('notes', new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>record_model</name>
                <field>
                    <name>record_model</name>
                </field>
            </index>
        '));
         
        $this->_backend->addIndex('notes', new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>record_backend</name>
                <field>
                    <name>record_backend</name>
                </field>
            </index>
        '));
        $this->setApplicationVersion('Tinebase', '3.10');
    }
    
    /**
     * update to 3.11
     * - change length of last_login_from
     */
    public function update_10()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_login_from</name>
                <type>text</type>
                <length>39</length>
            </field>');
        $this->_backend->alterCol('accounts', $declaration, 'last_login_from');
        $this->setApplicationVersion('Tinebase', '3.11');
    }
    
    /**
     * update to 3.12
     * - add department table
     */
    public function update_11()
    {
        $declaration = new Setup_Backend_Schema_Table_Xml('
            <table>
                <name>departments</name>
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
                        <length>128</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>description</name>
                        <type>text</type>
                        <length>254</length>
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
                        <length>40</length>
                        <field>
                            <name>name</name>
                        </field>
                    </index>
                </declaration>
            </table>'
        );
        $this->createTable('departments', $declaration);
                
        $this->setApplicationVersion('Tinebase', '3.12');
    }
    
    /**
     * update to 3.13
     * - add color to container table
     */
    public function update_12()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>color</name>
                <type>text</type>
                <length>7</length>
                <default>NULL</default>
            </field>');
        $this->_backend->addCol('container', $declaration, 3);
        
        $this->setTableVersion('container', '3', TRUE);
        $this->setApplicationVersion('Tinebase', '3.13');
    }

    /**
     * update to 3.14
     * - change type field in preferences table
     * - change type from normal -> user / default -> admin
     */
    public function update_13()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>type</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('preferences', $declaration);
        $this->setTableVersion('preferences', '6', TRUE);
        
        $this->_db->update(SQL_TABLE_PREFIX . 'preferences', array('type' => Tinebase_Model_Preference::TYPE_USER), array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('type') . ' = ?', 'normal')
        ));
        $this->_db->update(SQL_TABLE_PREFIX . 'preferences', array('type' => Tinebase_Model_Preference::TYPE_ADMIN), array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('type') . ' = ?', 'default')
        ));
        
        $this->setApplicationVersion('Tinebase', '3.14');
    }
    
/**
     * update to 3.15
     * - add client type to access log
     */
    public function update_14()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>clienttype</name>
                <type>text</type>
                <length>128</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->addCol('access_log', $declaration);
        
        $this->setTableVersion('access_log', '2', TRUE);
        $this->setApplicationVersion('Tinebase', '3.15');
    }
    
    /**
     * update to 3.16
     * - add customfield_acl table
     */
    public function update_15()
    {
        $declaration = new Setup_Backend_Schema_Table_Xml(
        '<table>
            <name>customfield_acl</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>customfield_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_type</name>
                    <type>enum</type>
                    <value>anyone</value>
                    <value>user</value>
                    <value>group</value>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_grant</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <index>
                    <name>customfield_id-account-type-account_id-account_grant</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                    <field>
                        <name>customfield_id</name>
                    </field>
                    <field>
                        <name>account_type</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                    <field>
                        <name>account_grant</name>
                    </field>
                </index>
                <index>
                    <name>id-account_type-account_id</name>
                    <field>
                        <name>customfield_id</name>
                    </field>
                    <field>
                        <name>account_type</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                </index>
                <index>
                    <name>customfield_acl::customfield_id--customfield_config::id</name>
                    <field>
                        <name>customfield_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>customfield_config</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                    </reference>
                </index>
            </declaration>
        </table>');
        $this->createTable('customfield_acl', $declaration);
        
        // add grants to existing customfields
        $configBackend = new Tinebase_CustomField_Config();
        $allCfConfigs = $configBackend->search();
        foreach ($allCfConfigs as $cfConfig) {
            Tinebase_CustomField::getInstance()->setGrants($cfConfig, Tinebase_Model_CustomField_Grant::getAllGrants());
        }
                
        $this->setApplicationVersion('Tinebase', '3.16');
    }

    /**
     * update to 3.17
     * - add new fields to store name(s) and email address in accounts table
     */
    public function update_16()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>email</name>
                <type>text</type>
                <length>64</length>
            </field>
        ');
        $this->_backend->addCol('accounts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>first_name</name>
                <type>text</type>
                <length>64</length>
            </field>
        ');
        $this->_backend->addCol('accounts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_name</name>
                <type>text</type>
                <length>64</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('accounts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>full_name</name>
                <type>text</type>
                <length>255</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('accounts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>display_name</name>
                <type>text</type>
                <length>255</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('accounts', $declaration);
        
        $select = $this->_db->select()
            ->from(
                array('addressbook' => SQL_TABLE_PREFIX . 'addressbook'), 
                array('addressbook.n_given', 'addressbook.n_family', 'addressbook.n_fileas', 'addressbook.n_fn', 'addressbook.email', 'addressbook.account_id')
            )
            ->where('account_id IS NOT NULL');
        
        $result = $this->_db->fetchAll($select);
        
        foreach ($result as $row) {
            // write contact data into accounts table
            $data = array(
                'first_name'   => $row['n_given'], 
                'last_name'    => $row['n_family'], 
                'full_name'    => $row['n_fn'], 
                'display_name' => $row['n_fileas'], 
                'email'        => $row['email']
            );
            $this->_db->update(SQL_TABLE_PREFIX . 'accounts', $data, $this->_db->quoteInto("id = ?", $row['account_id']));
        }
        
        $this->setTableVersion('accounts', '4', TRUE);
        $this->setApplicationVersion('Tinebase', '3.17');
    }
    
    /**
     * update to 3.18
     * - add contact_id column to accounts table and populate with defaults from addressbook table
     */
    public function update_17()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>contact_id</name>
                <type>text</type>
                <length>40</length>
            </field>
        ');
        $this->_backend->addCol('accounts', $declaration);
        
        $select = $this->_db->select()
            ->from(
                array('addressbook' => SQL_TABLE_PREFIX . 'addressbook'), 
                array('addressbook.id', 'addressbook.account_id')
            )
            ->where('account_id IS NOT NULL');
        
        $result = $this->_db->fetchAll($select);
        
        foreach ($result as $row) {
            // write contact data into accounts table
            $data = array(
                'contact_id'   => $row['id'] 
            );
            $this->_db->update(SQL_TABLE_PREFIX . 'accounts', $data, $this->_db->quoteInto("id = ?", $row['account_id']));
        }
        
        $this->setTableVersion('accounts', '5', TRUE);
        $this->setApplicationVersion('Tinebase', '3.18');
    }
    
    /**
     * update to 3.19
     * - add list_id, visibility and email column to groups table
     */
    public function update_18()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>visibility</name>
                <type>enum</type>
                <value>hidden</value>
                <value>displayed</value>
                <default>displayed</default>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('groups', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>email</name>
                <type>text</type>
                <length>64</length>
            </field>
        ');
        $this->_backend->addCol('groups', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>list_id</name>
                <type>text</type>
                <length>40</length>
            </field>
        ');
        $this->_backend->addCol('groups', $declaration);
        
        $this->setTableVersion('groups', '2', TRUE);
        $this->setApplicationVersion('Tinebase', '3.19');
    }
    
    /**
     * update to 3.20
     * - drop 'application_id-account_id-name' key
     */
    public function update_19()
    {
        // we need to drop the foreign key and the index first
        try {
            $this->_backend->dropForeignKey('filter', 'filter::application_id--applications::id');
        } catch (Zend_Db_Statement_Exception $zdse) {
            try {
                // try it again with table prefix
                $this->_backend->dropForeignKey('filter', SQL_TABLE_PREFIX . 'filter::application_id--applications::id');
            } catch (Zend_Db_Statement_Exception $zdse) {
                // already dropped
            }
        }
        try {
            $this->_backend->dropIndex('filter', 'application_id-account_id-name');
        } catch (Zend_Db_Statement_Exception $zdse) {
            // already dropped
        }
            
        // add foreign key again
        $this->_backend->addForeignKey('filter', new Setup_Backend_Schema_Index_Xml('<index>
                <name>filter::application_id--applications::id</name>
                <field>
                    <name>application_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>applications</table>
                    <field>id</field>
                </reference>
            </index>')
        );
        
        // increase versions
        $this->setTableVersion('filter', '2', TRUE);
        $this->setApplicationVersion('Tinebase', '3.20');
    }
    
    /**
     * update to 3.21
     * - set default value for login_failures to 0
     */
    public function update_20()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>login_failures</name>
                <type>integer</type>
                <default>0</default>
            </field>');
        $this->_backend->alterCol('accounts', $declaration);
        
        $this->_db->update(SQL_TABLE_PREFIX . 'accounts', array('login_failures' => 0));
                
        // increase versions
        $this->setTableVersion('accounts', 6, TRUE);
        $this->setApplicationVersion('Tinebase', '3.21');
    }
    
    /**
     * update to 3.22
     * - file store
     */
    public function update_21()
    {
        $declaration = new Setup_Backend_Schema_Table_Xml('
            <table>
                <name>tree_fileobjects</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>revision</name>
                        <type>integer</type>
                        <length>64</length>
                        <default>0</default>
                    </field>
                    <field>
                        <name>type</name>
                        <type>text</type>
                        <length>64</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>contenttype</name>
                        <type>text</type>
                        <length>128</length>
                    </field>
                    <field>
                        <name>created_by</name>
                        <type>text</type>
                        <length>40</length>
                    </field>
                    <field>
                        <name>description</name>
                        <type>text</type>
                        <length>255</length>
                        <notnull>false</notnull>
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
                        <name>type</name>
                        <field>
                            <name>type</name>
                        </field>
                    </index>
                    <index>
                        <name>is_deleted</name>
                        <field>
                            <name>is_deleted</name>
                        </field>
                    </index>
                </declaration>
            </table>');
        $this->_backend->createTable($declaration);
        $this->setTableVersion('tree_fileobjects', 1, true);
        
        $declaration = new Setup_Backend_Schema_Table_Xml('
            <table>
                <name>tree_filerevisions</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>revision</name>
                        <type>integer</type>
                        <length>64</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>hash</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>size</name>
                        <type>integer</type>
                        <length>64</length>
                        <notnull>true</notnull>
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
                    <index>
                        <name>id-revision</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                        <field>
                            <name>revision</name>
                        </field>
                    </index>
                    <index>
                        <name>revision</name>
                        <field>
                            <name>revision</name>
                        </field>
                    </index>
                    <index>
                        <name>tree_filerevisions::id--tree_fileobjects::id</name>
                        <field>
                            <name>id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>tree_fileobjects</table>
                            <field>id</field>
                            <onupdate>cascade</onupdate>
                            <ondelete>cascade</ondelete>
                        </reference>
                    </index>            
                    <index>
                        <name>tree_filerevisions::created_by--accounts::id</name>
                        <field>
                            <name>created_by</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>accounts</table>
                            <field>id</field>
                            <onupdate>CASCADE</onupdate>
                            <ondelete>SET NULL</ondelete>
                        </reference>
                    </index>            
                </declaration>
            </table>');
        $this->_backend->createTable($declaration);
        $this->setTableVersion('tree_filerevisions', 1, true);
        
        $declaration = new Setup_Backend_Schema_Table_Xml('
            <table>
                <name>tree_nodes</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>parent_id</name>
                        <type>text</type>
                        <length>40</length>
                    </field>
                    <field>
                        <name>object_id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>name</name>
                        <type>text</type>
                        <length>255</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>islink</name>
                        <type>boolean</type>
                    </field>
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                    <index>
                        <name>parent_id</name>
                        <field>
                            <name>parent_id</name>
                        </field>
                    </index>
                    <index>
                        <name>tree_nodes::object_id--tree_fileobjects::id</name>
                        <field>
                            <name>object_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>tree_fileobjects</table>
                            <field>id</field>
                            <onupdate>cascade</onupdate>
                        </reference>
                    </index>            
                    <index>
                        <name>tree_nodes::parent_id--tree_nodes::id</name>
                        <field>
                            <name>parent_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>tree_nodes</table>
                            <field>id</field>
                            <onupdate>cascade</onupdate>
                        </reference>
                    </index>            
                </declaration>
            </table>');
        $this->_backend->createTable($declaration);
        $this->setTableVersion('tree_nodes', 1, true);
        
        $this->setApplicationVersion('Tinebase', '3.22');
    }

    /**
     * update to 3.23
     * - add queue + cache cleanup tasks to scheduler
     */
    public function update_22()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Tinebase_Scheduler_Task::addQueueTask($scheduler);
        Tinebase_Scheduler_Task::addCacheCleanupTask($scheduler);
        $this->setApplicationVersion('Tinebase', '3.23');
    }    

    /**
     * update to 3.24
     * - add new index
     */
    public function update_23()
    {
        // add index and foreign key again
        $this->_backend->addIndex('tree_nodes', new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>parent_id-name</name>
                <unique>true</unique>
                <field>
                    <name>parent_id</name>
                </field>
                <field>
                    <name>name</name>
                </field>
            </index>')
        );
        
        $this->_backend->dropIndex('tree_nodes', 'parent_id');
        
        $this->setTableVersion('tree_nodes', 2, TRUE);
        
        $this->setApplicationVersion('Tinebase', '3.24');
    }
    
    /**
     * update to 3.25
     * - remove action queue task -> it's now a seperate cli method for a seperate cronjob
     */
    public function update_24()
    {
        $scheduler = Tinebase_Core::getScheduler();
        $scheduler->removeTask('Tinebase_ActionQueue');
        $scheduler->saveTask();
        
        $this->setApplicationVersion('Tinebase', '3.25');
    }
    
    /**
     * update to 3.26
     * - assign MANAGE_OWN_STATE right to all roles
     */
    public function update_25()
    {
        $roles = Tinebase_Acl_Roles::getInstance()->searchRoles(new Tinebase_Model_RoleFilter(array()), new Tinebase_Model_Pagination(array()));
        
        foreach($roles as $role) {
            Tinebase_Acl_Roles::getInstance()->addSingleRight(
                $role->getId(), 
                Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(), 
                Tinebase_Acl_Rights::MANAGE_OWN_STATE
            );
        }
        
        $this->setApplicationVersion('Tinebase', '3.26');
    }
    
    /**
     * update from 3.0 -> 4.0
     * @return void
     */
    public function update_26()
    {
        $this->setApplicationVersion('Tinebase', '4.0');
    }
    
    /**
     * update from 3.0 -> 4.0
     * 
     * Neele release received updates up to 3.28 after branching
     * 
     * @return void
     */
    public function update_28()
    {
        $this->setApplicationVersion('Tinebase', '4.0');
    }
}
