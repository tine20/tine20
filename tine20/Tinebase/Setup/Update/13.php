<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
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
        Setup_SchemaTool::updateSchema([Tinebase_Model_Container::class]);

        $this->addApplicationUpdate('Tinebase', '13.6', self::RELEASE013_UPDATE007);
    }
}
