<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tinebase_HelperTests::main();
}

/**
 * Test class for Tinebase_Helper
 */
class Tinebase_HelperTests extends PHPUnit_Framework_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_HelperTests');
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

    public function testArray_value()
    {
        $array = array(
            0     => 'foo',
            'one' => 'bar'
        );
        
        $this->assertEquals('foo', array_value(0, $array));
        $this->assertEquals('bar', array_value('one', $array));
    }
    
    public function testGetDevelopmentRevision()
    {
        $rev = getDevelopmentRevision();
        $this->assertFalse(empty($rev));
    }
    
    public function testConvertToBytes()
    {
        $this->assertEquals(1024, convertToBytes('1024'));
        $this->assertEquals(1024, convertToBytes('1K'));
        $this->assertEquals(1024*1024, convertToBytes('1M'));
        
    }
}
