<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected function setUp()
    {
        parent::setUp();
    }
    
    /**
     * tear down tests
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        Tinebase_FileSystem::getInstance()->clearStatCache();
        Tinebase_FileSystem::getInstance()->clearDeletedFilesFromFilesystem();
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
        )));
        $this->assertTrue(! empty($downloadLink->url));
        
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
            Tinebase_Container::getInstance()->getDefaultContainer('Filemanager_Model_Node')->getId(),
            $resultNode->name,
            'download link should resolve the default container'
        );
    }
    
    /**
     * @return Filemanager_Model_Node
     */
    protected function _getPersonalRootNode()
    {
        $defaultContainer = Tinebase_Container::getInstance()->getDefaultContainer('Filemanager_Model_Node');
        $filter = new Tinebase_Model_Tree_Node_Filter(array(array(
            'field'    => 'path',
            'operator' => 'equals',
            'value'    => '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName
        )));
        $node = Filemanager_Controller_Node::getInstance()->search($filter)->getFirstRecord();
        return $node;
    }
    
    /**
     * testGetFileList
     */
    public function testGetFileList()
    {
        $downloadLink = $this->testCreateDownloadLink();
        
        $basePath = '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName 
            . '/' . Tinebase_Container::getInstance()->getDefaultContainer('Filemanager_Model_Node')->name;
        $directories = array(
            $basePath . '/one',
            $basePath . '/two',
        );
        Filemanager_Controller_Node::getInstance()->createNodes($directories, Tinebase_Model_Tree_Node::TYPE_FOLDER);
        
        $fileList = $this->_getUit()->getFileList($downloadLink, array());
        
        $this->assertGreaterThan(1, count($fileList));
        $this->assertNotNull($fileList->filter('name', 'one')->getFirstRecord());
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
            $this->assertEquals('No permission to get node', $tead->getMessage());
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
}
