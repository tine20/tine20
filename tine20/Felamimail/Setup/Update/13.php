<?php

/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Felamimail_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ]
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
}
