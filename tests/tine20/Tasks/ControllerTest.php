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
class Tasks_ControllerTest extends Tinebase_AbstractControllerTest
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
        $this->_miminamDatas = array('Task' => array(
            'summary'       => 'minimal task by PHPUnit::Tasks_ControllerTest',
        ));
        parent::setUp();
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
}

