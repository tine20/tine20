<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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

    protected $_oldLoginnameAsFoldername;

    protected function setUp()
    {
        parent::setUp();

        $this->_oldLoginnameAsFoldername = Tinebase_Config::getInstance()
            ->{Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME};
    }

    /**
     * tear down tests
     */
    protected function tearDown()
    {
        parent::tearDown();
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME,
            $this->_oldLoginnameAsFoldername);
    }

    /**
     * testgetNodeForPath
     */
    public function testGetNodeForPath()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath(null);
        
        $this->assertInstanceOf('Tinebase_WebDav_Root', $node, 'wrong node class');
        
        $children = $node->getChildren();
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/');
    }
    
    public function testGetNodeForPath_webdav()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav');
        
        $this->assertInstanceOf('\Sabre\DAV\SimpleCollection', $node, 'wrong node class');
        $this->assertEquals('webdav', $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertInstanceOf('Tinebase_WebDav_Collection_AbstractContainerTree', $children[0], 'wrong child class');
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav');
    }
    
    public function testGetNodeForPath_webdav_filemanager()
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
     * @param string $property
     * @throws Timetracker_Exception_UnexpectedValue
     * @throws Tinebase_Exception_Backend
     * @throws Tinebase_Exception_NotFound
     * @throws \Sabre\DAV\Exception\NotFound
     */
    protected function _testGetNodeForPath_webdav_filemanagerWithOtherUsers($property)
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager');

        static::assertInstanceOf('Filemanager_Frontend_WebDAV', $node, 'wrong node class');
        static::assertEquals('Filemanager', $node->getName());

        /** @var Tinebase_Model_FullUser $sclever */
        $sclever = $this->_personas['sclever'];
        $fs = Tinebase_FileSystem::getInstance();
        $scleverFolder = $fs->getApplicationBasePath('Filemanager', Tinebase_FileSystem::FOLDER_TYPE_PERSONAL) . '/' .
            $sclever->getId();
        $scleverNode = $fs->stat($scleverFolder);
        $personalFolder = $fs->getTreeNodeChildren($scleverNode)->getFirstRecord();
        $personalFolder->grants = [[
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id' => Tinebase_Core::getUser()->getId(),
                'account_grant' => Tinebase_Model_Grants::GRANT_READ,
            ],[
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id' => Tinebase_Core::getUser()->getId(),
                'account_grant' => Tinebase_Model_Grants::GRANT_SYNC,
            ]];
        $fs->setGrantsForNode($personalFolder, $personalFolder->grants);

        $children = $node->getChildren();

        static::assertGreaterThanOrEqual(3, count($children));
        static::assertInstanceOf('Filemanager_Frontend_WebDAV', $children[0], 'wrong node class');
        $names = [];
        /** @var Filemanager_Frontend_WebDAV $child */
        foreach ($children as $child) {
            $names[] = $child->getName();
        }
        // we never use id as name, if it should be supported, go there and add it!
        // \Filemanager_Frontend_WebDAV::_getOtherUsersChildren
        static::assertEquals(1, count(array_intersect($names, [$sclever->{$property}])));
    }

    public function testGetNodeForPath_webdav_filemanagerWithOtherUsersLoginName()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        // we never use id as name, if it should be supported, go there and add it!
        // \Filemanager_Frontend_WebDAV::_getOtherUsersChildren
        $this->_testGetNodeForPath_webdav_filemanagerWithOtherUsers('accountLoginName');
    }

    public function testGetNodeForPath_webdav_filemanagerWithOtherUsersDisplayName()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, false);
        // we never use id as name, if it should be supported, go there and add it!
        // \Filemanager_Frontend_WebDAV::_getOtherUsersChildren
        $this->_testGetNodeForPath_webdav_filemanagerWithOtherUsers('accountDisplayName');
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
    public function testGetNodeForPath_webdav_filemanager_personal()
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
    
    public function testGetNodeForPath_webdav_filemanager_currentuser()
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
    public function testGetNodeForPath_webdav_filemanager_currentuser_unittestdirectory()
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

        Tinebase_WebDav_Collection_AbstractContainerTree::clearClassCache();
        $this->_webdavTree = null;
        $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountDisplayName .'/unittestdirectory');
    }
    
    public function testGetNodeForPath_webdav_filemanager_shared()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared');
        
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV', $node, 'wrong node class');
        $this->assertEquals('shared', $node->getName());
        
        $children = $node->getChildren();
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav/Filemanager/shared');
    }

    /**
     * test (UN)LOCK functionality of WebDAV
     * @group ServerTests
     *
     * @group nogitlabci_ldap
     */
    public function testUNandLOCKQueries()
    {
        $credentials = TestServer::getInstance()->getTestCredentials();

        Tinebase_FileSystem::getInstance()->createAclNode('Filemanager/folders/shared/unittestdirectory');
        static::assertSame(14, file_put_contents('tine20://Filemanager/folders/shared/unittestdirectory/aTestFile',
            'unittest stuff'), 'could file_put_contents test file into tine VFS');

        $request = Tinebase_Http_Request::fromString(<<<EOS
LOCK /webdav/Filemanager/shared/unittestdirectory/aTestFile HTTP/1.1\r
Host: localhost\r
Depth: 1\r
Content-Type: application/xml; charset="utf-8"\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r
\r
<?xml version="1.0" encoding="utf-8" ?>
   <D:lockinfo xmlns:D='DAV:'>
     <D:lockscope><D:exclusive/></D:lockscope>
     <D:locktype><D:write/></D:locktype>
     <D:owner>
       <D:href>http://example.org/~ejw/contact.html</D:href>
     </D:owner>
   </D:lockinfo>
EOS
        );

        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']     = '0';

        $request->getServer()->set('PHP_AUTH_USER', $credentials['username']);
        $request->getServer()->set('PHP_AUTH_PW',   $credentials['password']);
        $request->getServer()->set('REMOTE_ADDR',   'localhost');

        ob_start();
        $server = new Tinebase_Server_WebDAV();
        $server->handle($request);
        $result = ob_get_contents();
        ob_end_clean();

        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($result);
        $xpath = new DomXPath($responseDoc);
        $nodes = $xpath->query('//d:prop/d:lockdiscovery/d:activelock/d:locktoken/d:href');
        static::assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $lockToken = $nodes->item(0)->textContent;


        $request = Tinebase_Http_Request::fromString(<<<EOS
DELETE /webdav/Filemanager/shared/unittestdirectory/aTestFile HTTP/1.1\r
Host: localhost\r
Depth: 1\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r

EOS
        );

        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']     = '0';

        $request->getServer()->set('PHP_AUTH_USER', $credentials['username']);
        $request->getServer()->set('PHP_AUTH_PW',   $credentials['password']);
        $request->getServer()->set('REMOTE_ADDR',   'localhost');

        ob_start();
        $server = new Tinebase_Server_WebDAV();
        $server->handle($request);
        $result = ob_get_contents();
        ob_end_clean();
        static::assertContains('<s:exception>Sabre\DAV\Exception\Locked</s:exception>', $result);

        $request = Tinebase_Http_Request::fromString(<<<EOS
UNLOCK /webdav/Filemanager/shared/unittestdirectory/aTestFile HTTP/1.1\r
Host: localhost\r
Depth: 1\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r

EOS
        );

        $_SERVER['REQUEST_METHOD']  = $request->getMethod();
        $_SERVER['REQUEST_URI']     = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']      = '0';
        $_SERVER['HTTP_LOCK_TOKEN'] = $lockToken;

        $request->getServer()->set('PHP_AUTH_USER', $credentials['username']);
        $request->getServer()->set('PHP_AUTH_PW',   $credentials['password']);
        $request->getServer()->set('REMOTE_ADDR',   'localhost');
        $request->getServer()->set('HTTP_LOCK_TOKEN',   $lockToken);

        ob_start();
        $server = new Tinebase_Server_WebDAV();
        $server->handle($request);
        $result = ob_get_contents();
        ob_end_clean();

        static::assertEmpty($result);


        $request = Tinebase_Http_Request::fromString(<<<EOS
DELETE /webdav/Filemanager/shared/unittestdirectory/aTestFile HTTP/1.1\r
Host: localhost\r
Depth: 1\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r

EOS
        );

        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']     = '0';

        $request->getServer()->set('PHP_AUTH_USER', $credentials['username']);
        $request->getServer()->set('PHP_AUTH_PW',   $credentials['password']);
        $request->getServer()->set('REMOTE_ADDR',   'localhost');

        ob_start();
        $server = new Tinebase_Server_WebDAV();
        $server->handle($request);
        $result = ob_get_contents();
        ob_end_clean();

        static::assertEmpty($result);
        static::assertFalse(Tinebase_FileSystem::getInstance()
            ->isFile('Filemanager/folders/shared/unittestdirectory/aTestFile'), 'file was not deleted');
    }
    
    /**
     * testgetNodeForPath_webdav_filemanager_shared_unittestdirectory
     * 
     * @return Filemanager_Frontend_WebDAV_Container
     */
    public function testGetNodeForPath_webdav_filemanager_shared_unittestdirectory()
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

    public function testCreateFileInFilemanagerShared()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared');

        static::setExpectedException(\Sabre\DAV\Exception\Forbidden::class, null, 0,
            'it should not be possible to create a file in /webdav/Filemanager/shared folder');
        $node->createFile('test.file');
    }

    /**
     * testSharedACLs of shared node
     */
    public function testSharedACLs()
    {
        /** @var Filemanager_Frontend_WebDAV_Directory $sharedNode */
        $sharedNode = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared');
        $sharedNode->createDirectory('unittestdirectory');
        /** @var Filemanager_Frontend_WebDAV_Directory $createdNode */
        $createdNode = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory');

        $acl = $createdNode->getAcl();
        self::assertEquals(11, count($acl));

        $oldUser = Tinebase_Core::getUser();
        /** @var Tinebase_Model_FullUser $sClever */
        $sClever = $this->_personas['sclever'];

        $fsNode = Tinebase_FileSystem::getInstance()->get($createdNode->getId());
        $fsNode->grants = new Tinebase_Record_RecordSet(Tinebase_Model_Grants::class, [new Tinebase_Model_Grants([
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id'        => $sClever->getId(),
            Tinebase_Model_Grants::GRANT_READ => true,
        ])]);
        Tinebase_FileSystem::getInstance()->update($fsNode);

        try {
            Tinebase_Core::set(Tinebase_Core::USER, $sClever);

            $sharedNode->createDirectory('anotherTestDirectories');

            try {
                $createdNode->createDirectory('moreTestDirectories');
                static::fail('acl test failed');
            } catch (Sabre\DAV\Exception\Forbidden $f) {}

            $fileManagerId = Tinebase_Application::getInstance()->getApplicationByName('Filemanager')->getId();
            /** @var Tinebase_Model_Role $role */
            foreach (Tinebase_Acl_Roles::getInstance()->getAll() as $role) {
                $altered = false;
                $rights = array_filter(Tinebase_Acl_Roles::getInstance()->getRoleRights($role->getId()),
                    function($val) use($fileManagerId, &$altered) {
                        if ($fileManagerId === $val['application_id'] && $val['right'] ===
                            Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS) {
                            $altered = true;
                            return false;
                        }
                        return true;
                    });
                if ($altered) {
                    Tinebase_Acl_Roles::getInstance()->setRoleRights($role->getId(), $rights);
                }
            }
            Tinebase_Acl_Roles::unsetInstance();

            try {
                $sharedNode->createDirectory('moreTestDirectories');
                static::fail('creating shared folder in top level should require MANAGE_SHARED_FOLDERS right');
            } catch (Sabre\DAV\Exception\Forbidden $f) {}
            try {
                $createdNode->createDirectory('moreTestDirectories');
                static::fail('acl test failed');
            } catch (Sabre\DAV\Exception\Forbidden $f) {}
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
            Tinebase_Acl_Roles::unsetInstance();
        }
    }

    /**
     * get child folders of shared dir
     */
    public function testGetFoldersInShared()
    {
        // create folder in shared
        $path = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                'Filemanager', Tinebase_FileSystem::FOLDER_TYPE_SHARED
            ) . '/myshared';
        Tinebase_FileSystem::getInstance()->createAclNode($path);

        $sharedNode = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared');
        $nodes = $sharedNode->getChildren();
        self::assertGreaterThanOrEqual(1, count($nodes));
        $found = false;
        foreach ($nodes as $node) {
            if ($node->getName() === 'myshared') {
                $found = true;
            }
        }
        self::assertTrue($found);
    }
    
    /**
     * @return Filemanager_Frontend_WebDAV_File
     */
    public function testgetNodeForPath_webdav_filemanager_shared_unittestdirectory_file()
    {
        $parent = $this->testgetNodeForPath_webdav_filemanager_shared_unittestdirectory();
        $filename = dirname(__FILE__) . '/../../Tinebase/files/tine_logo.png';
        
        $etag = $parent->createFile('tine_logo.png', fopen($filename, 'r'));
        Tinebase_FileSystem::flushRefLogs();
        Tinebase_FileSystem::getInstance()->processRefLogs();
        Tinebase_FileSystem::getInstance()->clearStatCache();
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory/tine_logo.png');

        $this->assertInstanceOf('Filemanager_Frontend_WebDAV_File', $node, 'wrong node class');
        $this->assertTrue(is_resource($node->get()));
        $this->assertEquals('tine_logo.png', $node->getName());
        $this->assertEquals(7246, $node->getSize());
        $this->assertEquals('image/png', $node->getContentType());
        $this->assertEquals('"7424e2c16388bf388af1c4fe44c1dd67d31f468b"', $node->getETag());
        $this->assertTrue(preg_match('/"\w+"/', $etag) === 1);
        $this->assertTrue(fread($node->get(), 10000) == file_get_contents($filename), 'content not saved');

        $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared')->clearInstanceCache();
        $this->_getWebDAVTree()->markDirty('/webdav/Filemanager/shared/unittestdirectory');
        $updatedParent = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory');
        $props = $updatedParent->getProperties(['{DAV:}quota-available-bytes', '{DAV:}quota-used-bytes']);
        static::assertTrue(isset($props['{DAV:}quota-used-bytes']), 'property {DAV:}quota-used-bytes not available');
        static::assertGreaterThanOrEqual($node->getSize(), $props['{DAV:}quota-used-bytes']);

        static::assertTrue(isset($props['{DAV:}quota-available-bytes']),
            'property {DAV:}quota-available-bytes not available');
        static::assertGreaterThan(0, $props['{DAV:}quota-available-bytes']);
        
        return $node;
    }

    /**
     * @return Filemanager_Frontend_WebDAV_File
     */
    public function testgetNodeForPath_webdav_filemanager_shared_unittestdirectory_emptyfile()
    {
        $parent = $this->testgetNodeForPath_webdav_filemanager_shared_unittestdirectory();
        Tinebase_FileSystem::getInstance()->createFileTreeNode($parent->getContainer(), 'emptyFileTest');

        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory/emptyFileTest');

        $stream = $node->get();
        static::assertTrue(is_resource($stream));
        static::assertTrue(empty(@stream_get_contents($stream)));
        static::assertSame('"' . sha1($node->getNode()->object_id) . '"', $node->getETag());
    }
    
    public function testUpdateFile()
    {
        $node = $this->testgetNodeForPath_webdav_filemanager_shared_unittestdirectory_file();
        $eTag = $node->getETag();

        $updateFile = dirname(__FILE__) . '/../../Tinebase/files/tine_logo_setup.png';

        $updatedEtag = $node->put(fopen($updateFile, 'r'));

        $this->assertEquals('Filemanager_Frontend_WebDAV_File', get_class($node), 'wrong type');
        $this->assertNotEquals($eTag, $updatedEtag, 'eTag did not changed');
        $this->assertTrue(preg_match('/"\w+"/', $updatedEtag) === 1);

        $this->assertTrue(fread($node->get(), 10000) == file_get_contents($updateFile), 'content not updated');
    }

    public function testUpdateFileWithOCMTime()
    {
        $node = $this->testgetNodeForPath_webdav_filemanager_shared_unittestdirectory_file();
        $filename = dirname(__FILE__) . '/../../Tinebase/files/tine_logo.png';
        $updateFile = dirname(__FILE__) . '/../../Tinebase/files/tine_logo_setup.png';

        $mtime = Tinebase_DateTime::now()->subDay(1);
        $_SERVER['HTTP_X_OC_MTIME'] = $mtime->getTimestamp();

        $node->put(fopen($updateFile, 'r'));

        unset($_SERVER['HTTP_X_OC_MTIME']);

        $savedMTime = new Tinebase_DateTime($node->getLastModified());
        $this->assertEquals($mtime, $savedMTime, 'X_OC_MTIME not saved');
    }
    
    public function testgetNodeForPath_webdav_filemanager_currentuser2()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountLoginName);
    
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV', $node, 'wrong node class');
        $this->assertEquals(Tinebase_Core::getUser()->accountLoginName, $node->getName());
    
        $children = $node->getChildren();
    
        $this->assertGreaterThanOrEqual(1, count($children));
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV_Container', $children[0], 'wrong node class');
    
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
    
        $this->_getWebDAVTree()->delete('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountLoginName);
    }

    public function testCreateFileInFilemanagerOwnPersonalFolder()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/'
            . Tinebase_Core::getUser()->accountLoginName);

        static::setExpectedException(\Sabre\DAV\Exception\Forbidden::class, null, 0,
            'it should not be possible to create a file in own /webdav/Filemanager/personal folder');
        $node->createFile('test.file');
    }

    public function testCreateFileInFilemanagerForeignPersonalFolder()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/sclever');

        static::setExpectedException(\Sabre\DAV\Exception\Forbidden::class, null, 0,
            'it should not be possible to create a file in foreign /webdav/Filemanager/personal folder');
        $node->createFile('test.file');
    }

    public function testCreateFolderInFilemanagerForeignPersonalFolder()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/sclever');

        static::setExpectedException(\Sabre\DAV\Exception\Forbidden::class, null, 0,
            'it should not be possible to create a folder in foreign /webdav/Filemanager/personal folder');
        $node->createDirectory('testFolder');
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
