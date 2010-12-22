<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Frontend_WebDavTest::main');
}

/**
 * Test class for Filemanager_Frontend_Tree
 * 
 * @package     Tinebase
 */
class Calendar_Frontend_WebDavTest extends PHPUnit_Framework_TestCase
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
    protected $_webdavTree;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 webdav tree tests');
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
        $this->_webdavTree = new Tinebase_WebDav_Tree('/');
        
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
    
    public function testgetNodeForPath()
    {
        $node = $this->_webdavTree->getNodeForPath(null);
        
        $this->assertType('Tinebase_WebDav_Root', $node);
        
        $children = $node->getChildren();
        
        $this->assertEquals('dav', $children[0]->getName());
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
    
    public function testgetNodeForPath_dav()
    {
        
        $node = $this->_webdavTree->getNodeForPath('dav');
        
        $this->assertType('Tinebase_WebDav_Root', $node);
        $this->assertEquals('dav', $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertType('Sabre_DAV_ICollection', $children[0]);
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
    
    public function testgetNodeForPath_dav_calendar()
    {
        $node = $this->_webdavTree->getNodeForPath('dav/calendar');
        
        $this->assertType('Calendar_Frontend_WebDav', $node);
        $this->assertEquals('calendar', $node->getName());
        
        $children = $node->getChildren();
        
        #$this->assertEquals(2, count($children));
        #$this->assertType('Calendar_Frontend_WebDav', $children[0]);
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
    
    public function testgetNodeForPath_dav_calendar_principals()
    {
        $node = $this->_webdavTree->getNodeForPath('dav/calendar/principals');
        
        $this->assertType('Sabre_DAV_Auth_PrincipalCollection', $node);
        $this->assertEquals('principals', $node->getName());
        
        $children = $node->getChildren();
        
        #var_dump($children);
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
        
    public function testgetNodeForPath_dav_calendar_calendars()
    {
        $node = $this->_webdavTree->getNodeForPath('dav/calendar/calendars');
        
        $this->assertType('Sabre_CalDAV_CalendarRootNode', $node);
        $this->assertEquals('calendars', $node->getName());
        
        $children = $node->getChildren();
        
        #var_dump($children);
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }    
}		
	

if (PHPUnit_MAIN_METHOD == 'Calendar_Frontend_WebDavTest::main') {
    Calendar_Frontend_WebDavTest::main();
}
