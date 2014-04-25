<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Tinebase_WebDav_Root
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_RootTest extends TestCase
{
    /**
     * Tree
     *
     * @var Sabre\DAV\ObjectTree
     */
    protected $_webdavTree;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
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
     * test getChildren method
     */
    public function testGetChildren()
    {
        $children = $this->_getWebDAVTree()->getNodeForPath(null)->getChildren();
        
        $this->assertEquals(6, count($children));
    }
    
    /**
     * test getLastModified method
     */
    public function testGetLastModified()
    {
        $remoteWebDav = $this->_getWebDAVTree()->getNodeForPath('/remote.php/webdav');
        
        $this->assertNotEmpty($remoteWebDav->getLastModified());
        
        $personal = $remoteWebDav->getChild(Tinebase_Core::getUser()->accountDisplayName);
        
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
        $remoteWebDav = $this->_getWebDAVTree()->getNodeForPath('/remote.php/webdav');
        $properties = $remoteWebDav->getProperties(array('{DAV:}getetag'));
        $this->assertArrayHasKey('{DAV:}getetag', $properties);
        
        $currentUser = $remoteWebDav->getChild(Tinebase_Core::getUser()->accountDisplayName);
        $properties = $currentUser->getProperties(array('{DAV:}getetag'));
        $this->assertArrayHasKey('{DAV:}getetag', $properties);
        
        foreach ($currentUser->getChildren() as $child) {
            $properties = $child->getProperties(array('{DAV:}getetag'));
            $this->assertArrayHasKey('{DAV:}getetag', $properties);
        }
    }
    
    /**
     * 
     * @return \Sabre\DAV\ObjectTree
     */
    protected function _getWebDAVTree()
    {
        if (! $this->_webdavTree instanceof \Sabre\DAV\ObjectTree) {
            $this->_webdavTree = new \Sabre\DAV\ObjectTree(new Tinebase_WebDav_Root());
        }
        
        return $this->_webdavTree;
    }
}
