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

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
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
        $release11 = new Addressbook_Setup_Update_Release11($this->_backend);
        $release11->update_14();
        $this->addApplicationUpdate('Tinebase', '12.5', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        $release11 = new Addressbook_Setup_Update_Release11($this->_backend);
        $release11->update_15();
        $this->addApplicationUpdate('Tinebase', '12.6', self::RELEASE012_UPDATE002);
    }

}
