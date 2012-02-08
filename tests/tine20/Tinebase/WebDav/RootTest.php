<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_WebDav_RootTest::main');
}

/**
 * Test class for Tinebase_Group
 * @depricated, some fns might be moved to other testclasses
 */
class Tinebase_WebDav_RootTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * @var Tinebase_WebDav_Root
     */
    protected $_rootNode;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_WebDav_RootTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_rootNode = new Tinebase_WebDav_Root();
    }
    
    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }
    
    public function testGetChildren()
    {
        $children = $this->_rootNode->getChildren();
        
        $this->assertEquals(4, count($children));
    }
}


if (PHPUnit_MAIN_METHOD == 'Tinebase_WebDav_RootTest::main') {
    Tinebase_WebDav_RootTest::main();
}
