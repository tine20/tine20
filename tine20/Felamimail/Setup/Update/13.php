<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this ist 2020.11 (ONLY!)
 */
class Felamimail_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE013_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE013_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE013_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE013_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE013_UPDATE006 = __CLASS__ . '::update006';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE013_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE013_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE013_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE => [
            self::RELEASE013_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE013_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
    ];

    public function update001()
    {
        if ($this->getTableVersion('felamimail_account') < 26) {

            $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>xprops</name>
                <type>text</type>
                <length>65535</length>
            </field>
        ');

            $this->_backend->addCol('felamimail_account', $declaration);

            $this->setTableVersion('felamimail_account', 26);
        }

        $this->addApplicationUpdate('Felamimail', '13.0', self::RELEASE013_UPDATE001);
    }

    public function update002()
    {
        if ($this->getTableVersion('felamimail_account') < 27) {

            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>sieve_notification_move</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>
            ');
            $this->_backend->addCol('felamimail_account', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>sieve_notification_move_folder</name>
                    <type>text</type>
                    <length>255</length>
                </field>
            ');
            $this->_backend->addCol('felamimail_account', $declaration);

            $this->setTableVersion('felamimail_account', 27);
        }

        $this->addApplicationUpdate('Felamimail', '13.1', self::RELEASE013_UPDATE002);
    }

    public function update003()
    {
        if ($this->getTableVersion('felamimail_cache_message') < 12) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>is_spam_suspicions</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>
            ');
            $this->_backend->addCol('felamimail_cache_message', $declaration);
            $this->setTableVersion('felamimail_cache_message', 12);
        }

        $this->addApplicationUpdate('Felamimail', '13.2', self::RELEASE013_UPDATE003);
    }

    public function update004()
    {
        if ($this->getTableVersion('felamimail_cache_message') < 13) {
            foreach (['from_email', 'from_name'] as $indexName) {
                $declaration = new Setup_Backend_Schema_Index_Xml('
                    <index>
                        <name>' . $indexName . '</name>
                        <field>
                            <name>' . $indexName . '</name>
                        </field>
                    </index>
                ');
                try {
                    $this->_backend->addIndex('felamimail_cache_message', $declaration);
                } catch (Exception $e) {
                    // Ignore
                }
            }
            $this->setTableVersion('felamimail_cache_message', 13);
        }

        $this->addApplicationUpdate('Felamimail', '13.3', self::RELEASE013_UPDATE004);
    }

    public function update005()
    {
        $this->addApplicationUpdate('Felamimail', '13.4', self::RELEASE013_UPDATE005);
    }

    public function update006()
    {
        foreach (Tinebase_Core::getDb()->query('SELECT id,note FROM ' .
            SQL_TABLE_PREFIX . 'notes WHERE record_model = "Felamimail_Model_Account" and note like "%password%"')->fetchAll() as $row) {

            Tinebase_Core::getDB()->update(SQL_TABLE_PREFIX . 'notes', [
                'note' => preg_replace('/password \( -> .*\) /','password ( -> ********)', $row['note'])
            ] , 'id = '. Tinebase_Core::getDb()->quote($row['id']));
        }
        $this->addApplicationUpdate('Felamimail', '13.5', self::RELEASE013_UPDATE006);
    }
}
