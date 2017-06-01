<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     *
     * @see 0012162: create new MailFiler application
     */
    public function update_0()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_9();
        $this->setApplicationVersion('Tinebase', '10.1');
    }

    /**
     * update to 10.2
     *
     * @see 0012300: add container owner column
     */
    public function update_1()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        try {
            $release9->update_4();
        } catch (Zend_Db_Exception $zde) {
            Tinebase_Exception::log($zde);
        }
        $this->setApplicationVersion('Tinebase', '10.2');
    }

    /**
     * update to 10.3
     *
     * change length of groups.description column from varchar(255) to text
     */
    public function update_2()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_5();
        $this->setApplicationVersion('Tinebase', '10.3');
    }

    /**
     * update to 10.4
     *
     * dd numberables
     */
    public function update_3()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_10();
        $this->setApplicationVersion('Tinebase', '10.4');
    }

    /**
     * needs to be done again to make sure we have the column!
     *
     * @see 0012300: add container owner column
     */
    public function update_4()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        try {
            $release9->update_4();
        } catch (Zend_Db_Exception $zde) {
            Tinebase_Exception::log($zde);
        }
        $this->setApplicationVersion('Tinebase', '10.5');
    }

    /**
     * update to 10.6
     *
     * add account sync scheduler job
     */
    public function update_5()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Tinebase_Scheduler_Task::addAccountSyncTask($scheduler);

        $this->setApplicationVersion('Tinebase', '10.6');
    }

    /**
     * update to 10.7
     *
     * update timemachine_modlog table
     */
    public function update_6()
    {
        if (!$this->_backend->columnExists('instance_id', 'timemachine_modlog')) {

            $this->_backend->renameTable('timemachine_modlog', 'timemachine_modlog_bkp');
            $db = Tinebase_Core::getDb();
            if ($db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
                $db->exec('ALTER INDEX "' . SQL_TABLE_PREFIX . 'timemachine_modlog_pkey" RENAME TO ' . SQL_TABLE_PREFIX . 'timemachine_modlog_pkey_bkp');
                $db->exec('ALTER INDEX "' . SQL_TABLE_PREFIX . 'timemachine_modlog_seq" RENAME TO ' . SQL_TABLE_PREFIX . 'timemachine_modlog_seq_bkp');
                $db->exec('ALTER INDEX "' . SQL_TABLE_PREFIX . 'timemachine_modlog_unique-fields_key" RENAME TO "' . SQL_TABLE_PREFIX . 'timemachine_modlog_unique-fields_key_bkp"');
            }

            $this->_backend->createTable(new Setup_Backend_Schema_Table_Xml('<table>
                <name>timemachine_modlog</name>
                <version>5</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>instance_id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>instance_seq</name>
                        <type>integer</type>
                        <notnull>true</notnull>
                        <autoincrement>true</autoincrement>
                    </field>
                    <field>
                        <name>change_type</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>application_id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>record_id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>record_type</name>
                        <type>text</type>
                        <length>64</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>record_backend</name>
                        <type>text</type>
                        <length>64</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>modification_time</name>
                        <type>datetime</type>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>modification_account</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>modified_attribute</name>
                        <type>text</type>
                        <length>64</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>old_value</name>
                        <type>clob</type>
                    </field>
                    <field>
                        <name>new_value</name>
                        <type>clob</type>
                    </field>
                    <field>
                        <name>seq</name>
                        <type>integer</type>
                        <length>64</length>
                    </field>
                    <field>
                        <name>client</name>
                        <type>text</type>
                        <length>255</length>
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
                        <name>instance_id</name>
                        <field>
                            <name>instance_id</name>
                        </field>
                    </index>
                    <index>
                        <name>instance_seq</name>
                        <unique>true</unique>
                        <field>
                            <name>instance_seq</name>
                        </field>
                    </index>
                    <index>
                        <name>seq</name>
                        <field>
                            <name>seq</name>
                        </field>
                    </index>
                    <index>
                        <name>unique-fields</name>
                        <unique>true</unique>
                        <field>
                            <name>application_id</name>
                        </field>
                        <field>
                            <name>record_id</name>
                        </field>
                        <field>
                            <name>record_type</name>
                        </field>
                        <field>
                            <name>modification_time</name>
                        </field>
                        <field>
                            <name>modification_account</name>
                        </field>
                        <field>
                            <name>modified_attribute</name>
                        </field>
                        <field>
                            <name>seq</name>
                        </field>
                    </index>
                </declaration>
            </table>'));



            $appIds[] = Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId();
            if (Tinebase_Application::getInstance()->isInstalled('Calendar')) {
                $appIds[] = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId();
            }

            $select = $db->select()->from(SQL_TABLE_PREFIX . 'timemachine_modlog_bkp')->order('modification_time ASC')
                ->where($db->quoteInto($db->quoteIdentifier('application_id') . ' IN (?)', $appIds))
                ->where($db->quoteInto($db->quoteIdentifier('record_type') . ' IN (?)', array('Addressbook_Model_Contact', 'Calendar_Model_Resource')))
                ->where($db->quoteInto($db->quoteIdentifier('modified_attribute') . ' IN (?)', array('email', 'email_home')));

            $stmt = $db->query($select);
            $resultArray = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

            if (count($resultArray) > 0) {
                foreach($resultArray as $row) {
                    $row['client'] = 'update script';
                    $db->insert(SQL_TABLE_PREFIX . 'timemachine_modlog', $row);
                }
            }

            $this->setTableVersion('timemachine_modlog', '5');
        }

        $this->setApplicationVersion('Tinebase', '10.7');
    }

    /**
     * update to 10.8
     *
     * update roles and application table
     */
    public function update_7()
    {
        if (!$this->_backend->columnExists('is_deleted', 'roles')) {
            $query = $this->_backend->addAddCol(null, 'roles',
                new Setup_Backend_Schema_Field_Xml('<field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>'), 'last_modified_time'
            );
            $query = $this->_backend->addAddCol($query, 'roles',
                new Setup_Backend_Schema_Field_Xml('<field>
                    <name>deleted_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>'), 'is_deleted'
            );
            $query = $this->_backend->addAddCol($query, 'roles',
                new Setup_Backend_Schema_Field_Xml('<field>
                    <name>deleted_time</name>
                    <type>datetime</type>
                </field>'), 'deleted_by'
            );
            $query = $this->_backend->addAddCol($query, 'roles',
                new Setup_Backend_Schema_Field_Xml('<field>
                    <name>seq</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>'), 'deleted_time'
            );
            $this->_backend->execQueryVoid($query);
            $this->setTableVersion('roles', '2');
        }

        if (!$this->_backend->columnExists('state', 'applications')) {

            $this->_backend->addCol('applications',
                new Setup_Backend_Schema_Field_Xml('<field>
                    <name>state</name>
                    <type>text</type>
                    <length>65535</length>
                    <notnull>false</notnull>
                </field>')
            );

            $this->setTableVersion('applications', '4');
        }

        $this->setApplicationVersion('Tinebase', '10.8');
    }

    /**
     * update to 10.9
     *
     * add client row to timemachine_modlog
     *
     * @see 0012830: add client user agent to modlog
     */
    public function update_8()
    {
        if (! $this->_backend->columnExists('client', 'timemachine_modlog')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>client</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>');
            $this->_backend->addCol('timemachine_modlog', $declaration);

            $this->setTableVersion('timemachine_modlog', '5');
        }

        $this->setApplicationVersion('Tinebase', '10.9');
    }

    /**
     * update to 10.10
     *
     * adding path filter feature switch & structure update
     */
    public function update_9()
    {
        $this->dropTable('path');

        $declaration = new Setup_Backend_Schema_Table_Xml('<table>
            <name>path</name>
            <version>2</version>
            <requirements>
                <required>mysql >= 5.6.4</required>
            </requirements>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>path</name>
                    <type>text</type>
                    <length>65535</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>shadow_path</name>
                    <type>text</type>
                    <length>65535</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>creation_time</name>
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
                <name>path</name>
                    <fulltext>true</fulltext>
                    <field>
                        <name>path</name>
                    </field>
                </index>
                <index>
                    <name>shadow_path</name>
                    <fulltext>true</fulltext>
                    <field>
                        <name>shadow_path</name>
                    </field>
                </index>
            </declaration>
        </table>');

        $this->createTable('path', $declaration, 'Tinebase', 2);

        try {
            $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
            if ($setupUser) {
                Tinebase_Core::set(Tinebase_Core::USER, $setupUser);
                Tinebase_Controller::getInstance()->rebuildPaths();
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' Could not find valid setupuser. Skipping rebuildPaths: you might need to run this manually.');
                }
            }
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Skipping rebuildPaths: you might need to run this manually.');
        }

        $this->setApplicationVersion('Tinebase', '10.10');
    }

    /**
     * update to 10.11
     *
     * create external_fulltext table
     */
    public function update_10()
    {
        $this->_backend->createTable(new Setup_Backend_Schema_Table_Xml('<table>
            <name>external_fulltext</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>text_data</name>
                    <type>text</type>
                    <length>2147483647</length>
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
                    <name>text_data</name>
                    <fulltext>true</fulltext>
                    <field>
                        <name>text_data</name>
                    </field>
                </index>
            </declaration>
        </table>'), 'Tinebase', 'external_fulltext');

        $this->setApplicationVersion('Tinebase', '10.11');
    }

    /**
     * update to 10.12
     *
     * add revision_size to tree_fileobjects
     */
    public function update_11()
    {

        if (!$this->_backend->columnExists('total_size', 'tree_fileobjects')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>revision_size</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>');

            $query = $this->_backend->addAddCol('', 'tree_fileobjects', $declaration);

            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>indexed_hash</name>
                    <type>text</type>
                    <length>40</length>
                </field>');

            $query = $this->_backend->addAddCol($query, 'tree_fileobjects', $declaration);

            $this->_backend->execQueryVoid($query);

            $this->setTableVersion('tree_fileobjects', '4');
        }

        $this->setApplicationVersion('Tinebase', '10.12');
    }

    /**
     * add tree_node_acl
     */
    public function update_12()
    {
        if (! $this->_backend->columnExists('acl_node', 'tree_nodes')) {
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>acl_node</name>
                    <type>text</type>
                    <length>40</length>
                </field>
            ');
            $query = $this->_backend->addAddCol('', 'tree_nodes', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>revisionProps</name>
                    <type>text</type>
                    <length>65535</length>
                </field>');
            $query = $this->_backend->addAddCol($query, 'tree_nodes', $declaration);
            $this->_backend->execQueryVoid($query);

            $this->setTableVersion('tree_nodes', 2);

            $declaration = new Setup_Backend_Schema_Table_Xml('<table>
            <name>tree_node_acl</name>
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
                    <name>tree_node_acl::record_id--tree_nodes::id</name>
                    <field>
                        <name>record_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>tree_nodes</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                    </reference>
                </index>
            </declaration>
        </table>');
            $this->createTable('tree_node_acl', $declaration);
        }

        $this->setApplicationVersion('Tinebase', '10.13');
    }

    /**
     * update to 10.14
     *
     * add file revision cleanup task to scheduler
     */
    public function update_13()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Tinebase_Scheduler_Task::addFileRevisionCleanupTask($scheduler);

        $this->setApplicationVersion('Tinebase', '10.14');
    }

    /**
     * update to 10.15
     *
     * update record_observer
     */
    public function update_14()
    {
        $this->dropTable('record_observer', 'Tinebase');

        $this->createTable('record_observer', new Setup_Backend_Schema_Table_Xml('<table>
            <name>record_observer</name>
            <version>4</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>integer</type>
                    <autoincrement>true</autoincrement>
                </field>
                <field>
                    <name>observable_model</name>
                    <type>text</type>
                    <length>100</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>observable_identifier</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>observer_model</name>
                    <type>text</type>
                    <length>100</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>observer_identifier</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>observed_event</name>
                    <type>text</type>
                    <length>100</length>
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
                    <name>observable-observer-event</name>
                    <unique>true</unique>
                    <field>
                        <name>observable_model</name>
                    </field>
                    <field>
                        <name>observable_identifier</name>
                    </field>
                    <field>
                        <name>observer_model</name>
                    </field>
                    <field>
                        <name>observer_identifier</name>
                    </field>
                    <field>
                        <name>observed_event</name>
                    </field>
                </index>
                <index>
                    <name>observer</name>
                    <field>
                        <name>observer_model</name>
                    </field>
                    <field>
                        <name>observer_identifier</name>
                    </field>
                </index>
            </declaration>
        </table>'), 'Tinebase', 3);

        $this->setApplicationVersion('Tinebase', '10.15');
    }

    /**
     * update to 10.16
     *
     * add container xprops column
     */
    public function update_15()
    {
        if (! $this->_backend->columnExists('xprops', 'container')) {
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>xprops</name>
                    <type>text</type>
                    <notnull>false</notnull>
                    <default>NULL</default>
                </field>
            ');
            $this->_backend->addCol('container', $declaration);
            $this->setTableVersion('container', 12);
        }

        $this->setApplicationVersion('Tinebase', '10.16');
    }

    /**
     * update node acl: find all nodes that have containers, copy acl to node and remove container
     *
     * TODO allow to call from cli?
     */
    public function update_16()
    {
        // this is needed for filesystem operations
        $this->_addRevisionPreviewCountCol();

        $applications = Tinebase_Application::getInstance()->getApplications();
        $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
        if ($setupUser) {
            Tinebase_Core::set(Tinebase_Core::USER, $setupUser);
        }
        foreach ($applications as $application) {
            if ($setupUser && ! $setupUser->hasRight($application, Tinebase_Acl_Rights::RUN)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' Skipping ' . $application->name . ' because setupuser has no RUN right');
                }
                continue;
            }

            $this->_migrateAclForApplication($application, Tinebase_FileSystem::FOLDER_TYPE_PERSONAL);
            $this->_migrateAclForApplication($application, Tinebase_FileSystem::FOLDER_TYPE_SHARED);
        }

        $this->setApplicationVersion('Tinebase', '10.17');
    }

    /**
     * @param $application
     * @param $type
     */
    protected function _migrateAclForApplication($application, $type)
    {
        $path = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
            $application->name,
            $type
        );
        try {
            $parentNode = Tinebase_FileSystem::getInstance()->stat($path);
        } catch (Tinebase_Exception_NotFound $tenf) {
            return;
        }

        $childNodes = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($parentNode);

        if (count($childNodes) === 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . " No container nodes found for application " . $application->name);
            return;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' ' . count($childNodes) . " nodes found for application " . $application->name);

        if ($type === Tinebase_FileSystem::FOLDER_TYPE_PERSONAL) {
            foreach ($childNodes as $accountNode) {
                $personalNodes = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($accountNode);
                $this->_moveAclFromContainersToNodes($personalNodes);
            }
        } else {
            // shared
            $this->_moveAclFromContainersToNodes($childNodes);
        }
    }

    /**
     * @param Tinebase_Record_RecordSet $nodes
     *
     * TODO move to TFS?
     */
    protected function _moveAclFromContainersToNodes(Tinebase_Record_RecordSet $nodes)
    {
        foreach ($nodes as $node) {
            try {
                $container = Tinebase_Container::getInstance()->getContainerById($node->name);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // already converted
                continue;
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                // already converted
                continue;
            }
            //print_r($container->toArray());
            if ($container->model === 'HumanResources_Model_Employee') {
                // fix broken HR template container to prevent problems when removing data
                $container->model = 'Tinebase_Model_Tree_Node';
                Tinebase_Container::getInstance()->update($container);
            }

            // set container acl in node
            $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($container, /* ignore acl */ true);
            Tinebase_FileSystem::getInstance()->setGrantsForNode($node, $grants);

            // set container name in node
            $node->name = $container->name;
            // check if node exists and if yes: attach uid
            $parentNode = Tinebase_FileSystem::getInstance()->get($node->parent_id);
            $parentPath = Tinebase_FileSystem::getInstance()->getPathOfNode($parentNode, true);
            if (Tinebase_FileSystem::getInstance()->fileExists($parentPath . '/' . $node->name)) {
                $node->name .= ' ' . Tinebase_Record_Abstract::generateUID(8);
            }

            $node->acl_node = $node->getId();
            Tinebase_FileSystem::getInstance()->update($node);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Updated node acl for ' . $node->name .' (container id: ' . $container->getId() . ')');

            // remove old acl container
            Tinebase_Container::getInstance()->deleteContainer($container, /* ignore acl */ true);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Removed old container ' . $container->name);
        }
    }

    /**
     * update to 10.18
     *
     * Add fulltext index for description field
     */
    public function update_17()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        $this->_backend->addIndex('tree_fileobjects', $declaration);

        $this->setTableVersion('tree_fileobjects', '5');
        $this->setApplicationVersion('Tinebase', '10.18');
    }

    /**
     * update to 10.19
     *
     * Add fulltext search index for tags description
     */
    public function update_18()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        try {
            $this->_backend->dropIndex('tags', 'description');
        } catch (Exception $e) {
            // Ignore, if there is no index, we can just go on and create one.
        }

        $this->_backend->addIndex('tags', $declaration);

        $this->setTableVersion('tags', 8);
        $this->setApplicationVersion('Tinebase', '10.19');
    }

    /**
     * update to 10.20
     *
     * Make tags description a longtext field
     */
    public function update_19()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>description</name>
                <!--Long text!-->
                <length>2147483647</length>
                <type>text</type>
                <default>NULL</default>
            </field>
        ');

        $this->_backend->alterCol('tags', $declaration);

        $this->setTableVersion('tags', 9);
        $this->setApplicationVersion('Tinebase', '10.20');
    }

    /**
     * update to 10.21
     *
     * add new file system tasks to scheduler
     */
    public function update_20()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Tinebase_Scheduler_Task::addFileSystemSizeRecalculation($scheduler);
        Tinebase_Scheduler_Task::addFileSystemCheckIndexTask($scheduler);

        $this->setApplicationVersion('Tinebase', '10.21');
    }

    /**
     * update to 10.22
     *
     * add favorite column to importexport_definition
     */
    public function update_21()
    {
        if (! $this->_backend->columnExists('favorite', 'importexport_definition')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>icon_class</name>
                    <type>text</type>
                    <length>255</length>
                </field>');
            $this->_backend->addCol('importexport_definition', $declaration);

            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>favorite</name>
                    <type>boolean</type>
                </field>');
            $this->_backend->addCol('importexport_definition', $declaration);

            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>order</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>');
            $this->_backend->addCol('importexport_definition', $declaration);

            $this->setTableVersion('importexport_definition', 9);
        }

        $this->setApplicationVersion('Tinebase', '10.22');
    }

    /**
     * update to 10.23
     *
     * add preview_count column to tree_filerevisions
     */
    public function update_22()
    {
        $this->_addRevisionPreviewCountCol();
        $this->setApplicationVersion('Tinebase', '10.23');
    }

    protected function _addRevisionPreviewCountCol()
    {
        if (! $this->_backend->columnExists('preview_count', 'tree_filerevisions')) {
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>preview_count</name>
                    <type>integer</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>');
            $this->_backend->addCol('tree_filerevisions', $declaration);
            $this->setTableVersion('tree_filerevisions', 2);
        }
    }

    /**
     * update to 10.24
     *
     * 0013032: add GRANT_DOWNLOAD
     * 0013034: add GRANT_PUBLISH
     */
    public function update_23()
    {
        // get all folder nodes with own acl
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'type',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_Tree_FileObject::TYPE_FOLDER
            )
        ), Tinebase_Model_Filter_FilterGroup::CONDITION_AND, array('ignoreAcl' => true));
        $folders = Tinebase_FileSystem::getInstance()->searchNodes($searchFilter);
        $updateCount = 0;
        foreach ($folders as $folder) {
            if ($folder->acl_node === $folder->getId()) {
                $grants = Tinebase_FileSystem::getInstance()->getGrantsOfContainer($folder, /* ignoreAcl */ true);
                foreach ($grants as $grant) {
                    // add download & publish for admins and only download for the rest
                    if ($grant->{Tinebase_Model_Grants::GRANT_ADMIN}) {
                        $grant->{Tinebase_Model_Grants::GRANT_DOWNLOAD} = true;
                        $grant->{Tinebase_Model_Grants::GRANT_PUBLISH} = true;
                    } else {
                        $grant->{Tinebase_Model_Grants::GRANT_DOWNLOAD} = true;
                    }
                }
                Tinebase_FileSystem::getInstance()->setGrantsForNode($folder, $grants);
                $updateCount++;
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Added DOWNLOAD & PUBLISH grants to ' . $updateCount . ' folder nodes');

        $this->setApplicationVersion('Tinebase', '10.24');
    }

    /**
     * update to 10.25
     *
     * add notification props
     */
    public function update_24()
    {
        if (! $this->_backend->columnExists('notificationProps', 'tree_nodes')) {
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>notificationProps</name>
                    <type>text</type>
                    <length>65535</length>
                </field>
            ');
            $this->_backend->addCol('tree_nodes', $declaration);
            $this->setTableVersion('tree_nodes', 3);
        }

        $this->setApplicationVersion('Tinebase', '10.25');
    }

    /**
     * update to 10.26
     *
     * add scope column to importexport_definition
     */
    public function update_25()
    {
        if (! $this->_backend->columnExists('scope', 'importexport_definition')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>scope</name>
                    <type>text</type>
                    <length>255</length>
                </field>');
            $this->_backend->addCol('importexport_definition', $declaration);

            $this->setTableVersion('importexport_definition', 10);
        }

        $this->setApplicationVersion('Tinebase', '10.26');
    }

    /**
     * update to 10.27
     *
     * change role_accounts id to uuid
     */
    public function update_26()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                </field>');
        $this->_backend->alterCol('role_accounts', $declaration);

        $this->setTableVersion('role_accounts', 4);
        $this->setApplicationVersion('Tinebase', '10.27');
    }

    /**
     * update to 10.28
     *
     * add scope column to importexport_definition
     */
    public function update_27()
    {
        if (! $this->_backend->columnExists('format', 'importexport_definition')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>format</name>
                    <type>text</type>
                    <length>255</length>
                </field>');
            $this->_backend->addCol('importexport_definition', $declaration);

            $this->setTableVersion('importexport_definition', 11);
        }

        $this->setApplicationVersion('Tinebase', '10.28');
    }
}
