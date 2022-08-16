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
    
    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ]
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
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Felamimail', '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
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
}
