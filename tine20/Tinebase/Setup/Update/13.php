<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this ist 2020.11 (ONLY!)
 */
class Tinebase_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE013_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE013_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE013_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE013_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE013_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE013_UPDATE007 = __CLASS__ . '::update007';
    const RELEASE013_UPDATE008 = __CLASS__ . '::update008';
    const RELEASE013_UPDATE009 = __CLASS__ . '::update009';
    const RELEASE013_UPDATE010 = __CLASS__ . '::update010';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT => [
            self::RELEASE013_UPDATE007           => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
        ],
        self::PRIO_TINEBASE_STRUCTURE   => [
            self::RELEASE013_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE013_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE013_UPDATE008           => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
        ],
        self::PRIO_TINEBASE_UPDATE      => [
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE013_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE013_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE013_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE013_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ],
            self::RELEASE013_UPDATE010          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update010',
            ],
        ]
    ];

    public function update001()
    {
        $this->addApplicationUpdate('Tinebase', '13.0', self::RELEASE013_UPDATE001);
    }

    public function update002()
    {
        if ($this->getTableVersion('importexport_definition') < 12) {
            $this->_backend->addCol('importexport_definition', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>filter</name>
                    <type>text</type>
                    <length>16000</length>
                </field>'));
            $this->setTableVersion('importexport_definition', 12);
        }

        $this->addApplicationUpdate('Tinebase', '13.1', self::RELEASE013_UPDATE002);
    }

    public function update003()
    {
        if (Tinebase_Application::getInstance()->isInstalled('Felamimail')) {
            Felamimail_Controller_Account::getInstance()->convertAccountsToSaveUserIdInXprops();
        }

        Admin_Controller_User::getInstance()->convertAccountsToSaveUserIdInXprops();

        // activate config
        Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS} = true;

        $this->addApplicationUpdate('Tinebase', '13.2', self::RELEASE013_UPDATE003);
    }

    public function update004()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_LogEntry::class,
        ]);
        Tinebase_Scheduler_Task::addLogEntryCleanUpTask(Tinebase_Core::getScheduler());
        $this->addApplicationUpdate('Tinebase', '13.3', self::RELEASE013_UPDATE004);
    }

    /**
     * move dblclick action config to tinebase
     * 
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Zend_Db_Adapter_Exception
     */
    public function update005()
    {
        $db = $this->getDb();
        $db->update(
            SQL_TABLE_PREFIX . 'preferences', [
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
                'name' => Tinebase_Preference::FILE_DBLCLICK_ACTION
            ],
            $db->quoteInto('name = ?', 'dbClickAction')
        );
        
        $this->addApplicationUpdate('Tinebase', '13.4', self::RELEASE013_UPDATE005);
    }

    /**
     * delete too big entries from external_fulltext table
     */
    public function update006()
    {
        $maxBlobSize = Tinebase_Fulltext_Indexer::getMaxBlobSize();
        if ($maxBlobSize > 0) {
            $db = Tinebase_Core::getDb();
            $deleted = $db->delete(SQL_TABLE_PREFIX . 'external_fulltext',
                $db->quoteInto('LENGTH(text_data) > ?', $maxBlobSize)
            );
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Removed ' . $deleted
                . ' records from external_fulltext which are bigger than ' . $maxBlobSize);
        }
        $this->addApplicationUpdate('Tinebase', '13.5', self::RELEASE013_UPDATE006);
    }

    public function update007()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $db = Tinebase_Core::getDb();
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'container SET type = "personal" WHERE type IS NULL');
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'container SET model = "" WHERE model IS NULL');
        // remove obsolete containers that have been created by an old bug
        $db->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'container WHERE model = "Felamimail_Model_Message"');

        foreach (Tinebase_Application::getInstance()->getApplications() as $app) {
            try {
                Tinebase_Container::getInstance()->deleteDuplicateContainer($app->name);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        }

        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'container SET deleted_time = "1970-01-01 00:00:00" WHERE deleted_time IS NULL');
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'container SET owner_id = "" WHERE owner_id IS NULL');

        while (count($rows = $db->query('select group_concat(id), count(id) AS c from ' . SQL_TABLE_PREFIX . 'container where deleted_time > "1970-01-01 00:00:00" group by application_id, name, owner_id, model, deleted_time having c > 1')
                ->fetchAll(Zend_Db::FETCH_NUM)) > 0) {
            foreach ($rows as $row) {
                foreach(explode(',', $row[0]) as $key => $id) {
                    if (0 === $key) continue;
                    $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'container set deleted_time = DATE_ADD(deleted_time, INTERVAL ' . $key . ' SECOND) WHERE id = "' . $id .'"');
                }
            }
        }
        while (count($rows = $db->query('select group_concat(id), count(id) AS c from ' . SQL_TABLE_PREFIX . 'container group by application_id, name, owner_id, model, deleted_time having c > 1')
                ->fetchAll(Zend_Db::FETCH_NUM)) > 0) {
            foreach ($rows as $row) {
                foreach(explode(',', $row[0]) as $key => $id) {
                    if (0 === $key) continue;
                    $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'container set name = CONCAT(name, " ('.$key.')") WHERE id = "' . $id .'"');
                }
            }
        }

        Setup_SchemaTool::updateSchema([Tinebase_Model_Container::class]);

        $this->addApplicationUpdate('Tinebase', '13.6', self::RELEASE013_UPDATE007);
    }

    public function update008()
    {
        $this->_db->update(SQL_TABLE_PREFIX . 'importexport_definition', ['deleted_time' => '1970-01-01 00:00:00'],
            'deleted_time IS NULL');

        $this->_backend->alterCol('importexport_definition', new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>deleted_time</name>
                <type>datetime</type>
                <notnull>true</notnull>
                <default>1970-01-01 00:00:00</default>
            </field>'));

        try {
            $this->_backend->dropIndex('importexport_definition', 'model-name-type');
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
        }

        $this->_backend->addIndex('importexport_definition', new Setup_Backend_Schema_Index_Xml(
            '  <index>
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
                    <field>
                        <name>deleted_time</name>
                    </field>
                </index>'));

        if ($this->getTableVersion('importexport_definition') < 12) {
            $this->setTableVersion('importexport_definition', 12);
        }
        $this->addApplicationUpdate('Tinebase', '13.7', self::RELEASE013_UPDATE008);
    }

    public function update009()
    {
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX .
            'container SET deleted_time = NOW() WHERE is_deleted = 1 and deleted_time = "1970-01-01 00:00:00"');
        $this->addApplicationUpdate('Tinebase', '13.8', self::RELEASE013_UPDATE009);
    }

    /**
     * remove leading and trailing spaces from tree_nodes.name
     */
    public function update010()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        try {
            $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX .
                'tree_nodes SET name = TRIM(name) WHERE is_deleted = 0 and (name like " %" or name like "% ")');
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Could not trim node names: ' . $e);
        }
        $this->addApplicationUpdate('Tinebase', '13.9', self::RELEASE013_UPDATE010);
    }
}
