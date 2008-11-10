<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        implement tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tasks_ControllerTest::main();
}

/**
 * Test class for Tinebase_Relations
 */
class Tasks_ControllerTest extends PHPUnit_Framework_TestCase //Tinebase_AbstractControllerTest
{
    /**
     * application name of the controller to test
     *
     * @var string
     */
    protected $_appName = 'Tasks';
    
    /**
     * Name of the model(s) this controller handels
     *
     * @var array
     */
    protected $_modelNames = array('Tasks_Model_Task' => 'Task');
    
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tasks_ControllerTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_controller = Tasks_Controller_Task::getInstance();
        $this->_minimalDatas = array('Task' => array(
            'summary'       => 'minimal task by PHPUnit::Tasks_ControllerTest',
        ));
        //parent::setUp();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        //parent::tearDown();
    }

    /**
     * tests if completed gets deleted when status is open
     *
     */
    public function testCompletedNULL()
    {
        $task = new Tasks_Model_Task($this->_minimalDatas['Task']);
        $task->status_id = $this->_getStatus()->getId();
        $task->completed = Zend_Date::now();
        
        $pTask = $this->_controller->create($task);
        $this->assertNull($pTask->completed);
        
        $this->_controller->delete($pTask->getId());
    }
    
    public function testCompletedViaStatus()
    {
        $task = new Tasks_Model_Task($this->_minimalDatas['Task']);
        $task->status_id = $this->_getStatus(false)->getId();
        //$task->completed = Zend_Date::now();
        
        $pTask = $this->_controller->create($task);
        $this->assertTrue($pTask->completed instanceof Zend_Date);
        
        $this->_controller->delete($pTask->getId());
    }
    
    /**
     * returns a status which is defined as open state
     *
     * @return Tasks_Model_Status
     */
    protected function _getStatus($_open=true)
    {
        foreach (Tasks_Controller_Status::getInstance()->getAllStatus() as $idx => $status) {
            if (! ($status->status_is_open xor $_open)) {
                return $status;
            }
        }
    }
}

