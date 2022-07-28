<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * Test class for Admin_Controller_SchedulerTask
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Admin_Controller_SchedulerTaskTest extends TestCase
{
    public function testCreateSchedulerTask()
    {
        $task = new Admin_Model_SchedulerTask([
            Admin_Model_SchedulerTask::FLD_NAME => 'unittest import scheduled task',
            Admin_Model_SchedulerTask::FLD_CONFIG_CLASS => Admin_Model_SchedulerTask_Import::class,
            Admin_Model_SchedulerTask::FLD_CONFIG       => [
                Admin_Model_SchedulerTask_Import::FLD_PLUGIN_CLASS      => Calendar_Import_Ical::class,
                Admin_Model_SchedulerTask_Import::FLD_OPTIONS           => [
                    'container_id' => $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true)->getId(),
                    'url' => dirname(dirname(__DIR__)) . '/Calendar/Import/files/gotomeeting.ics',
                ],
            ],
            Admin_Model_SchedulerTask::FLD_CRON         => '* * * * *',
        ]);
        $createdTask = Admin_Controller_SchedulerTask::getInstance()->create($task);

        $this->assertSame($task->{Admin_Model_SchedulerTask::FLD_CRON}, $createdTask->{Admin_Model_SchedulerTask::FLD_CRON});
        $this->assertSame($task->{Admin_Model_SchedulerTask::FLD_CONFIG_CLASS}, $createdTask->{Admin_Model_SchedulerTask::FLD_CONFIG_CLASS});
        $this->assertSame($createdTask->getId(), $createdTask->{Admin_Model_SchedulerTask::FLD_CONFIG}->{Admin_Model_SchedulerTask_Abstract::FLD_PARENT_ID});
        $this->assertNull($createdTask->last_run);
        $this->assertSame('0', $createdTask->failure_count);

        $this->assertTrue(Tinebase_Scheduler::getInstance()->run());

        $runTask = Admin_Controller_SchedulerTask::getInstance()->get($createdTask->getId());
        $this->assertNotNull($runTask->last_run);
        $this->assertSame('0', $runTask->failure_count);
    }
}
