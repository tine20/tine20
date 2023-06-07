<?php

/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2022.11 (ONLY!)
 */
class Felamimail_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE015_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE015_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE015_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE015_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE015_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE015_UPDATE007 = __CLASS__ . '::update007';
    const RELEASE015_UPDATE008 = __CLASS__ . '::update008';
    
    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE015_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE015_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE=> [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE015_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE015_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE015_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE015_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
            self::RELEASE015_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Felamimail', '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        $declaration = new Setup_Backend_Schema_Field_Xml('
              <field>
                    <name>date_enabled</name>
                    <type>boolean</type>
                    <default>false</default>
                    <notnull>true</notnull>
                </field>
        ');

        $this->_backend->addCol('felamimail_sieve_vacation', $declaration);
        $this->setTableVersion('felamimail_sieve_vacation', 5);
        $this->addApplicationUpdate('Felamimail', '15.1', self::RELEASE015_UPDATE001);
    }

    public function update002()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if ($this->getTableVersion('felamimail_account') < 28) {
            $accounts = Felamimail_Controller_Account::getInstance()->getBackend()->getAll();

            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>sieve_notification_move</name>
                    <length>255</length>
                    <type>text</type>
                    <default>AUTO</default>
                </field>
            ');
            $this->_backend->alterCol('felamimail_account', $declaration);
            $this->setTableVersion('felamimail_account', 28);

            foreach ($accounts as $account) {
                if (!isset($account['sieve_notification_move'])) {
                    continue;
                }

                if ($account->sieve_notification_move == '1') {
                    $account->sieve_notification_move = Felamimail_Model_Account::SIEVE_NOTIFICATION_MOVE_ACTIVE;
                }

                if ($account->sieve_notification_move == '0') {
                    $account->sieve_notification_move = Felamimail_Model_Account::SIEVE_NOTIFICATION_MOVE_INACTIVE;
                }

                if (empty($account->sieve_notification_move)) {
                    $account->sieve_notification_move = Felamimail_Model_Account::SIEVE_NOTIFICATION_MOVE_AUTO;
                }

                Felamimail_Controller_Account::getInstance()->getBackend()->update($account);
            }
        }

        $this->addApplicationUpdate('Felamimail', '15.2',self::RELEASE015_UPDATE002);
    }


    public function update003()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if ($this->getTableVersion('felamimail_account') < 29) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>message_sent_copy_behavior</name>
                    <type>text</type>
                    <length>255</length>
                    <default>sent</default>
                </field>
            ');
            $this->_backend->addCol('felamimail_account', $declaration);
            $this->setTableVersion('felamimail_account', 29);
        }
        $db = $this->getDb();
        $db->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'preferences WHERE name = "autoAttachNote"');

        $this->addApplicationUpdate('Felamimail', '15.3', self::RELEASE015_UPDATE003);
    }

    public function update004()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Felamimail_Model_AttachmentCache::class,
        ]);

        if ($this->getTableVersion(Felamimail_Model_AttachmentCache::TABLE_NAME) < 2) {
            $this->setTableVersion(Felamimail_Model_AttachmentCache::TABLE_NAME, 2);
        }

        $this->getDb()->update(SQL_TABLE_PREFIX . Felamimail_Model_AttachmentCache::TABLE_NAME, [
            Felamimail_Model_AttachmentCache::FLD_TTL => Tinebase_DateTime::now()->addWeek(2)->toString()
        ]);

        Felamimail_Setup_Initialize::addPruneAttachmentCacheSchedule();

        $this->addApplicationUpdate('Felamimail', '15.4', self::RELEASE015_UPDATE004);
    }
    
    public function update005()
    {
        Tinebase_Core::getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'felamimail_folder' . ' set imap_lastmodseq = 0 where imap_totalcount > 500 and supports_condstore = 1');
        $this->addApplicationUpdate('Felamimail', '15.5', self::RELEASE015_UPDATE005);

    }

    public function update006()
    {
        $stateRepo = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_State',
            'tableName' => 'state',
        ));

        $states = $stateRepo->search(new Tinebase_Model_StateFilter(array(
            array('field' => 'state_id', 'operator' => 'in', 'value' => [
                "Felamimail-Message-GridPanel-Grid",
                "Felamimail-Message-GridPanel-Grid-SendFolder",
            ]),
        )));

        foreach ($states as $state) {
            $decodedState = Tinebase_State::decode($state->data);
            $spliceAt = 3;
            $columns = $decodedState['columns'];
            $column = end($columns);

            if ($column['id'] == 'tags') {
                array_splice($columns, $spliceAt, 0, [['id' => 'tags', 'width' => 25]]);
                array_pop($columns);
            }
            $decodedState['columns'] = $columns;
            $state->data = Tinebase_State::encode($decodedState);
            $stateRepo->update($state);
        }

        $this->addApplicationUpdate('Felamimail', '15.6', self::RELEASE015_UPDATE006);
    }

    public function update007()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if ($this->getTableVersion('felamimail_cache_message') < 14) {            
            // truncate email cache to make this go faster
            Felamimail_Controller::getInstance()->truncateEmailCache();
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>sender</name>
                    <type>text</type>
                </field>
            ');
            $this->_backend->alterCol('felamimail_cache_message', $declaration);
            $this->setTableVersion('felamimail_cache_message', 14);
        }
        $this->addApplicationUpdate('Felamimail', '15.7',self::RELEASE015_UPDATE007);
    }

    public function update008()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if (!$this->_backend->columnExists('to', 'felamimail_cache_message')) {
            // truncate email cache to make this go faster
            Felamimail_Controller::getInstance()->truncateEmailCache();
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>to_list</name>
                    <type>text</type>
                </field>');
            $sql = $this->_backend->addAddCol('', 'felamimail_cache_message', $declaration);

            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>cc_list</name>
                    <type>text</type>
                </field>');
            $sql = $this->_backend->addAddCol($sql, 'felamimail_cache_message', $declaration);

            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>bcc_list</name>
                    <type>text</type>
                </field>');
            $sql = $this->_backend->addAddCol($sql, 'felamimail_cache_message', $declaration);

            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>flag_list</name>
                    <type>text</type>
                </field>');
            $sql = $this->_backend->addAddCol($sql, 'felamimail_cache_message', $declaration);
            $this->getDb()->query($sql);
        }

        if ($this->getTableVersion('felamimail_cache_message') < 15) {
            $this->setTableVersion('felamimail_cache_message', 15);
        }
        $this->addApplicationUpdate('Felamimail', '15.8', self::RELEASE015_UPDATE008);
    }
}
