<?php

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Calendar_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE013_UPDATE002 = __CLASS__ . '::update002';


    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE013_UPDATE002 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        $this->addApplicationUpdate('Calendar', '13.0', self::RELEASE013_UPDATE001);
    }

    public function update002()
    {
        if ($this->getTableVersion('cal_events') < 17) {
            if (! $this->_backend->columnExists('mute', 'cal_events')) {
                $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>mute</name>
                <type>boolean</type>
            </field>');
                $this->_backend->addCol('cal_events', $declaration);
            }
            $this->setTableVersion('cal_events', 17);
        }

        $this->setTableVersion('cal_events', 13.1);
        $this->addApplicationUpdate('Calendar', '13.1', self::RELEASE013_UPDATE002);
    }
}
