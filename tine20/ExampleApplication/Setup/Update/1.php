<?php

/**
 * Tine 2.0
 *
 * @package     ExampleApplication
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class ExampleApplication_Setup_Update_1 extends Setup_Update_Abstract
{
    const RELEASE001_UPDATE001 = 'release001::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE001_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];


    public function update001()
    {
        $this->addApplicationUpdate('ExampleApplication', '10.0', self::RELEASE001_UPDATE001);
    }
}