<?php

/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Felamimail_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE012_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE012_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
    ];

    public function update001()
    {
        $release11 = new Felamimail_Setup_Update_Release11($this->_backend);
        $release11->update_2();
        $this->addApplicationUpdate('Felamimail', '12.4', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        if (! $this->_backend->tableExists('felamimail_account_acl')) {
            $tableDefinition = new Setup_Backend_Schema_Table_Xml('<table>
                <name>felamimail_account_acl</name>
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
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                    <index>
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
                    <index>
                        <name>fmail_account_acl::record_id--fmail_account::id</name>
                        <field>
                            <name>record_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>felamimail_account</table>
                            <field>id</field>
                            <ondelete>cascade</ondelete>
                            <onupdate>cascade</onupdate>
                        </reference>
                    </index>
                </declaration>
            </table>');

            $this->_backend->createTable($tableDefinition, 'Felamimail', 'felamimail_account_acl');
        }

        $accountCtrl = Felamimail_Controller_Account::getInstance();
        foreach ($accountCtrl->getAll() as $account) {
            $accountCtrl->setDefaultGrants($account);
        }
        $this->addApplicationUpdate('Felamimail', '12.5', self::RELEASE012_UPDATE002);
    }
}
