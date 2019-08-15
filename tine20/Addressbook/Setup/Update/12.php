<?php

/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Addressbook_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE012_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE012_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE012_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        // ATTENTION !!! PRIO TB !!! NOT NORMAL APP !!!
        // this is because Tinebase_Group_Sql will join the column xprops created here
        // DO NOT ADD ANYMORE UPDATES INTO THIS, use normal_app_structure instead (except well... except "except" of course)
        self::PRIO_TINEBASE_STRUCTURE       => [
            self::RELEASE012_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],


        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE012_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE012_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
    ];

    public function update001()
    {
        $release11 = new Addressbook_Setup_Update_Release11($this->_backend);
        $release11->update_14();
        $this->addApplicationUpdate('Addressbook', '12.5', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        $release11 = new Addressbook_Setup_Update_Release11($this->_backend);
        $release11->update_15();
        $this->addApplicationUpdate('Addressbook', '12.6', self::RELEASE012_UPDATE002);
    }

    public function update003()
    {
        if (! $this->_backend->columnExists('xprops', 'addressbook_lists')) {
            $this->_backend->addCol('addressbook_lists', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>xprops</name>
                    <type>text</type>
                    <length>65535</length>
                </field>'));
        }

        if ($this->getTableVersion('addressbook_lists') < 7) {
            $this->setTableVersion('addressbook_lists', 7);
        }

        $this->addApplicationUpdate('Addressbook', '12.7', self::RELEASE012_UPDATE003);

        Tinebase_Group_Sql::doJoinXProps();
    }

    public function update004()
    {
        Tinebase_Container::getInstance()->deleteDuplicateContainer('Addressbook');
        $this->addApplicationUpdate('Addressbook', '12.8', self::RELEASE012_UPDATE004);
    }
}
