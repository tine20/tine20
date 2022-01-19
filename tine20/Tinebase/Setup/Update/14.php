<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2020-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2021.11 (ONLY!)
 */
class Tinebase_Setup_Update_14 extends Setup_Update_Abstract
{
    const RELEASE014_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE014_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE014_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE014_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE014_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE014_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE014_UPDATE007 = __CLASS__ . '::update007';
    const RELEASE014_UPDATE008 = __CLASS__ . '::update008';
    const RELEASE014_UPDATE009 = __CLASS__ . '::update009';
    const RELEASE014_UPDATE010 = __CLASS__ . '::update010';
    const RELEASE014_UPDATE011 = __CLASS__ . '::update011';
    const RELEASE014_UPDATE012 = __CLASS__ . '::update012';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_STRUCTURE   => [
            self::RELEASE014_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE014_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE014_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE014_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE014_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE014_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE014_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
            self::RELEASE014_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
            self::RELEASE014_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ],
            self::RELEASE014_UPDATE010          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update010',
            ],
            self::RELEASE014_UPDATE011          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update011',
            ],
            self::RELEASE014_UPDATE012          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update012',
            ],
        ],
        self::PRIO_TINEBASE_UPDATE      => [
        ]
    ];

    public function update001()
    {
        try {
            Setup_SchemaTool::updateSchema([
                Tinebase_Model_Tree_FileObject::class,
                Tinebase_Model_Tree_Node::class
            ]);
        } catch (Exception $e) {
            // sometimes this fails with: "PDOException: SQLSTATE[42000]: Syntax error or access violation:
            //                            1091 Can't DROP FOREIGN KEY `main_tree_nodes::parent_id--tree_nodes::id`;
            //                            check that it exists"
            // -> maybe some doctrine problem?
            // -> we just try it again
            Tinebase_Exception::log($e);
            Setup_SchemaTool::updateSchema([
                Tinebase_Model_Tree_FileObject::class,
                Tinebase_Model_Tree_Node::class
            ]);
        }

        $this->addApplicationUpdate('Tinebase', '14.1', self::RELEASE014_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_AuthToken::class,
        ]);

        $this->addApplicationUpdate('Tinebase', '14.2', self::RELEASE014_UPDATE002);
    }

    public function update003()
    {
        if (!$this->_backend->columnExists('mfa_configs', 'accounts')) {
            $this->_backend->addCol('accounts', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>mfa_configs</name>
                    <type>text</type>
                    <length>65535</length>
                </field>'));
        }
        if ($this->_backend->columnExists('pin', 'accounts')) {
            $mfas = Tinebase_Config::getInstance()->{Tinebase_Config::MFA};
            if ($mfas && $mfas->records && ($pinMfa = $mfas->records
                    ->find(Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS, Tinebase_Auth_MFA_PinAdapter::class))) {
                $userCtrl = Tinebase_User::getInstance();
                $failcount = 0;
                foreach ($this->getDb()
                         ->query('select `id`, `pin` from ' . SQL_TABLE_PREFIX . 'accounts WHERE LENGTH(`pin`) > 0')
                         ->fetchAll(Zend_Db::FETCH_ASSOC) as $row) {
                    try {
                        $user = $userCtrl->getUserById($row['id'], Tinebase_Model_FullUser::class);
                    } catch (Exception $e) {
                        continue;
                    }
                    $user->mfa_configs = new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class, [[
                        Tinebase_Model_MFA_UserConfig::FLD_ID => 'pin',
                        Tinebase_Model_MFA_UserConfig::FLD_NOTE => 'pin',
                        Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => $pinMfa->{Tinebase_Model_MFA_Config::FLD_ID},
                        Tinebase_Model_MFA_UserConfig::FLD_CONFIG => new Tinebase_Model_MFA_PinUserConfig(),
                        Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS => Tinebase_Model_MFA_PinUserConfig::class,
                    ]]);
                    $user->mfa_configs->getFirstRecord()->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}
                        ->{Tinebase_Model_MFA_PinUserConfig::FLD_HASHED_PIN} = $row['pin'];
                    try {
                        $userCtrl->updateUser($user);
                    } catch (Exception $e) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::ERR))
                            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Problem with User '
                                . $row['id'] . ' when trying to convert PIN ('. $row['pin']  . ')');
                        Tinebase_Exception::log($e);
                        $failcount++;
                        if ($failcount > 10) {
                            throw new Setup_Exception('Too many broken users! Aborting...');
                        }
                    }
                }
            }
            $this->_backend->dropCol('accounts', 'pin');
        }

        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'config WHERE application_id = ' .
            $this->getDb()->quote(Tinebase_Core::getTinebaseId()) . ' AND name = "areaLocks"');

        if ($this->getTableVersion('accounts') < 17) {
            $this->setTableVersion('accounts', 17);
        }

        $this->addApplicationUpdate('Tinebase', '14.3', self::RELEASE014_UPDATE003);
    }

    public function update004()
    {
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'accounts SET deleted_time = "1970-01-01 00:00:00" WHERE deleted_time IS NULL');

        $this->_backend->alterCol('accounts', new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>deleted_time</name>
                <type>datetime</type>
                <notnull>true</notnull>
                <default>1970-01-01 00:00:00</default>
            </field>'));
        $this->_backend->dropIndex('accounts', 'login_name');
        $this->_backend->addIndex('accounts', new Setup_Backend_Schema_Index_Xml(
            '<index>
                <name>login_name</name>
                <unique>true</unique>
                <field>
                    <name>login_name</name>
                </field>
                <field>
                    <name>deleted_time</name>
                </field>
            </index>'
        ));

        $this->addApplicationUpdate('Tinebase', '14.4', self::RELEASE014_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([Tinebase_Model_MunicipalityKey::class]);
        $this->addApplicationUpdate('Tinebase', '14.5', self::RELEASE014_UPDATE005);
    }

    public function update006()
    {
        if ($this->getTableVersion('notes') < 3) {
            $this->_backend->alterCol('notes', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>record_model</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>'));
            $this->setTableVersion('notes', 3);
        }

        $this->addApplicationUpdate('Tinebase', '14.6', self::RELEASE014_UPDATE006);
    }

    public function update007()
    {
        if (! $this->_backend->columnExists('container_id', 'importexport_definition')) {
            $this->_backend->addCol('importexport_definition', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>container_id</name>
                    <type>text</type>
                    <length>40</length>
                </field>'));
        }

        $defaultContainer = null;
        if (Tinebase_Core::isReplica()) {
            $tries = 0;
            do {
                Tinebase_Timemachine_ModificationLog::getInstance()->readModificationLogFromMaster();
                try {
                    $defaultContainer = Tinebase_ImportExportDefinition::getDefaultImportExportContainer();
                    break;
                } catch (Exception $e) {}
            } while (++$tries < 100);
        } else {
            $defaultContainer = Tinebase_ImportExportDefinition::getDefaultImportExportContainer();
        }
        if (! $defaultContainer) {
            throw new Setup_Exception('could not find default container');
        }

        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'importexport_definition SET container_id = "' .
            $defaultContainer->getId() . '"');

        $this->_backend->alterCol('importexport_definition', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>container_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>'));

        $this->addApplicationUpdate('Tinebase', '14.7', self::RELEASE014_UPDATE007);
    }

    public function update008()
    {
        $this->addApplicationUpdate('Tinebase', '14.8', self::RELEASE014_UPDATE008);
    }

    public function update009()
    {
        // update needs this - it waits for table metadata lock for a very long time otherwise ...
        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . Tinebase_Model_MunicipalityKey::TABLE_NAME
            . ' SET deleted_time = "1970-01-01 00:00:00" WHERE deleted_time IS NULL');
        Setup_SchemaTool::updateSchema([Tinebase_Model_MunicipalityKey::class]);
        $this->addApplicationUpdate('Tinebase', '14.9', self::RELEASE014_UPDATE009);
    }

    /** recreate alarm task for php8 */
    public function update010()
    {
        if (Tinebase_Scheduler::getInstance()->hasTask('Tinebase_Alarm')) {
            Tinebase_Scheduler::getInstance()->removeTask('Tinebase_Alarm');
        }
        Tinebase_Scheduler_Task::addAlarmTask(Tinebase_Scheduler::getInstance());
        $this->addApplicationUpdate('Tinebase', '14.10', self::RELEASE014_UPDATE010);
    }

    public function update011()
    {
        if ($this->getTableVersion('tags') < 10) {
            $this->_backend->addCol('tags', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>system_tag</name>
                    <type>boolean</type>
                    <notnull>true</notnull>
                    <default>false</default>
                </field>'));
            $this->setTableVersion('tags', 10);
        };

        $this->addApplicationUpdate('Tinebase', '14.11', self::RELEASE014_UPDATE011);
    }


    public function update012()
    {
        Setup_SchemaTool::updateSchema([Tinebase_Model_WebauthnPublicKey::class]);
        $this->addApplicationUpdate('Tinebase', '13.2', self::RELEASE014_UPDATE012); // yes, 13 something, just any old version number works
    }
}
