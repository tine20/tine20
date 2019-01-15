<?php

/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class HumanResources_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE => [
            self::RELEASE012_UPDATE001 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        $scheduler = Tinebase_Core::getScheduler();
        if (!$scheduler->hasTask('HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask')) {
            HumanResources_Scheduler_Task::addCalculateDailyWorkingTimeReportsTask($scheduler);
        }
        $this->addApplicationUpdate('HumanResources', '12.6', self::RELEASE012_UPDATE001);
    }
}
