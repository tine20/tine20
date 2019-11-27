<?php

/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Tasks_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE012_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE012_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE012_UPDATE004 = __CLASS__ . '::update004';

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
            self::RELEASE012_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE012_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
    ];

    public function update001()
    {
        $release11 = new Tasks_Setup_Update_Release11($this->_backend);
        $release11->update_1();
        $this->addApplicationUpdate('Tasks', '12.2', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        $release11 = new Tasks_Setup_Update_Release11($this->_backend);
        $release11->update_2();
        $this->addApplicationUpdate('Tasks', '12.3', self::RELEASE012_UPDATE002);
    }

    public function update003()
    {
        $this->addApplicationUpdate('Tasks', '12.4', self::RELEASE012_UPDATE003);
    }

    public function update004()
    {
        $release11 = new Tasks_Setup_Update_Release11($this->_backend);
        $release11->update_2();
        $this->addApplicationUpdate('Tasks', '12.5', self::RELEASE012_UPDATE004);
    }
}
