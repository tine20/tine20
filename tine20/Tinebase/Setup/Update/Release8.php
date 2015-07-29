<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     * 
     * @see 0009152: saving of record fails because of too many relations
     */
    public function update_0()
    {
        $valueFields = array('old_value', 'new_value');
        foreach ($valueFields as $field) {
            
            // check schema, only change if type == text
            $typeMapping = $this->_backend->getTypeMapping('text');
            $schema = Tinebase_Db_Table::getTableDescriptionFromCache(SQL_TABLE_PREFIX . 'timemachine_modlog', $this->_backend->getDb());
            
            if ($schema[$field]['DATA_TYPE'] === $typeMapping['defaultType']) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Old column type (' . $schema[$field]['DATA_TYPE'] . ') is going to be altered to clob');
                
                $declaration = new Setup_Backend_Schema_Field_Xml('
                    <field>
                        <name>' . $field . '</name>
                        <type>clob</type>
                    </field>
                ');
            
                $this->_backend->alterCol('timemachine_modlog', $declaration);
            }
        }
        $this->setTableVersion('timemachine_modlog', '3');
        $this->setApplicationVersion('Tinebase', '8.1');
    }

    /**
     * update to 8.2
     * 
     * @see 0009644: remove user registration
     */
    public function update_1()
    {
        if ($this->_backend->tableExists('registrations')) {
            $this->dropTable('registrations');
        }
        
        if ($this->_backend->tableExists('registration_invitation')) {
            $this->dropTable('registration_invitation');
        }
        
        $this->setApplicationVersion('Tinebase', '8.2');
    }
    
    /**
     * update to 8.3
     * - update 256 char fields
     * 
     * @see 0008070: check index lengths
     */
    public function update_2()
    {
        $columns = array("container" => array(
                            "name" => 'true'
                            ),
                        "note_types" => array(
                            "icon" => 'true',
                            "description" => 'null'
                            ),
                        "tags" => array(
                            "name" => 'null',
                            "description" => 'null'
                            ),
                        "accounts" => array(
                            "home_dir" => 'false'
                            )
                        );
        $this->truncateTextColumn($columns, 255);
        $this->setTableVersion('container', '9');
        $this->setTableVersion('note_types', '3');
        $this->setTableVersion('tags', '7');
        $this->setTableVersion('accounts', '10');
        $this->setApplicationVersion('Tinebase', '8.3');
    }
    
    /**
     * - add filter acl
     * - update current filter (add default grants: user for personal favorites, Admin group for shared favorites)
     * 
     * @see 0009610: shared favorites acl
     */
    public function update_3()
    {
        $this->_addFilterAclTable();
        $this->_addGrantsToExistingFilters();
        $this->setApplicationVersion('Tinebase', '8.4');
    }
    
    /**
     * add filter acl table
     */
    protected function _addFilterAclTable()
    {
        $xml = $declaration = new Setup_Backend_Schema_Table_Xml('<table>
            <name>filter_acl</name>
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
                    <name>account_type</name>
                    <type>text</type>
                    <length>32</length>
                    <default>user</default>
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
                    <name>record_id-account-type-account_id-account_grant</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                    <field>
                        <name>record_id</name>
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
                        <name>record_id</name>
                    </field>
                    <field>
                        <name>account_type</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                </index>
                <index>
                    <name>filter_acl::record_id--filter::id</name>
                    <field>
                        <name>record_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>filter</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                    </reference>
                </index>
            </declaration>
        </table>');
        
        $this->createTable('filter_acl', $declaration);
    }
    
    /**
     * add default grants to existing filters
     */
    protected function _addGrantsToExistingFilters()
    {
        $pfBackend = new Tinebase_PersistentFilter_Backend_Sql();
        $filters = $pfBackend->getAll();
        $pfGrantsBackend = new Tinebase_Backend_Sql_Grants(array(
            'modelName' => 'Tinebase_Model_PersistentFilterGrant',
            'tableName' => 'filter_acl'
        ));
        $pfGrantsBackend->getGrantsForRecords($filters);
    
        foreach ($filters as $filter) {
            if (count($filter->grants) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Filter ' . $filter->name . ' already has grants.');
                continue;
            }
            $grant = new Tinebase_Model_PersistentFilterGrant(array(
                'account_type' => $filter->isPersonal() ? Tinebase_Acl_Rights::ACCOUNT_TYPE_USER : Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                'account_id'   => $filter->account_id,
                'record_id'    => $filter->getId(),
            ));
    
            $grant->sanitizeAccountIdAndFillWithAllGrants();
    
            $filter->grants = new Tinebase_Record_RecordSet('Tinebase_Model_PersistentFilterGrant');
            $filter->grants->addRecord($grant);
    
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Updating filter "' . $filter->name . '" with grant: ' . print_r($grant->toArray(), true));
    
            Tinebase_PersistentFilter::getInstance()->setGrants($filter);
        }
    }
    
    /**
     * adds a label property to hold a humanreadable text
     */
    protected function _addImportExportDefinitionLabel()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('<field>
            <name>label</name>
            <type>text</type>
            <length>128</length>
            <notnull>false</notnull>
        </field>');
        
        $this->_backend->addCol('importexport_definition', $declaration);
        
        $this->setTableVersion('importexport_definition', '8');
    }
    
    /**
     * updates import export definitions
     * adds a label property to hold a humanreadable text if not exists
     */
    public function update_4()
    {

        if (! $this->_backend->columnExists('label', 'importexport_definition')) {
            $this->_addImportExportDefinitionLabel();
        }
        
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'));
        
        $this->setApplicationVersion('Tinebase', '8.5');
    }
    
    /**
     * adds import table
     */
    public function update_5()
    {
        $tableDefinition = '
            <table>
                <name>import</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>80</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>timestamp</name>
                        <type>datetime</type>
                    </field>
                    <field>
                        <name>user_id</name>
                        <type>text</type>
                        <length>80</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>model</name>
                        <type>text</type>
                        <length>80</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>application_id</name>
                        <type>text</type>
                        <length>80</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>synctoken</name>
                        <type>text</type>
                        <length>80</length>
                    </field>
                    <field>
                        <name>container_id</name>
                        <length>80</length>
                        <type>text</type>
                    </field>
                    <field>
                        <name>sourcetype</name>
                        <type>text</type>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>interval</name>
                        <type>text</type>
                    </field>
                    <field>
                        <name>source</name>
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
                        <notnull>true</notnull>
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
                        <name>import::application_id--applications::id</name>
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
                        <name>import::user_id--accounts::id</name>
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
            </table>';


        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition);
        $this->_backend->createTable($table);
        
        $scheduler = Tinebase_Core::getScheduler();
        Tinebase_Scheduler_Task::addImportTask($scheduler);
        
        $this->setApplicationVersion('Tinebase', '8.6');
    }

    /**
     * - add filter acl (check if table already exists)
     * 
     * @see 0009610: shared favorites acl
     */
    public function update_6()
    {
        if (! $this->_backend->tableExists('filter_acl')) {
            $this->_addFilterAclTable();
            $this->_addGrantsToExistingFilters();
        }
        $this->setApplicationVersion('Tinebase', '8.7');
    }
    
    /**
     * adds and updates access_log columns
     */
    public function update_7()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('<field>
            <name>id</name>
            <type>text</type>
            <length>40</length>
            <notnull>true</notnull>
        </field>');
        
        $this->_backend->alterCol('access_log', $declaration);
        
        if (! $this->_backend->columnExists('user_agent', 'access_log')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                <name>user_agent</name>
                <type>text</type>
                <length>255</length>
            </field>');
            $this->_backend->addCol('access_log', $declaration);
        }
        
        $declaration = new Setup_Backend_Schema_Index_Xml('<index>
            <name>account_id-ip</name>
            <field>
                <name>account_id</name>
            </field>
            <field>
                <name>ip</name>
            </field>
        </index>');
        
        $this->_backend->addIndex('access_log', $declaration);
        
        $this->setTableVersion('access_log', 5);
        
        $this->setApplicationVersion('Tinebase', '8.8');
    }
    
    /**
     * update 8 -> adds index for id column of table container_content
     */
    public function update_8()
    {
        $tableVersion = $this->getTableVersion('container_content');
        
        if ($tableVersion < 2) {
            $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>id</name>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            ');
            
            $this->_backend->addIndex('container_content', $declaration);
            
            $this->setTableVersion('container_content', '2');
        }
        
        $this->setApplicationVersion('Tinebase', '8.9');
    }

    /**
     * update 9 -> adds modlog to users and groups
     */
    public function update_9()
    {
        $this->_addModlogFields('accounts');
        $this->setTableVersion('accounts', '11');
        $this->_addModlogFields('groups');
        $this->setTableVersion('groups', '5');
        $this->setApplicationVersion('Tinebase', '8.10');
    }
    
    /**
     * update 10 -> adds "use personal tags" role right to all installed apps
     * 
     * @see 0010732: add "use personal tags" right to all applications
     */
    public function update_10()
    {
        try {
            $userRole = Tinebase_Acl_Roles::getInstance()->getRoleByName('user role');
            
            $enabledApplications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
            foreach ($enabledApplications as $application) {
                $allRights = Tinebase_Application::getInstance()->getAllRights($application->getId());
                if (in_array(Tinebase_Acl_Rights::USE_PERSONAL_TAGS, $allRights)) {
                    Tinebase_Acl_Roles::getInstance()->addSingleRight($userRole->getId(), $application->getId(), Tinebase_Acl_Rights::USE_PERSONAL_TAGS);
                }
            }
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing
        }
        
        $this->setApplicationVersion('Tinebase', '8.11');
    }

    /**
     * update 11
     *
     * @see 0011178: allow to lock preferences for individual users
     */
    public function update_11()
    {
        if ($this->getTableVersion('preferences') != 8) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                        <name>locked</name>
                        <type>boolean</type>
                    </field>');
            $this->_backend->addCol('preferences', $declaration);
            $this->setTableVersion('preferences', '8');
        }
        $this->setApplicationVersion('Tinebase', '8.12');
    }

    /**
     * update to 9.0
     *
     * @return void
     */
    public function update_12()
    {
        $this->setApplicationVersion('Tinebase', '9.0');
    }
}
