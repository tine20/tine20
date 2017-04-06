<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Filemanager_Frontend_Json
 * 
 * @package     Filemanager
 */
class Filemanager_Frontend_JsonTests extends TestCase
{
    /**
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * uit
     *
     * @var Filemanager_Frontend_Json
     */
    protected $_json;
    
    /**
     * fs controller
     *
     * @var Tinebase_FileSystem
     */
    protected $_fsController;
    
    /**
     * filemanager app
     *
     * @var Tinebase_Model_Application
     */
    protected $_application;
    
    /**
     * personal container
     *
     * @var Tinebase_Model_Container
     */
    protected $_personalContainer;
    
    /**
     * shared container
     *
     * @var Tinebase_Model_Container
     */
    protected $_sharedContainer;
    
    /**
     * other user container
     *
     * @var Tinebase_Model_Container
     */
    protected $_otherUserContainer;

    /**
     * folder paths to be deleted
     *
     * @var array
     */
    protected $_rmDir = array();
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->_json = new Filemanager_Frontend_Json();
        $this->_fsController = Tinebase_FileSystem::getInstance();
        $this->_application = Tinebase_Application::getInstance()->getApplicationByName('Filemanager');
        $this->_rmDir = array();

        // make sure account root node exists
        $this->_getPersonalFilemanagerContainer();
    }
    
    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        parent::tearDown();

        if (count($this->_rmDir) > 0) {
            foreach($this->_rmDir as $dir) {
                $this->_json->deleteNodes($dir);
            }
        }
        
        Tinebase_FileSystem::getInstance()->clearStatCache();
        Tinebase_FileSystem::getInstance()->clearDeletedFilesFromFilesystem();
        
        $this->_personalContainer  = null;
        $this->_sharedContainer    = null;
        $this->_otherUserContainer = null;
    }
    
    /**
     * test search nodes (personal)
     */
    public function testSearchRoot()
    {
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/'
        ));
        $result = $this->_json->searchNodes($filter, array());
        $this->_assertRootNodes($result);
    }
    
    /**
     * assert 3 root nodes
     * 
     * @param array $searchResult
     */
    protected function _assertRootNodes($searchResult)
    {
        $this->assertEquals(3, $searchResult['totalcount'], 'did not get root nodes: ' . print_r($searchResult, TRUE));
        $this->assertEquals('/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName, $searchResult['results'][0]['path']);
    }
    
    /**
     * test search nodes (personal)
     */
    public function testSearchPersonalNodes()
    {
        $this->_setupTestPath(Tinebase_FileSystem::FOLDER_TYPE_PERSONAL);
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/' . $this->_getPersonalFilemanagerContainer()->name
        ));
        $this->_searchHelper($filter, 'unittestdir_personal');
    }
    
    /**
     * search node helper
     * 
     * @param array $_filter
     * @param string $_expectedName
     * @return array search result
     */
    protected function _searchHelper($_filter, $_expectedName, $_toplevel = FALSE, $_checkAccountGrants = TRUE)
    {
        $result = $this->_json->searchNodes($_filter, array('sort' => 'size'));
        
        $this->assertGreaterThanOrEqual(1, $result['totalcount'], 'expected at least one entry');
        if ($_toplevel) {
            $found = FALSE;
            foreach ($result['results'] as $container) {
                // toplevel containers are resolved (array structure below [name])
                if ($_expectedName == $container['name']) {
                    $found = TRUE;
                    if ($_checkAccountGrants) {
                        $this->assertTrue(isset($container['account_grants']));
                        $this->assertEquals(Tinebase_Core::getUser()->getId(), $container['account_grants']['account_id']);
                    }
                }
            }
            $this->assertTrue($found, 'container not found: ' . print_r($result['results'], TRUE));
        } else {
            $this->assertTrue(isset($result['results'][0]), print_r($result, true));
            $this->assertEquals($_expectedName, $result['results'][0]['name']);
        }
        
        if ($_checkAccountGrants) {
            $this->assertTrue(isset($result['results'][0]['account_grants']));
            $this->assertEquals(Tinebase_Core::getUser()->getId(), $result['results'][0]['account_grants']['account_id']);
        }
        
        return $result;
    }
    
    /**
     * test search nodes (shared)
     */
    public function testSearchSharedNodes()
    {
        $this->_setupTestPath(Tinebase_FileSystem::FOLDER_TYPE_SHARED);
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/' . $this->_getSharedContainer()->name
        ));
        $this->_searchHelper($filter, 'unittestdir_shared');
    }
    
    /**
     * test search nodes (other)
     */
    public function testSearchOtherUsersNodes()
    {
        $this->_setupTestPath(Tinebase_Model_Container::TYPE_OTHERUSERS);
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/sclever/' . $this->_getOtherUserContainer()->name
        ));
        $this->_searchHelper($filter, 'unittestdir_other');
    }
    
    /**
     * search top level containers of user
     * 
     * @see 0007400: Newly created directories disappear
     */
    public function testSearchTopLevelContainersOfUser()
    {
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName
        ));
        $this->_searchHelper($filter, $this->_getPersonalFilemanagerContainer()->name, TRUE);
        
        $another = $this->testCreateContainerNodeInPersonalFolder();
        $this->_searchHelper($filter, $another['name'], TRUE);
    }

    /**
     * search shared top level containers 
     */
    public function testSearchSharedTopLevelContainers()
    {
        $this->_setupTestPath(Tinebase_FileSystem::FOLDER_TYPE_SHARED);
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED
        ));
        $result = $this->_searchHelper($filter, $this->_getSharedContainer()->name, TRUE);
    }

    /**
     * search top level containers of other users
     */
    public function testSearchTopLevelContainersOfOtherUsers()
    {
        $this->_getOtherUserContainer();
        
        $filter = array(
            array(
                'field'    => 'path', 
                'operator' => 'equals', 
                'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL
            )
        );
        $this->_searchHelper($filter, 'clever', FALSE, FALSE);
    }

    /**
     * search containers of other user
     */
    public function testSearchContainersOfOtherUser()
    {
        $this->_setupTestPath(Tinebase_Model_Container::TYPE_OTHERUSERS);
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/sclever'
        ), array(
            'field'    => 'creation_time', 
            'operator' => 'within', 
            'value'    => 'weekThis',
        ));
        $result = $this->_searchHelper($filter, $this->_getOtherUserContainer()->name, TRUE);
        
        $expectedPath = $filter[0]['value'] . '/' . $this->_getOtherUserContainer()->name;
        $this->assertEquals($expectedPath, $result['results'][0]['path'], 'node path mismatch');
        $this->assertEquals($filter[0]['value'], $result['filter'][0]['value']['path'], 'filter path mismatch');
    }

    /**
     * testSearchWithInvalidPath
     * 
     * @see 0007110: don't show exception for invalid path filters
     */
    public function testSearchWithInvalidPath()
    {
        // wrong user
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/xyz'
        ));
        
        $result = $this->_json->searchNodes($filter, array());
        $this->_assertRootNodes($result);
        
        // wrong type
        $filter[0]['value'] = '/lala';
        $result = $this->_json->searchNodes($filter, array());
        $this->_assertRootNodes($result);

        // no path filter
        $result = $this->_json->searchNodes(array(), array());
        $this->_assertRootNodes($result);
    }
    
    /**
     * create container in personal folder
     * 
     * @return array created node
     */
    public function testCreateContainerNodeInPersonalFolder($containerName = 'testcontainer')
    {
        $testPath = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/' . $containerName;
        $result = $this->_json->createNodes($testPath, Tinebase_Model_Tree_Node::TYPE_FOLDER, array(), FALSE);
        $createdNode = $result[0];
        
        $this->assertTrue(isset($createdNode['name']));
        $this->assertEquals($containerName, $createdNode['name']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $createdNode['created_by']['accountId']);
        
        return $createdNode;
    }

    /**
     * create container with bad name
     * 
     * @see 0006524: Access Problems via Webdav
     */
    public function testCreateContainerNodeWithBadName()
    {
        $testPath = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/testcon/tainer';
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $result = $this->_json->createNodes($testPath, Tinebase_Model_Tree_Node::TYPE_FOLDER, array(), FALSE);
    }

    /**
     * create container in shared folder
     * 
     * @return array created node
     */
    public function testCreateContainerNodeInSharedFolder()
    {
        $testPath = '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/testcontainer';
        $result = $this->_json->createNode($testPath, Tinebase_Model_Tree_Node::TYPE_FOLDER, NULL, FALSE);
        $createdNode = $result;
        
        $this->assertEquals('testcontainer', $createdNode['name']);
        $this->assertEquals($testPath, $createdNode['path']);
        
        return $createdNode;
    }

    /**
     * testCreateFileNodes
     * 
     * @return array file paths
     */
    public function testCreateFileNodes()
    {
        $sharedContainerNode = $this->testCreateContainerNodeInSharedFolder();
        
        $this->_objects['paths'][] = Filemanager_Controller_Node::getInstance()->addBasePath($sharedContainerNode['path']);
        
        $filepaths = array(
            $sharedContainerNode['path'] . '/file1',
            $sharedContainerNode['path'] . '/file2',
        );
        $result = $this->_json->createNodes($filepaths, Tinebase_Model_Tree_Node::TYPE_FILE, array(), FALSE);
        
        $this->assertEquals(2, count($result));
        $this->assertEquals('file1', $result[0]['name']);
        $this->assertEquals(Tinebase_Model_Tree_Node::TYPE_FILE, $result[0]['type']);
        $this->assertEquals('file2', $result[1]['name']);
        $this->assertEquals(Tinebase_Model_Tree_Node::TYPE_FILE, $result[1]['type']);
        
        return $filepaths;
    }

    /**
    * testCreateFileNodeWithUTF8Filenames
    * 
    * @see 0006068: No umlauts at beginning of file names / https://forge.tine20.org/mantisbt/view.php?id=6068
    * @see 0006150: Problem loading files with russian file names / https://forge.tine20.org/mantisbt/view.php?id=6150
    * 
    * @return array first created node
    */
    public function testCreateFileNodeWithUTF8Filenames()
    {
        $personalContainerNode = $this->testCreateContainerNodeInPersonalFolder();
        
        $testPaths = array($personalContainerNode['path'] . '/ütest.eml', $personalContainerNode['path'] . '/Безимени.txt');
        $result = $this->_json->createNodes($testPaths, Tinebase_Model_Tree_Node::TYPE_FILE, array(), FALSE);
    
        $this->assertEquals(2, count($result));
        $this->assertEquals('ütest.eml', $result[0]['name']);
        $this->assertEquals('Безимени.txt', $result[1]['name']);
        $this->assertEquals(Tinebase_Model_Tree_Node::TYPE_FILE, $result[0]['type']);
        
        return $result[0];
    }
    
    /**
     * testCreateFileNodeWithTempfile
     * 
     * @return array node
     */
    public function testCreateFileNodeWithTempfile()
    {
        $sharedContainerNode = $this->testCreateContainerNodeInSharedFolder();
        
        $this->_objects['paths'][] = Filemanager_Controller_Node::getInstance()->addBasePath($sharedContainerNode['path']);
        
        $filepath = $sharedContainerNode['path'] . '/test.txt';
        // create empty file first (like the js frontend does)
        $result = $this->_json->createNode($filepath, Tinebase_Model_Tree_Node::TYPE_FILE, array(), FALSE);

        $tempFileBackend = new Tinebase_TempFile();
        $tempFile = $tempFileBackend->createTempFile(dirname(dirname(__FILE__)) . '/files/test.txt');
        $result = $this->_json->createNode($filepath, Tinebase_Model_Tree_Node::TYPE_FILE, $tempFile->getId(), TRUE);
        
        $this->assertEquals('text/plain', $result['contenttype'], print_r($result, TRUE));
        $this->assertEquals(17, $result['size']);
        
        return $result;
    }
    
    /**
     * testCreateFileCountTempDir
     * 
     * @see 0007370: Unable to upload files
     */
    public function testCreateFileCountTempDir()
    {
        $tmp = Tinebase_Core::getTempDir();
        $filecountInTmpBefore = count(scandir($tmp));
        
        $this->testCreateFileNodeWithTempfile();
        
        // check if tempfile has been created in tine20 tempdir
        $filecountInTmpAfter = count(scandir($tmp));
        
        $this->assertEquals($filecountInTmpBefore + 2, $filecountInTmpAfter, '2 tempfiles should have been created');
    }

    /**
     * testCreateDirectoryNodesInShared
     * 
     * @return array dir paths
     */
    public function testCreateDirectoryNodesInShared()
    {
        $sharedContainerNode = $this->testCreateContainerNodeInSharedFolder();
        
        $this->_objects['paths'][] = Filemanager_Controller_Node::getInstance()->addBasePath($sharedContainerNode['path']);
        
        $dirpaths = array(
            $sharedContainerNode['path'] . '/dir1',
            $sharedContainerNode['path'] . '/dir2',
        );
        $result = $this->_json->createNodes($dirpaths, Tinebase_Model_Tree_Node::TYPE_FOLDER, array(), FALSE);
        
        $this->assertEquals(2, count($result));
        $this->assertEquals('dir1', $result[0]['name']);
        $this->assertEquals('dir2', $result[1]['name']);
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => $sharedContainerNode['path']
        ), array(
            'field'    => 'type', 
            'operator' => 'equals', 
            'value'    => Tinebase_Model_Tree_Node::TYPE_FOLDER,
        ));
        $result = $this->_json->searchNodes($filter, array('sort' => 'creation_time'));
        $this->assertEquals(2, $result['totalcount']);
        
        return $dirpaths;
    }

    /**
     * testCreateDirectoryNodesInPersonal
     * 
     * @return array dir paths
     */
    public function testCreateDirectoryNodesInPersonal()
    {
        $personalContainerNode = $this->testCreateContainerNodeInPersonalFolder();
        
        $this->_objects['paths'][] = Filemanager_Controller_Node::getInstance()->addBasePath($personalContainerNode['path']);
        
        $dirpaths = array(
            $personalContainerNode['path'] . '/dir1',
            $personalContainerNode['path'] . '/dir2',
        );
        $result = $this->_json->createNodes($dirpaths, Tinebase_Model_Tree_Node::TYPE_FOLDER, array(), FALSE);
        
        $this->assertEquals(2, count($result));
        $this->assertEquals('dir1', $result[0]['name']);
        $this->assertEquals('dir2', $result[1]['name']);
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => $personalContainerNode['path']
        ), array(
            'field'    => 'type', 
            'operator' => 'equals', 
            'value'    => Tinebase_Model_Tree_Node::TYPE_FOLDER,
        ));
        $result = $this->_json->searchNodes($filter, array('sort' => 'contenttype'));
        $this->assertEquals(2, $result['totalcount']);
        
        return $dirpaths;
    }
    
    /**
     * testCreateDirectoryNodeInPersonalWithSameNameAsOtherUsersDir
     * 
     * @see 0008044: could not create a personal folder with the name of a folder of another user
     */
    public function testCreateDirectoryNodeInPersonalWithSameNameAsOtherUsersDir()
    {
        $this->testCreateContainerNodeInPersonalFolder();
        
        $personas = Zend_Registry::get('personas');
        Tinebase_Core::set(Tinebase_Core::USER, $personas['sclever']);
        $personalContainerNodeOfsclever = $this->testCreateContainerNodeInPersonalFolder();
        
        $this->assertEquals('/personal/sclever/testcontainer', $personalContainerNodeOfsclever['path']);
    }

    /**
     * testUpdateNodeWithCustomfield
     *
     * ·@see 0009292: Filemanager Custom Fields not saved
     */
    public function testUpdateNodeWithCustomfield()
    {
        $cf = $this->_createCustomfield('fmancf', 'Filemanager_Model_Node');
        $personalContainerNode = $this->testCreateContainerNodeInPersonalFolder();

        $personalContainerNode = $this->_json->getNode($personalContainerNode['id']);
        $personalContainerNode['customfields'][$cf->name] = 'cf value';
        $updatedNode = $this->_json->saveNode($personalContainerNode);

        $this->assertTrue(isset($updatedNode['customfields']) && isset($updatedNode['customfields'][$cf->name]), 'no customfields in record');
        $this->assertEquals($personalContainerNode['customfields'][$cf->name], $updatedNode['customfields'][$cf->name]);
    }
    
    /**
     * testRenameDirectoryNodeInPersonalToSameNameAsOtherUsersDir
     * 
     * @see 0008046: Rename personal folder to personal folder of another user
     */
    public function testRenameDirectoryNodeInPersonalToSameNameAsOtherUsersDir()
    {
        $personalContainerNode = $this->testCreateContainerNodeInPersonalFolder();
        
        $personas = Zend_Registry::get('personas');
        Tinebase_Core::set(Tinebase_Core::USER, $personas['sclever']);
        $personalContainerNodeOfsclever = $this->testCreateContainerNodeInPersonalFolder('testcontainer2');
        
        $this->assertEquals('/personal/sclever/testcontainer2', $personalContainerNodeOfsclever['path']);
        
        // rename
        $newPath = '/personal/sclever/testcontainer';
        $result = $this->_json->moveNodes(array($personalContainerNodeOfsclever['path']), array($newPath), FALSE);
        $this->assertEquals(1, count($result));
        $this->assertEquals($newPath, $result[0]['path']);
    }
    
    /**
     * testCopyFolderNodes
     */
    public function testCopyFolderNodesToFolder()
    {
        $dirsToCopy = $this->testCreateDirectoryNodesInShared();
        $targetNode = $this->testCreateContainerNodeInPersonalFolder();
        
        $result = $this->_json->copyNodes($dirsToCopy, $targetNode['path'], FALSE);
        $this->assertEquals(2, count($result));
        $this->assertEquals($targetNode['path'] . '/dir1', $result[0]['path']);
    }

    /**
     * testCopyContainerNode
     */
    public function testCopyContainerNode()
    {
        $sharedContainerNode = $this->testCreateContainerNodeInSharedFolder();
        $target = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName;
        $this->_objects['paths'][] = Filemanager_Controller_Node::getInstance()->addBasePath($target . '/testcontainer');
        $result = $this->_json->copyNodes($sharedContainerNode['path'], $target, FALSE);
        $this->assertEquals(1, count($result));
        $this->assertTrue(isset($result[0]['name']), print_r($result, true));
        $this->assertEquals('testcontainer', $result[0]['name']);
    }
    
    /**
     * testCopyFileNodesToFolder
     * 
     * @return array target node
     */
    public function testCopyFileNodesToFolder()
    {
        $filesToCopy = $this->testCreateFileNodes();
        $targetNode = $this->testCreateContainerNodeInPersonalFolder();
        
        $result = $this->_json->copyNodes($filesToCopy, $targetNode['path'], FALSE);
        $this->assertEquals(2, count($result));
        $this->assertEquals($targetNode['path'] . '/file1', $result[0]['path']);
        
        return $targetNode;
    }

    /**
     * testCopyFolderWithNodes
     */
    public function testCopyFolderWithNodes()
    {
        $filesToCopy = $this->testCreateFileNodes();
        $target = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName;
        
        $result = $this->_json->copyNodes(
            '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/testcontainer',
            $target, 
            FALSE
        );
        
        $this->_objects['paths'][] = Filemanager_Controller_Node::getInstance()->addBasePath($target . '/testcontainer');
        $this->assertEquals(1, count($result));

        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/testcontainer',
        ), array(
            'field'    => 'type', 
            'operator' => 'equals', 
            'value'    => Tinebase_Model_Tree_Node::TYPE_FILE,
        ));
        $result = $this->_json->searchNodes($filter, array());
        $this->assertEquals(2, $result['totalcount']);
    }
    
    /**
     * testCopyFileWithContentToFolder
     */
    public function testCopyFileWithContentToFolder()
    {
        $fileToCopy = $this->testCreateFileNodeWithTempfile();
        $targetNode = $this->testCreateContainerNodeInPersonalFolder();
        
        $result = $this->_json->copyNodes($fileToCopy['path'], $targetNode['path'], FALSE);
        $this->assertEquals(1, count($result));
        $this->assertEquals($targetNode['path'] . '/test.txt', $result[0]['path']);
        $this->assertEquals('text/plain', $result[0]['contenttype']);
    }
    
    /**
     * testCopyFileNodeToFileExisting
     */
    public function testCopyFileNodeToFileExisting()
    {
        $filesToCopy = $this->testCreateFileNodes();
        $file1 = $filesToCopy[0];
        $file2 = $filesToCopy[1];
        
        $this->setExpectedException('Filemanager_Exception_NodeExists');
        $result = $this->_json->copyNodes(array($file1), array($file2), FALSE);
    }
    
    /**
     * testCopyFileNodeToFileExistingCatchException
     */
    public function testCopyFileNodeToFileExistingCatchException()
    {
        $filesToCopy = $this->testCreateFileNodes();
        $file1 = $filesToCopy[0];
        $file2 = $filesToCopy[1];
        
        try {
            $result = $this->_json->copyNodes(array($file1), array($file2), FALSE);
        } catch (Filemanager_Exception_NodeExists $fene) {
            $info = $fene->toArray();
            $this->assertEquals(1, count($info['existingnodesinfo']));
            return;
        }
        
        $this->fail('An expected exception has not been raised.');
    }
    
    /**
     * testMoveFolderNodesToFolder
     */
    public function testMoveFolderNodesToFolder()
    {
        $dirsToMove = $this->testCreateDirectoryNodesInShared();
        $targetNode = $this->testCreateContainerNodeInPersonalFolder();
        
        $result = $this->_json->moveNodes($dirsToMove, $targetNode['path'], FALSE);
        $this->assertEquals(2, count($result));
        $this->assertEquals($targetNode['path'] . '/dir1', $result[0]['path'], 'no new path: ' . print_r($result, TRUE));
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/testcontainer'
        ), array(
            'field'    => 'type', 
            'operator' => 'equals', 
            'value'    => Tinebase_Model_Tree_Node::TYPE_FOLDER,
        ));

        $result = $this->_json->searchNodes($filter, array());
        $this->assertEquals(0, $result['totalcount']);
    }
    
    /**
     * testMoveFolderNodesToFolderExisting
     * 
     * @see 0007028: moving a folder to another folder with a folder with the same name
     */
    public function testMoveFolderNodesToFolderExisting()
    {
        sleep(1);
        $targetNode = $this->testCreateContainerNodeInPersonalFolder();
        $testPath = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/dir1';
        $result = $this->_json->moveNodes(array($targetNode['path']), array($testPath), FALSE);
        $dirs = $this->testCreateDirectoryNodesInShared();
        try {
            $result = $this->_json->moveNodes(array($testPath), '/shared/testcontainer', FALSE);
            $this->fail('Expected Filemanager_Exception_NodeExists!');
        } catch (Filemanager_Exception_NodeExists $fene) {
            $result = $this->_json->moveNodes(array($testPath), '/shared/testcontainer', TRUE);
            $this->assertEquals(1, count($result));
            $this->assertEquals('/shared/testcontainer/dir1', $result[0]['path']);
        }
    }

    /**
     * testMoveContainerFolderNodeToExistingContainer
     * 
     * @see 0007028: moving a folder to another folder with a folder with the same name
     */
    public function testMoveContainerFolderNodeToExistingContainer()
    {
        $targetNode = $this->testCreateContainerNodeInPersonalFolder();
        
        $testPath = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/testcontainer2';
        $result = $this->_json->createNodes($testPath, Tinebase_Model_Tree_Node::TYPE_FOLDER, array(), FALSE);
        $createdNode = $result[0];

        try {
            $result = $this->_json->moveNodes(array($targetNode['path']), array($createdNode['path']), FALSE);
            $this->fail('Expected Filemanager_Exception_NodeExists!');
        } catch (Filemanager_Exception_NodeExists $fene) {
            $result = $this->_json->moveNodes(array($targetNode['path']), array($createdNode['path']), TRUE);
            $this->assertEquals(1, count($result));
            $this->assertEquals($testPath, $result[0]['path']);
        }
    }
    
    /**
    * testMoveFolderNodesToTopLevel
    */
    public function testMoveFolderNodesToTopLevel()
    {
        // we need the personal folder for the test user
        $this->_setupTestPath(Tinebase_FileSystem::FOLDER_TYPE_PERSONAL);
        
        $dirsToMove = $this->testCreateDirectoryNodesInShared();
        $targetPath = '/personal/' . Tinebase_Core::getUser()->accountLoginName;
        $this->_objects['paths'][] = Filemanager_Controller_Node::getInstance()->addBasePath($targetPath . '/dir1');
        $this->_objects['paths'][] = Filemanager_Controller_Node::getInstance()->addBasePath($targetPath . '/dir2');
        
        $result = $this->_json->moveNodes($dirsToMove, $targetPath, FALSE);
        $this->assertEquals(2, count($result));
        $this->assertEquals($targetPath . '/dir1', $result[0]['path']);
    }
    
    /**
     * testMoveContainerFolderNodesToContainerFolder
     */
    public function testMoveContainerFolderNodesToContainerFolder()
    {
        $sourceNode = $this->testCreateContainerNodeInPersonalFolder();
        
        $newPath = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/testcontainermoved';
        $result = $this->_json->moveNodes($sourceNode['path'], array($newPath), FALSE);
        $this->assertEquals(1, count($result));
        $this->assertEquals($newPath, $result[0]['path'], 'no new path: ' . print_r($result, TRUE));

        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName
        ), array(
            'field'    => 'type', 
            'operator' => 'equals', 
            'value'    => Tinebase_Model_Tree_Node::TYPE_FOLDER,
        ));
        $result = $this->_json->searchNodes($filter, array());
        foreach ($result['results'] as $node) {
            $this->assertNotEquals($sourceNode['path'], $node['path']);
        }
    }
    
    /**
     * testMoveContainerFolderNodesToContainerFolderWithChildNodes
     */
    public function testMoveContainerFolderNodesToContainerFolderWithChildNodes()
    {
        $children = $this->testCreateDirectoryNodesInPersonal();
        
        $oldPath = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/testcontainer';
        $newPath = $oldPath . 'moved';
        $result = $this->_json->moveNodes(array($oldPath), array($newPath), FALSE);
        $this->assertEquals(1, count($result));
        $this->assertEquals($newPath, $result[0]['path']);

        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => $newPath
        ), array(
            'field'    => 'type', 
            'operator' => 'equals', 
            'value'    => Tinebase_Model_Tree_Node::TYPE_FOLDER,
        ));
        $result = $this->_json->searchNodes($filter, array());
        $this->assertEquals(2, $result['totalcount']);
    }
    
    /**
     * testMoveFileNodesToFolder
     * 
     * @return array target node
     */
    public function testMoveFileNodesToFolder()
    {
        $filesToMove = $this->testCreateFileNodes();
        $targetNode = $this->testCreateContainerNodeInPersonalFolder();
        
        $result = $this->_json->moveNodes($filesToMove, $targetNode['path'], FALSE);
        $this->assertEquals(2, count($result));
        $this->assertEquals($targetNode['path'] . '/file1', $result[0]['path']);

        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/testcontainer'
        ), array(
            'field'    => 'type', 
            'operator' => 'equals', 
            'value'    => Tinebase_Model_Tree_Node::TYPE_FILE,
        ));
        $result = $this->_json->searchNodes($filter, array());
        $this->assertEquals(0, $result['totalcount']);
        
        return $targetNode;
    }

    /**
     * testMoveFileNodesOverwrite
     */
    public function testMoveFileNodesOverwrite()
    {
        $targetNode = $this->testCopyFileNodesToFolder();
        
        $sharedContainerPath = '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/testcontainer/';
        $filesToMove = array($sharedContainerPath . 'file1', $sharedContainerPath . 'file2');
        $result = $this->_json->moveNodes($filesToMove, $targetNode['path'], TRUE);
        
        $this->assertEquals(2, count($result));
    }
    
    /**
     * testMoveFolderNodeToRoot
     */
    public function testMoveFolderNodeToRoot()
    {
        $children = $this->testCreateDirectoryNodesInPersonal();
        
        $target = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName;
        $this->_objects['paths'][] = Filemanager_Controller_Node::getInstance()->addBasePath($target . '/testcontainer');
        $result = $this->_json->moveNodes($children[0], $target, FALSE);
        $this->assertEquals(1, count($result));
        $this->assertEquals('dir1', $result[0]['name'], print_r($result[0], TRUE));
    }

    /**
     * Test if notes are correctly decorated with path field
     */
    public function testGetNode()
    {
        $node = $this->testCreateContainerNodeInPersonalFolder();

        $result = Filemanager_Controller_Node::getInstance()->get($node['id']);

        $this->assertTrue($result->path != "");
        $this->assertEquals('/personal/' . Tinebase_Core::getUser()->accountLoginName . '/testcontainer', $result->path);
    }

    /**
     * testDeleteContainerNode
     */
    public function testDeleteContainerNode()
    {
        $sharedContainerNode = $this->testCreateContainerNodeInSharedFolder();
        
        $this->_json->deleteNodes($sharedContainerNode['path']);

        // check if node is deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_fsController->stat($sharedContainerNode['path']);
    }

    /**
     * testDeleteFileNodes
     */
    public function testDeleteFileNodes()
    {
        $filepaths = $this->testCreateFileNodes();
        
        $result = $this->_json->deleteNodes($filepaths);

        // check if node is deleted
        try {
            $this->_fsController->stat(Filemanager_Controller_Node::getInstance()->addBasePath($filepaths[0]));
            $this->assertTrue(FALSE);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $this->assertTrue(TRUE);
        }
    }
    
    /**
     * test cleanup of deleted files (database)
     * 
     * @see 0008062: add cleanup script for deleted files
     */
    public function testDeletedFileCleanupFromDatabase()
    {
        $fileNode = $this->testCreateFileNodeWithTempfile();
        
        // get "real" filesystem path + unlink
        $fileObjectBackend = new Tinebase_Tree_FileObject();
        $fileObject = $fileObjectBackend->get($fileNode['object_id']);
        unlink($fileObject->getFilesystemPath());
        
        $result = Tinebase_FileSystem::getInstance()->clearDeletedFilesFromDatabase();
        $this->assertEquals(1, $result, 'should cleanup one file');

        $result = Tinebase_FileSystem::getInstance()->clearDeletedFilesFromDatabase();
        $this->assertEquals(0, $result, 'should cleanup no file');
        
        // node should no longer be found
        try {
            $this->_json->getNode($fileNode['id']);
            $this->fail('tree node still exists: ' . print_r($fileNode, TRUE));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $this->assertEquals('Tinebase_Model_Tree_Node record with id = ' . $fileNode['id'] . ' not found!', $tenf->getMessage());
        }
    }
    
    /**
     * testDeleteDirectoryNodes
     */
    public function testDeleteDirectoryNodes()
    {
        $dirpaths = $this->testCreateDirectoryNodesInShared();
        
        $result = $this->_json->deleteNodes($dirpaths);

        // check if node is deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $node = $this->_fsController->stat(Filemanager_Controller_Node::getInstance()->addBasePath($dirpaths[0]));
    }
    
    /**
     * testGetUpdate
     * 
     * @see 0006736: Create File (Edit)InfoDialog
     * @return array
     */
    public function testGetUpdate()
    {
        $this->testCreateFileNodes();
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/testcontainer'
        ));
        $result = $this->_json->searchNodes($filter, array());
        
        $this->assertEquals(2, $result['totalcount']);
        $initialNode = $result['results'][0];
        
        $node = $this->_json->getNode($initialNode['id']);
        $this->assertEquals('file', $node['type']);
        
        $node['description'] = 'UNITTEST';
        $node = $this->_json->saveNode($node);
        
        $this->assertEquals('UNITTEST', $node['description']);
        $this->assertEquals($initialNode['contenttype'], $node['contenttype'], 'contenttype  not preserved');

        
        return $node;
    }

    /**
     * testAttachTag
     *
     * @see 0012284: file type changes to 'directory' if tag is assigned
     */
    public function testAttachTagPreserveContentType()
    {
        $node = $this->testCreateFileNodeWithTempfile();
        $node['tags'] = array(array(
            'type'          => Tinebase_Model_Tag::TYPE_PERSONAL,
            'name'          => 'file tag',
        ));
        $node['path'] = '';
        // remove hash field that the client does not send
        unset($node['hash']);
        $updatedNode = $this->_json->saveNode($node);

        $this->assertEquals(1, count($updatedNode['tags']));
        $this->assertEquals($node['contenttype'], $updatedNode['contenttype'], 'contenttype  not preserved');
    }

    /**
     * testSetRelation
     * 
     * @see 0006736: Create File (Edit)InfoDialog
     */
    public function testSetRelation()
    {
        $node = $this->testGetUpdate();
        $node['relations'] = array($this->_getRelationData($node));
        $node = $this->_json->saveNode($node);
        
        $this->assertEquals(1, count($node['relations']));
        $this->assertEquals('PHPUNIT, ali', $node['relations'][0]['related_record']['n_fileas']);
        
        $adbJson = new Addressbook_Frontend_Json();
        $contact = $adbJson->getContact($node['relations'][0]['related_id']);
        $this->assertEquals(1, count($contact['relations']), 'relations are missing');
        $this->assertEquals($node['name'], $contact['relations'][0]['related_record']['name']);
    }
    
    /**
     * get contact relation data
     * 
     * @param array $node
     * @return array
     */
    protected function _getRelationData($node)
    {
        return array(
            'own_model'              => 'Filemanager_Model_Node',
            'own_backend'            => 'Sql',
            'own_id'                 => $node['id'],
            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
            'type'                   => 'FILE',
            'related_backend'        => 'Sql',
            'related_model'          => 'Addressbook_Model_Contact',
            'remark'                 => NULL,
            'related_record'         => array(
                'n_given'           => 'ali',
                'n_family'          => 'PHPUNIT',
                'org_name'          => Tinebase_Record_Abstract::generateUID(),
                'tel_cell_private'  => '+49TELCELLPRIVATE',
            )
        );
    }

    /**
     * testSetRelationToFileInPersonalFolder
     * 
     * @see 0006736: Create File (Edit)InfoDialog
     */
    public function testSetRelationToFileInPersonalFolder()
    {
        $node = $this->testCreateFileNodeWithUTF8Filenames();
        $node['relations'] = array($this->_getRelationData($node));
        $node = $this->_json->saveNode($node);
        
        $adbJson = new Addressbook_Frontend_Json();
        $contact = $adbJson->getContact($node['relations'][0]['related_id']);
        $this->assertEquals(1, count($contact['relations']));
        $relatedNode = $contact['relations'][0]['related_record'];
        $this->assertEquals($node['name'], $relatedNode['name']);
        $pathRegEx = '@^/personal/[a-f0-9-]+/testcontainer/' . preg_quote($relatedNode['name']) . '$@';
        $this->assertTrue(preg_match($pathRegEx, $relatedNode['path']) === 1, 'path mismatch: ' . print_r($relatedNode, TRUE) . ' regex: ' . $pathRegEx);
    }
    
    /**
     * test renaming a folder in a folder containing a folder with the same name
     *
     * @see: https://forge.tine20.org/mantisbt/view.php?id=10132
     */
     public function testRenameFolderInFolderContainingFolderAlready()
     {
        $path = '/personal/' .Tinebase_Core::getUser()->accountLoginName . '/' . $this->_getPersonalFilemanagerContainer()->name;
     
        $this->_json->createNode($path . '/Test1', 'folder', NULL, FALSE);
        $this->_json->createNode($path . '/Test1/Test2', 'folder', NULL, FALSE);
        $this->_json->createNode($path . '/Test1/Test3', 'folder', NULL, FALSE);
        
        $this->setExpectedException('Filemanager_Exception_NodeExists');
        
        $this->_json->moveNodes(array($path . '/Test1/Test3'), array($path . '/Test1/Test2'), FALSE);
     }
    
    /**
     * tests the recursive filter
     */
    public function testSearchRecursiveFilter()
    {
        $fixtures = array(
            array('/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/testa', 'color-red.gif'),
            array('/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/testb', 'color-green.gif'),
            array('/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/testc', 'color-blue.gif'));

        $tempFileBackend = new Tinebase_TempFile();
        
        foreach($fixtures as $path) {
            $node = $this->_json->createNode($path[0], Tinebase_Model_Tree_Node::TYPE_FOLDER, NULL, FALSE);
            
            $this->assertEquals(str_replace('/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/', '', $path[0]), $node['name']);
            $this->assertEquals($path[0], $node['path']);
            
            $this->_objects['paths'][] = Filemanager_Controller_Node::getInstance()->addBasePath($node['path']);
    
            $filepath = $node['path'] . '/' . $path[1];
            // create empty file first (like the js frontend does)
            $result = $this->_json->createNode($filepath, Tinebase_Model_Tree_Node::TYPE_FILE, array(), FALSE);
            $tempFile = $tempFileBackend->createTempFile(dirname(dirname(__FILE__)) . '/files/' . $path[1]);
            $result = $this->_json->createNode($filepath, Tinebase_Model_Tree_Node::TYPE_FILE, $tempFile->getId(), TRUE);
        }
        
        $filter = array(
            array('field' => 'recursive', 'operator' => 'equals',   'value' => 1),
            array('field' => 'path',      'operator' => 'equals',   'value' => '/'),
            array('field' => 'query',     'operator' => 'contains', 'value' => 'color'),
        'AND');
        
        $result = $this->_json->searchNodes($filter, array('sort' => 'name', 'start' => 0, 'limit' => 0));
        $this->assertEquals(3, count($result), '3 files should have been found!');
    }
    
    /**
     * test cleanup of deleted files (filesystem)
     */
    public function testDeletedFileCleanupFromFilesystem()
    {
        // remove all files with size 0 first
        $size0Nodes = Tinebase_FileSystem::getInstance()->searchNodes(new Tinebase_Model_Tree_Node_Filter(array(
            array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FILE),
            array('field' => 'size', 'operator' => 'equals', 'value' => 0)
        )));
        foreach ($size0Nodes as $node) {
            Tinebase_FileSystem::getInstance()->deleteFileNode($node);
        }
        
        $this->testDeleteFileNodes();
        $result = Tinebase_FileSystem::getInstance()->clearDeletedFilesFromFilesystem();
        $this->assertGreaterThan(0, $result, 'should cleanup one file or more');
        $this->tearDown();
        
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $this->testDeleteFileNodes();
        $result = Tinebase_FileSystem::getInstance()->clearDeletedFilesFromFilesystem();
        $this->assertEquals(1, $result, 'should cleanup one file');
    }
    
    /**
     * test preventing to copy a folder in its subfolder
     * 
     * @see: https://forge.tine20.org/mantisbt/view.php?id=9990
     */
    public function testMoveFolderIntoChildFolder()
    {
        $this->_json->createNode('/shared/Parent', 'folder', NULL, FALSE);
        $this->_json->createNode('/shared/Parent/Child', 'folder', NULL, FALSE);
        
        $this->setExpectedException('Filemanager_Exception_DestinationIsOwnChild');
        
        // this must not work
        $this->_json->moveNodes(array('/shared/Parent'), array('/shared/Parent/Child/Parent'), FALSE);
    }
    
    /**
     * test exception on moving to the same position
     * 
     * @see: https://forge.tine20.org/mantisbt/view.php?id=9990
     */
    public function testMoveFolderToSamePosition()
    {
        $this->_json->createNode('/shared/Parent', 'folder', NULL, FALSE);
        $this->_json->createNode('/shared/Parent/Child', 'folder', NULL, FALSE);
    
        $this->setExpectedException('Filemanager_Exception_DestinationIsSameNode');
    
        // this must not work
        $this->_json->moveNodes(array('/shared/Parent/Child'), array('/shared/Parent/Child'), FALSE);
    }

    /**
     * test to move a folder containing another folder
     *
     * @see: https://forge.tine20.org/mantisbt/view.php?id=9990
     */
    public function testMove2FoldersOnToplevel()
    {
        $path = '/personal/' .Tinebase_Core::getUser()->accountLoginName . '/' . $this->_getPersonalFilemanagerContainer()->name;
    
        $this->_json->createNode($path . '/Parent', 'folder', NULL, FALSE);
        $this->_json->createNode($path . '/Parent/Child', 'folder', NULL, FALSE);
        $this->_json->createNode('/shared/Another', 'folder', NULL, FALSE);
    
        // move forth and back, no exception should occur
        $this->_json->moveNodes(array($path . '/Parent'), array('/shared/Parent'), FALSE);
        $this->_json->moveNodes(array('/shared/Parent'), array($path . '/Parent'), FALSE);
    
        try {
            $c = Tinebase_Container::getInstance()->getContainerByName('Filemanager', 'Parent', Tinebase_FileSystem::FOLDER_TYPE_SHARED);
            $this->fail('Container doesn\'t get deleted');
        } catch (Tinebase_Exception_NotFound $e) {
        }
        
        // may be any exception
        $e = new Tinebase_Exception('Dog eats cat');
    
        try {
            $this->_json->moveNodes(array($path . '/Parent'), array('/shared/Parent'), FALSE);
        } catch (Filemanager_Exception_NodeExists $e) {
        }
    
        // if $e gets overridden, an error occured (the exception Filemanager_Exception_NodeExists must not be thrown)
        $this->assertEquals('Tinebase_Exception', get_class($e));
    }
    
    /**
     * test creating a folder in a folder with the same name (below personal folders)
     *
     * @see: https://forge.tine20.org/mantisbt/view.php?id=10132
     */
    public function testCreateFolderInFolderWithSameName()
    {
        $path = '/personal/' .Tinebase_Core::getUser()->accountLoginName . '/' . $this->_getPersonalFilemanagerContainer()->name;

        $result = $this->_json->createNode($path . '/Test1', 'folder', NULL, FALSE);
        $this->assertTrue(isset($result['id']));
        $result = $this->_json->createNode($path . '/Test1/Test1', 'folder', NULL, FALSE);
        $this->assertTrue(isset($result['id']), 'node has not been created');
        $e = new Tinebase_Exception('nothing');
        try {
            $this->_json->createNode($path . '/Test1/Test1/Test2', 'folder', NULL, FALSE);
        } catch (Exception $e) {
            $this->fail('The folder couldn\'t be found, so it hasn\'t ben created');
        }
        
        $this->assertEquals('nothing', $e->getMessage());
    }
    
    /**
     * get other users container
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getOtherUserContainer()
    {
        if (!$this->_otherUserContainer) {
            $sclever = $this->_personas['sclever'];

            $path = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                $this->_application, Tinebase_FileSystem::FOLDER_TYPE_PERSONAL) . '/' . $sclever->getId() . '/clever';
            if (Tinebase_FileSystem::getInstance()->fileExists($path)) {
                $this->_otherUserContainer = Tinebase_FileSystem::getInstance()->stat($path);
            } else {
                $grants = Tinebase_Model_Grants::getPersonalGrants($sclever);
                $grants->addRecord(new Tinebase_Model_Grants(array(
                    'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                    'account_id' => 0,
                    Tinebase_Model_Grants::GRANT_READ => true,
                )));
                $this->_otherUserContainer = Tinebase_FileSystem::getInstance()->createAclNode($path, $grants);
            }
        }
        
        return $this->_otherUserContainer;
    }
    
    /**
     * get personal container
     * 
     * @return Tinebase_Model_Tree_Node
     */
    protected function _getPersonalFilemanagerContainer()
    {
        if (!$this->_personalContainer) {
            $user = Tinebase_Core::getUser();
            $this->_personalContainer = Tinebase_FileSystem::getInstance()->getPersonalContainer($user, 'Filemanager', $user)->getFirstRecord();
        }
        
        return $this->_personalContainer;
    }
    
    /**
     * get shared container
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getSharedContainer()
    {
        if (!$this->_sharedContainer) {
            $path = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                    $this->_application, Tinebase_FileSystem::FOLDER_TYPE_SHARED) . '/shared';
            try {
                $this->_sharedContainer = Tinebase_FileSystem::getInstance()->stat($path);
            } catch (Tinebase_Exception_NotFound $tenf) {
                $this->_sharedContainer = Tinebase_FileSystem::getInstance()->createAclNode($path);
            }
        }
        
        return $this->_sharedContainer;
    }
    
    /**
     * setup the test paths
     * 
     * @param string|array $_types
     */
    protected function _setupTestPath($_types)
    {
        $testPaths = array();
        $types = (array) $_types;
        
        foreach ($types as $type) {
            switch ($type) {
                case Tinebase_FileSystem::FOLDER_TYPE_PERSONAL:
                    $testPaths[] = Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->getId() . '/' 
                        . $this->_getPersonalFilemanagerContainer()->name . '/unittestdir_personal';
                    break;
                case Tinebase_FileSystem::FOLDER_TYPE_SHARED:
                    $testPaths[] = Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/' . $this->_getSharedContainer()->name . '/unittestdir_shared';
                    break;
                case Tinebase_Model_Container::TYPE_OTHERUSERS:
                    $personas = Zend_Registry::get('personas');
                    $testPaths[] = Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . $personas['sclever']->getId() . '/' 
                        . $this->_getOtherUserContainer()->name . '/unittestdir_other';
                    break;
            }
        }
        
        foreach ($testPaths as $path) {
            $path = Filemanager_Controller_Node::getInstance()->addBasePath($path);
            $this->_objects['paths'][] = $path;
            $this->_fsController->mkdir($path);
        }
    }
    
    /**
     * testSaveDownloadLinkFile
     * 
     * @return array Filemanager_Model_DownloadLink
     */
    public function testSaveDownloadLinkFile()
    {
        $downloadLinkData = $this->_getDownloadLinkData();
        $result = $this->_json->saveDownloadLink($downloadLinkData);
        
        $this->assertTrue(! empty($result['url']));
        $this->assertEquals($this->_getDownloadUrl($result['id']), $result['url']);
        $this->assertEquals(0, $result['access_count']);
        
        return $result;
    }

    protected function _getDownloadUrl($id)
    {
        return Tinebase_Core::getUrl() . '/download/show/' . $id;
    }
    
    /**
     * testSaveDownloadLinkDirectory
     *
     * @return array Filemanager_Model_DownloadLink
     */
    public function testSaveDownloadLinkDirectory()
    {
        $downloadLinkData = $this->_getDownloadLinkData();
        $result = $this->_json->saveDownloadLink($downloadLinkData);
        
        $this->assertTrue(! empty($result['url']));
        $this->assertEquals($this->_getDownloadUrl($result['id']), $result['url']);
        
        return $result;
    }
    
    /**
     * get download link data
     * 
     * @param string $nodeType
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getDownloadLinkData($nodeType = Tinebase_Model_Tree_Node::TYPE_FILE)
    {
        // create node first
        if ($nodeType === Tinebase_Model_Tree_Node::TYPE_FILE) {
            $node = $this->testCreateFileNodeWithTempfile();
        } else if ($nodeType === Tinebase_Model_Tree_Node::TYPE_FOLDER) {
            $node = $this->testCreateContainerNodeInPersonalFolder();
        } else {
            throw new Tinebase_Exception_InvalidArgument('only file and folder nodes are supported');
        }
        
        return array(
            'node_id'       => $node['id'],
            'expiry_date'   => Tinebase_DateTime::now()->addDay(1)->toString(),
            'access_count'  => 7,
        );
    }
    
    /**
     * testGetDownloadLink
     */
    public function testGetDownloadLink()
    {
        $downloadLink = $this->testSaveDownloadLinkFile();
        
        $this->assertEquals($downloadLink, $this->_json->getDownloadLink($downloadLink['id']));
    }
    
    /**
     * testSearchDownloadLinks
     */
    public function testSearchDownloadLinks()
    {
        $downloadLink = $this->testSaveDownloadLinkFile();
        $filter = array(array(
            'field'     => 'id',
            'operator'  => 'equals',
            'value'     => $downloadLink['id']
        ));
        $result = $this->_json->searchDownloadLinks($filter, array());
        
        $this->assertEquals(1, $result['totalcount']);
    }

    /**
     * testDeleteDownloadLinks
     */
    public function testDeleteDownloadLinks()
    {
        $downloadLink = $this->testSaveDownloadLinkFile();

        $this->_json->deleteDownloadLinks(array($downloadLink['id']));
        try {
            Filemanager_Controller_DownloadLink::getInstance()->get($downloadLink['id']);
            $this->fail('link should have been deleted');
        } catch (Exception $e) {
            $this->assertTrue($e instanceof Tinebase_Exception_NotFound);
        }
    }

    /**
     * @see 0012788: allow acl for all folder nodes
     */
    public function testNodeAclAndPathResolving()
    {
        $this->testCreateFileNodes();

        // search folders + assert grants
        $sharedRoot = '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED;
        $filter = array(array(
            'field'    => 'path',
            'operator' => 'equals',
            'value'    => $sharedRoot
        ));
        $result = $this->_json->searchNodes($filter, array());

        self::assertEquals(1, $result['totalcount']);
        $node = $result['results'][0];
        self::assertEquals('/shared/testcontainer', $node['path'], 'no path found in node: ' . print_r($node, true));
        $this->_assertGrantsInNode($node);

        // search files + assert grants
        $filter = array(array(
            'field'    => 'path',
            'operator' => 'equals',
            'value'    => $node['path']
        ));
        $result = $this->_json->searchNodes($filter, array());
        self::assertEquals(2, $result['totalcount'], 'no files found in path: ' . print_r($result, true));
        $file1Node = $result['results'][0];
        self::assertContains('/shared/testcontainer/file', $file1Node['path'], 'no path found in node: ' . print_r($file1Node, true));
        $this->_assertGrantsInNode($file1Node);

        $file2Node = $this->_json->getNode($result['results'][1]['id']);
        self::assertContains('/shared/testcontainer/file', $file2Node['path'], 'no path found in node: ' . print_r($file2Node, true));
        $this->_assertGrantsInNode($file2Node);
    }

    protected function _assertGrantsInNode($nodeArray)
    {
        self::assertEquals(2, count($nodeArray['grants']));
        self::assertEquals(true, count($nodeArray['grants'][0]['adminGrant']));
        self::assertEquals(true, count($nodeArray['account_grants']['adminGrant']));
    }

    public function testRecursiveFilter()
    {
        $folders = $this->testCreateDirectoryNodesInPersonal();
        $prefix = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager') . '/folders';

        $i = 0;
        foreach($folders as $folder) {
            $path = Tinebase_Model_Tree_Node_Path::createFromPath($prefix . $folder . '/test.txt');
            $handle = Tinebase_FileSystem::getInstance()->fopen($path->statpath, 'w');
            $this->assertTrue(is_resource($handle), 'fopen did not return resource');

            $written = fwrite($handle, 'RecursiveTest' . (++$i));
            $this->assertEquals(14, $written, 'failed to write 14 bytes to ' . $folder . '/test.txt');

            $result = Tinebase_FileSystem::getInstance()->fclose($handle);
            $this->assertTrue($result, 'fclose did not return true');
        }

        $path = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/testcontainer';
        $this->_rmDir[] = $path;
        $paths = array($path . '/dir1/test.txt', $path . '/dir2/test.txt');
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $result = $this->_json->searchNodes(array(
            array(
                'field'    => 'recursive',
                'operator' => 'equals',
                'value'    => 'true'
            ),
            array(
                'field'    => 'query',
                'operator' => 'contains',
                'value'    => 'RecursiveTe'
            ),
        ), null);

        $this->assertEquals(2, $result['totalcount'], 'did not find expected 2 files: ' . print_r($result, true));
        foreach($result['results'] as $result) {
            $this->assertTrue(in_array($result['path'], $paths), 'result doesn\'t match expected paths: ' . print_r($result, true));
        }
    }
}
