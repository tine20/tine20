<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

echo __FILE__ . ' must not be used or autoloaded or required etc.' . PHP_EOL;
exit(1);


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
        $scheduler->removeTask('Tinebase_User/Group::syncUsers/Groups');
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
        if (!$scheduler->hasTask('Tinebase_AclTablesCleanup')) {
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
            'name' => $defaultAnonymousGroupName,
            'description' => 'Group of anonymous user accounts',
            'visibility' => Tinebase_Model_Group::VISIBILITY_HIDDEN
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
            } catch (Tinebase_Exception_Record_NotDefined $e) {
            }
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

        if (!$this->_backend->columnExists('pin_protected_node', 'tree_nodes')) {
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
            Tinebase_Config::PASSWORD_POLICY_ACTIVE => 0,
            Tinebase_Config::PASSWORD_POLICY_ONLYASCII => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_LENGTH => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_UPPERCASE_CHARS => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_SPECIAL_CHARS => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_NUMBERS => 0,
            Tinebase_Config::PASSWORD_POLICY_CHANGE_AFTER => 0,
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
        if (!$this->_backend->columnExists('ntlmv2hash', 'accounts')) {
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
        if (!$this->_backend->tableExists('application_states')) {
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

            $tmpApp = new Tinebase_Model_Application([], true);
            if ($tmpApp->has('state')) {
                $appController = Tinebase_Application::getInstance();
                /** @var Tinebase_Model_Application $application */
                foreach ($appController->getApplications() as $application) {
                    foreach ($application->xprops('state') as $name => $value) {
                        $appController->setApplicationState($application, $name, $value);
                    }
                }
            } else {
                Setup_Core::getLogger()->err(Tinebase_Model_Application::class . ' does not have property state!');
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
     * was: upgrade to utf8mb4 for mysql
     */
    public function update_24()
    {
        $this->setApplicationVersion('Tinebase', '11.25');
    }

    /**
     * update to 11.26
     *
     * add actionQueueMonitoringTask to scheduler
     */
    public function update_25()
    {
        $scheduler = Tinebase_Core::getScheduler();
        $oldRightValue = $scheduler->doRightChecks(false);

        try {
            Tinebase_Scheduler_Task::addActionQueueMonitoringTask($scheduler);
        } finally {
            $scheduler->doRightChecks($oldRightValue);
        }

        $this->setApplicationVersion('Tinebase', '11.26');
    }

    /**
     * update to 11.27
     *
     * eventually add missing indexed_hash column to tree_nodes
     */
    public function update_26()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_55();

        $this->setApplicationVersion('Tinebase', '11.27');
    }

    /**
     * update to 11.28
     *
     * check for container without a model and set app default model if NULL
     */
    public function update_27()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->setContainerModels();

        $this->setApplicationVersion('Tinebase', '11.28');
    }

    /**
     * update to 11.29
     *
     * add hierarchy column to container
     */
    public function update_28()
    {
        // first we need to do the calendar structure update. When we touch the containers below we might trigger
        // a resource update too, so the structure needs to be available by then, we can't wait for the Calendar
        // to update later
        if (Tinebase_Application::getInstance()->isInstalled('Calendar')) {
            $calendarUpdate = new Calendar_Setup_Update_Release11($this->_backend);
            $calendarUpdate->update_9(false);
        }

        if (!$this->_backend->columnExists('hierarchy', 'container')) {
            $this->_backend->addCol('container', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>hierarchy</name>
                    <type>text</type>
                    <length>65535</length>
                </field>'));

            $containerController = Tinebase_Container::getInstance();
            /** @var Tinebase_Model_Container $container */
            foreach ($containerController->getAll() as $container) {
                $container->hierarchy = $container->name;
                $containerController->update($container);
            }
        }

        if ($this->getTableVersion('container') == 13) {
            $this->setTableVersion('container', 14);
        }

        $this->setApplicationVersion('Tinebase', '11.29');
    }

    /**
     * update to 11.30
     *
     * create replication user
     */
    public function update_29()
    {
        try {
            Tinebase_User::getInstance()->getFullUserByLoginName(Tinebase_User::SYSTEM_USER_REPLICATION);
            // nothing to do
        } catch (Tinebase_Exception_NotFound $tenf) {
            $replicationUser = Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_REPLICATION,
                Tinebase_Group::getInstance()->getDefaultReplicationGroup());
            if (null !== $replicationUser) {
                $replicationMasterConf = Tinebase_Config::getInstance()->get(Tinebase_Config::REPLICATION_MASTER);
                if (empty(($password = $replicationMasterConf->{Tinebase_Config::REPLICATION_USER_PASSWORD}))) {
                    $password = Tinebase_Record_Abstract::generateUID(12);
                }
                // TODO auto create pw that is matching the policy
                $pwPolicyActive = Tinebase_Config::getInstance()->{Tinebase_Config::USER_PASSWORD_POLICY}->{Tinebase_Config::PASSWORD_POLICY_ACTIVE};
                if ($pwPolicyActive) {
                    Tinebase_Config::getInstance()->{Tinebase_Config::USER_PASSWORD_POLICY}->{Tinebase_Config::PASSWORD_POLICY_ACTIVE} = false;
                }
                Tinebase_User::getInstance()->setPassword($replicationUser, $password);
                if ($pwPolicyActive) {
                    Tinebase_Config::getInstance()->{Tinebase_Config::USER_PASSWORD_POLICY}->{Tinebase_Config::PASSWORD_POLICY_ACTIVE} = true;
                }
            }
        }

        $this->setApplicationVersion('Tinebase', '11.30');
    }

    /**
     * update to 11.31
     *
     * removing db prefix from application_tables if present
     */
    public function update_30()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_56();
        $this->setApplicationVersion('Tinebase', '11.31');
    }

    /**
     * delete some obsolete export definitions
     */
    public function update_31()
    {
        $obsoleteNames = ['adb_default_xls', 'adb_ods', 'lead_excel5_xls'];
        $filter = new Tinebase_Model_ImportExportDefinitionFilter([
            ['field' => 'name', 'operator' => 'in', 'value' => $obsoleteNames]
        ]);
        Tinebase_ImportExportDefinition::getInstance()->deleteByFilter($filter);
        $this->setApplicationVersion('Tinebase', '11.32');
    }

    /**
     * update to 11.33
     *
     * increase temp_file size column to bigint
     */
    public function update_32()
    {
        $this->_backend->alterCol('temp_files', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>size</name>
                    <type>integer</type>
                    <length>64</length>
                    <unsigned>true</unsigned>
                    <notnull>true</notnull>
                </field>'));

        if ($this->getTableVersion('temp_files') == 1) {
            $this->setTableVersion('temp_files', 2);
        }

        $this->setApplicationVersion('Tinebase', '11.33');
    }

    /**
     * update to 11.34
     *
     * change unique key parent_id - name - deleted_time so that it really works
     */
    public function update_33()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_57();
        $this->setApplicationVersion('Tinebase', '11.34');
    }

    /**
     * update to 11.35
     *
     * container xprops need to be [] not NULL
     */
    public function update_34()
    {
        $quotedField = $this->_db->quoteIdentifier('xprops');
        $this->_db->update(SQL_TABLE_PREFIX . 'container', ['xprops' => '[]'],
            $quotedField . ' IS NULL');

        $this->setApplicationVersion('Tinebase', '11.35');
    }

    /**
     * update to 11.36
     *
     * create filterSyncToken table and add clean up job
     */
    public function update_35()
    {
        $this->updateSchema('Tinebase', array(Tinebase_Model_FilterSyncToken::class));

        Tinebase_Scheduler_Task::addFilterSyncTokenCleanUpTask(Tinebase_Core::getScheduler());

        $this->setApplicationVersion('Tinebase', '11.36');
    }

    /**
     * update to 11.37
     *
     * update temp file cleanup task
     */
    public function update_36()
    {
        $scheduler = new Tinebase_Backend_Scheduler();
        try {
            /** @var Tinebase_Model_SchedulerTask $task */
            $task = $scheduler->getByProperty('Tinebase_TempFileCleanup', 'name');
            $task->config->setCron(Tinebase_Scheduler_Task::TASK_TYPE_HOURLY);
            $scheduler->update($task);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Scheduler_Task::addTempFileCleanupTask(Tinebase_Scheduler::getInstance());
        }

        $this->setApplicationVersion('Tinebase', '11.37');
    }

    /**
     * update to 11.38
     *
     * do nothing here
     */
    public function update_37()
    {
        $this->setApplicationVersion('Tinebase', '11.38');
    }

    /**
     * update to 11.39
     *
     * add is_system column to customfield_config
     */
    public function update_38()
    {
        $this->addIsSystemToCustomFieldConfig();

        $this->setApplicationVersion('Tinebase', '11.39');
    }
    public function addIsSystemToCustomFieldConfig()
    {
        if (!$this->_backend->columnExists('is_system', 'customfield_config')) {
            $this->_backend->addCol('customfield_config', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>is_system</name>
                    <type>boolean</type>
                    <notnull>true</notnull>
                    <default>false</default>
                </field>'));
        }

        if ($this->getTableVersion('customfield_config') < 6) {
            $this->setTableVersion('customfield_config', 6);
        }
    }

    /**
     * update to 11.40
     *
     * update filterSyncToken table
     */
    public function update_39()
    {
        $this->updateSchema('Tinebase', array(Tinebase_Model_FilterSyncToken::class));

        $this->setApplicationVersion('Tinebase', '11.40');
    }

    public function update_40()
    {
        $note_types = array('note', 'telephone', 'email', 'created', 'changed');
        foreach ($note_types as $note_type) {
            $note = Tinebase_Notes::getInstance()->getNoteTypeByName($note_type);
            $icon = '';
            switch ($note_type) {
                case 'note':
                    $icon = 'images/icon-set/icon_note.svg';
                    break;
                case 'telephone':
                    $icon = 'images/icon-set/icon_phone.svg';
                    break;
                case 'email':
                    $icon = 'images/icon-set/icon_email.svg';
                    break;
                case 'created':
                    $icon = 'images/icon-set/icon_star_out.svg';
                    break;
                case 'changed':
                    $icon = 'images/icon-set/icon_doc_file.svg';
                    break;
            }
            $note['icon'] = $icon;
            if ($note['icon'] != '') {
                Tinebase_Notes::getInstance()->updateNoteType($note);
            }
        }

        $this->setApplicationVersion('Tinebase', '11.40');
    }

    /**
     * update to 11.42
     *
     * ensure tree_filerevision exist for all fileobjects
     */
    public function update_41()
    {
        $db = $this->getDb();
        $user = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
        foreach ($db->query('SELECT fo.id, fo.revision from ' . SQL_TABLE_PREFIX . 'tree_fileobjects AS fo LEFT JOIN ' .
            SQL_TABLE_PREFIX . 'tree_filerevisions AS fr ON fo.id = fr.id AND fo.revision = fr.revision ' .
            'WHERE fr.id IS NULL AND fo.type = "folder"')->fetchAll(Zend_Db::FETCH_ASSOC) as $row) {
            $db->insert(SQL_TABLE_PREFIX . 'tree_filerevisions', [
                'id' => $row['id'],
                'revision' => $row['revision'] + 1,
                'hash' => Tinebase_Record_Abstract::generateUID(),
                'size' => 0,
                'created_by' => $user->getId(),
                'creation_time' => new Zend_Db_Expr('NOW()'),
                'preview_count' => 0,
            ]);

            $db->update(SQL_TABLE_PREFIX . 'tree_fileobjects', [
                'revision' => $row['revision'] + 1,
                'last_modified_by' => $user->getId(),
                'last_modified_time' => new Zend_Db_Expr('NOW()'),
            ], 'id = "' . $row['id'] . '"');
        }

        $this->setApplicationVersion('Tinebase', '11.42');
    }

    /**
     * update to 11.43
     *
     * fix role name unique key
     */
    public function update_42()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_58();
        $this->setApplicationVersion('Tinebase', '11.43');
    }

    /**
     * update to 11.44
     *
     * add lastavscan_time and is_quarantined to tree_filerevisions
     * add scheduler task to av scan fs once a day
     */
    public function update_43()
    {
        $this->fsAVupdates();

        Tinebase_Scheduler_Task::addFileSystemAVScanTask(Tinebase_Core::getScheduler());

        $this->setApplicationVersion('Tinebase', '11.44');
    }

    public function fsAVupdates()
    {
        if (!$this->_backend->columnExists('lastavscan_time', 'tree_filerevisions')) {
            $this->_backend->addCol('tree_filerevisions', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>lastavscan_time</name>
                    <type>datetime</type>
                </field>'));
        }

        if (!$this->_backend->columnExists('is_quarantined', 'tree_filerevisions')) {
            $this->_backend->addCol('tree_filerevisions', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>is_quarantined</name>
                    <type>boolean</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>'));
        }

        if ($this->getTableVersion('tree_filerevisions') < 3) {
            $this->setTableVersion('tree_filerevisions', 3);
        }
    }

    /**
     * update to 11.45
     *
     * reimport templates
     */
    public function update_44()
    {
        $setupController = Setup_Controller::getInstance();

        /** @var Tinebase_Model_Application $application */
        foreach (Tinebase_Application::getInstance()->getApplications() as $application) {
            $setupController->createImportExportDefinitions($application, Tinebase_Core::isReplicationSlave());
        }

        $this->setApplicationVersion('Tinebase', '11.45');
    }

    /**
     * update history Icon
     */
    public function update_45()
    {
        $note = Tinebase_Notes::getInstance()->getNoteTypeByName('changed');
        if ($note !== null) {
            $note['icon'] = 'images/icon-set/icon_file.svg';
            Tinebase_Notes::getInstance()->updateNoteType($note);
        }
        $this->setApplicationVersion('Tinebase', '11.46');
    }

    /**
     * update to 12.0
     */
    public function update_46()
    {
        $this->setApplicationVersion('Tinebase', '12.0');
    }
}
