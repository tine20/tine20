<?php

/**
 * Tine 2.0
 *
 * @package     Crm
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Crm_Setup_Update_12 extends Setup_Update_Abstract
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
        $this->addApplicationUpdate('Crm', '12.1', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        if ($this->getTableVersion('metacrm_lead') < 11) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>mute</name>
                <type>boolean</type>
            </field>');

            $this->_backend->addCol('metacrm_lead', $declaration);
            $this->setTableVersion('metacrm_lead', 11);
        }

        $this->setTableVersion('metacrm_lead', 12.2);
        $this->addApplicationUpdate('Crm', '12.2', self::RELEASE012_UPDATE002);
    }
}
