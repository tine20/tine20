<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

require_once 'vendor/sabre/dav/tests/Sabre/HTTP/ResponseMock.php';

/**
 * Test class for Filemanager_Frontend_Tree
 * 
 * @package     Filemanager
 */
class Filemanager_Frontend_WebDAVTest extends TestCase
{
    /**
     *
     * @var Sabre\DAV\Server
     */
    protected $server;

    /**
     *
     * @var Sabre\HTTP\ResponseMock
     */
    protected $response;

    /**
     * Tree
     *
     * @var Sabre\DAV\ObjectTree
     */
    protected $_webdavTree;

    protected $_oldLoginnameAsFoldername;

    protected function setUp(): void
    {
        parent::setUp();

        $this->_oldLoginnameAsFoldername = Tinebase_Config::getInstance()
            ->{Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME};

        // avoid cache issues
        $this->_webdavTree = null;
        $this->server = new Sabre\DAV\Server($this->_getWebDAVTree());
        $this->server->debugExceptions = true;

        $this->response = new Sabre\HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;
    }

    /**
     * tear down tests
     */
    protected function tearDown(): void
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
        
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/');
    }
    
    public function testGetNodeForPath_webdav()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav');
        
        $this->assertInstanceOf('\Sabre\DAV\SimpleCollection', $node, 'wrong node class');
        $this->assertEquals('webdav', $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertInstanceOf('Tinebase_WebDav_Collection_AbstractContainerTree', $children[0], 'wrong child class');
        
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        
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
        
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        
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

    /**
     * should not change default personal grants
     */
    public function testGetNodeForPath_webdav_filemanagerWithoutGrants_currentUser()
    {
        $nodeFsRootPath = '/webdav/Filemanager';
 
        // try to get folder /user , should always sync
        // it doesn't check grants in \Tinebase_WebDav_Collection_AbstractContainerTree::_getUser
        $children = $this->_getNewWebDAVTreeNode($nodeFsRootPath)->getChildren();
        static::assertCount(2, $children, 'child root nodes should always be synced');
        
        // try to get folder /user/personal, should always sync
        // check grants in \Tinebase_WebDav_Collection_AbstractContainerTree::_getSharedChildren
        $nodeUserRootPath = $nodeFsRootPath . '/'. Tinebase_Core::getUser()->accountDisplayName;
        $children = $this->_getNewWebDAVTreeNode($nodeUserRootPath)->getChildren();
        static::assertGreaterThanOrEqual(1, count($children), 'child node personal default should be synced');

        // try to get folder /user/personal/dir1
        // check grants in \Tinebase_Frontend_WebDAV_Directory::getChildren
        $nodeUserRoot = $this->_getNewWebDAVTreeNode($nodeUserRootPath);
        $nodeUserDefault = current($nodeUserRoot->getChildren());
        $nodeUserDefault->createDirectory('dir1');
        $nodeUserDefaultPath = $nodeUserRootPath . '/' . $nodeUserDefault->getName();
        $children = $this->_getNewWebDAVTreeNode($nodeUserDefaultPath)->getChildren();
        static::assertCount(1, $children, 'child node dir1 should be synced');
        
        // try to get folder /user/personal/dir1/dir2
        // check grants in \Tinebase_Frontend_WebDAV_Directory::getChildren
        $nodeDir1 = current($nodeUserDefault->getChildren());
        $nodeDir1->createDirectory('dir2');
        $nodeDir1Path = $nodeUserDefaultPath . '/' . $nodeDir1->getName();
        $children = $this->_getNewWebDAVTreeNode($nodeDir1Path)->getChildren();
        static::assertCount(1, $children, 'child node dir2 should be synced');
    }

    /**
     * @throws Tinebase_Exception_NotFound
     * @throws \Sabre\DAV\Exception\NotFound
     */
    public function testGetNodeForPath_webdav_filemanagerWithoutGrants_otherUser()
    {
        $nodeFsRootPath = '/webdav/Filemanager';
  
        // try to get folder /user
        // check grants in \Filemanager_Frontend_WebDAV::_getOtherUsersChildren
        $treeNodeUserRootPath = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager', Tinebase_FileSystem::FOLDER_TYPE_PERSONAL) . '/' .
            Tinebase_Core::getUser()->getId();
        $treeNodeUserRoot = Tinebase_FileSystem::getInstance()->stat($treeNodeUserRootPath);
        $treeNodeUserDefault = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($treeNodeUserRoot)->getFirstRecord();
        $this->_testGrantsHelper($treeNodeUserDefault, $nodeFsRootPath);

        // try to get folder /user/personal ,
        // check grants in \Tinebase_WebDav_Collection_AbstractContainerTree::_getSharedChildren
        $nodeUserRootPath = $nodeFsRootPath . '/'. Tinebase_Core::getUser()->accountDisplayName;
        $this->_testGrantsHelper($treeNodeUserDefault, $nodeUserRootPath);

        // try to get folder /user/personal/dir1
        // check grants in \Tinebase_Frontend_WebDAV_Directory::getChildren
        $nodeUserRoot = $this->_getNewWebDAVTreeNode($nodeUserRootPath);
        $nodeUserDefault = current($nodeUserRoot->getChildren());
        $nodeUserDefaultPath = $nodeUserRootPath . '/' . $nodeUserDefault->getName();
        $nodeUserDefault->createDirectory('dir1');
        $treeNodeDir1 = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($treeNodeUserDefault)->getFirstRecord();
        $this->_testGrantsHelper($treeNodeDir1, $nodeUserDefaultPath);

        // try to get folder /user/personal/dir1/dir2
        // check grants in \Tinebase_Frontend_WebDAV_Directory::getChildren
        $nodeDir1 = current($nodeUserDefault->getChildren());
        $nodeDir1->createDirectory('dir2');
        $nodeDir1Path = $nodeUserDefaultPath . '/' . $nodeDir1->getName();
        $treeNodeDir2 = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($treeNodeDir1)->getFirstRecord();
        $this->_testGrantsHelper($treeNodeDir2, $nodeDir1Path);
    }

    /**
     * @throws Tinebase_Exception_NotFound
     * @throws \Sabre\DAV\Exception\NotFound
     */
    public function testGetNodeForPath_webdav_filemanagerWithoutGrants_shared()
    {
        $nodeFsRootPath = '/webdav/Filemanager';
        // try to get folder /shared , should always sync and ignore grants setting
        $children = $this->_getNewWebDAVTreeNode($nodeFsRootPath)->getChildren();
        static::assertCount(2, $children, 'child root nodes should always be sync');
        
        // try to get folder /shared/dir1
        // check grants in \Tinebase_WebDav_Collection_AbstractContainerTree::_getSharedDirectories
        $nodeSharedRootPath = $nodeFsRootPath . '/'. 'shared';
        $nodeSharedRoot = $this->_getNewWebDAVTreeNode($nodeSharedRootPath);
        $nodeSharedRoot->createDirectory('dir1');
        $treeNodeSharedRootPath = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager', Tinebase_FileSystem::FOLDER_TYPE_SHARED);
        $treeNodeSharedRoot = Tinebase_FileSystem::getInstance()->stat($treeNodeSharedRootPath);
        $treeNodeDir1 = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($treeNodeSharedRoot)->getFirstRecord();
        $this->_testGrantsHelper($treeNodeDir1, $nodeSharedRootPath);
        
        // try to get folder /shared/dir1/dir2
        // check grants in \Tinebase_Frontend_WebDAV_Directory::getChildren
        $nodeDir1 = current($nodeSharedRoot->getChildren());
        $nodeDir1->createDirectory('dir2');
        $nodeDir1Path = $nodeSharedRootPath . '/' . $nodeDir1->getName();
        $treeNodeDir2 = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($treeNodeDir1)->getFirstRecord();
        $this->_testGrantsHelper($treeNodeDir2, $nodeDir1Path);
    }

    /**
     * @throws Tinebase_Exception_NotFound
     * @throws \Sabre\DAV\Exception\NotFound
     */
    public function testGetNodeForPath_webdav_filemanager_with_pin_protection_shared()
    {
        $nodeFsRootPath = '/webdav/Filemanager';
        // try to get folder /shared , should always sync and ignore grants setting
        $children = $this->_getNewWebDAVTreeNode($nodeFsRootPath)->getChildren();
        static::assertCount(2, $children, 'child root nodes should always be sync');

        // try to get folder /shared/dir1
        // check grants in \Tinebase_WebDav_Collection_AbstractContainerTree::_getSharedDirectories
        $nodeSharedRootPath = $nodeFsRootPath . '/'. 'shared';
        $nodeSharedRoot = $this->_getNewWebDAVTreeNode($nodeSharedRootPath);
        $nodeSharedRoot->createDirectory('dir1');
        $treeNodeSharedRootPath = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager', Tinebase_FileSystem::FOLDER_TYPE_SHARED);
        $treeNodeSharedRoot = Tinebase_FileSystem::getInstance()->stat($treeNodeSharedRootPath);
        $treeNodeDir1 = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($treeNodeSharedRoot)->getFirstRecord();
        $this->_testPinProtectionHelper($treeNodeDir1, $nodeSharedRootPath);

        // try to get folder /shared/dir1/dir2
        // check grants in \Tinebase_Frontend_WebDAV_Directory::getChildren
        $nodeDir1 = current($nodeSharedRoot->getChildren());
        $nodeDir1->createDirectory('dir2');
        $nodeDir1Path = $nodeSharedRootPath . '/' . $nodeDir1->getName();
        $treeNodeDir2 = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($treeNodeDir1)->getFirstRecord();
        $this->_testPinProtectionHelper($treeNodeDir2, $nodeDir1Path);
    }
    
    /**
     * node should only be sync when user has both READ_GRANT and SYNC_GRANT
     * 
     * @throws Tinebase_Exception_NotFound
     * @throws \Sabre\DAV\Exception\NotFound
     */
    protected function _testGrantsHelper($folder, $nodePath, $isForcedSyncNode = false)
    {
        if (! $folder) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . ' FIXME: Folder not set, skipping grant checks');
            return;
        }

        Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($folder);
        if (! $folder->grants instanceof Tinebase_Record_RecordSet) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . ' FIXME: Folder has no grants, skipping grant checks: '
                . print_r($folder->toArray(), true));
            return;
        }

        $testSyncUser = $this->_personas['sclever'];
        $hideNodesCount = ! $isForcedSyncNode ? 1 : 0;

        // set default grant for test sync user first
        $folder->grants->addRecord(new Tinebase_Model_Grants([
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id' => $testSyncUser->getId(),
            Tinebase_Model_Grants::GRANT_ADMIN => true
        ]));
        Tinebase_FileSystem::getInstance()->setGrantsForNode($folder, $folder->grants);

        // change current user to test the sync ability
        Tinebase_Core::setUser($testSyncUser);
        $expectChildren = $this->_getNewWebDAVTreeNode($nodePath)->getChildren();
        $expectChildCount = count($expectChildren);

        // assert sync only without read_grant nor sync_grant
        foreach ($folder->grants as $grant) {
            if ($grant->account_id === $testSyncUser->getId() || $grant->account_type === Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP) {
                $grant->adminGrant = false;
                $grant->syncGrant = false;
                $grant->readGrant = false;
            }
        }
        Tinebase_FileSystem::getInstance()->setGrantsForNode($folder, $folder->grants);
        $children = $this->_getNewWebDAVTreeNode($nodePath)->getChildren();
        static::assertCount($expectChildCount - $hideNodesCount, $children, 'children node should not be sync');

        // assert sync only with read_grant or sync_grant
        foreach ($folder->grants as $grant) {
            if ($grant->account_id === $testSyncUser->getId()) {
                $grant['syncGrant'] = false;
                $grant['readGrant'] = true;
            }
        }
        Tinebase_FileSystem::getInstance()->setGrantsForNode($folder, $folder->grants);
        $children = $this->_getNewWebDAVTreeNode($nodePath)->getChildren();
        static::assertCount($expectChildCount - $hideNodesCount, $children, 'children node should not be sync');

        foreach ($folder->grants as $grant) {
            if ($grant->account_id === $testSyncUser->getId()) {
                $grant['syncGrant'] = true;
                $grant['readGrant'] = false;
            }
        }
        Tinebase_FileSystem::getInstance()->setGrantsForNode($folder, $folder->grants);
        $children = $this->_getNewWebDAVTreeNode($nodePath)->getChildren();
        static::assertCount($expectChildCount - $hideNodesCount, $children, 'children node should not be sync');

        // assert sync only with both read_grant and sync_grant
        foreach ( $folder->grants as $grant) {
            if ($grant->account_id === $testSyncUser->getId()) {
                $grant['syncGrant'] = true;
                $grant['readGrant'] = true;
            }
        }
        Tinebase_FileSystem::getInstance()->setGrantsForNode($folder, $folder->grants);
        $children = $this->_getNewWebDAVTreeNode($nodePath)->getChildren();
        static::assertCount($expectChildCount, $children, 'children node should be sync');

        // reset current user and folder grants for operating nodes
        Tinebase_Core::setUser($this->_originalTestUser);
        
        foreach ( $folder->grants as $grant) {
            if ($grant->account_id === $testSyncUser->getId()) {
                $folder->grants->removeRecord($grant);
            }
        }
    }
    
    public function _testPinProtectionHelper($folder, $nodePath)
    {
        // should detect delete
        if (! $folder) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . ' FIXME: Folder not set, skipping grant checks');
            return;
        }

        Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($folder);
        if (! $folder->grants instanceof Tinebase_Record_RecordSet) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . ' FIXME: Folder has no grants, skipping grant checks: '
                . print_r($folder->toArray(), true));
            return;
        }

        $testSyncUser = $this->_personas['sclever'];

        // set default grant for test sync user first
        $folder->grants->addRecord(new Tinebase_Model_Grants([
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id' => $testSyncUser->getId(),
            Tinebase_Model_Grants::GRANT_ADMIN => true
        ]));
        Tinebase_FileSystem::getInstance()->setGrantsForNode($folder, $folder->grants);

        // change current user to test the sync ability
        Tinebase_Core::setUser($testSyncUser);
        $originalChildren = $this->_getNewWebDAVTreeNode($nodePath)->getChildren();

        // set to pin protection
        $folder['pin_protected_node'] = $folder['id'];
        $folder = Filemanager_Controller_Node::getInstance()->update($folder);
        
        $children = $this->_getNewWebDAVTreeNode($nodePath)->getChildren();
        static::assertCount(count($originalChildren) - 1 , $children, 'children node should not be sync');
        
        // reset current user and folder grants for operating nodes
        Tinebase_Core::setUser($this->_originalTestUser);

        foreach ( $folder->grants as $grant) {
            if ($grant->account_id === $testSyncUser->getId()) {
                $folder->grants->removeRecord($grant);
            }
        }

        $folder['pin_protected_node'] = null;
        Filemanager_Controller_Node::getInstance()->update($folder);
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
        
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav/Filemanager/personal');
    }
    
    public function testGetNodeForPath_webdav_filemanager_currentuser()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountDisplayName);
        
        $this->assertInstanceOf('Filemanager_Frontend_WebDAV', $node, 'wrong node class');
        $this->assertEquals(Tinebase_Core::getUser()->accountDisplayName, $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertGreaterThanOrEqual(1, count($children));
        $this->assertInstanceOf(Filemanager_Frontend_WebDAV_Directory::class, $children[0], 'wrong node class');
        
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        
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
        
        $this->assertInstanceOf(Filemanager_Frontend_WebDAV_Directory::class, $node, 'wrong node class');
        $this->assertEquals('unittestdirectory', $node->getName());
        
        $children = $this->_getWebDAVTree()->getChildren('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountDisplayName);
        foreach ($children as $node) {
            $names[] = $node->getName();
        }
        $this->assertContains('unittestdirectory', $names);
        
        $this->_getWebDAVTree()->delete('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountDisplayName .'/unittestdirectory');
        
        $this->expectException('Sabre\DAV\Exception\NotFound');

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
        
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav/Filemanager/shared');
    }

    public function testMoveDir()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->createAclNode('/Filemanager/folders/shared/a');
        $fs->createAclNode(($oldPath = 'Filemanager/folders/shared/a/unittestdirectory'));
        $fs->createAclNode(($newPath = 'Filemanager/folders/shared/a/unittestdirectory1'));
        $this->assertNotFalse(
            file_put_contents('tine20://Filemanager/folders/shared/a/unittestdirectory/aTestFile.test', 'unittesting'));

        $uNode = $fs->stat($oldPath);
        $fNode = $fs->stat($oldPath . '/aTestFile.test');

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD'    => 'MOVE',
            'REQUEST_URI'       => '/webdav/Filemanager/shared/a/unittestdirectory',
            'HTTP_DESTINATION'  => '/webdav/Filemanager/shared/a/unittestdirectory1/new',
        ));

        $this->server->httpRequest = $request;
        $this->server->exec();

        $fs->clearStatCache();
        $this->assertSame('HTTP/1.1 201 Created', $this->response->status);
        $this->assertFalse($fs->isFile($oldPath . '/aTestFile.test'));
        $this->assertTrue($fs->isFile($newPath . '/new/aTestFile.test'));
        $fs->clearStatCache();
        $this->assertFalse($fs->isFile($oldPath . '/aTestFile.test'));
        $this->assertTrue($fs->isFile($newPath . '/new/aTestFile.test'));

        $this->assertSame('unittesting',
            file_get_contents('tine20://Filemanager/folders/shared/a/unittestdirectory1/new/aTestFile.test'));

        $newu1Node = $fs->stat($newPath);
        $newNode = $fs->stat($newPath . '/new');
        $newfNode = $fs->stat($newPath . '/new/aTestFile.test');

        $this->assertSame($newNode->getId(), $uNode->getId());
        $this->assertSame($newNode->parent_id, $newu1Node->getId());
        $this->assertSame($newfNode->parent_id, $newNode->getId());
        $this->assertSame($newfNode->getId(), $fNode->getId());
    }

    public function testMoveWithoutGrant()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->mkdir('Filemanager/folders/shared/unittestdirectory');

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'MOVE',
            'REQUEST_URI' => '/webdav/Filemanager/shared/unittestdirectory',
            'HTTP_DESTINATION' => '/webdav/Filemanager/shared/unittestdirectory1',
        ));

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertSame('HTTP/1.1 403 Forbidden', $this->response->status);
        $this->assertTrue($fs->isDir('Filemanager/folders/shared/unittestdirectory'));
        $this->assertFalse($fs->fileExists('Filemanager/folders/shared/unittestdirectory1'));
    }

    public function testMoveWithoutGrant1()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->mkdir('Filemanager/folders/shared/unittestdirectory');
        $fs->createAclNode('Filemanager/folders/shared/unittestdirectory1');

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'MOVE',
            'REQUEST_URI' => '/webdav/Filemanager/shared/unittestdirectory',
            'HTTP_DESTINATION' => '/webdav/Filemanager/shared/unittestdirectory1/foo',
        ));

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertSame('HTTP/1.1 403 Forbidden', $this->response->status);
        $this->assertTrue($fs->isDir('Filemanager/folders/shared/unittestdirectory'));
        $this->assertFalse($fs->fileExists('Filemanager/folders/shared/unittestdirectory1/foo'));
    }

    public function testMoveWithGrant()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->createAclNode('Filemanager/folders/shared/unittestdirectory');

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'MOVE',
            'REQUEST_URI' => '/webdav/Filemanager/shared/unittestdirectory',
            'HTTP_DESTINATION' => '/webdav/Filemanager/shared/unittestdirectory1',
        ));

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertSame('HTTP/1.1 201 Created', $this->response->status);
        $this->assertFalse($fs->fileExists('Filemanager/folders/shared/unittestdirectory'));
        $this->assertTrue($fs->isDir('Filemanager/folders/shared/unittestdirectory1'));
    }

    public function testMoveWithGrant1()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->createAclNode('Filemanager/folders/shared/unittestdirectory');
        $fs->createAclNode('Filemanager/folders/shared/unittestdirectory1');

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'MOVE',
            'REQUEST_URI' => '/webdav/Filemanager/shared/unittestdirectory',
            'HTTP_DESTINATION' => '/webdav/Filemanager/shared/unittestdirectory1/foo',
        ));

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertSame('HTTP/1.1 201 Created', $this->response->status);
        $this->assertFalse($fs->fileExists('Filemanager/folders/shared/unittestdirectory'));
        $this->assertTrue($fs->isDir('Filemanager/folders/shared/unittestdirectory1/foo'));
    }

    public function testMove($destination = null)
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->createAclNode(($oldPath = 'Filemanager/folders/shared/unittestdirectory'));
        $fs->createAclNode(($newPath = 'Filemanager/folders/shared/unittestdirectory1'));
        $this->assertNotFalse(
            file_put_contents('tine20://Filemanager/folders/shared/unittestdirectory/aTestFile.test', 'unittesting'));

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD'    => 'MOVE',
            'REQUEST_URI'       => '/webdav/Filemanager/shared/unittestdirectory/aTestFile.test',
            'HTTP_DESTINATION'  => $destination ?: '/webdav/Filemanager/shared/unittestdirectory1/aTestFile.test',
        ));

        $this->server->httpRequest = $request;
        $this->server->exec();

        if ($destination) return;

        $this->assertSame('HTTP/1.1 201 Created', $this->response->status);
        $this->assertFalse($fs->isFile($oldPath . '/aTestFile.test'));
        $this->assertTrue($fs->isFile($newPath . '/aTestFile.test'));
        $fs->clearStatCache();
        $this->assertFalse($fs->isFile($oldPath . '/aTestFile.test'));
        $this->assertTrue($fs->isFile($newPath . '/aTestFile.test'));

        $this->assertSame('unittesting',
            file_get_contents('tine20://Filemanager/folders/shared/unittestdirectory1/aTestFile.test'));
    }

    public function testMove1()
    {
        $this->testMove('/webdav/Filemanager/shared/unittestdirectory1/');
        $this->assertSame('HTTP/1.1 204 No Content', $this->response->status);

        $fs = Tinebase_FileSystem::getInstance();
        $oldPath = 'Filemanager/folders/shared/unittestdirectory/aTestFile.test';
        $newPath = 'Filemanager/folders/shared/unittestdirectory1';

        $this->assertFalse($fs->isFile($oldPath));
        $this->assertTrue($fs->isFile($newPath));
        $fs->clearStatCache();
        $this->assertFalse($fs->isFile($oldPath));
        $this->assertTrue($fs->isFile($newPath));

        $this->assertSame('unittesting',
            file_get_contents('tine20://Filemanager/folders/shared/unittestdirectory1'));
    }

    public function testMove2()
    {
        $this->testMove('/webdav/Filemanager/shared/unittestdirectory1');
        $this->assertSame('HTTP/1.1 204 No Content', $this->response->status);

        $fs = Tinebase_FileSystem::getInstance();
        $oldPath = 'Filemanager/folders/shared/unittestdirectory/aTestFile.test';
        $newPath = 'Filemanager/folders/shared/unittestdirectory1';

        $this->assertFalse($fs->isFile($oldPath));
        $this->assertTrue($fs->isFile($newPath));
        $fs->clearStatCache();
        $this->assertFalse($fs->isFile($oldPath));
        $this->assertTrue($fs->isFile($newPath));

        $this->assertSame('unittesting',
            file_get_contents('tine20://Filemanager/folders/shared/unittestdirectory1'));
    }

    public function testMove3()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->createAclNode('Filemanager/folders/shared/foo');
        $fs->createAclNode('Filemanager/folders/shared/foo/unittestdirectory1');

        $this->testMove('/webdav/Filemanager/shared/foo/unittestdirectory1/aTestFile.test');
        $this->assertSame('HTTP/1.1 201 Created', $this->response->status);

        $fs = Tinebase_FileSystem::getInstance();
        $oldPath = 'Filemanager/folders/shared/unittestdirectory/aTestFile.test';
        $newPath = 'Filemanager/folders/shared/foo/unittestdirectory1/aTestFile.test';

        $this->assertFalse($fs->isFile($oldPath));
        $this->assertTrue($fs->isFile($newPath));
        $fs->clearStatCache();
        $this->assertFalse($fs->isFile($oldPath));
        $this->assertTrue($fs->isFile($newPath));

        $this->assertSame('unittesting',
            file_get_contents('tine20://Filemanager/folders/shared/foo/unittestdirectory1/aTestFile.test'));
    }

    public function testMove4()
    {
        $user = Tinebase_Core::getUser();
        Filemanager_Controller_Node::getInstance()->createNodes(
            ['/personal/' . $user->getId() . '/home'], Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        $this->testMove('/remote.php/webdav/' . (
            Tinebase_Config::getInstance()->get(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME) ? $user->accountLoginName
                : $user->accountDisplayName) . '/home/aTestFile.test');
        $this->assertSame('HTTP/1.1 201 Created', $this->response->status);

        $fs = Tinebase_FileSystem::getInstance();
        $oldPath = 'Filemanager/folders/shared/unittestdirectory/aTestFile.test';
        $newPath = 'Filemanager/folders/personal/' . $user->getId() . '/home/aTestFile.test';

        $this->assertFalse($fs->isFile($oldPath));
        $this->assertTrue($fs->isFile($newPath));
        $fs->clearStatCache();
        $this->assertFalse($fs->isFile($oldPath));
        $this->assertTrue($fs->isFile($newPath));

        $this->assertSame('unittesting',
            file_get_contents('tine20://' . $newPath));
    }

    public function testMove5()
    {
        $user = Tinebase_Core::getUser();
        $this->testMove('/remote.php/webdav/' . (
            Tinebase_Config::getInstance()->get(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME) ? $user->accountLoginName
                : $user->accountDisplayName) . '/aTestFile.test');
        $this->assertSame('HTTP/1.1 403 Forbidden', $this->response->status);
    }

    public function testPutWithUrlencode()
    {
        $this->_skipIfLDAPBackend('FIXME: auth has a problem with LDAP backend');

        $credentials = TestServer::getInstance()->getTestCredentials();

        Tinebase_FileSystem::getInstance()->createAclNode('Filemanager/folders/shared/unittestdirectory');

        $request = Tinebase_Http_Request::fromString(<<<EOS
PUT /webdav/Filemanager/shared/unittestdirectory/aTestFile%25.test HTTP/1.1\r
Host: localhost\r
Content-Length: 8\r
 Content-Type: application/octet-stream" \r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r
\r
abcdefgh
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
        ob_end_clean();

        Tinebase_Core::setUser($this->_originalTestUser);
        static::assertSame('abcdefgh', file_get_contents(
            'tine20://Filemanager/folders/shared/unittestdirectory/aTestFile%.test'));
    }

    /**
     * test (UN)LOCK functionality of WebDAV
     * @group ServerTests
     */
    public function testUNandLOCKQueries()
    {
        $this->_skipIfLDAPBackend('FIXME: auth has a problem with LDAP backend');

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
        static::assertStringContainsString('<s:exception>Sabre\DAV\Exception\Locked</s:exception>', $result);

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
     * @return Filemanager_Frontend_WebDAV_Direcotry
     */
    public function testGetNodeForPath_webdav_filemanager_shared_unittestdirectory()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared');

        $node->createDirectory('unittestdirectory');

        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory');

        $this->assertInstanceOf(Filemanager_Frontend_WebDAV_Directory::class, $node, 'wrong node class');
        $this->assertEquals('unittestdirectory', $node->getName());
        
        $children = $node->getChildren();
        
        $properties = $node->getProperties(array());
        
        return $node;
    }

    public function testCreateFileInFilemanagerShared()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared');

        // it should not be possible to create a file in /webdav/Filemanager/shared folder
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $this->expectExceptionCode(0);

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
        Tinebase_FileSystem::getInstance()->createFileTreeNode($parent->getNode(), 'emptyFileTest');

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

    public function testCreateFile()
    {
        $parent = $this->testgetNodeForPath_webdav_filemanager_shared_unittestdirectory();
        $rc = fopen('php://temp', 'r');
        $etag = $parent->createFile('AR-0394xa5 => GS-00sb064', $rc);
        fclose($rc);
        self::assertRegExp('/"[a-z0-9]+"/', $etag);
    }

    public function testUpdateFileWithOCMTime()
    {
        $node = $this->testgetNodeForPath_webdav_filemanager_shared_unittestdirectory_file();
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
        $this->assertInstanceOf(Filemanager_Frontend_WebDAV_Directory::class, $children[0], 'wrong node class');
    
        $this->expectException('Sabre\DAV\Exception\Forbidden');
    
        $this->_getWebDAVTree()->delete('/webdav/Filemanager/' . Tinebase_Core::getUser()->accountLoginName);
    }

    public function testCreateFileInFilemanagerOwnPersonalFolder()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/'
            . Tinebase_Core::getUser()->accountLoginName);

        // it should not be possible to create a file in own /webdav/Filemanager/personal folder
        static::expectException(\Sabre\DAV\Exception\Forbidden::class);

        $node->createFile('test.file');
    }

    public function testCreateFileInFilemanagerForeignPersonalFolder()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/sclever');

        // it should not be possible to create a file in foreign /webdav/Filemanager/personal folder
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);

        $node->createFile('test.file');
    }

    public function testCreateFolderInFilemanagerForeignPersonalFolder()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/sclever');

        // it should not be possible to create a folder in foreign /webdav/Filemanager/personal folder
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);

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
    
        $this->expectException('Sabre\DAV\Exception\NotFound');
        
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
        $this->expectException('Sabre\DAV\Exception\NotFound');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/invalidApplication');
    }
    
    public function testgetNodeForPath_invalidContainerType()
    {
        $this->expectException('Sabre\DAV\Exception\NotFound');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/invalidContainerType');
    }
    
    public function testgetNodeForPath_invalidFolder()
    {
        $this->expectException('Sabre\DAV\Exception\NotFound');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Filemanager/shared/invalidContainer');
    }
    
    /**
     * 
     * @return \Sabre\DAV\ObjectTree
     */
    protected function _getWebDAVTree()
    {
        if (! $this->_webdavTree instanceof \Sabre\DAV\ObjectTree) {
            $this->_webdavTree = new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root());
        }
        
        return $this->_webdavTree;
    }

    /**
     *
     * @return \Sabre\DAV\ICollection|\Sabre\DAV\INode|\Sabre\DAV\ObjectTree
     */
    protected function _getNewWebDAVTreeNode($path)
    {   
        $node = new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root());
        $node = $node->getNodeForPath($path);
        return $node;
    }
}
