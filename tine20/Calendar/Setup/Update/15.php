<?php

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2022.11 (ONLY!)
 */
class Calendar_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE015_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE        => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE015_UPDATE002 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update002',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Calendar', '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        if (!$this->_backend->columnExists('status_with_grant', 'cal_resources')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status_with_grant</name>
                <type>text</type>
                <length>32</length>
                <default>NEEDS-ACTION</default>
                <notnull>true</notnull>
            </field>');
            $this->_backend->addCol('cal_resources', $declaration);
            if ($this->getTableVersion('cal_resources') < 9) {
                $this->setTableVersion('cal_resources', 9);
            }
        }
        $this->addApplicationUpdate('Calendar', '15.1', self::RELEASE015_UPDATE001);
    }

    public function update002()
    {
        if (! $this->_backend->columnExists('location_record', 'cal_events')) {
            $this->_backend->addCol('cal_events', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>location_record</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>'));

            $this->_backend->addIndex('cal_events', new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>location_record</name>
                <field>
                    <name>location_record</name>
                </field>
            </index>'));
        }

        if ($this->getTableVersion('cal_events') < 18) {
            $this->setTableVersion('cal_events', 19);
        }

        $this->addApplicationUpdate('Calendar', '15.2', self::RELEASE015_UPDATE002);
    }
}
