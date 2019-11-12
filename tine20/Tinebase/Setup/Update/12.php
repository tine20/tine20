<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE012_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE012_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE012_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE012_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE012_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE012_UPDATE007 = __CLASS__ . '::update007';
    const RELEASE012_UPDATE008 = __CLASS__ . '::update008';
    const RELEASE012_UPDATE009 = __CLASS__ . '::update009';
    const RELEASE012_UPDATE010 = __CLASS__ . '::update010';
    const RELEASE012_UPDATE011 = __CLASS__ . '::update011';
    const RELEASE012_UPDATE012 = __CLASS__ . '::update012';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT => [
            self::RELEASE012_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE012_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ],
        ],

        self::PRIO_TINEBASE_STRUCTURE => [
            self::RELEASE012_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE012_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE012_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE012_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
            self::RELEASE012_UPDATE012          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update012',
            ],
        ],

        self::PRIO_TINEBASE_UPDATE        => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE012_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE012_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
            self::RELEASE012_UPDATE010          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update010',
            ],
            self::RELEASE012_UPDATE011          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update011',
            ],
        ],
    ];

    public function update001()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_45();
        $this->addApplicationUpdate('Tinebase', '12.19', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        // clear open transactions
        Tinebase_TransactionManager::getInstance()->rollBack();
        try {
            Setup_SchemaTool::updateAllSchema();
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Schema update failed - retrying once ...');
            Setup_Controller::getInstance()->clearCache();
            sleep(5);
            Setup_SchemaTool::updateAllSchema();
        }
        $this->addApplicationUpdate('Tinebase', '12.20', self::RELEASE012_UPDATE002);
    }

    public function update003()
    {
        $this->addPreviewStatusAndErrorCount();
        $this->addApplicationUpdate('Tinebase', '12.21', self::RELEASE012_UPDATE003);
    }

    public function addPreviewStatusAndErrorCount()
    {
        if (!$this->_backend->columnExists('preview_status', 'tree_filerevisions')) {
            $this->_backend->addCol('tree_filerevisions', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>preview_status</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>'));
        }

        if (!$this->_backend->columnExists('preview_error_count', 'tree_filerevisions')) {
            $this->_backend->addCol('tree_filerevisions', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>preview_error_count</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>'));
        }

        if ($this->getTableVersion('tree_filerevisions') < 4) {
            $this->setTableVersion('tree_filerevisions', 4);
        }
    }

    public function update004()
    {
        $this->addApplicationUpdate('Tinebase', '12.22', self::RELEASE012_UPDATE004);
    }

    public function update005()
    {
        if ($this->getTableVersion('groups') < 8) {
            $this->_backend->alterCol('groups', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>email</name>
                    <type>text</type>
                    <length>255</length>
                </field>'));
            $this->setTableVersion('groups', 8);
        }

        $this->addApplicationUpdate('Tinebase', '12.23', self::RELEASE012_UPDATE005);
    }

    public function update006()
    {
        if ($this->getTableVersion('accounts') < 16) {
            $this->_backend->addCol('accounts', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>password_must_change</name>
                    <type>boolean</type>                 
                    <default>false</default>
                </field>'));
            $this->setTableVersion('accounts', 16);
        }

        $this->addApplicationUpdate('Tinebase', '12.24', self::RELEASE012_UPDATE006);
    }

    public function update007()
    {
        $fs = Tinebase_FileSystem::getInstance();
        foreach (Tinebase_Core::getDb()->query('SELECT tnchild.id, tnparent.acl_node FROM ' .
                SQL_TABLE_PREFIX . 'tree_nodes as tnchild JOIN ' . SQL_TABLE_PREFIX .
                'tree_nodes as tnparent ON tnchild.parent_id = tnparent.id WHERE tnchild.is_deleted = 1 AND ' .
                'tnparent.is_deleted = 0 AND (tnparent.acl_node <> tnchild.acl_node OR (tnparent.acl_node IS NOT NULL '
                . 'AND tnchild.acl_node IS NULL))')->fetchAll() as $row) {

            if (empty($row['acl_node'])) continue;

            $node = $fs->get($row['id'], true);

            $r = new ReflectionMethod(Tinebase_FileSystem::class, '_recursiveInheritPropertyUpdate');
            $r->setAccessible(true);
            $r->invoke($fs, $node, 'acl_node', $row['acl_node'], $node->acl_node, true, true);
            $node->acl_node = $row['acl_node'];
            $fs->_getTreeNodeBackend()->update($node);
        }

        $this->addApplicationUpdate('Tinebase', '12.25', self::RELEASE012_UPDATE007);
    }

    public function update008()
    {
        if ($this->getTableVersion('groups') < 9) {
            $this->_backend->addCol('groups', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>account_only</name>
                    <type>boolean</type>                 
                    <default>true</default>
                </field>'));
            $this->setTableVersion('groups', 9);
        }

        $this->addApplicationUpdate('Tinebase', '12.26', self::RELEASE012_UPDATE008);
    }

    public function update009()
    {
        // clear open transactions
        Tinebase_TransactionManager::getInstance()->rollBack();
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_Tree_RefLog::class,
        ]);
        $this->addApplicationUpdate('Tinebase', '12.27', self::RELEASE012_UPDATE009);
    }

    public function update010()
    {
        $this->addApplicationUpdate('Tinebase', '12.28', self::RELEASE012_UPDATE010);
    }

    public function update011()
    {
        $scheduler = new Tinebase_Backend_Scheduler();
        try {
            /** @var Tinebase_Model_SchedulerTask $task */
            $task = $scheduler->getByProperty('Tinebase_FileSystem::avScan', 'name');
            $task->config->setCron(Tinebase_Scheduler_Task::TASK_TYPE_WEEKLY);
            $scheduler->update($task);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Scheduler_Task::addFileSystemAVScanTask($scheduler);
        }
        $this->addApplicationUpdate('Tinebase', '12.29', self::RELEASE012_UPDATE011);
    }

    public function update012()
    {
        // clear open transactions
        Tinebase_TransactionManager::getInstance()->rollBack();
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_Tree_RefLog::class,
        ]);
        $this->addApplicationUpdate('Tinebase', '12.30', self::RELEASE012_UPDATE012);
    }
}
