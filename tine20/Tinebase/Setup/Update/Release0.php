<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Tinebase_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update function 0
     * has no real functionality, only shows how its done
     *
     */    
    public function update_0()
    {
        // just show how it works
        $tableDefinition = 
                '<table>
                    <name>test_table</name>
                    <version>1</version>
                    <declaration>
                        <field>
                            <name>id</name>
                            <type>integer</type>
                            <autoincrement>true</autoincrement>
                        </field>
                        <field>
                            <name>name</name>
                            <type>text</type>
                            <length>128</length>
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
                </table>'
				;
        $table = Setup_Backend_Schema_Index_Factory::factory('String', $tableDefinition); 
		$this->_backend->createTable($table);
        $this->_backend->dropTable('test_table');
    }

    /**
     * update function 1
     * adds application rights
     *
     */    
    public function update_1()
    {
        $this->validateTableVersion('application_rights', '1');
        
        $declaration = new Setup_Backend_Schema_Field();
        
        $declaration->name      = 'account_type';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'true';
        $declaration->value     = array('anyone', 'account', 'group');
        
        $this->_backend->addCol('application_rights', $declaration);
        
        
        $declaration = new Setup_Backend_Schema_Field();
        
        $declaration->name      = 'right';
        $declaration->type      = 'text';
        $declaration->length    = 64;
        $declaration->notnull   = 'true';
        
        $this->_backend->alterCol('application_rights', $declaration);
        
        $rightsTable = new Tinebase_Db_Table(array('name' =>  'application_rights'));
        
        
        $data = array(
            'account_type' => 'anyone'
        );
        $where = array(
            'account_id IS NULL',
            'group_id IS NULL'
        );
        $rightsTable->update($data, $where);

        
        $data = array(
            'account_type' => 'account'
        );
        $where = array(
            'account_id IS NOT NULL',
            'group_id IS NULL'
        );
        $rightsTable->update($data, $where);
        
        
        $data = array(
            'account_type' => 'group'
        );
        $where = array(
            'account_id IS NULL',
            'group_id IS NOT NULL'
        );
        $rightsTable->update($data, $where);
        
        
        $data = array(
            'account_id' => new Zend_Db_Expr('group_id'),
        );
        $where = array(
            $rightsTable->getAdapter()->quoteInto('account_type = ?', 'group'),
        );
        $rightsTable->update($data, $where);

        $this->_backend->dropIndex('application_rights', 'account_id-group_id-application_id-right');
        
        $index = new StdClass();
        $index->name = 'account_id-account_type-application_id-right';
        $index->unique = 'true';
        $index->field = array();
        
        $field = new StdClass();
        $field->name = 'account_id';
        $index->field[] = $field;
        
        $field = new StdClass();
        $field->name = 'account_type';
        $index->field[] = $field;
        
        $field = new StdClass();
        $field->name = 'application_id';
        $index->field[] = $field;
        
        $field = new StdClass();
        $field->name = 'right';
        $index->field[] = $field;
        
        $this->_backend->addIndex( 'application_rights', $index);
        
        $this->_backend->dropCol('application_rights', 'group_id');
        
        $this->setTableVersion('application_rights', '2');
        $this->setApplicationVersion('Tinebase', '0.2');
    }
    
    /**
     * update function 2
     * adds roles (tables and user/admin role)
     *
     */    
    function update_2()
    {
        /************ create roles tables **************/
        
        $tableDefinitions = array( 
            '<table>
            <name>roles</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>integer</type>
                    <autoincrement>true</autoincrement>
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
                    <name>created_by</name>
                    <type>integer</type>
                </field>
                <field>
                    <name>creation_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>last_modified_by</name>
                    <type>integer</type>
                </field>
                <field>
                    <name>last_modified_time</name>
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
            ',
            '<table>
            <name>role_rights</name>
            <version>1</version>
            <declaration>
               <field>
                    <name>id</name>
                    <type>integer</type>
                    <autoincrement>true</autoincrement>
                </field>
                <field>
                    <name>role_id</name>
                    <type>integer</type>
                    <unsigned>true</unsigned>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>application_id</name>
                    <type>integer</type>
                    <length>11</length>
                    <unsigned>true</unsigned>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>right</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>

               <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>role_id</name>
                    <field>
                        <name>role_id</name>
                    </field>
                </index>
                <index>
                    <name>application_id</name>
                    <field>
                        <name>application_id</name>
                    </field>
                </index>
                <index>
                    <name>role_rights::application_id--applications::id</name>
                    <field>
                        <name>application_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>applications</table>
                        <field>id</field>
                    </reference>
                </index>
                <index>
                    <name>role_rights::role_id--roles::id</name>
                    <field>
                        <name>role_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>roles</table>
                        <field>id</field>
                    </reference>
                </index>


            </declaration>
        </table>',        
            '<table>
            <name>role_accounts</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>integer</type>
                    <autoincrement>true</autoincrement>
                </field>
                <field>
                    <name>role_id</name>
                    <type>integer</type>
                    <unsigned>true</unsigned>
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
                    <type>integer</type>
                    <unsigned>true</unsigned>
                    <notnull>false</notnull>
                </field>
                
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>account_id-account_type-role_id</name>
                    <unique>true</unique>
                    <field>
                        <name>role_id</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                    <field>
                        <name>account_type</name>
                    </field>
                </index>
                <index>
                    <name>role_accounts::role_id--roles::id</name>
                    <field>
                        <name>role_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>roles</table>
                        <field>id</field>
                    </reference>
                </index>
            </declaration>
        </table>'     
        );

        foreach ( $tableDefinitions as $tableDefinition ) {                    
            $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
            $this->_backend->createTable($table);        
        }
        
        /************ create roles ***************/
        
        try {
            $tinebaseConfig = Tinebase_Config::getInstance()->getConfigForApplication('Tinebase');
        } catch ( Exception $e ) {
            // set default values
            $tinebaseConfig = array(
                'Default Admin Group'   => 'Administrators',
                'Default User Group'    => 'Users',
            );
        }
        
        // get admin and user groups
        $adminGroup = Tinebase_Group::getInstance()->getGroupByName($tinebaseConfig['Default Admin Group']);
        $userGroup = Tinebase_Group::getInstance()->getGroupByName($tinebaseConfig['Default User Group']);
        
        # add roles and add the groups to the roles
        $adminRole = new Tinebase_Model_Role(array(
            'name'                  => 'admin role',
            'description'           => 'admin role for tine. this role has all rights per default.',
        ));
        $adminRole = Tinebase_Acl_Roles::getInstance()->createRole($adminRole);
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($adminRole->getId(), array(
            array(
                'account_id'    => $adminGroup->getId(),
                'account_type'  => 'group', 
            )
        ));
        
        $userRole = new Tinebase_Model_Role(array(
            'name'                  => 'user role',
            'description'           => 'userrole for tine. this role has only the run rights for all applications per default.',
        ));
        $userRole = Tinebase_Acl_Roles::getInstance()->createRole($userRole);
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($userRole->getId(), array(
            array(
                'account_id'    => $userGroup->getId(),
                'account_type'  => 'group', 
            )
        ));
        
        # enable the applications for the user group/role
        # give all rights to the admin group/role for all applications
        $applications = Tinebase_Application::getInstance()->getApplications();
        foreach ($applications as $application) {
            
            if ( $application->name  !== 'Admin' ) {

                /***** All applications except Admin *****/
                
                // run right for user role
                Tinebase_Acl_Roles::getInstance()->addSingleRight(
                    $userRole->getId(), 
                    $application->getId(), 
                    Tinebase_Acl_Rights::RUN
                );
                
                // all rights for admin role
                $allRights = Tinebase_Application::getInstance()->getAllRights($application->getId());
                foreach ( $allRights as $right ) {
                    Tinebase_Acl_Roles::getInstance()->addSingleRight(
                        $adminRole->getId(), 
                        $application->getId(), 
                        $right
                    );
                }                                
            } else {

                /***** Admin application *****/

                // all rights for admin role
                $allRights = Tinebase_Application::getInstance()->getAllRights($application->getId());
                foreach ( $allRights as $right ) {
                    Tinebase_Acl_Roles::getInstance()->addSingleRight(
                        $adminRole->getId(), 
                        $application->getId(), 
                        $right
                    );
                }                                                
            }
        } // end foreach applications               

        $this->setApplicationVersion('Tinebase', '0.3');
        
    }
    
    /**
     * add temp_files table and update to version 0.4 of tinebase
     */    
    function update_3()
    {
        $tableDefinition = ('
            <table>
                <name>temp_files</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>session_id</name>
                        <type>text</type>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>time</name>
                        <type>datetime</type>
                    </field>
                    <field>
                        <name>path</name>
                        <type>text</type>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>name</name>
                        <type>text</type>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>type</name>
                        <type>text</type>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>error</name>
                        <type>integer</type>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>size</name>
                        <type>integer</type>
                        <unsigned>true</unsigned>
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
            </table>
        ');

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        
        
        $this->setApplicationVersion('Tinebase', '0.4');
    }
    
    /**
     * rename timemachine_modificationlog to timemachine_modlog
     */    
    function update_4()
    {        
        try {
            $this->validateTableVersion('timemachine_modificationlog', '1');
            
            $this->renameTable('timemachine_modificationlog', 'timemachine_modlog');
    
            $this->increaseTableVersion('timemachine_modlog');
        } catch ( Exception $e ) {
            echo "renaming table 'timemachine_modificationlog' failed\n";
        }
        
        $this->setApplicationVersion('Tinebase', '0.5');
    }
    
    /**
     * drop table application_rights and update to version 0.6 of tinebase
     */    
    function update_5()
    {
        //echo "drop table application_rights";
        
        $this->dropTable('application_rights');
        
        $this->setApplicationVersion('Tinebase', '0.6');
    }
    
    /**
     * add config table and update to version 0.7 of tinebase
     */    
    function update_6()
    {
        $tableDefinition = ('
            <table>
                <name>config</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>application_id</name>
                        <type>integer</type>
                        <unsigned>true</unsigned>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>name</name>
                        <type>text</type>
                        <length>255</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>value</name>
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
                    <index>
                        <name>application_id-name</name>
                        <unique>true</unique>
                        <field>
                            <name>application_id</name>
                        </field>
                        <field>
                            <name>name</name>
                        </field>
                    </index>
                    <index>
                        <name>config::application_id--applications::id</name>
                        <field>
                            <name>application_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>applications</table>
                            <field>id</field>
                        </reference>
                    </index>
                </declaration>
            </table>
        ');

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        
        
        // add config settings for admin and user groups
        $configBackend = Tinebase_Config::getInstance();
        $configUserGroupName = new Tinebase_Model_Config(array(
            "application_id"    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            "name"              => "Default User Group",
            "value"             => "Users",              
        ));
        $configBackend->setConfig($configUserGroupName);
        $configAdminGroupName = new Tinebase_Model_Config(array(
            "application_id"    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            "name"              => "Default Admin Group",
            "value"             => "Administrators",              
        ));
        $configBackend->setConfig($configAdminGroupName);
        
        $this->setApplicationVersion('Tinebase', '0.7');
    }    
    
    /**
     * add config table and update to version 0.8 of tinebase
     */    
    function update_7()
    {
        $tableDefinition = ('
        <table>
                <name>registration_invitations</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>email</name>
                        <type>text</type>
                        <length>128</length>
                        <notnull>true</notnull>
                    </field>
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                    <index>
                        <name>email</name>
                        <unique>true</unique>
                        <field>
                            <name>email</name>
                        </field>
                    </index>
                </declaration>
            </table>
        ');

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        

        $this->setApplicationVersion('Tinebase', '0.8');
    }
    
    /**
     * switch ids to alnum. As the table was not used, we don't need to update
     * its contents
     */
    function update_8()
    {
        //$this->validateTableVersion('record_relations', '1');
        //$this->_backend->dropTable('record_relations');
        
        $tableDefinition = ('
        <table>
            <name>relations</name>
            <version>3</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>own_model</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>own_backend</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>own_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>own_degree</name>
                    <type>enum</type>
                    <value>parent</value>
                    <value>child</value>
                    <value>sibling</value>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>related_model</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>related_backend</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>related_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>type</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>remark</name>
                    <type>text</type>
                    <length>256</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>created_by</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>creation_time</name>
                    <type>datetime</type>
                    <notnull>true</notnull>
                </field> 
                <field>
                    <name>last_modified_by</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>last_modified_time</name>
                    <type>datetime</type>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <notnull>true</notnull>
                    <default>false</default>
                </field>
                <field>
                    <name>deleted_by</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                </field>            
                <field>
                    <name>deleted_time</name>
                    <type>datetime</type>
                </field>
                <index>
                    <name>id-own_model-own_backend-own_id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                    <field>
                        <name>own_model</name>
                    </field>
                    <field>
                        <name>own_backend</name>
                    </field>
                    <field>
                        <name>own_id</name>
                    </field>
                </index>
                <index>
                    <name>unique-fields</name>
                    <unique>true</unique>
                    <field>
                        <name>own_model</name>
                    </field>
                    <field>
                        <name>own_backend</name>
                    </field>
                    <field>
                        <name>own_id</name>
                    </field>
                    <field>
                        <name>related_model</name>
                    </field>
                    <field>
                        <name>related_backend</name>
                    </field>
                    <field>
                        <name>related_id</name>
                    </field>
                </index>
            </declaration>
        </table>
        ');
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        

        $this->setApplicationVersion('Tinebase', '0.9');
    }

    /**
     * update to 0.10
     * - migrate old links to relations
     * 
     */
    function update_9()
    {
        /************* migrate old links to relations *************/
        
        // get all links
        $linksTable = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX.'links'));
        echo "fetching links ... <br/>\n";
        $links = $linksTable->fetchAll();
        
        // create relations from links
        $relationsByLeadId = array();
        foreach ($links as $link) {

            if ($link->link_app1 === 'crm') {
                
                switch (strtolower($link->link_app2)) {
                    case 'tasks':
                        $relatedModel = 'Tasks_Model_Task';
                        $backend = Tasks_Backend_Factory::SQL;
                        $degree = Tinebase_Model_Relation::DEGREE_SIBLING;
                        $type = 'TASK';
                        break;
                    case 'addressbook':
                        $relatedModel = 'Addressbook_Model_Contact';
                        $backend = Addressbook_Backend_Factory::SQL;
                        switch ($link->link_remark) {
                            case 'account':
                                $type = 'RESPONSIBLE';
                                $degree = Tinebase_Model_Relation::DEGREE_CHILD;
                                break;
                            case 'customer':
                                $type = 'CUSTOMER';
                                $degree = Tinebase_Model_Relation::DEGREE_SIBLING;
                                break;
                            case 'partner':
                                $type = 'PARTNER';
                                $degree = Tinebase_Model_Relation::DEGREE_SIBLING;
                                break;
                        }
                        break;
                        
                    default:
                        echo 'link type (' . $link->link_app2 . ") not supported<br/>\n";
                }                
                
                if (isset($relatedModel) && isset($backend)) {
                    $relationsByLeadId[$link->link_id1][] = new Tinebase_Model_Relation(array(
                        'own_model'              => 'Crm_Model_Lead',
                        'own_backend'            => 'SQL',
                        'own_id'                 => $link->link_id1,
                        'own_degree'             => $degree,
                        'related_model'          => $relatedModel,
                        'related_backend'        => $backend,
                        'related_id'             => $link->link_id2,
                        'type'                   => $type,
                        'created_by'             => $link->link_owner,
                        'creation_time'          => Zend_Date::now()
                    ));                        
                }
            }
        }
        
        echo "creating relations ...<br/>\n";
        
        $relationsBackend = new Tinebase_Relation_Backend_Sql();
        foreach ($relationsByLeadId as $leadId => $relations) {
            foreach ($relations as $relation) {
                try {
                    $relationsBackend->addRelation($relation);
                } catch (Exception $e) {
                    // cweiss 2008-08-25 this duplicates come from an earlier upgrading failure don't confuse user with verbosity ;-) 
                    // echo 'do not add duplicate relation ' . $relation->own_id . '-' . $relation->related_id . "...<br/>\n";
                }
            }
        }
        $this->setApplicationVersion('Tinebase', '0.10');        
    }

    /**
     * update to 0.11
     * - add notes table
     * 
     */
    function update_10()
    {
        $tableDefinition = ('
        <table>
            <name>note_types</name>
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
                    <length>64</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <length>256</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>icon</name>
                    <type>text</type>
                    <length>256</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>is_user_type</name>
                    <type>boolean</type>
                    <notnull>true</notnull>
                    <default>true</default>
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
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);    

        $tableDefinition = ('
        <table>
            <name>notes</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>note_type_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>note</name>
                    <type>text</type>
                    <default>NULL</default>
                </field>
                <field>
                    <name>record_model</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>record_backend</name>
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
                    <name>created_by</name>
                    <type>integer</type>
                </field>
                <field>
                    <name>creation_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>last_modified_by</name>
                    <type>integer</type>
                </field>
                <field>
                    <name>last_modified_time</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <notnull>true</notnull>
                    <default>false</default>
                </field>
                <field>
                    <name>deleted_by</name>
                    <type>integer</type>
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
                    <name>notes::note_type_id--note_types::id</name>
                    <field>
                        <name>note_type_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>note_types</table>
                        <field>id</field>
                    </reference>
                </index>
            </declaration>
        </table>
        ');
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        
        
        $nodeType = new Tinebase_Model_NoteType(array(
            'id'            => '1',
            'name'          => 'note',
            'description'   => 'the default note type',
            'icon'          => 'images/oxygen/16x16/actions/note.png',
            'is_user_type'  => true
        ));
        Tinebase_Notes::getInstance()->addNoteType($nodeType);

        $nodeType = new Tinebase_Model_NoteType(array(
            'id'            => '2',
            'name'          => 'telephone',
            'description'   => 'telephone call',
            'icon'          => 'images/oxygen/16x16/apps/kcall.png',
            'is_user_type'  => true
        ));
        Tinebase_Notes::getInstance()->addNoteType($nodeType);
        
        $nodeType = new Tinebase_Model_NoteType(array(
            'id'            => '3',
            'name'          => 'email',
            'description'   => 'email contact',
            'icon'          => 'images/oxygen/16x16/actions/kontact-mail.png',
            'is_user_type'  => true
        ));
        Tinebase_Notes::getInstance()->addNoteType($nodeType);

        $nodeType = new Tinebase_Model_NoteType(array(
            'id'            => '4',
            'name'          => 'created',
            'description'   => 'record created',
            'icon'          => 'images/oxygen/16x16/actions/knewstuff.png',
            'is_user_type'  => false
        ));
        Tinebase_Notes::getInstance()->addNoteType($nodeType);

        $nodeType = new Tinebase_Model_NoteType(array(
            'id'            => '5',
            'name'          => 'changed',
            'description'   => 'record changed',
            'icon'          => 'images/oxygen/16x16/actions/document-properties.png',
            'is_user_type'  => false
        ));
        Tinebase_Notes::getInstance()->addNoteType($nodeType);
        
        $this->setApplicationVersion('Tinebase', '0.11');
    }

    /**
     * update to 0.12
     * - delete links table
     */
    function update_11()
    {
        $this->_backend->dropTable('links');
        
        $this->setApplicationVersion('Tinebase', '0.12');
    }

    /**
     * update to 0.13
     * - rename Dialer app to 'Phone'
     */
    function update_12()
    {
        // rename app in application table
        $appTable = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX.'applications'));
        $appTable->update(
            array( 'name' => 'Phone' ),
            "name = 'Dialer'"
        );
        
        $this->setApplicationVersion('Tinebase', '0.13');
    }
}
