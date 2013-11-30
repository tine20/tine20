<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_WebDav_Root
 * 
 * @package     Tinebase
 * @subpackage  WebDav
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
    
    /**
     * test getChildren method
     */
    public function testGetChildren()
    {
        $children = $this->_rootNode->getChildren();
        
        $this->assertEquals(6, count($children));
    }
    
    /**
     * test getLastModified method
     */
    public function testGetLastModified()
    {
        $remoteWebDav = $this->_rootNode->getChild('remote.php')->getChild('webdav');
        
        $this->assertNotEmpty($remoteWebDav->getLastModified());
        
        $personal = $remoteWebDav->getChild('personal');
        
        $this->assertNotEmpty($personal->getLastModified());
        
        $shared = $remoteWebDav->getChild('shared');
        
        $this->assertNotEmpty($shared->getLastModified());
        
        $this->assertContains($remoteWebDav->getLastModified(), array($personal->getLastModified(), $shared->getLastModified()));
    }
    
    /**
     * test getETag 
     */
    public function testGetETag()
    {
        $remoteWebDav = $this->_rootNode->getChild('remote.php')->getChild('webdav');
        $properties = $remoteWebDav->getProperties(array('{DAV:}getetag'));
        $this->assertArrayHasKey('{DAV:}getetag', $properties);
        
        $personal = $remoteWebDav->getChild('personal');
        $properties = $personal->getProperties(array('{DAV:}getetag'));
        $this->assertArrayHasKey('{DAV:}getetag', $properties);
        
        $currentUser = $personal->getChild(Tinebase_Core::getUser()->accountLoginName);
        $properties = $currentUser->getProperties(array('{DAV:}getetag'));
        $this->assertArrayHasKey('{DAV:}getetag', $properties);
        
        foreach ($currentUser->getChildren() as $child) {
            $properties = $child->getProperties(array('{DAV:}getetag'));
            $this->assertArrayHasKey('{DAV:}getetag', $properties);
        }
    }
}
