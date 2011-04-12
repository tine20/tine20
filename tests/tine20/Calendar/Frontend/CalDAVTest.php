<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Filemanager_Frontend_Tree
 * 
 * @package     Tinebase
 */
class Calendar_Frontend_CalDAVTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Tree
     *
     * @var Tinebase_WebDav_Tree
     */
    protected $_CalDAVTree;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 CalDAV tree tests');
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
        $this->_CalDAVTree = new Calendar_Frontend_CalDAV('caldav');
        
        $this->objects['nodes'] = array();
        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->objects['nodes'] as $node) {
            #$this->_rmDir($node);
        } 
    }
    
    public function testDAVNode()
    {
        $children = $this->_CalDAVTree->getChildren();
        
        $this->assertEquals(2, count($children));
    }
    
    public function testGetNodeForPath_calendars()
    {
        $node = $this->_CalDAVTree->getChild('calendars');
        
        $this->assertType('Sabre_CalDAV_CalendarRootNode', $node);
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
    
    public function testGetNodeForPath_principals()
    {
        $node = $this->_CalDAVTree->getChild('principals');
        
        $this->assertType('Sabre_DAV_Auth_PrincipalCollection', $node);
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
   
}		
	

if (PHPUnit_MAIN_METHOD == 'Calendar_Frontend_WebDavTest::main') {
    Calendar_Frontend_WebDavTest::main();
}
