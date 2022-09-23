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
     * tear down tests
     */
    protected function tearDown(): void
{
        parent::tearDown();
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, false);
    }

    /**
     * test getChildren method
     */
    public function testGetChildren()
    {
        $children = $this->_getWebDAVTree()->getNodeForPath('')->getChildren();
        
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
     * test getLastModified method
     */
    public function testGetLastModified2()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        
        $remoteWebDav = $this->_getWebDAVTree()->getNodeForPath('/remote.php/webdav');
        
        $this->assertNotEmpty($remoteWebDav->getLastModified());
        
        $personal = $remoteWebDav->getChild(Tinebase_Core::getUser()->accountLoginName);
        
        $this->assertNotEmpty($personal->getLastModified());
        
        $shared = $remoteWebDav->getChild('shared');
        
        $this->assertNotEmpty($shared->getLastModified());
        
        $this->assertContains($remoteWebDav->getLastModified(), array($personal->getLastModified(), $shared->getLastModified()));
        
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, false);
        
    }
    
    /**
     * test getETag
     */
    public function testGetETag2()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        
        $remoteWebDav = $this->_getWebDAVTree()->getNodeForPath('/remote.php/webdav');
        $properties = $remoteWebDav->getProperties(array('{DAV:}getetag'));
        $this->assertArrayHasKey('{DAV:}getetag', $properties);
    
        $currentUser = $remoteWebDav->getChild(Tinebase_Core::getUser()->accountLoginName);
        $properties = $currentUser->getProperties(array('{DAV:}getetag'));
        $this->assertArrayHasKey('{DAV:}getetag', $properties);
    
        foreach ($currentUser->getChildren() as $child) {
            $properties = $child->getProperties(array('{DAV:}getetag'));
            $this->assertArrayHasKey('{DAV:}getetag', $properties);
        }
        
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, false);
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

    /**
     * @see 0012216: Caldav Directory calendars not found
     */
    public function testCalendarRoot()
    {
        $calendarRoot = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT, true);
        $children = $calendarRoot->getChildren();
        $this->assertTrue(count($children) > 0 && $children[0] instanceof Calendar_Frontend_WebDAV);
    }
}
