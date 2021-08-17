<?php

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this ist 2020.11 (ONLY!)
 */
class Calendar_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE013_UPDATE002 = __CLASS__ . '::update002'; // burned number @see 14.1
    const RELEASE013_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE013_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE013_UPDATE003 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update003',
            ],
            self::RELEASE013_UPDATE004 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update004',
            ],
        ],
    ];

    public function update001()
    {
        $this->addApplicationUpdate('Calendar', '13.0', self::RELEASE013_UPDATE001);
    }


    public function update003()
    {
        if (! $this->_backend->columnExists('mute', 'cal_events')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>mute</name>
                    <type>boolean</type>
                </field>');
            $this->_backend->addCol('cal_events', $declaration);
        }

        if ($this->getTableVersion('cal_events') < 17) {
            $this->setTableVersion('cal_events', 17);
        }

        $this->setTableVersion('cal_events', 13.2);
        $this->addApplicationUpdate('Calendar', '13.2', self::RELEASE013_UPDATE003);
    }

    /**
     * url field to short
     * @throws Setup_Exception_NotFound
     */
    public function update004()
    {
        $this->_backend->alterCol('cal_events', new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>url</name>
                <type>text</type>
                <length>65535</length>
                <notnull>false</notnull>
            </field>'));

        if ($this->getTableVersion('cal_events') < 18) {
            $this->setTableVersion('cal_events', 18);
        }

        $this->addApplicationUpdate('Calendar', '13.3', self::RELEASE013_UPDATE004);
    }

}
