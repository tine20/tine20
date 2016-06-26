<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Filemanager_Frontend_Tree
 * 
 * @package     Filemanager
 */
class Filemanager_Frontend_WebDAVTest extends TestCase
{
    /**
     * Tree
     *
     * @var Sabre\DAV\ObjectTree
     */
    protected $_webdavTree;
    
    /**
     * testgetNodeForPath
     */
    public function testgetNodeForPath()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath(null);
        
        $this->assertInstanceOf('Tinebase_WebDav_Root', $node, 'wrong node class');
        
        $children = $node->getChildren();
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/');
    }
    
    public function testgetNodeForPath_webdav()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav');
        
        $this->assertInstanceOf('\Sabre\DAV\SimpleCollection', $node, 'wrong node class');
        $this->assertEquals('webdav', $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertInstanceOf('Tinebase_WebDav_Collection_AbstractContainerTree', $children[0], 'wrong child class');
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav');
    }
    
    public function testgetNodeForPath_webdav_filemanager()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager');
        
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV', $node, 'wrong node class');
        $this->assertEquals('Filemanager', $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertGreaterThanOrEqual(2, count($children));
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV', $children[0], 'wrong node class');
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav/Filemanager');
    }
    
    /**
     * test currently fails:
     * 
     * 1) Filemanager_Frontend_WebDAVTest::testgetNodeForPath_webdav_filemanager_personal
     * Sabre\DAV\Exception\NotFound: Directory Filemanager/personal not found
     * 
     * /var/lib/jenkins-tine20.com/jobs/tine20com-gerrit/workspace/tine20/Tinebase/WebDav/Collection/AbstractContainerTree.php:128
     * /var/lib/jenkins-tine20.com/jobs/tine20com-gerrit/workspace/tine20/vendor/sabre/dav/lib/Sabre/DAV/ObjectTree.php:72
     * /var/lib/jenkins-tine20.com/jobs/tine20com-gerrit/workspace/tests/tine20/Filemanager/Frontend/WebDAVTest.php:76
     */
    public function testgetNodeForPath_webdav_filemanager_personal()
    {
        $this->markTestSkipped('FIXME');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/personal');
        
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV', $node, 'wrong node class');
        $this->assertEquals('personal', $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertEquals(1, count($children));
        $this->assertEquals('Filemanager_Frontend_WebDAV', get_class($children[0]), 'wrong child class');
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav/Filemanager/personal');
    }
    
    public function testgetNodeForPath_webdav_filemanager_currentuser()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountDisplayName);
        
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV', $node, 'wrong node class');
        $this->assertEquals(Tinebase_Core::getUser()->accountDisplayName, $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertGreaterThanOrEqual(1, count($children));
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV_Container', $children[0], 'wrong node class');
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountDisplayName);
    }
    
    /**
     * @return Filemanager_Frontend_WebDAV_Directory
     */
    public function testgetNodeForPath_webdav_filemanager_currentuser_unittestdirectory()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountDisplayName);
        
        $node->createDirectory('unittestdirectory');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountDisplayName .'/unittestdirectory');
        
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV_Container', $node, 'wrong node class');
        $this->assertEquals('unittestdirectory', $node->getName());
        
        $children = $this->_getWebDAVTree()->getChildren('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountDisplayName);
        foreach ($children as $node) {
            $names[] = $node->getName();
        }
        $this->assertContains('unittestdirectory', $names);
        
        $this->_getWebDAVTree()->delete('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountDisplayName .'/unittestdirectory');
        
        $this->setExpectedException('Sabre\DAV\Exception\NotFound');
        
        $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountDisplayName .'/unittestdirectory');
    }
    
    public function testgetNodeForPath_webdav_filemanager_shared()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared');
        
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV', $node, 'wrong node class');
        $this->assertEquals('shared', $node->getName());
        
        $children = $node->getChildren();
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav/Filemanager/shared');
    }
    
    /**
     * testgetNodeForPath_webdav_filemanager_shared_unittestdirectory
     * 
     * @return Filemanager_Frontend_WebDAV_Container
     */
    public function testgetNodeForPath_webdav_filemanager_shared_unittestdirectory()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared');
        
        $node->createDirectory('unittestdirectory');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory');
        
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV_Container', $node, 'wrong node class');
        $this->assertEquals('unittestdirectory', $node->getName());
        
        $children = $node->getChildren();
        
        $properties = $node->getProperties(array());
        
        return $node;
    }
    
    /**
     * @return Filemanager_Frontend_WebDAV_File
     */
    public function testgetNodeForPath_webdav_filemanager_shared_unittestdirectory_file()
    {
        $parent = $this->testgetNodeForPath_webdav_filemanager_shared_unittestdirectory();
        
        $etag = $parent->createFile('tine_logo.png', fopen(dirname(__FILE__) . '/../../Tinebase/files/tine_logo.png', 'r'));
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory/tine_logo.png');
        
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV_File', $node, 'wrong node class');
        $this->assertTrue(is_resource($node->get()));
        $this->assertEquals('tine_logo.png', $node->getName());
        $this->assertEquals(7246, $node->getSize());
        $this->assertEquals('image/png', $node->getContentType());
        $this->assertEquals('"7424e2c16388bf388af1c4fe44c1dd67d31f468b"', $node->getETag());
        $this->assertTrue(preg_match('/"\w+"/', $etag) === 1);
        
        return $node;
    }
    
    public function testUpdateFile()
    {
        $node = $this->testgetNodeForPath_webdav_filemanager_shared_unittestdirectory_file();
        
        $etag = $node->put(fopen(dirname(__FILE__) . '/../../Tinebase/files/tine_logo.png', 'r'));
        
        $this->assertEquals('Filemanager_Frontend_WebDAV_File', get_class($node), 'wrong type');
        $this->assertTrue(preg_match('/"\w+"/', $etag) === 1);
    }
    
    /**
     * test chunked upload from OwnCloud clients
     * 
     * @backupGlobals enabled
     * @return Filemanager_Frontend_WebDAV_File
     */
