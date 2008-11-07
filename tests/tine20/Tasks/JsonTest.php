<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        implement more tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tasks_JsonTest::main();
}

/**
 * Test class for Tasks_JsonTest
 */
class Tasks_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * Backend
     *
     * @var Tasks_Frontend_Json
     */
    protected $_backend;
    
    /**
     * main function
     *
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tasks_JsonTest');
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
        $this->_backend = new Tasks_Frontend_Json();  
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * try to search for tasks
     *
     */
    public function testSearchTasks()    
    {
        // create task
        $task = $this->_getTask();
        $task = Tasks_Controller_Task::getInstance()->create($task);
        
        // search tasks
        $tasks = $this->_backend->searchTasks(Zend_Json::encode($this->_getFilter()), Zend_Json::encode($this->_getPaging()));
        
        // check
        $this->assertEquals(1, $tasks['totalcount']);
        
        // delete task
        Tasks_Controller_Task::getInstance()->delete($task->getId());        

        // search and check again
        $tasks = $this->_backend->searchTasks(Zend_Json::encode($this->_getFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals(0, $tasks['totalcount']);
    }
    
    /**
     * get task record
     *
     * @return Tasks_Model_Task
     * 
     * @todo add task to objects
     */
    protected function _getTask()
    {
        return new Tasks_Model_Task(array(
            'summary'       => 'minimal task by PHPUnit::Tasks_ControllerTest',
        ));
    }

    /**
     * get filter for task search
     *
     * @return Tasks_Model_Task
     */
    protected function _getFilter()
    {
        // define filter
        return array(
            'containerType' => 'all',
            'query' => 'minimal task by PHPUnit'     
        );
    }
    
    /**
     * get default paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        // define paging
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'summary',
            'dir' => 'ASC',
        );
    }
}

