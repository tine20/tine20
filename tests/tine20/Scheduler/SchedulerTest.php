<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Scheduler
 */
class Scheduler_SchedulerTest extends TestCase
{
    /**
     * @var Tinebase_Scheduler
     */
    protected $_scheduler = null;

    /**
     * Sets up unit tests.
     */
    public function setUp()
    {
        parent::setup();

        $this->_scheduler = Tinebase_Core::getScheduler();
        $this->_scheduler->delete($this->_scheduler->getAll()->getArrayOfIds());
    }
    
    /**
     * tears down the fixture
     */
    public function tearDown()
    {
        parent::tearDown();
    }
    
    /**
     * Tests if a task can be added.
     */
    public function testCanAddTask()
    {
        $task = new Tinebase_Model_SchedulerTask([
            'name'      => 'test',
            'config'    => new Tinebase_Scheduler_Task([
                'cron'      => Tinebase_Scheduler_Task::TASK_TYPE_MINUTELY,
                'callables' => [
                    [
                        Tinebase_Scheduler_Task::CLASS_NAME     => Scheduler_Mock::class,
                        Tinebase_Scheduler_Task::METHOD_NAME    => 'run'
                    ], [
                        Tinebase_Scheduler_Task::CONTROLLER     => Tinebase_Scheduler::class,
                        Tinebase_Scheduler_Task::METHOD_NAME    => 'doContainerACLChecks',
                        Tinebase_Scheduler_Task::ARGS           => [true]
                    ]
                ]
            ]),
            'next_run'  => Tinebase_DateTime::now()->subDay(100)
        ]);

        $this->_scheduler->create($task);

        $this->assertTrue($this->_scheduler->hasTask('test'), 'Task could not be added');
    }

    /**
     * Tests if a task can be removed.
     */
    public function testCanRemoveTask()
    {
        $this->testCanAddTask();

        $this->_scheduler->removeTask('test');

        $this->assertFalse($this->_scheduler->hasTask('test'), 'Task could not be removed');
    }

    /**
     * Tests if a task can be successfully dispatched.
     * @group nogitlabci
     */
    public function testCanDispatchTask()
    {
        $oldValue = $this->_scheduler->doContainerACLChecks(false);

        try {
            $this->testCanAddTask();

            static::assertTrue($this->_scheduler->run(), 'scheduler run failed');
            static::assertTrue(Scheduler_Mock::didRun(), 'class dispatch didnt work');
            static::assertTrue($this->_scheduler->doContainerACLChecks(), 'controller dispatch didnt work');

        } finally {
            $this->_scheduler->doContainerACLChecks($oldValue);
        }
    }
}
