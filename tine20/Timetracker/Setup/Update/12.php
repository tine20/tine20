<?php

/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Timetracker_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [

        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        $this->updateSchema(Timetracker_Config::APP_NAME, [Timetracker_Model_Timeaccount::class]);
        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '12.4', self::RELEASE012_UPDATE001);
    }
}
