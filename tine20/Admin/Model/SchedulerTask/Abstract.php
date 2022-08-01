<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Scheduler Task Model Interface
 *
 * @package     Admin
 * @subpackage  Scheduler
 */

abstract class Admin_Model_SchedulerTask_Abstract extends Tinebase_Record_NewAbstract
{
    public const FLD_PARENT_ID = 'parent_id';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME          => Admin_Config::APP_NAME,
        self::RECORD_NAME       => 'Task config', // gettext('GENDER_Task config')
        self::RECORDS_NAME      => 'Task configs', // ngettext('Task config', 'Task configs', n)

        self::FIELDS => [
            self::FLD_PARENT_ID => [
                self::TYPE          => self::TYPE_STRING,
                self::DISABLED      => true,
            ],
        ],
    ];

    public function getCallables(): array
    {
        return [[
            Tinebase_Scheduler_Task::CONTROLLER     => Admin_Controller_SchedulerTask::class,
            Tinebase_Scheduler_Task::METHOD_NAME    => 'runCustomScheduledTask',
            Tinebase_Scheduler_Task::ARGS           => [
                $this->{self::FLD_PARENT_ID},
            ],
        ]];
    }

    abstract public function run(): bool;
}