//    public function testOwnCloudChunkedUpload()
//    {
//        // this currently fails on http://ci.tine20.org/job/tine20-gerrit-cloud
//        // -> Exception: DateTimeZone::__construct(): Unknown or bad timezone (+00:00)
//        $this->markTestSkipped('FIXME repair this test');
//
//        $_SERVER['HTTP_OC_CHUNKED'] = 1;
//
//        $fileStream = fopen(dirname(__FILE__) . '/../../Tinebase/files/tine_logo.png', 'r');
//
//        $parent = $this->testgetNodeForPath_webdav_filemanager_shared_unittestdirectory();
//
//        // upload first chunk
//        $tempStream = fopen('php://temp', 'w');
//        $_SERVER['CONTENT_LENGTH'] = stream_copy_to_stream($fileStream, $tempStream, 1000);
//        rewind($tempStream);
//        $etag = $parent->createFile('tine_logo.png-chunking-1000-3-0', $tempStream);
//        fclose($tempStream);
//
//        // upload second chunk
//        $tempStream = fopen('php://temp', 'w');
//        $_SERVER['CONTENT_LENGTH'] = stream_copy_to_stream($fileStream, $tempStream, 1000);
//        rewind($tempStream);
//        $etag = $parent->createFile('tine_logo.png-chunking-1000-3-1', $tempStream);
//        fclose($tempStream);
//
//        // upload last chunk
//        $tempStream = fopen('php://temp', 'w');
//        $_SERVER['CONTENT_LENGTH'] = stream_copy_to_stream($fileStream, $tempStream);
//        rewind($tempStream);
//        $etag = $parent->createFile('tine_logo.png-chunking-1000-3-2', $tempStream);
//        fclose($tempStream);
//        fclose($fileStream);
//
//        // retrieve final node
//        $this->_getWebDAVTree()->markDirty('/webdav/Filemanager/shared/unittestdirectory/tine_logo.png');
//        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory/tine_logo.png');
//
//        $this->assertInstanceOf('Filemanager_Frontend_WebDAV_File', $node, 'wrong node class');
//        $this->assertTrue(is_resource($node->get()));
//        $this->assertEquals('tine_logo.png', $node->getName());
//        $this->assertEquals(7246, $node->getSize());
//        $this->assertEquals('image/png', $node->getContentType());
//        $this->assertEquals('"7424e2c16388bf388af1c4fe44c1dd67d31f468b"', $node->getETag());
//        $this->assertTrue(preg_match('/"\w+"/', $etag) === 1);
//
//        $fileStream = fopen(dirname(__FILE__) . '/../../Tinebase/files/tine_logo_setup.png', 'r');
//
//        // upload first chunk
//        $tempStream = fopen('php://temp', 'w');
//        $_SERVER['CONTENT_LENGTH'] = stream_copy_to_stream($fileStream, $tempStream, 1000);
//        rewind($tempStream);
//        $etag = $parent->createFile('tine_logo.png-chunking-1001-3-0', $tempStream);
//        fclose($tempStream);
//
//        // upload second chunk
//        $tempStream = fopen('php://temp', 'w');
//        $_SERVER['CONTENT_LENGTH'] = stream_copy_to_stream($fileStream, $tempStream, 1000);
//        rewind($tempStream);
//        $etag = $parent->createFile('tine_logo.png-chunking-1001-3-1', $tempStream);
//        fclose($tempStream);
//
//        // upload last chunk
//        $tempStream = fopen('php://temp', 'w');
//        $_SERVER['CONTENT_LENGTH'] = stream_copy_to_stream($fileStream, $tempStream);
//        rewind($tempStream);
//        $etag = $parent->createFile('tine_logo.png-chunking-1001-3-2', $tempStream);
//        fclose($tempStream);
//
//        // retrieve final node
//        $this->_getWebDAVTree()->markDirty('/webdav/Filemanager/shared/unittestdirectory/tine_logo.png');
//        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory/tine_logo.png');
//
//        $this->assertInstanceOf('Filemanager_Frontend_WebDAV_File', $node, 'wrong node class');
//        $this->assertTrue(is_resource($node->get()));
//        $this->assertEquals('tine_logo.png', $node->getName());
//        $this->assertEquals(7258, $node->getSize());
//        $this->assertEquals('image/png', $node->getContentType());
//        $this->assertEquals('"434f1e3474a04ce3a10febad78a64e65d7b4f531"', $node->getETag());
//        $this->assertTrue(preg_match('/"\w+"/', $etag) === 1);
//
//        return $node;
//    }
    
    public function testDeleteFile()
    {
        $node = $this->testgetNodeForPath_webdav_filemanager_shared_unittestdirectory_file();
    
        $this->_getWebDAVTree()->delete('/webdav/Filemanager/shared/unittestdirectory/tine_logo.png');
    
        $this->setExpectedException('Sabre\DAV\Exception\NotFound');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory/tine_logo.png');
    }
    
    /**
     * @return Filemanager_Frontend_WebDAV_Directory
     */
    public function testgetNodeForPath_webdav_filemanager_shared_unittestdirectory_directory()
    {
        $parent = $this->testgetNodeForPath_webdav_filemanager_shared_unittestdirectory();
    
        $file = $parent->createDirectory('directory');
    
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory/directory');
    
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV_Directory', $node, 'wrong node class');
        $this->assertEquals('directory', $node->getName());
            
        return $node;
    }
    
    public function testgetNodeForPath_invalidApplication()
    {
        $this->setExpectedException('Sabre\DAV\Exception\NotFound');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/invalidApplication');
    }
    
    public function testgetNodeForPath_invalidContainerType()
    {
        $this->setExpectedException('Sabre\DAV\Exception\NotFound');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/invalidContainerType');
    }
    
    public function testgetNodeForPath_invalidFolder()
    {
        $this->setExpectedException('Sabre\DAV\Exception\NotFound');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/invalidContainer');
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
