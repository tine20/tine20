<?php

/**
 * Tine 2.0
 *
 * @package     ExampleApplication
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class ExampleApplication_Setup_Update_0 extends Setup_Update_Abstract
{
    const RELEASE000_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE000_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE000_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE000_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
    ];


    // gets executed 2nd
    public function update001()
    {
        $this->addApplicationUpdate('ExampleApplication', '0.1', self::RELEASE000_UPDATE001);
    }

    // gets executed first
    public function update002()
    {
        $this->addApplicationUpdate('ExampleApplication', '0.2', self::RELEASE000_UPDATE002);
    }
}