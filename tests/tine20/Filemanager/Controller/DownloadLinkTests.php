<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Filemanager_Controller_DownloadLink
 * 
 * @package     Filemanager
 */
class Filemanager_Controller_DownloadLinkTests extends TestCase
{
    /**
     * set up tests
     */
    protected function setUp(): void
{
        parent::setUp();
    }
    
    /**
     * tear down tests
     */
    protected function tearDown(): void
{
        parent::tearDown();
        
        Tinebase_FileSystem::getInstance()->clearStatCache();
        Tinebase_FileSystem::getInstance()->clearDeletedFilesFromFilesystem(false);
        Tinebase_Cache_PerRequest::getInstance()->reset();
    }
    
    /**
     * testCreateDownloadLink
     */
    public function testCreateDownloadLink()
    {
        $node = $this->_getPersonalRootNode();
        $downloadLink = $this->_getUit()->create(new Filemanager_Model_DownloadLink(array(
            'node_id'       => $node->getId(),
            'expiry_date'   => Tinebase_DateTime::now()->addDay(1)->toString(),
            'access_count'  => 7,
            'password'      => 'myDownloadPassword'
        )));
        self::assertTrue(! empty($downloadLink->url));
        self::assertTrue($this->_getUit()->hasPassword($downloadLink), 'link should have pw');
        
        return $downloadLink;
    }
    
    /**
     * testGetNode
     */
    public function testGetNode()
    {
        $downloadLink = $this->testCreateDownloadLink();
        
        $resultNode = $this->_getUit()->getNode($downloadLink, array());
        
        $this->assertEquals(
            Tinebase_FileSystem::getInstance()->getDefaultContainer('Filemanager_Model_Node')->name,
            $resultNode->name,
            'download link should resolve the default container'
        );
    }
    
    /**
     * @return Tinebase_Model_Tree_Node
     */
    protected function _getPersonalRootNode()
    {
        $node = Tinebase_FileSystem::getInstance()->getPersonalContainer(
            Tinebase_Core::getUser(),
            'Filemanager_Model_Node',
            Tinebase_Core::getUser()
        )->getFirstRecord();
        $this->assertInstanceOf('Tinebase_Model_Tree_Node', $node);
        return $node;
    }
    
    /**
     * testGetFileList
     */
    public function testGetFileList()
    {
        $downloadLink = $this->testCreateDownloadLink();
        
        $basePath = '/' .Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName 
            . '/' . Tinebase_FileSystem::getInstance()->getDefaultContainer('Filemanager_Model_Node')->name;
        $directories = array(
            $basePath . '/one',
            $basePath . '/two',
        );
        Filemanager_Controller_Node::getInstance()->createNodes($directories, Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        
        $fileList = $this->_getUit()->getFileList($downloadLink, array());

        $this->assertGreaterThan(1, count($fileList));
        $this->assertNotNull($fileList->filter('name', 'one')->getFirstRecord());

        return $fileList;
    }
    
    /**
     * testDownloadLinkAcl
     */
    public function testDownloadLinkAcl()
    {
        // try to access download link to personal container of another user
        $downloadLink = $this->testCreateDownloadLink();
        
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        try {
            $resultNode = $this->_getUit()->getNode($downloadLink, array());
            $this->fail('user should not be able to access download link node');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $str = 'No permission to get node';
            $strLen = strlen($str);
            $this->assertEquals($str, substr($tead->getMessage(), 0, $strLen));
        }
    }
    
    /**
     * testDownloadLinkExpiry
     */
    public function testDownloadLinkExpiry()
    {
        // let download link expire yesterday
        $downloadLink = $this->testCreateDownloadLink();
        $downloadLink->expiry_time = Tinebase_DateTime::now()->subDay(1);
        $downloadLink = $this->_getUit()->update($downloadLink);
        
        try {
            $resultNode = $this->_getUit()->getNode($downloadLink, array());
            $this->fail('user should not be able to access expired download link node');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $this->assertEquals('Download link has expired', $tead->getMessage());
        }
    }

    /**
     * testDownloadLinkAccessCount
     */
    public function testDownloadLinkAccessCount()
    {
        $initialDownloadLink = $this->testCreateDownloadLink();

        // simulate two concurrent downloads
        $this->_getUit()->increaseAccessCount($initialDownloadLink);
        $this->_getUit()->increaseAccessCount($initialDownloadLink);

        $downloadLink = $this->_getUit()->get($initialDownloadLink->getId());

        $this->assertEquals(2, $downloadLink->access_count);
    }

    public function testDownloadLinkAlternativeURL()
    {
        Filemanager_Config::getInstance()->set(Filemanager_Config::PUBLIC_DOWNLOAD_URL, 'https://download.example.com/');
        $downloadLink = $this->testCreateDownloadLink();

        $this->assertStringContainsString('example', $downloadLink->url);
    }

    public function testDownloadListAlternativeURL()
    {
        Filemanager_Config::getInstance()->set(Filemanager_Config::PUBLIC_DOWNLOAD_URL, 'https://download.example.com/');
        $fileList = $this->testGetFileList();

        $this->assertStringContainsString('example', $fileList[0]->path);
    }

    /**
     * @see 0013072: add password protection to download links
     *
     * @throws Exception
     */
    public function testDownloadPassword()
    {
        $dl = $this->testCreateDownloadLink();

        self::assertFalse($this->_getUit()->validatePassword($dl, 'myWrongPassword'),
            'user should not be able to access password protected download link node');
        self::assertTrue($this->_getUit()->validatePassword($dl, 'myDownloadPassword'),
            'user should be able to access password protected download link node');

        $resultNode = $this->_getUit()->getNode($dl, array());
        $this->assertEquals(
            Tinebase_FileSystem::getInstance()->getDefaultContainer('Filemanager_Model_Node')->name,
            $resultNode->name,
            'download link should resolve the default container'
        );
    }
}
