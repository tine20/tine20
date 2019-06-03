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
class Tinebase_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     */
    public function update_0()
    {
        $this->_addIsDeletedToTreeNodes();

        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_9();

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>description</name>
                <type>text</type>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->alterCol('tree_fileobjects', $declaration);

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
        if ($this->getTableVersion('numberable') === 0) {
            $declaration = new Setup_Backend_Schema_Table_Xml('<table>
                <name>numberable</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>integer</type>
                        <notnull>true</notnull>
                        <autoincrement>true</autoincrement>
                    </field>
                    <field>
                        <name>bucket</name>
                        <type>text</type>
                        <length>255</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>number</name>
                        <type>integer</type>
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
                        <name>bucket_number</name>
                        <unique>true</unique>
                        <field>
                            <name>bucket</name>
                        </field>
                        <field>
                            <name>number</name>
                        </field>
                    </index>
                </declaration>
            </table>');

            $this->createTable('numberable', $declaration);
        }
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
            $this->_backend->dropTable('timemachine_modlog', Tinebase_Core::getTinebaseId());

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

            $this->_backend->dropTable('timemachine_modlog_bkp');

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
        $this->_addIsDeletedToTreeNodes();

        $this->dropTable('path');

        $declaration = new Setup_Backend_Schema_Table_Xml('<table>
            <name>path</name>
            <version>2</version>
            <requirements>
                <required>mysql >= 5.6.4 | mariadb >= 10.0.5</required>
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
        if (! $this->_backend->tableExists('external_fulltext')) {
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
        }
        $this->setApplicationVersion('Tinebase', '10.11');
    }

    /**
     * update to 10.12
     *
     * add revision_size to tree_fileobjects
     */
    public function update_11()
    {
        if (! $this->_backend->columnExists('revision_size', 'tree_fileobjects')) {
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
        $this->setContainerModels();
        $this->_addIsDeletedToTreeNodes();

        // this is needed for filesystem operations
        $this->_addRevisionPreviewCountCol();

        $count = $this->_db->update(SQL_TABLE_PREFIX . 'container', array('model' => 'Tinebase_Model_Tree_Node'),
            $this->_db->quoteIdentifier('model') . ' = \'Tinebase_Model_Node\'');
        if ($count > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' updated ' . $count . ' containers with model "Tinebase_Model_Node" to "Tinebase_Model_Tree_Node"');
            }
        }

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
            try {
                Tinebase_Container::getInstance()->deleteContainer($container, /* ignore acl */
                    true);
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Removed old container ' . $container->name);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                Tinebase_Exception::log($teia);
            }
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

        try {
            $this->_backend->dropIndex('tree_fileobjects', 'description');
        } catch (Exception $e) {
            // Ignore, if there is no index, we can just go on and create one.
        }

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
                    <default>0</default>
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
        $this->_addNotificationProps();
        $this->_addIsDeletedToTreeNodes();

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
        $this->_addNotificationProps();
        $this->setApplicationVersion('Tinebase', '10.25');
    }

    protected function _addNotificationProps()
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

    /**
     * update to 10.29
     *
     * add scope column to importexport_definition
     */
    public function update_28()
    {
        $this->_addIsDeletedToTreeNodes();

        foreach (Tinebase_Application::getInstance()->getApplications() as $application) {
            Setup_Controller::getInstance()->createImportExportDefinitions($application);
        }

        $this->setApplicationVersion('Tinebase', '10.29');
    }

    /**
     * update to 10.30
     *
     * add scope column to importexport_definition
     */
    public function update_29()
    {
        $this->_backend->dropIndex('record_observer', 'observable-observer-event');

        $this->_backend->alterCol('record_observer', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>observable_identifier</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>'));

        $this->_backend->addIndex('record_observer', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>observable-observer-event</name>
                    <unique>true</unique>
                    <field>
                        <name>observable_model</name>
                    </field>
                    <field>
                        <name>observed_event</name>
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
                </index>'));

        $this->setTableVersion('record_observer', 5);

        $this->setApplicationVersion('Tinebase', '10.30');
    }

    /**
     * update to 10.31
     *
     * add is_deleted column to tree nodes
     */
    public function update_30()
    {
        $this->_addIsDeletedToTreeNodes();

        $this->setApplicationVersion('Tinebase', '10.31');
    }

    protected function _addIsDeletedToTreeNodes()
    {
        if (! $this->_backend->columnExists('is_deleted', 'tree_nodes')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <default>false</default>
                    <notnull>true</notnull>
                </field>');
            $this->_backend->addCol('tree_nodes', $declaration);

            $this->setTableVersion('tree_nodes', 4);
        }

        if (! $this->_backend->columnExists('pin_protected', 'tree_nodes')) {
            $this->_backend->addCol('tree_nodes', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>pin_protected</name>
                    <type>boolean</type>
                    <default>false</default>
                    <notnull>true</notnull>
                </field>'));
        }

        if (! $this->_backend->columnExists('deleted_time', 'tree_nodes')) {
            $this->_backend->addCol('tree_nodes', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>deleted_time</name>
                    <type>datetime</type>
                </field>'));
        }
    }

    /**
     * update to 10.32
     *
     * change container id from int to uuid
     */
    public function update_31()
    {
        //ATTENTION foreign constraints

        try {
            $this->_backend->dropForeignKey('container_acl', 'container_acl::container_id--container::id');
        } catch (Exception $e) {}
        try {
            $this->_backend->dropForeignKey('container_content', 'container_content::container_id--container::id');
        } catch (Exception $e) {}

        if ($this->_backend->tableExists('addressbook')) {
            try {
                $this->_backend->dropForeignKey('addressbook', 'addressbook::container_id--container::id');
            } catch (Exception $e) {}
        }
        if ($this->_backend->tableExists('cal_events')) {
            try {
                $this->_backend->dropForeignKey('cal_events', 'cal_events::container_id--container::id');
            } catch (Exception $e) {}
        }
        if ($this->_backend->tableExists('cal_resources')) {
            try {
                $this->_backend->dropForeignKey('cal_resources', 'cal_resources::container_id--container::id');
            } catch (Exception $e) {}
        }
        if ($this->_backend->tableExists('cal_attendee')) {
            try {
                $this->_backend->dropForeignKey('cal_attendee', 'cal_attendee::displaycontainer_id--container::id');
            } catch (Exception $e) {}
        }
        if ($this->_backend->tableExists('metacrm_lead')) {
            try {
                $this->_backend->dropForeignKey('metacrm_lead', 'metacrm_lead::container_id--container::id');
            } catch (Exception $e) {}
        }
        if ($this->_backend->tableExists('sales_contracts')) {
            try {
                $this->_backend->dropForeignKey('sales_contracts', 'sales_contracts::container_id--container::id');
            } catch (Exception $e) {}
        }
        if ($this->_backend->tableExists('sales_contracts')) {
            try {
                $this->_backend->dropForeignKey('sales_contracts', 'tine20_erp_contracts::container_id--container::id');
            } catch (Exception $e) {}
        }
        if ($this->_backend->tableExists('timetracker_timeaccount')) {
            try {
                $this->_backend->dropForeignKey('timetracker_timeaccount', 'timeaccount::container_id--container::id');
            } catch (Exception $e) {}
        }
        if ($this->_backend->tableExists('humanresources_contract')) {
            try {
                $this->_backend->dropForeignKey('humanresources_contract', 'tine20_contract::feast_calendar_id--container::id');
            } catch (Exception $e) {}
        }

        if ($this->getTableVersion('container') < 13) {
            $this->_backend->alterCol('container', new Setup_Backend_Schema_Field_Xml('<field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>'));
            $this->setTableVersion('container', 13);
        }

        if ($this->getTableVersion('container_acl') < 4) {
            $this->_backend->alterCol('container_acl', new Setup_Backend_Schema_Field_Xml('<field>
                        <name>container_id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>'));
            $this->setTableVersion('container_acl', 4);
        }

        if ($this->getTableVersion('container_content') < 3) {
            $this->_backend->alterCol('container_content', new Setup_Backend_Schema_Field_Xml('<field>
                        <name>container_id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>'));
            $this->setTableVersion('container_content', 3);
        }


        $this->_backend->alterCol('addressbook', new Setup_Backend_Schema_Field_Xml('<field>
            <name>container_id</name>
            <type>text</type>
            <length>40</length>
            <notnull>false</notnull>
        </field>'));

        $this->_backend->alterCol('addressbook_lists', new Setup_Backend_Schema_Field_Xml('<field>
            <name>container_id</name>
            <type>text</type>
            <length>40</length>
            <notnull>false</notnull>
        </field>'));


        if (Tinebase_Application::getInstance()->isInstalled('Calendar')) {
            $this->_backend->alterCol('cal_events', new Setup_Backend_Schema_Field_Xml('<field>
                <name>container_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>'));

            $this->_backend->alterCol('cal_attendee', new Setup_Backend_Schema_Field_Xml('<field>
                <name>displaycontainer_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>'));

            $this->_backend->alterCol('cal_resources', new Setup_Backend_Schema_Field_Xml('<field>
                <name>container_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>'));
        }

        if (Tinebase_Application::getInstance()->isInstalled('Crm')) {
            $this->_backend->alterCol('metacrm_lead', new Setup_Backend_Schema_Field_Xml('<field>
                <name>container_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>'));
        }

        if (Tinebase_Application::getInstance()->isInstalled('Events')) {
            $this->_backend->alterCol('events_event', new Setup_Backend_Schema_Field_Xml('<field>
                <name>container_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>'));
        }

        if (Tinebase_Application::getInstance()->isInstalled('Projects')) {
            $this->_backend->alterCol('projects_project', new Setup_Backend_Schema_Field_Xml('<field>
                <name>container_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>'));
        }

        if (Tinebase_Application::getInstance()->isInstalled('Sales')) {
            $this->_backend->alterCol('sales_contracts', new Setup_Backend_Schema_Field_Xml('<field>
                <name>container_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>'));
        }

        if (Tinebase_Application::getInstance()->isInstalled('SimpleFAQ')) {
            $this->_backend->alterCol('simple_faq', new Setup_Backend_Schema_Field_Xml('<field>
                <name>container_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>'));
        }

        if (Tinebase_Application::getInstance()->isInstalled('Tasks')) {
            $this->_backend->alterCol('tasks', new Setup_Backend_Schema_Field_Xml('<field>
                <name>container_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>'));
        }

        if (Tinebase_Application::getInstance()->isInstalled('Timetracker')) {
            $this->_backend->alterCol('timetracker_timeaccount', new Setup_Backend_Schema_Field_Xml('<field>
                <name>container_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>'));

        }

        $this->_backend->addForeignKey('container_content', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>container_content::container_id--container::id</name>
                    <field>
                        <name>container_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>container</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                    </reference>
                </index>'));

        $ids = $this->_db->select()->from(['acl' => SQL_TABLE_PREFIX . 'container_acl'], ['id'])->joinLeft(
            array('c' => SQL_TABLE_PREFIX . 'container'),
            $this->_db->quoteIdentifier(['acl', 'container_id']) . ' = ' . $this->_db->quoteIdentifier(['c', 'id']),
            []
        )->where($this->_db->quoteIdentifier(['c', 'id']) . ' IS NULL')->query()->fetchAll(Zend_Db::FETCH_NUM);
        if (count($ids)) {
            $allIds = [];
            foreach($ids as $row) {
                $allIds[] = $row[0];
            }
            $this->_db->query('DELETE FROM ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'container_acl') .
                ' WHERE ' . $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', $allIds));
        }

        $this->_backend->addForeignKey('container_acl', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>container_acl::container_id--container::id</name>
                    <field>
                        <name>container_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>container</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                        <!-- add onupdate? -->
                    </reference>
                </index>'));

        if (Tinebase_Application::getInstance()->isInstalled('Timetracker') &&
                $this->_backend->tableExists('timeaccount')) {
            $this->_backend->addForeignKey('timeaccount', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>timeaccount::container_id--container::id</name>
                    <field>
                        <name>container_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>container</table>
                        <field>id</field>
                    </reference>
                </index>'));
        }

        if (Tinebase_Application::getInstance()->isInstalled('Sales') &&
                $this->_backend->tableExists('sales_contracts')) {
            $this->_backend->addForeignKey('sales_contracts', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>sales_contracts::container_id--container::id</name>
                    <field>
                        <name>container_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>container</table>
                        <field>id</field>
                    </reference>
                </index>'));
        }

        if (Tinebase_Application::getInstance()->isInstalled('Crm') &&
                $this->_backend->tableExists('metacrm_lead')) {
            $this->_backend->addForeignKey('metacrm_lead', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>metacrm_lead::container_id--container::id</name>
                    <field>
                        <name>container_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>container</table>
                        <field>id</field>
                    </reference>
                </index>'));
        }

        if (Tinebase_Application::getInstance()->isInstalled('Calendar')) {
            if ($this->_backend->tableExists('cal_resources')) {
                $this->_backend->addForeignKey('cal_resources', new Setup_Backend_Schema_Index_Xml('<index>
                        <name>cal_resources::container_id--container::id</name>
                        <field>
                            <name>container_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>container</table>
                            <field>id</field>
                        </reference>
                    </index>'));
            }

            if ($this->_backend->tableExists('cal_events')) {
                $this->_backend->addForeignKey('cal_events', new Setup_Backend_Schema_Index_Xml('<index>
                        <name>cal_events::container_id--container::id</name>
                        <field>
                            <name>container_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>container</table>
                            <field>id</field>
                        </reference>
                    </index>'));
            }

            if ($this->_backend->tableExists('cal_attendee')) {
                $this->_backend->addForeignKey('cal_attendee', new Setup_Backend_Schema_Index_Xml('<index>
                        <name>cal_attendee::displaycontainer_id--container::id</name>
                        <field>
                            <name>displaycontainer_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>container</table>
                            <field>id</field>
                        </reference>
                    </index>'));
            }
        }

        if ($this->_backend->tableExists('addressbook')) {
            $this->_backend->addForeignKey('addressbook', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>addressbook::container_id--container::id</name>
                    <field>
                        <name>container_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>container</table>
                        <field>id</field>
                    </reference>
                </index>'));
        }

        $this->setApplicationVersion('Tinebase', '10.32');
    }

    /**
     * update to 10.33
     *
     * change role id from int to uuid
     */
    public function update_32()
    {
        try {
            $this->_backend->dropForeignKey('role_rights', 'role_rights::role_id--roles::id');
        } catch (Exception $e) {}
        try {
            $this->_backend->dropForeignKey('role_accounts', 'role_accounts::role_id--roles::id');
        } catch (Exception $e) {}

        if ($this->getTableVersion('roles') < 3) {
            $this->_backend->alterCol('roles', new Setup_Backend_Schema_Field_Xml('<field>
                            <name>id</name>
                            <type>text</type>
                            <length>40</length>
                            <notnull>true</notnull>
                        </field>'));
            $this->setTableVersion('roles', 3);
        }

        if ($this->getTableVersion('role_rights') < 3) {
            $this->_backend->alterCol('role_rights', new Setup_Backend_Schema_Field_Xml('<field>
                            <name>id</name>
                            <type>text</type>
                            <length>40</length>
                            <notnull>true</notnull>
                        </field>'));

            $this->_backend->alterCol('role_rights', new Setup_Backend_Schema_Field_Xml('<field>
                            <name>role_id</name>
                            <type>text</type>
                            <length>40</length>
                            <notnull>true</notnull>
                        </field>'));
            $this->setTableVersion('role_rights', 3);
        }

        if ($this->getTableVersion('role_accounts') < 5) {
            $this->_backend->alterCol('role_accounts', new Setup_Backend_Schema_Field_Xml('<field>
                            <name>role_id</name>
                            <type>text</type>
                            <length>40</length>
                            <notnull>true</notnull>
                        </field>'));
            $this->setTableVersion('role_accounts', 5);
        }

        if ($this->_backend->tableExists('role_rights')) {
            $this->_backend->addForeignKey('role_rights', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>role_rights::role_id--roles::id</name>
                    <field>
                        <name>role_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>roles</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                    </reference>
                </index>'));
        }

        if ($this->_backend->tableExists('role_accounts')) {
            $this->_backend->addForeignKey('role_accounts', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>role_accounts::role_id--roles::id</name>
                    <field>
                        <name>role_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>roles</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                    </reference>
                </index>'));
        }

        $this->setApplicationVersion('Tinebase', '10.33');
    }

    /**
     * update to 10.34
     *
     * add pin column to accounts
     */
    public function update_33()
    {
        if (! $this->_backend->columnExists('pin', 'accounts')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>pin</name>
                    <type>text</type>
                    <length>100</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('accounts', $declaration);

            $this->setTableVersion('accounts', 12);
        }

        $this->setApplicationVersion('Tinebase', '10.34');
    }

    /**
     * update to 10.35
     *
     * add configuration column to accounts
     */
    public function update_34()
    {
        if (! $this->_backend->columnExists('configuration', 'accounts')) {
            $this->_backend->addCol('accounts', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>configuration</name>
                    <type>text</type>
                    <length>65535</length>
                </field>'));
            $this->setTableVersion('accounts', 13);
        }
        $this->setApplicationVersion('Tinebase', '10.35');
    }

    /**
     * update to 10.36
     *
     * add quota column to tree_nodes
     */
    public function update_35()
    {
        if (! $this->_backend->columnExists('quota', 'tree_nodes')) {
            $this->_backend->addCol('tree_nodes', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>quota</name>
                    <type>integer</type>
                    <length>64</length>
                    <notnull>false</notnull>
                </field>'));
            $this->setTableVersion('tree_nodes', 5);
        }

        if ($this->getTableVersion('tree_fileobjects') < 6) {
            $this->_backend->alterCol('tree_fileobjects', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>revision_size</name>
                    <type>integer</type>
                    <length>64</length>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>'));
            $this->setTableVersion('tree_fileobjects', 6);
        }

        $this->setApplicationVersion('Tinebase', '10.36');
    }

    /**
     * update to 10.37
     *
     * fix tree_node_acl and container_acl tables re primary / unique keys
     */
    public function update_36()
    {
        $result = $this->_db->select()->from(SQL_TABLE_PREFIX . 'container_acl')->query(Zend_Db::FETCH_ASSOC);
        $quotedId = $this->_db->quoteIdentifier('id');
        $quotedContainerId = $this->_db->quoteIdentifier('container_id');
        $quotedAccountType = $this->_db->quoteIdentifier('account_type');
        $quotedAccountId = $this->_db->quoteIdentifier('account_id');
        $quotedAccountGrant = $this->_db->quoteIdentifier('account_grant');
        foreach ($result->fetchAll() as $row) {
            $this->_db->update(SQL_TABLE_PREFIX . 'container_acl',
                array('id' => Tinebase_Record_Abstract::generateUID()),
                $quotedId           . ' = ' . $this->_db->quote($row['id'])           . ' AND ' .
                $quotedContainerId  . ' = ' . $this->_db->quote($row['container_id']) . ' AND ' .
                $quotedAccountType  . ' = ' . $this->_db->quote($row['account_type']) . ' AND ' .
                $quotedAccountId    . ' = ' . $this->_db->quote($row['account_id'])   . ' AND ' .
                $quotedAccountGrant . ' = ' . $this->_db->quote($row['account_grant'])
            );
        }

        $result = $this->_db->select()->from(SQL_TABLE_PREFIX . 'tree_node_acl')->query(Zend_Db::FETCH_ASSOC);
        $quotedRecordId = $this->_db->quoteIdentifier('record_id');
        foreach ($result->fetchAll() as $row) {
            try {
                $this->_db->update(SQL_TABLE_PREFIX . 'tree_node_acl',
                    array('id' => Tinebase_Record_Abstract::generateUID()),
                    $quotedId . ' = ' . $this->_db->quote($row['id']) . ' AND ' .
                    $quotedRecordId . ' = ' . $this->_db->quote($row['record_id']) . ' AND ' .
                    $quotedAccountType . ' = ' . $this->_db->quote($row['account_type']) . ' AND ' .
                    $quotedAccountId . ' = ' . $this->_db->quote($row['account_id']) . ' AND ' .
                    $quotedAccountGrant . ' = ' . $this->_db->quote($row['account_grant'])
                );
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        }

        /** @var Tinebase_Backend_Sql_Command_Interface $command */
        $command = Tinebase_Backend_Sql_Command::factory($this->_db);
        $quotedC = $this->_db->quoteIdentifier('c');
        $stmt = $this->_db->select()->from(SQL_TABLE_PREFIX . 'container_acl', array($command->getAggregate('id'),
            new Zend_Db_Expr('count(*) AS ' . $quotedC)))
            ->group(array('container_id', 'account_type', 'account_id', 'account_grant'));
        if ($this->_db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $stmt->having('count(*) > 1');
        } else {
            $stmt->having('c > 1');
        }
        $result = $stmt->query(Zend_Db::FETCH_NUM);
        foreach ($result->fetchAll() as $row) {
            $ids = explode(',', ltrim(rtrim($row[0], '}'), '{'));
            array_pop($ids);
            $this->_db->delete(SQL_TABLE_PREFIX . 'container_acl', $this->_db->quoteInto($quotedId . ' in (?)', $ids));
        }

        $stmt = $this->_db->select()->from(SQL_TABLE_PREFIX . 'tree_node_acl', array($command->getAggregate('id'),
            new Zend_Db_Expr('count(*) AS '. $quotedC)))
            ->group(array('record_id', 'account_type', 'account_id', 'account_grant'));
        if ($this->_db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $stmt->having('count(*) > 1');
        } else {
            $stmt->having('c > 1');
        }
        $result = $stmt->query(Zend_Db::FETCH_NUM);
        foreach ($result->fetchAll() as $row) {
            $ids = explode(',', ltrim(rtrim($row[0], '}'), '{'));
            array_pop($ids);
            $this->_db->delete(SQL_TABLE_PREFIX . 'tree_node_acl', $this->_db->quoteInto($quotedId . ' in (?)', $ids));
        }

        if ($this->getTableVersion('container_acl') < 5) {
            $this->_backend->dropPrimaryKey('container_acl');
            $this->_backend->addIndex('container_acl', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            '));
            $this->_backend->addIndex('container_acl', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>container_id-account_type-account_id-acount_grant</name>
                    <unique>true</unique>
                    <field>
                        <name>container_id</name>
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
            '));
            try {
                $this->_backend->dropIndex('container_acl', 'id-account_type-account_id');
            } catch(Exception $e) {}
            $this->setTableVersion('container_acl', 5);
        }

        if ($this->getTableVersion('tree_node_acl') < 2) {
            $this->_backend->dropPrimaryKey('tree_node_acl');
            $this->_backend->addIndex('tree_node_acl', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            '));
            $this->_backend->addIndex('tree_node_acl', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>record_id-account-type-account_id-account_grant</name>
                    <unique>true</unique>
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
            '));
            try {
                $this->_backend->dropIndex('tree_node_acl', 'id-account_type-account_id');
            } catch(Exception $e) {}
            $this->setTableVersion('tree_node_acl', 2);
        }
        $this->setApplicationVersion('Tinebase', '10.37');
    }

    /**
     * update to 10.38
     *
     * set shared folders acl not if not set
     */
    public function update_37()
    {
        $this->_addIsDeletedToTreeNodes();
        $fileSystem = Tinebase_FileSystem::getInstance();
        $inheritPropertyMethod = $this->_getProtectedMethod($fileSystem, '_recursiveInheritPropertyUpdate');
        if (Tinebase_Application::getInstance()->isInstalled('Filemanager')) {
            try {
                $node = $fileSystem->stat('/Filemanager/folders/shared');
                if (null === $node->acl_node) {
                    $fileSystem->setGrantsForNode($node, Tinebase_Model_Grants::getDefaultGrants(array(
                        Tinebase_Model_Grants::GRANT_DOWNLOAD => true
                    ), array(
                        Tinebase_Model_Grants::GRANT_PUBLISH => true
                    )));
                    $inheritPropertyMethod->invoke($fileSystem, $node, 'acl_node', $node->acl_node, null);
                }
            } catch(Tinebase_Exception_NotFound $tenf) {}
        }

        $this->setApplicationVersion('Tinebase', '10.38');
    }

    /**
     * GetProtectedMethod constructor.
     * @param $object
     * @param $method
     * @return ReflectionMethod
     */
    protected function _getProtectedMethod($object, $method)
    {
        $class = new ReflectionClass($object);
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * update to 10.39
     *
     * tree_nodes add pin_protected
     */
    public function update_38()
    {
        if ($this->getTableVersion('tree_nodes') < 6) {
            $this->_addIsDeletedToTreeNodes();
            $this->setTableVersion('tree_nodes', 6);
        }
        $this->setApplicationVersion('Tinebase', '10.39');
    }

    /**
     * update to 10.40
     *
     * tree_nodes make name column case sensitive
     */
    public function update_39()
    {
        if ($this->getTableVersion('tree_nodes') < 7) {
            if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Mysql) {
                $this->_backend->alterCol('tree_nodes', new Setup_Backend_Schema_Field_Xml('<field>
                        <name>name</name>
                        <type>text</type>
                        <length>255</length>
                        <notnull>true</notnull>
                        <collation>utf8_bin</collation>
                    </field>'));
            }
            $this->setTableVersion('tree_nodes', 7);
        }
        $this->setApplicationVersion('Tinebase', '10.40');
    }

    /**
     * update to 10.41
     *
     * pgsql - fix bigint issue
     */
    public function update_40()
    {
        $applications = Tinebase_Application::getInstance()->getApplications();
        /** @var Tinebase_Model_Application $application */
        foreach ($applications as $application) {
            try {
                $setupXml = Setup_Controller::getInstance()->getSetupXml($application->name);
            } catch (Setup_Exception_NotFound $senf) {
                Tinebase_Exception::log($senf);
                $setupXml = false;
            }
            if (!$setupXml || !$setupXml->tables || !$setupXml->tables->table) {
                continue;
            }
            foreach ($setupXml->tables->table as $key => $table) {
                /** @var SimpleXMLElement $field */
                foreach ($table->declaration->field as $field) {
                    if ($field->type == 'integer' && !empty($field->length) && $field->length > 19) {
                        $this->_backend->alterCol($table->name, new Setup_Backend_Schema_Field_Xml(
                            $field->asXML()
                        ));
                    }
                }
            }
        }

        $this->setApplicationVersion('Tinebase', '10.41');
    }

    /**
     * update to 10.42
     *
     * fix static scheduler issue
     */
    public function update_41()
    {
        /* schema update, also it will be done now in \Tinebase_Setup_Update_Release11::update_11
         * $scheduler = Tinebase_Core::getScheduler();
        $scheduler->removeTask('Tinebase_User/Group::syncUsers/Groups');
        Tinebase_Scheduler_Task::addAccountSyncTask($scheduler);*/
        $this->setApplicationVersion('Tinebase', '10.42');
    }

    /**
     * update to 10.43
     *
     * change configuration column to xprops in accounts
     */
    public function update_42()
    {
        if ($this->_backend->columnExists('configuration', 'accounts')) {
            $this->_backend->dropCol('accounts', 'configuration');
        }
        if (!$this->_backend->columnExists('xprops', 'accounts')) {
            $this->_backend->addCol('accounts', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>xprops</name>
                    <type>text</type>
                    <length>65535</length>
                </field>'));
        }
        if ($this->getTableVersion('accounts') < 14) {
            $this->setTableVersion('accounts', 14);
        }

        $this->setApplicationVersion('Tinebase', '10.43');
    }

    /**
     * update to 10.44
     *
     * add deleted_time to unique index for groups, roles, tree_nodes
     */
    public function update_43()
    {
        try {
            $this->_backend->dropIndex('groups', 'name');
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
        }
        $this->_backend->addIndex('groups', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>name</name>
                    <unique>true</unique>
                    <field>
                        <name>name</name>
                    </field>    
                    <field>
                        <name>deleted_time</name>
                    </field>
                </index>'));
        $this->setTableVersion('groups', 7);

        $this->_backend->dropIndex('roles', 'name');
        $this->_backend->addIndex('roles', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>name</name>
                    <unique>true</unique>
                    <field>
                        <name>name</name>
                    </field>    
                    <field>
                        <name>deleted_time</name>
                    </field>
                </index>'));
        $this->setTableVersion('roles', 4);

        if (!$this->_backend->columnExists('deleted_time', 'tree_nodes')) {
            $this->_backend->addCol('tree_nodes', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>deleted_time</name>
                    <type>datetime</type>
                </field>'));
        }

        try {
            $this->_backend->dropForeignKey('tree_nodes', 'tree_nodes::parent_id--tree_nodes::id');
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
        }
        $this->_backend->dropIndex('tree_nodes', 'parent_id-name');
        $this->_backend->addIndex('tree_nodes', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>parent_id-name</name>
                    <unique>true</unique>
                    <field>
                        <name>parent_id</name>
                    </field>
                    <field>
                        <name>name</name>
                    </field>
                    <field>
                        <name>deleted_time</name>
                    </field>
                </index>'));
        $this->_backend->addForeignKey('tree_nodes', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>tree_nodes::parent_id--tree_nodes::id</name>
                    <field>
                        <name>parent_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>tree_nodes</table>
                        <field>id</field>
                        <onupdate>cascade</onupdate>
                        <!-- add ondelete? -->
                    </reference>
                </index>'));
        $this->setTableVersion('tree_nodes', 8);

        $this->setApplicationVersion('Tinebase', '10.44');
    }

    /**
     * update to 10.45
     *
     * add do_acl to record_observer
     */
    public function update_44()
    {
        if (!$this->_backend->columnExists('do_acl', 'record_observer')) {
            $this->_backend->addCol('record_observer', new Setup_Backend_Schema_Field_Xml('<field>
                        <name>do_acl</name>
                        <type>boolean</type>
                        <default>true</default>
                        <notnull>true</notnull>
                    </field>'));
            $this->setTableVersion('record_observer', 6);
        }

        $this->setApplicationVersion('Tinebase', '10.45');
    }

    /**
     * update to 10.46
     *
     * fix pgsql index creation issue
     */
    public function update_45()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_11();
        $this->setApplicationVersion('Tinebase', '10.46');
    }

    /**
     * update to 10.47
     *
     * add acl table cleanup task
     */
    public function update_46()
    {
        /* schema update, will be done now in \Tinebase_Setup_Update_Release11::update_11
        $scheduler = Tinebase_Core::getScheduler();
        if (! $scheduler->hasTask('Tinebase_AclTablesCleanup')) {
            Tinebase_Scheduler_Task::addAclTableCleanupTask($scheduler);
        }*/
        $this->setApplicationVersion('Tinebase', '10.47');
    }

    /**
     * update to 10.48
     *
     * add full text index on customfield.value
     */
    public function update_47()
    {
        try {
            $this->_backend->addIndex('customfield', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>value</name>
                    <fulltext>true</fulltext>
                    <field>
                        <name>value</name>
                    </field>
                </index>'));
            $this->setTableVersion('customfield', 4);
        } catch (Exception $e) {
        }
        $this->setApplicationVersion('Tinebase', '10.48');
    }

    /**
     * update to 10.49
     *
     * add index(255) on customfield.value
     */
    public function update_48()
    {
        if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Mysql && $this->getTableVersion('customfield') < 5) {
            try {
                $this->_backend->addIndex('customfield', new Setup_Backend_Schema_Index_Xml('<index>
                        <name>value_index</name>
                        <length>255</length>
                        <field>
                            <name>value</name>
                        </field>
                    </index>'));
                $this->setTableVersion('customfield', 5);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        }
        $this->setApplicationVersion('Tinebase', '10.49');
    }

    /**
     * update to 10.50
     *
     * addFileSystemSanitizePreviewsTask
     */
    public function update_49()
    {
        /* schema update, will be done now in \Tinebase_Setup_Update_Release11::update_11
        $scheduler = Tinebase_Core::getScheduler();
        Tinebase_Scheduler_Task::addFileSystemSanitizePreviewsTask($scheduler); */
        $this->setApplicationVersion('Tinebase', '10.50');
    }

    /**
     * update to 10.51
     *
     * reimport all template files
     */
    public function update_50()
    {
        if (Tinebase_Core::isReplicationMaster()) {
            $fileSystem = Tinebase_FileSystem::getInstance();
            $basePath = $fileSystem->getApplicationBasePath(
                    'Tinebase',
                    Tinebase_FileSystem::FOLDER_TYPE_SHARED
                ) . '/export/templates';

            if (!$fileSystem->isDir($basePath)) {
                $fileSystem->createAclNode($basePath);
            }

            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;

            /** @var Tinebase_Model_Application $application */
            foreach (Tinebase_Application::getInstance()->getApplications() as $application) {
                $appPath = $path . $application->name . DIRECTORY_SEPARATOR . 'Export' . DIRECTORY_SEPARATOR . 'templates';
                if (!is_dir($appPath)) {
                    continue;
                }

                $templateAppPath = $basePath . '/' . $application->name;
                if (!$fileSystem->isDir($templateAppPath)) {
                    $fileSystem->createAclNode($templateAppPath);
                }

                foreach (new DirectoryIterator($appPath) as $item) {
                    if (!$item->isFile()) {
                        continue;
                    }
                    if (false === ($content = file_get_contents($item->getPathname()))) {
                        Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' Could not import template: ' . $item->getPathname());
                        continue;
                    }
                    if (false === ($file = $fileSystem->fopen($templateAppPath . '/' . $item->getFileName(), 'w'))) {
                        Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' could not open ' . $templateAppPath . '/' . $item->getFileName() . ' for writting');
                        continue;
                    }
                    fwrite($file, $content);
                    if (true !== $fileSystem->fclose($file)) {
                        Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' write to ' . $templateAppPath . '/' . $item->getFileName() . ' did not succeed');
                        continue;
                    }
                }
            }
        }

        $this->setApplicationVersion('Tinebase', '10.51');
    }

    /**
     * update to 10.52
     *
     * add filesystem notify quota task to scheduler
     */
    public function update_51()
    {
        // scheduler_task table might not be available yet - task is added in \Tinebase_Setup_Update_Release11::update_11
//        $scheduler = Tinebase_Core::getScheduler();
//        Tinebase_Scheduler_Task::addFileSystemNotifyQuotaTask($scheduler);

        $this->setApplicationVersion('Tinebase', '10.52');
    }

    /**
     * update to 10.53
     *
     * make sure setup user has admin rights
     */
    public function update_52()
    {
        $update = new Tinebase_Setup_Update_Release9($this->_backend);
        $update->update_12();

        $this->setApplicationVersion('Tinebase', '10.53');
    }

    /**
     * update to 10.54
     *
     * update mariadb if required
     */
    public function update_53()
    {
        if (Setup_Backend_Factory::factory()->supports('mariadb >= 10.0.5')) {
            $failures = Setup_Controller::getInstance()->upgradeMysql564();
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' upgradeMysql564 failures: '
                    . print_r($failures, true));
        }

        $this->setApplicationVersion('Tinebase', '10.54');
    }

    /**
     * update to 10.55
     *
     * make file objects unique
     */
    public function update_54()
    {
        $update = new Tinebase_Setup_Update_Release9($this->_backend);
        $update->update_13();

        $this->setApplicationVersion('Tinebase', '10.55');
    }

    /**
     * update to 10.56
     *
     * eventually add missing indexed_hash column to tree_nodes
     */
    public function update_55()
    {
        if (!$this->_backend->columnExists('indexed_hash', 'tree_nodes')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                            <name>indexed_hash</name>
                            <type>text</type>
                            <length>40</length>
                        </field>');

            $this->_backend->addCol('tree_nodes', $declaration);
        }

        $this->setApplicationVersion('Tinebase', '10.56');
    }

    /**
     * update to 10.57
     *
     * removing db prefix from application_tables if present
     */
    public function update_56()
    {
        $command = Tinebase_Backend_Sql_Command::factory($this->_db);
        foreach ($this->_db->select()->from(SQL_TABLE_PREFIX . 'application_tables')->where(
                $this->_db->quoteIdentifier('name') . ' ' . $command->getLike() . $this->_db->quoteInto(' ?',
                    SQL_TABLE_PREFIX . '%'))->query()->fetchAll(Zend_DB::FETCH_ASSOC) as $row) {
            $this->_db->delete(SQL_TABLE_PREFIX . 'application_tables', $this->_db->quoteIdentifier('application_id') .
                $this->_db->quoteInto(' = ? AND ', $row['application_id']) . $this->_db->quoteIdentifier('name') .
                $this->_db->quoteInto(' = ?', $row['name']));
            $this->_db->insert(SQL_TABLE_PREFIX . 'application_tables',[
                'application_id'    => $row['application_id'],
                'name'              => substr($row['name'], strlen(SQL_TABLE_PREFIX)),
                'version'           => $row['version']
            ]);
        }

        $this->setApplicationVersion('Tinebase', '10.57');
    }

    /**
     * update to 10.58
     *
     * change unique key parent_id - name - deleted_time so that it really works
     */
    public function update_57()
    {
        // turn on FS modLog temporarily
        $oldFsConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::FILESYSTEM);
        if (!$oldFsConfig) throw new Tinebase_Exception('did not find filesystem config!');
        if (!$oldFsConfig->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            $fsConfig = clone $oldFsConfig;
            $fsConfig->unsetParent();
            $fsConfig->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE} = true;
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::FILESYSTEM, $fsConfig);
        }

        $doSleep = false;
        $fsController = Tinebase_FileSystem::getInstance();
        $fsController->resetBackends();

        do {
            $cont = false;
            $stmt = $this->_db->select()->from(['n1' => SQL_TABLE_PREFIX . 'tree_nodes'], 'n1.id')
                ->join(['n2' => SQL_TABLE_PREFIX . 'tree_nodes'], 'n1.parent_id = n2.parent_id AND '.
                    'n1.name = n2.name AND n1.id <> n2.id AND (n1.deleted_time IS NULL OR n1.deleted_time =
                    "1970-01-01 00:00:00") AND (n2.deleted_time IS NULL OR n2.deleted_time = "1970-01-01 00:00:00")',
                    ['id2' => 'n2.id'])->limit(1000)->query();

            if ($doSleep) sleep(1);
            $doSleep = true;
            $namesProcessed = [];

            foreach ($stmt->fetchAll(Zend_Db::FETCH_ASSOC) as $row) {
                try {
                    $node = $fsController->get($row['id']);
                } catch(Tinebase_Exception_NotFound $tenf) { continue; }
                $key = $node->parent_id . $node->name;
                if (isset($namesProcessed[$key])) continue;
                $namesProcessed[$key] = true;

                try {
                    $node2 = $fsController->get($row['id2']);
                } catch(Tinebase_Exception_NotFound $tenf) { continue; }
                if ($node->type === Tinebase_Model_Tree_FileObject::TYPE_FILE && $node2->type  ===
                        Tinebase_Model_Tree_FileObject::TYPE_FILE) {
                    if (empty($node->hash) || !is_file($node->getFilesystemPath())) {
                        // delete $node
                    } elseif (empty($node2->hash) || !is_file($node2->getFilesystemPath())) {
                        // delete $node2
                        $node = $node2;
                    } elseif ($node2->last_modified_time < $node->last_modified_time) {
                        // delete node2
                        $node = $node2;
                    }
                } else {
                    if ($node2->last_modified_time < $node->last_modified_time) {
                        // delete node2
                        $node = $node2;
                    }
                }

                if ($node->type === Tinebase_Model_Tree_FileObject::TYPE_FILE) {
                    $fsController->deleteFileNode($node);
                } else {
                    $fsController->_getTreeNodeBackend()->softDelete($node->getId());

                    // delete object only, if no other tree node refers to it, really?
                    if ($fsController->_getTreeNodeBackend()->getObjectCount($node->object_id) == 0) {
                        $fsController->getFileObjectBackend()->softDelete($node->object_id);
                    }
                }

                $cont = true;
            }
        } while ($cont);

        $this->_db->update(SQL_TABLE_PREFIX . 'tree_nodes', ['deleted_time' => '1970-01-01 00:00:00'],
            'deleted_time IS NULL');

        $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>deleted_time</name>
                    <type>datetime</type>
                    <default>1970-01-01 00:00:00</default>
                    <notnull>true</notnull>
                </field>');

        $this->_backend->alterCol('tree_nodes', $declaration);
        if ($this->getTableVersion('tree_nodes') < 9) {
            $this->setTableVersion('tree_nodes', 9);
        }

        if (isset($fsConfig)) {
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::FILESYSTEM, $oldFsConfig);
            $fsController->resetBackends();
        }

        $this->setApplicationVersion('Tinebase', '10.58');
    }

    /**
     * update to 10.59
     *
     * fix role name unique key
     */
    public function update_58()
    {
        $counter = 1;
        $processedIds = [];
        do {
            $cont = false;
            $stmt = $this->_db->select()->from(['r1' => SQL_TABLE_PREFIX . 'roles'], ['r1.id', 'r1.name'])
                ->join(['r2' => SQL_TABLE_PREFIX . 'roles'], 'r1.name = r2.name AND r1.id <> r2.id AND
                    (r1.deleted_time IS NULL OR r1.deleted_time = "1970-01-01 00:00:00") AND
                    (r2.deleted_time IS NULL OR r2.deleted_time = "1970-01-01 00:00:00")',
                    ['id2' => 'r2.id'])->limit(1000)->query();
            foreach ($stmt->fetchAll(Zend_Db::FETCH_NUM) as $row) {
                if (isset($processedIds[$row[0]]) || isset($processedIds[$row[2]])) {
                    continue;
                }
                $processedIds[$row[0]] = true;
                $processedIds[$row[2]] = true;
                $cont = true;
                $this->_db->update(SQL_TABLE_PREFIX . 'roles', ['name' => $row[1] . '_' . $counter ],
                    'id = "' . $row[0] . '"');
            }
            $counter += 1;
            $processedIds = [];
        } while ($cont && $counter < 100);

        $this->_db->update(SQL_TABLE_PREFIX . 'roles', ['deleted_time' => '1970-01-01 00:00:00'],
            'deleted_time IS NULL');

        $this->_backend->alterCol('roles', new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>deleted_time</name>
                <type>datetime</type>
                <notnull>true</notnull>
                <default>1970-01-01 00:00:00</default>
            </field>'));

        if ($this->getTableVersion('roles') < 5) {
            $this->setTableVersion('roles', 5);
        }

        $this->setApplicationVersion('Tinebase', '10.59');
    }

    /**
     * update to 11.0
     */
    public function update_59()
    {
        $this->setApplicationVersion('Tinebase', '11.0');
    }

    public function setContainerModels()
    {
        $models = [];
        $containers = $this->_db->select()->from(SQL_TABLE_PREFIX . 'container', ['id', 'application_id'])
            ->where($this->_db->quoteIdentifier('model') . ' IS NULL OR ' . $this->_db->quoteIdentifier('model')
                . ' = ' . $this->_db->quote(''))->query()->fetchAll(Zend_DB::FETCH_ASSOC);

        foreach ($containers as $container) {
            if (!isset($models[$container['application_id']])) {
                throw new Tinebase_Exception('you have to update to the max minor version of each major version. ' .
                    'Do not make major version jumps. This is what happens. No other way than doing it right.');
            }

            if ($models[$container['application_id']]) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Setting model ' . $models[$container['application_id']] . ' for container ' . $container['id']);
                }
                $this->_db->update(SQL_TABLE_PREFIX . 'container', ['model' => $models[$container['application_id']]],
                    $this->_db->quoteInto('id = ?', $container['id']));
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' Could not find default model for app id ' . $container['application_id']);
                }
            }
        }
    }
}
