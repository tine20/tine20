<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 11.1
     *
     * change configuration column to xprops in accounts
     */
    public function update_0()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_42();
        $this->setApplicationVersion('Tinebase', '11.1');
    }

    /**
     * update to 11.2
     *
     * add deleted_time to unique index for groups, roles, tree_nodes
     */
    public function update_1()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_43();
        $this->setApplicationVersion('Tinebase', '11.2');
    }

    /**
     * update to 11.3
     *
     * add do_acl to record_observer
     */
    public function update_2()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_44();
        $this->setApplicationVersion('Tinebase', '11.3');
    }

    /**
     * update to 11.4
     *
     * fix pgsql index creation issue
     */
    public function update_3()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_11();
        $this->setApplicationVersion('Tinebase', '11.4');
    }

    /**
     * update to 11.5
     *
     * add acl table cleanup task
     */
    public function update_4()
    {
        if (version_compare($this->getApplicationVersion('Tinebase'), '11.12') === -1) {
            return;
        }
        $release9 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release9->update_46();
        $this->setApplicationVersion('Tinebase', '11.5');
    }

    /**
     * update to 11.6
     *
     * fix pgsql index creation issue (again! as it did not work in the previous CE release)
     */
    public function update_5()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_11();
        $this->setApplicationVersion('Tinebase', '11.6');
    }

    /**
     * update to 11.7
     *
     * add full text index to customfield
     */
    public function update_6()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_47();
        $this->setApplicationVersion('Tinebase', '11.7');
    }

    /**
     * update to 11.8
     *
     * add index(255) on customfield.value
     */
    public function update_7()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_48();
        $this->setApplicationVersion('Tinebase', '11.8');
    }

    /**
     * update to 11.9
     *
     * addFileSystemSanitizePreviewsTask
     */
    public function update_8()
    {
        if (version_compare($this->getApplicationVersion('Tinebase'), '11.12') === -1) {
            return;
        }
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_49();
        $this->setApplicationVersion('Tinebase', '11.9');
    }

    /**
     * update to 11.10
     *
     * reimport all template files
     */
    public function update_9()
    {
        // file updates! need to have application state change first
        $this->update_23();
        // and the structure updates
        $this->update_17();

        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_50();
        $this->setApplicationVersion('Tinebase', '11.10');
    }

    /**
     * update to 11.11
     *
     * remove timemachine_modlog_bkp if it exists
     */
    public function update_10()
    {
        $this->_backend->dropTable('timemachine_modlog_bkp');

        $this->setApplicationVersion('Tinebase', '11.11');
    }

    /**
     * update to 11.12
     *
     * remove scheduler table
     * remove async_job table
     * recreate scheduler tasks
     * @throws Setup_Exception_NotFound
     */
    public function update_11()
    {
        $this->_backend->dropTable('async_job', Tinebase_Core::getTinebaseId());
        $this->_backend->dropTable('scheduler', Tinebase_Core::getTinebaseId());
        $this->updateSchema('Tinebase', array(Tinebase_Model_SchedulerTask::class));

        $scheduler = Tinebase_Core::getScheduler();
        // TODO create methods for fetching and creating all known application tasks (maybe with existence check)
        Tinebase_Scheduler_Task::addAlarmTask($scheduler);
        Tinebase_Scheduler_Task::addCacheCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addCredentialCacheCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addTempFileCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addDeletedFileCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addSessionsCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addAccessLogCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addImportTask($scheduler);
        Tinebase_Scheduler_Task::addAccountSyncTask($scheduler);
        Tinebase_Scheduler_Task::addReplicationTask($scheduler);
        Tinebase_Scheduler_Task::addFileRevisionCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addFileSystemSizeRecalculation($scheduler);
        Tinebase_Scheduler_Task::addFileSystemCheckIndexTask($scheduler);
        Tinebase_Scheduler_Task::addFileSystemSanitizePreviewsTask($scheduler);
        Tinebase_Scheduler_Task::addFileSystemNotifyQuotaTask($scheduler);
        if (! $scheduler->hasTask('Tinebase_AclTablesCleanup')) {
            Tinebase_Scheduler_Task::addAclTableCleanupTask($scheduler);
        }

        if (Tinebase_Application::getInstance()->isInstalled('Calendar')) {
            Calendar_Scheduler_Task::addUpdateConstraintsExdatesTask($scheduler);
            Calendar_Scheduler_Task::addTentativeNotificationTask($scheduler);
        }

        if (Tinebase_Application::getInstance()->isInstalled('Sales')) {
            Sales_Scheduler_Task::addUpdateProductLifespanTask($scheduler);
        }

        $this->setApplicationVersion('Tinebase', '11.12');
    }

    /**
     * update to 11.13
     *
     * rerun update 4 + 8 as we don't want them to run before update_11
     */
    public function update_12()
    {
        $this->update_4();
        $this->update_8();

        $this->setApplicationVersion('Tinebase', '11.13');
    }

    /**
     * update to 11.14
     *
     * add anonymous group and user
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function update_13()
    {
        // make filesystem structure changes first, we may create a folder here?
        $this->update_17();

        $defaultAnonymousGroupName =
            Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_ANONYMOUS_GROUP_NAME_KEY)
                ? Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_ANONYMOUS_GROUP_NAME_KEY)
                : Tinebase_Group::DEFAULT_ANONYMOUS_GROUP;
        $anonymousGroup = new Tinebase_Model_Group(array(
            'name'          => $defaultAnonymousGroupName,
            'description'   => 'Group of anonymous user accounts',
            'visibility'    => Tinebase_Model_Group::VISIBILITY_HIDDEN
        ));
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($anonymousGroup, 'create');
        /** @var Tinebase_Model_Group $group */
        /** @noinspection PhpUndefinedMethodInspection */
        $group = Tinebase_Group::getInstance()->addGroupInSqlBackend($anonymousGroup);

        Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_ANONYMOUS, $group);

        $this->setApplicationVersion('Tinebase', '11.14');
    }

    /**
     * update to 11.15
     *
     * update relations of type SITE
     */
    public function update_14()
    {
        $filter = new Tinebase_Model_RelationFilter([
            ['field' => 'type', 'operator' => 'equals', 'value' => 'SITE'],
        ]);
        $relationController = Tinebase_Relations::getInstance();
        $relationBackend = new Tinebase_Relation_Backend_Sql();
        $childModels = [
            Addressbook_Model_List::class,
            Calendar_Model_Resource::class,
        ];

        /** @var Tinebase_Model_Relation $relation */
        foreach ($relationController->search($filter) as $relation) {
            if (in_array($relation->own_model, $childModels)) {
                if ($relation->related_degree === Tinebase_Model_Relation::DEGREE_PARENT) {
                    continue;
                }
                $relation->related_degree = Tinebase_Model_Relation::DEGREE_PARENT;
            } elseif (in_array($relation->related_model, $childModels)) {
                if ($relation->related_degree === Tinebase_Model_Relation::DEGREE_CHILD) {
                    continue;
                }
                $relation->related_degree = Tinebase_Model_Relation::DEGREE_CHILD;
            } elseif ($relation->related_degree !== Tinebase_Model_Relation::DEGREE_SIBLING) {
                $relation->related_degree = Tinebase_Model_Relation::DEGREE_SIBLING;
            } else {
                continue;
            }
            try {
                $relationBackend->updateRelation($relation);
            } catch (Tinebase_Exception_Record_NotDefined $e) {}
        }

        $this->setApplicationVersion('Tinebase', '11.15');
    }

    /**
     * update to 11.16
     *
     * make sure setup user has admin rights
     */
    public function update_15()
    {
        $update = new Tinebase_Setup_Update_Release9($this->_backend);
        $update->update_12();

        $this->setApplicationVersion('Tinebase', '11.16');
    }

    /**
     * update to 11.17
     *
     * update mariadb if required
     */
    public function update_16()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_53();
        $this->setApplicationVersion('Tinebase', '11.17');
    }

    /**
     * update to 11.18
     *
     * replace pin_protected with pin_protected_node in tree_nodes table
     */
    public function update_17()
    {
        if ($this->_backend->columnExists('pin_protected', 'tree_nodes')) {
            $this->_backend->dropCol('tree_nodes', 'pin_protected');
        }

        if (! $this->_backend->columnExists('pin_protected_node', 'tree_nodes')) {
            $this->_backend->addCol('tree_nodes', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>pin_protected_node</name>
                    <type>text</type>
                    <length>40</length>
                </field>'));
        }

        if ($this->getTableVersion('tree_nodes') == 8) {
            $this->setTableVersion('tree_nodes', 9);
        }

        Tinebase_FileSystem::getInstance()->resetBackends();
        Tinebase_Db_Table::clearTableDescriptionInCache(SQL_TABLE_PREFIX . 'tree_nodes');

        $this->setApplicationVersion('Tinebase', '11.18');
    }

    /**
     * update to 11.19
     *
     * add fileobject clean up task
     */
    public function update_18()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Tinebase_Scheduler_Task::addFileObjectsCleanupTask($scheduler);

        $this->setApplicationVersion('Tinebase', '11.19');
    }

    /**
     * update to 11.20
     *
     * move user pw policy to sub-struct
     */
    public function update_19()
    {
        // fetch current pw policy config and save it in new struct
        $configs = array(
            Tinebase_Config::PASSWORD_POLICY_ACTIVE              => 0,
            Tinebase_Config::PASSWORD_POLICY_ONLYASCII           => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_LENGTH          => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS      => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_UPPERCASE_CHARS => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_SPECIAL_CHARS   => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_NUMBERS         => 0,
            Tinebase_Config::PASSWORD_POLICY_CHANGE_AFTER        => 0,
        );

        foreach ($configs as $config => $default) {
            $value = Tinebase_Config::getInstance()->get($config);
            if ($value !== $default) {
                Tinebase_Config::getInstance()->get(Tinebase_Config::USER_PASSWORD_POLICY)->{$config} = $value;
                Tinebase_Config::getInstance()->delete($config);
            }
        }

        $this->setApplicationVersion('Tinebase', '11.20');
    }

    /**
     * update to 11.21
     *
     * fix pin_protected_node
     */
    public function update_20()
    {
        $quotedField = $this->_db->quoteIdentifier('pin_protected_node');
        $this->_db->update(SQL_TABLE_PREFIX . 'tree_nodes', ['pin_protected_node' => null],
            $quotedField . ' IS NOT NULL AND char_length(' . $quotedField . ') < 2');
        
        $this->setApplicationVersion('Tinebase', '11.21');
    }

    /**
     * update to 11.22
     *
     * add ntlmv2hash column
     */
    public function update_21()
    {
        if (! $this->_backend->columnExists('ntlmv2hash', 'accounts')) {
            $this->_backend->addCol('accounts', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>ntlmv2hash</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>'));
        }

        if ($this->getTableVersion('accounts') == 14) {
            $this->setTableVersion('accounts', 15);
        }

        $this->setApplicationVersion('Tinebase', '11.22');
    }

    /**
     * update to 11.23
     *
     * make file objects unique
     */
    public function update_22()
    {
        $update = new Tinebase_Setup_Update_Release9($this->_backend);
        $update->update_13();

        $this->setApplicationVersion('Tinebase', '11.23');
    }

    /**
     * update to 11.24
     *
     * create application_states table
     * fill it with data from applications.state
     * drop applications.state
     */
    public function update_23()
    {
        if (! $this->_backend->tableExists('application_states')) {
            $this->_backend->createTable(new Setup_Backend_Schema_Table_Xml('<table>
                <name>application_states</name>
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
                        <length>100</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>state</name>
                        <type>text</type>
                        <length>65535</length>
                        <notnull>true</notnull>
                    </field>
                    <index>
                        <name>id-name</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                        <field>
                            <name>name</name>
                        </field>
                    </index>
                </declaration>
            </table>'), 'Tinebase', 'application_states');

            $appController = Tinebase_Application::getInstance();
            /** @var Tinebase_Model_Application $application */
            foreach ($appController->getApplications() as $application) {
                foreach ($application->xprops('state') as $name => $value) {
                    $appController->setApplicationState($application, $name, $value);
                }
            }
        }

        if ($this->_backend->columnExists('state', 'applications')) {
            $this->_backend->dropCol('applications', 'state');
        }

        if ($this->getTableVersion('applications') == 4) {
            $this->setTableVersion('applications', 5);
        }

        Tinebase_Db_Table::clearTableDescriptionInCache('applications');
        Tinebase_Application::getInstance()->resetBackend();

        $this->setApplicationVersion('Tinebase', '11.24');
    }

    /**
     * update to 11.25
     *
     * make file objects unique
     */
    public function update_24()
    {
        $db = $this->getDb();
        if (!$db instanceof Zend_Db_Adapter_Pdo_Mysql) {
            $this->setApplicationVersion('Tinebase', '11.25');
            return;
        }

        if (($ilp = $db->query('SELECT @@innodb_large_prefix')->fetchColumn()) !== '1') {
            throw new Tinebase_Exception_Backend_Database('innodb_large_prefix seems not be turned on: ' . $ilp);
        }
        if (($iff = $db->query('SELECT @@innodb_file_format')->fetchColumn()) !== 'Barracuda') {
            throw new Tinebase_Exception_Backend_Database('innodb_file_format seems not to be Barracuda: ' . $iff);
        }
        if (($ift = $db->query('SELECT @@innodb_file_per_table')->fetchColumn()) !== '1') {
            throw new Tinebase_Exception_Backend_Database('innodb_file_per_table seems not to be turned on: ' . $ift);
        }

        try {
            $db->query('ALTER DATABASE ' . $db->quoteIdentifier($db->getConfig()['dbname']) .
                ' CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci');
        } catch (Zend_Db_Exception $zde) {
            Tinebase_Exception::log($zde);
        }

        $tables = $db->listTables();

        $db->query('SET foreign_key_checks = 0');
        $db->query('SET unique_checks = 0');
        foreach ($tables as $table) {
            try {
                $db->query('ALTER TABLE ' . $db->quoteIdentifier($table) . ' ROW_FORMAT = DYNAMIC');
                $db->query('ALTER TABLE ' . $db->quoteIdentifier($table) .
                    ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            } catch (Zend_Db_Exception $zde) {
                Tinebase_Exception::log($zde);
            }
        }

        $this->_backend->alterCol('tree_nodes', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>name</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                    <collation>utf8mb4_bin</collation>
                </field>'));
        $this->setTableVersion('tree_nodes', 9);

        $db->query('SET foreign_key_checks = 1');
        $db->query('SET unique_checks = 1');

        foreach ($tables as $table) {
            $db->query('REPAIR TABLE ' . $db->quoteIdentifier($table));
            $db->query('OPTIMIZE TABLE ' . $db->quoteIdentifier($table));
        }

        $this->setApplicationVersion('Tinebase', '11.25');
    }
}
