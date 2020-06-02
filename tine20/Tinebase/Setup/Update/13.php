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

    static protected $_allUpdates = [
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
        if ($this->getTableVersion('importexport_definition') < 13) {
            $this->_backend->addCol('importexport_definition', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>plugin_options_definition</name>
                    <type>text</type>
                </field>'));
            $this->setTableVersion('importexport_definition', 13);
        }

        $this->addApplicationUpdate('Tinebase', '13.3', self::RELEASE013_UPDATE004);
    }
}
