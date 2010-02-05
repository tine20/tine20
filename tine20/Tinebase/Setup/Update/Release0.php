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
        $declaration->value     = array(Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE, 'account', Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP);
        
        $this->_backend->addCol('application_rights', $declaration);
        
        
        $declaration = new Setup_Backend_Schema_Field();
        
        $declaration->name      = 'right';
        $declaration->type      = 'text';
        $declaration->length    = 64;
        $declaration->notnull   = 'true';
        
        $this->_backend->alterCol('application_rights', $declaration);
        
        $rightsTable = new Tinebase_Db_Table(array('name' =>  'application_rights'));
        
        
        $data = array(
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE
        );
        $where = array(
            $this->_db->quoteIdentifier('account_id') . ' IS NULL',
            $this->_db->quoteIdentifier('group_id') . ' IS NULL'
        );
        $rightsTable->update($data, $where);

        
        $data = array(
            'account_type' => 'account'
        );
        $where = array(
            $this->_db->quoteIdentifier('account_id') . ' IS NOT NULL',
            $this->_db->quoteIdentifier('group_id') . ' IS NULL'
        );
        $rightsTable->update($data, $where);
        
        
        $data = array(
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP
        );
        $where = array(
            $this->_db->quoteIdentifier('account_id') . ' IS NULL',
            $this->_db->quoteIdentifier('group_id') . ' IS NOT NULL'
        );
        $rightsTable->update($data, $where);
        
        
        $data = array(
            'account_id' => new Zend_Db_Expr('group_id'),
        );
        $where = array(
            $rightsTable->getAdapter()->quoteInto($this->_db->quoteIdentifier('account_type') . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP),
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
    public function update_2()
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
        
        // get admin and user groups
        $adminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        $userGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        
        # add roles and add the groups to the roles
        $adminRole = new Tinebase_Model_Role(array(
            'name'                  => 'admin role',
            'description'           => 'admin role for tine. this role has all rights per default.',
        ));
        $adminRole = Tinebase_Acl_Roles::getInstance()->createRole($adminRole);
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($adminRole->getId(), array(
            array(
                'id'    => $adminGroup->getId(),
                'type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, 
            )
        ));
        
        $userRole = new Tinebase_Model_Role(array(
            'name'                  => 'user role',
            'description'           => 'userrole for tine. this role has only the run rights for all applications per default.',
        ));
        $userRole = Tinebase_Acl_Roles::getInstance()->createRole($userRole);
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($userRole->getId(), array(
            array(
                'id'    => $userGroup->getId(),
                'type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, 
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
    public function update_3()
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
    public function update_4()
    {        
        try {
            $this->validateTableVersion('timemachine_modificationlog', '1');
            
            $this->renameTable('timemachine_modificationlog', 'timemachine_modlog');
    
            $this->increaseTableVersion('timemachine_modlog');
        } catch (Exception $e) {
            echo "renaming table 'timemachine_modificationlog' failed\n";
        }
        
        $this->setApplicationVersion('Tinebase', '0.5');
    }
    
    /**
     * drop table application_rights and update to version 0.6 of tinebase
     */    
    public function update_5()
    {
        //echo "drop table application_rights";
        
        $this->dropTable('application_rights');
        
        $this->setApplicationVersion('Tinebase', '0.6');
    }
    
    /**
     * add config table and update to version 0.7 of tinebase
     */    
    public function update_6()
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
        Tinebase_User::setBackendConfiguration(Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY, 'Users');
        Tinebase_User::setBackendConfiguration(Tinebase_User::DEFAULT_ADMIN_GROUP_NAME_KEY, 'Administrators');
        Tinebase_User::saveBackendConfiguration();
        
        $this->setApplicationVersion('Tinebase', '0.7');
    }    
    
    /**
     * add config table and update to version 0.8 of tinebase
     */    
    public function update_7()
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
    public function update_8()
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
    public function update_9()
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
                    echo "set relation: " . $relation->type . " " . $relation->related_model ."<br/>\n";
                    $relationsBackend->addRelation($relation);
                } catch (Exception $e) {
                    // cweiss 2008-08-25 this duplicates come from an earlier upgrading failure don't confuse user with verbosity ;-) 
                    // echo 'do not add duplicate relation ' . $relation->own_id . '-' . $relation->related_id . "...<br/>\n";
                }
            }
        }
        echo "done<br/>\n";
        $this->setApplicationVersion('Tinebase', '0.10');        
    }

    /**
     * update to 0.11
     * - add notes table
     * 
     */
    public function update_10()
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
        
        $noteType = new Tinebase_Model_NoteType(array(
            'id'            => '1',
            'name'          => 'note',
            'description'   => 'the default note type',
            'icon'          => 'images/oxygen/16x16/actions/note.png',
            'is_user_type'  => true
        ));
        Tinebase_Notes::getInstance()->addNoteType($noteType);

        $noteType = new Tinebase_Model_NoteType(array(
            'id'            => '2',
            'name'          => 'telephone',
            'description'   => 'telephone call',
            'icon'          => 'images/oxygen/16x16/apps/kcall.png',
            'is_user_type'  => true
        ));
        Tinebase_Notes::getInstance()->addNoteType($noteType);
        
        $noteType = new Tinebase_Model_NoteType(array(
            'id'            => '3',
            'name'          => 'email',
            'description'   => 'email contact',
            'icon'          => 'images/oxygen/16x16/actions/kontact-mail.png',
            'is_user_type'  => true
        ));
        Tinebase_Notes::getInstance()->addNoteType($noteType);

        $noteType = new Tinebase_Model_NoteType(array(
            'id'            => '4',
            'name'          => 'created',
            'description'   => 'record created',
            'icon'          => 'images/oxygen/16x16/actions/knewstuff.png',
            'is_user_type'  => false
        ));
        Tinebase_Notes::getInstance()->addNoteType($noteType);

        $noteType = new Tinebase_Model_NoteType(array(
            'id'            => '5',
            'name'          => 'changed',
            'description'   => 'record changed',
            'icon'          => 'images/oxygen/16x16/actions/document-properties.png',
            'is_user_type'  => false
        ));
        Tinebase_Notes::getInstance()->addNoteType($noteType);
        
        $this->setApplicationVersion('Tinebase', '0.11');
    }

    /**
     * update to 0.12
     * - delete links table
     */
    public function update_11()
    {
        $this->_backend->dropTable('links');
        
        $this->setApplicationVersion('Tinebase', '0.12');
    }

    /**
     * update to 0.13
     * - rename Dialer app to 'Phone'
     */
    public function update_12()
    {
        // rename app in application table
        $appTable = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX.'applications'));
        $appTable->update(
            array( 'name' => 'Phone' ),
            "name = 'Dialer'"
        );
        
        $this->setApplicationVersion('Tinebase', '0.13');
    }

    /**
     * update to 0.14
     * - add config user table 
     * - create Locale/Timezone default settings
     */
    public function update_13()
    {
        $tableDefinition = ('
        <table>
            <name>config_user</name>
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
                    <type>integer</type>
                    <unsigned>true</unsigned>
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
                    <name>application_id-name-user_id</name>
                    <unique>true</unique>
                    <field>
                        <name>application_id</name>
                    </field>
                    <field>
                        <name>name</name>
                    </field>
                    <field>
                        <name>user_id</name>
                    </field>
                </index>
                <index>
                    <name>config_user::application_id--applications::id</name>
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
                    <name>config_user::user_id--accounts::id</name>
                    <field>
                        <name>user_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>accounts</table>
                        <field>id</field>
                    </reference>
                </index>
            </declaration>
        </table>');
    
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        
        
        $this->setApplicationVersion('Tinebase', '0.14');
    }

    /**
     * update to 0.15
     * - remove constraint from config user table
     */
    public function update_14()
    {
        $this->_backend->dropForeignKey('config_user', 'config_user::user_id--accounts::id');
        
        $this->setApplicationVersion('Tinebase', '0.15');
    }

    /**
     * update to 0.16
     * - add icon class to note types
     */
    public function update_15()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>icon_class</name>
                <type>text</type>
                <length>128</length>
                <notnull>true</notnull>
            </field>
        ');
        
        $this->_backend->addCol('note_types', $declaration);

        // delete all note types and add new ones with icon class
        $noteTypes = Tinebase_Notes::getInstance()->getNoteTypes();
        $toUpdate = array(
            1 => 'notes_noteIcon',
            2 => 'notes_telephoneIcon',
            3 => 'notes_emailIcon',
            4 => 'notes_createdIcon',
            5 => 'notes_changedIcon',
        );
        foreach ($noteTypes as $type) {
            $type->icon_class = $toUpdate[$type->getId()];
            Tinebase_Notes::getInstance()->updateNoteType($type);
        }
        
        $this->setApplicationVersion('Tinebase', '0.16');
    }
    
    /**
     * update to 0.17
     * - add config customfields table 
     */
    public function update_16()
    {
        $tableDefinition = ('
        <table>
            <name>config_customfields</name>
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
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>label</name>
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
                    <name>type</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>length</name>
                    <type>integer</type>
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
                    <name>config_customfields::application_id--applications::id</name>
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
        
        $this->setApplicationVersion('Tinebase', '0.17');
    }
    
    /**
     * update to 0.18
     * - change columtype of applications.id and application_table.applications_id to text/40
     */
    public function update_17()
    {
        // tables with application_id as foreign key
        $appIdTables = array(
            'application_tables' => 'application_tables::application_id--applications::id',
            'container' => 'container_application_id',
            'role_rights' => 'role_rights::application_id--applications::id',
            'config' => 'config::application_id--applications::id',
            'config_user' => 'config_user::application_id--applications::id',
            'config_customfields' => 'config_customfields::application_id--applications::id',
            'timemachine_modlog' => 'timemachine_modlog::application_id--applications::id'
        );
        
        foreach($appIdTables as $table => $key) {
            try {
                $this->_backend->dropForeignKey($table, $key);
            } catch (Zend_Db_Statement_Exception $ze) {
                // skip error
            }
        }
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('applications', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>application_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>
        ');
        
        foreach ($appIdTables as $table => $fk) {
            $this->_backend->alterCol($table, $declaration);
        }

        $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>application_tables::application_id--applications::id</name>
                    <field>
                        <name>application_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>applications</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                        </reference>
                </index>
        ');
        $this->_backend->addForeignKey('application_tables', $declaration);

        $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>container::application_id--applications::id</name>
                    <field>
                        <name>application_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>applications</table>
                        <field>id</field>
                    </reference>
                </index>
        ');
        $this->_backend->addForeignKey('container', $declaration);

        $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>timemachine_modlog::application_id--applications::id</name>
                    <field>
                        <name>application_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>applications</table>
                        <field>id</field>
                    </reference>
                </index>
        ');
        $this->_backend->addForeignKey('timemachine_modlog', $declaration);

        $declaration = new Setup_Backend_Schema_Index_Xml('
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
        ');
        $this->_backend->addForeignKey('role_rights', $declaration);

        $declaration = new Setup_Backend_Schema_Index_Xml('
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
        ');
        $this->_backend->addForeignKey('config', $declaration);

        $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>config_user::application_id--applications::id</name>
                    <field>
                        <name>application_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>applications</table>
                        <field>id</field>
                    </reference>
                </index>
        ');
        $this->_backend->addForeignKey('config_user', $declaration);

        $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>config_customfields::application_id--applications::id</name>
                    <field>
                        <name>application_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>applications</table>
                        <field>id</field>
                    </reference>
                </index>
        ');
        $this->_backend->addForeignKey('config_customfields', $declaration);
        
        // remove container acl, when deleting container
        // and give foreign key a proper name
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>container_acl::container_id--container::id</name>
                <field>
                    <name>container_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>container</table>
                    <field>id</field>
                    <ondelete>cascade</ondelete>
                </reference>
            </index>
        ');
        try {
            $this->_backend->dropForeignKey('container_acl', 'container_id');
        } catch (Zend_Db_Statement_Exception $ze) {
            // skip error
        }
        $this->_backend->addForeignKey('container_acl', $declaration);
        
        $this->setApplicationVersion('Tinebase', '0.18');            
    }

    /**
     * update to 0.19
     * - add 'shared contracts' container for erp
     */
    public function update_18()
    {
        try {
            $application = Tinebase_Application::getInstance()->getApplicationByName('Timetracker');
        } catch (Tinebase_Exception_NotFound $enf) {
            // timetracker not installed
            $this->setApplicationVersion('Tinebase', '0.19');
            return TRUE;
        }

        try {
            $sharedContracts = Tinebase_Container::getInstance()->getContainerByName('Sales', 'Shared Contracts', Tinebase_Model_Container::TYPE_SHARED);
        } catch (Tinebase_Exception_NotFound $enf) {            
            // create it if it doesn't exists
            $newContainer = new Tinebase_Model_Container(array(
                'name'              => 'Shared Contracts',
                'type'              => Tinebase_Model_Container::TYPE_SHARED,
                'backend'           => 'Sql',
                'application_id'    => $application->getId() 
            ));        
            $sharedContracts = Tinebase_Container::getInstance()->addContainer($newContainer);
        }            

        $adminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        $userGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        
        Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $userGroup, array(
            Tinebase_Model_Grants::READGRANT,
            Tinebase_Model_Grants::EDITGRANT
        ), TRUE);
        Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $adminGroup, array(
            Tinebase_Model_Grants::ADDGRANT,
            Tinebase_Model_Grants::READGRANT,
            Tinebase_Model_Grants::EDITGRANT,
            Tinebase_Model_Grants::ADMINGRANT
        ), TRUE);
        
        $this->setApplicationVersion('Tinebase', '0.19');
    }
    
    /**
     * update to 0.20
     * - set proper default values for several database fields
     *
     */
    public function update_19()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>remark</name>
                <type>text</type>
                <length>256</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('relations', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>integer</type>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('relations', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>creation_time</name>
                <type>datetime</type>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('relations', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>integer</type>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('relations', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_time</name>
                <type>datetime</type>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('relations', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>integer</type>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('relations', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_time</name>
                <type>datetime</type>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('relations', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>icon_class</name>
                <type>text</type>
                <length>128</length>
                <default>NULL</default>
            </field>
        ');
        $this->_backend->alterCol('note_types', $declaration);
        
        $this->setApplicationVersion('Tinebase', '0.20');
    }
    
    /**
     * update to 0.21
     * - alter result col in access log table
     */
    public function update_20()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>result</name>
                <type>integer</type>
                <notnull>true</notnull>
                <unsigned>false</unsigned>
            </field>
        ');
        $this->_backend->alterCol('access_log', $declaration);
        
        $this->setApplicationVersion('Tinebase', '0.21');
    }

    /**
     * update to 0.22
     * - add persistent filters table 
     */
    public function update_21()
    {
        $tableDefinition = ('
        <table>
            <name>filter</name>
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
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_id</name>
                    <type>integer</type>
                    <unsigned>true</unsigned>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>model</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>                
                <field>
                    <name>filters</name>
                    <type>text</type>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>name</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>is_default</name>
                    <type>boolean</type>
                    <default>false</default>
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
                    <name>application_id-account_id-name</name>
                    <unique>true</unique>
                    <field>
                        <name>application_id</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                    <field>
                        <name>name</name>
                    </field>
                </index>
                <index>
                    <name>filter::application_id--applications::id</name>
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
        
        $this->setApplicationVersion('Tinebase', '0.22');
    }

    /**
     * update to 0.23
     * - add importexport definitions table 
     */
    public function update_22()
    {
        $tableDefinition =    
        '<table>
            <name>importexport_definitions</name>
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
                    <name>name</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>type</name>
                    <type>enum</type>
                    <value>import</value>
                    <value>export</value>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>plugin</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>plugin_options</name>
                    <type>text</type>
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
                    <name>application_id-name-type</name>
                    <unique>true</unique>
                    <field>
                        <name>application_id</name>
                    </field>
                    <field>
                        <name>name</name>
                    </field>
                    <field>
                        <name>type</name>
                    </field>
                </index>
                <index>
                    <name>importexport_definitions::app_id--applications::id</name>
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
        </table>';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        
        
        $this->setApplicationVersion('Tinebase', '0.23');
    }
    
    /**
     * update to 0.24
     * - add new fields to user table: login_shell + home_dir
     */
    public function update_23()
    {
        $field = '<field>
                    <name>login_shell</name>
                    <type>text</type>
                    <length>100</length>
                    <notnull>false</notnull>
                </field>';
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('accounts', $declaration);
        
        $field = '<field>
                    <name>home_dir</name>
                    <type>text</type>
                    <length>256</length>
                    <notnull>false</notnull>
                </field>';
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('accounts', $declaration);
        
        $this->setApplicationVersion('Tinebase', '0.24');
    }

    /**
     * update to 0.25
     * - rename config_user table to preferences
     * - add/alter some fields
     */
    public function update_24()
    {
        $this->_backend->renameTable('config_user', 'preferences');

        // rename user_id to account id
        $field = '<field>
                    <name>account_id</name>
                    <type>integer</type>
                    <unsigned>true</unsigned>
                    <notnull>false</notnull>
                </field>';
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('preferences', $declaration, 'user_id');
        
        // add new fields
        $field = '<field>
                    <name>account_type</name>
                    <type>enum</type>
                    <value>user</value>
                    <value>anyone</value>
                    <value>group</value>
                    <notnull>true</notnull>
                </field>';
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('preferences', $declaration);

        $field = '<field>
                    <name>type</name>
                    <type>enum</type>
                    <value>normal</value>
                    <value>default</value>
                    <value>forced</value>
                    <notnull>true</notnull>
                </field>';
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('preferences', $declaration);
        
        // delete old unique key constraint and foreign key
        $this->_backend->dropForeignKey('preferences', 'config_user::application_id--applications::id');
        $this->_backend->dropIndex('preferences', 'application_id-name-user_id');
        
        // add unique key constraint(app_id-name-account_id-account_type)
        $index = '<index>
                    <name>app_id-name-account_id-account_type</name>
                    <unique>true</unique>
                    <field>
                        <name>application_id</name>
                    </field>
                    <field>
                        <name>name</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                    <field>
                        <name>account_type</name>
                    </field>
                </index>';
        $declaration = new Setup_Backend_Schema_Index_Xml($index);
        $this->_backend->addIndex('preferences', $declaration);
        
        // add foreign key
        $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>preferences::application_id--applications::id</name>
                    <field>
                        <name>application_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>applications</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                    </reference>
                </index>
        ');
        $this->_backend->addForeignKey('preferences', $declaration);
        
        // delete old user config settings
        $oldSettings = array('Locale', 'Timezone');
        foreach ($oldSettings as $oldSetting) {
            try {
                $oldConfig = Tinebase_Config::getInstance()->getConfig($oldSetting);
                Tinebase_Config::getInstance()->deleteConfig($oldConfig); 
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }
        
        $this->setApplicationVersion('Tinebase', '0.25');
    }

    /**
     * update to 0.26
     * - add new field to preferences table: options
     */
    public function update_25()
    {
        $field = '<field>
                    <name>options</name>
                    <type>text</type>
                </field>';
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('preferences', $declaration);
        
        // get default prefs and add options
        $filter = new Tinebase_Model_PreferenceFilter(array(
            array(
                'field' => 'account', 
                'operator' => 'equals', 
                'value' => array(
                    'accountId'     => 0,
                    'accountType'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE
                ),
            ),
            array(
                'field' => 'type', 
                'operator' => 'equals', 
                'value' => Tinebase_Model_Preference::TYPE_DEFAULT
            )
        ));        
        
        $backend = Tinebase_Core::getPreference();
        $prefs = $backend->search($filter, NULL);
        
        foreach($prefs as $pref) {
            if(in_array($pref->name, array(Tinebase_Preference::LOCALE, Tinebase_Preference::TIMEZONE))) {
                $pref->options = '<?xml version="1.0" encoding="UTF-8"?>
                <options>
                    <special>' . $pref->name . '</special>
                </options>';
                $backend->update($pref);
            }
        }
        
        $this->setApplicationVersion('Tinebase', '0.26');
    }
    
    /**
     * update to 0.27
     * - add table credential_cache
     */
    public function update_26()
    {
        $tableDefinition = '
            <table>
                <name>credential_cache</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>cache</name>
                        <type>clob</type>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>creation_time</name>
                        <type>datetime</type>
                    </field>
                    <field>
                        <name>valid_until</name>
                        <type>datetime</type>
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
        ';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        
        
        $this->setApplicationVersion('Tinebase', '0.27');
    }
    
    /**
     * update to 0.28
     * - repair db charset for users with non default utf8 client charset
     */
    public function update_27()
    {
        $config = Setup_Core::getConfig();
        $tableprefix = $config->database->tableprefix;
        
        // have a second db connection with default charset
        $orgDb = Zend_Db::factory('Pdo_Mysql', $config->database->toArray());
        
        // fix for signed / unsigned problem
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>integer</type>
                <autoincrement>true</autoincrement>
            </field>
        ');
        $this->_backend->alterCol('addressbook', $declaration);
        
        /** addressbook: store image in separate table **/
        $tableDefinition = '
            <table>
                <name>addressbook_image</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>contact_id</name>
                        <type>integer</type>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>image</name>
                        <type>blob</type>
                    </field>
                    <index>
                        <name>contact_id</name>
                        <primary>true</primary>
                        <field>
                            <name>contact_id</name>
                        </field>
                    </index>
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
                        </reference>
                    </index>
                </declaration>
            </table>
        ';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);
        
        $select = $orgDb->select()
            ->from("{$tableprefix}addressbook", array('id'))
            ->where($orgDb->quoteIdentifier('jpegphoto') . " IS NOT NULL");
        $contactIds = $orgDb->fetchAll($select);
        
        foreach ($contactIds as $contactId) {
            $contactId = $contactId['id'];
            $select = $orgDb->select()
                ->from("{$tableprefix}addressbook", array('id', 'jpegphoto'))
                ->where($orgDb->quoteInto($orgDb->quoteIdentifier('id') .' = ?', $contactId));
            
            $imageData = $orgDb->fetchRow($select);
            $orgDb->insert("{$tableprefix}addressbook_image", array(
                'contact_id' => $imageData['id'],
                'image'      => base64_encode($imageData['jpegphoto'])
            ));
        }
        $this->_backend->dropCol('addressbook', 'jpegphoto');
        
        /** convert serialized object into json objects **/
        $select = $orgDb->select()
            ->from("{$tableprefix}filter", array('id', 'filters'));
        $filters = $orgDb->fetchAll($select);
        
        foreach ($filters as $filter) {
            $filterObject = unserialize($filter['filters']);
            $orgDb->update(
                "{$tableprefix}filter", 
                array('filters' => Zend_Json::encode($filterObject)), 
                $orgDb->quoteInto($orgDb->quoteIdentifier('id') .' = ?', $filter['id'])
            );
        }
        
        /** convert db contenets for installations which had a clientcharset != utf8 **/
        $originalCharset = array_value('Value', array_value(0, $orgDb->query("SHOW VARIABLES LIKE 'character_set_client'")->fetchAll()));
        if (strtolower($originalCharset) != 'utf8') {
            $this->_db->query("SET FOREIGN_KEY_CHECKS=0");
            $orgDb->query("SET FOREIGN_KEY_CHECKS=0");
            
            // build the list of tables to convert
            $tables = array();
            $rawTables = $this->_db->query("SHOW TABLES")->fetchAll();
            foreach ($rawTables as $rawTable) {
                $tableName = array_values($rawTable);
                $tableName = $tableName[0];
                
                if (preg_match("/^$tableprefix/", $tableName) && $tableName != "{$tableprefix}addressbook_image") {
                    $tables[] = $tableName;
                }
            }
            
            // the actual charset conversion is done by the db.
            foreach ($tables as $tableName) {
                
                Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Converting table ' . $tableName);
                
                //$this->_db->query("SET character_set_client = '$originalCharset'");
                
                $select = $orgDb->select()->from($tableName);
                $result = $orgDb->fetchAll($select);
                
                $orgDb->query("TRUNCATE TABLE " . $this->_db->quoteIdentifier($tableName));
                //$this->_db->query("SET character_set_client = 'utf8'");
                
                foreach ($result as $row) {
                    try {
                        $this->_db->insert($tableName, $row);
                    } catch (Zend_Db_Statement_Exception $zdse) {
                        Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
                        
                        // try to convert strings if failure
                        if (preg_match('/(description|title|note|old_value|org_name|adr_one_street)/', $zdse->getMessage(), $match)) {
                            $field = $match[1];
                            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                                . ' Converting field ' . $field 
                                . ((array_key_exists('id', $row)) ? ' of record ' . $row['id'] : '') 
                                //. ' value: ' . $row[$field]
                            );
                            $row[$field] = utf8_encode($row[$field]);
                            $this->_db->insert($tableName, $row);
                        } else {
                            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not convert field');
                            throw $zdse;
                        }
                    }
                }
            
                
            }
            $this->_db->query("SET FOREIGN_KEY_CHECKS=1");
        }
        $orgDb->closeConnection();
        $this->setApplicationVersion('Tinebase', '0.28');
    }

    /**
     * update to 1.0
     * - tag name length increased to 40 chars
     * - last update script in this class -> add new funcs to Release1.php
     */    
    public function update_28()
    {
        $this->validateTableVersion('tags', '1');        
        
        // fix for signed / unsigned problem
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>integer</type>
                <autoincrement>true</autoincrement>
            </field>
        ');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
        <field>
            <name>name</name>
            <type>text</type>
            <length>40</length>
            <default>NULL</default>
        </field>
        ');
        $this->_backend->alterCol('tags', $declaration);
        
        $this->setTableVersion('tags', '2');
        $this->setApplicationVersion('Tinebase', '1.0');
    }
}
