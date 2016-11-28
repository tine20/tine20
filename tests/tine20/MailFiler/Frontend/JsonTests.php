<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     MailFiler
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for MailFiler_Frontend_Json
 * 
 * @package     MailFiler
 */
class MailFiler_Frontend_JsonTests extends TestCase
{
    /**
     * uit
     *
     * @var MailFiler_Frontend_Json
     */
    protected $_json;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_json = new MailFiler_Frontend_Json();
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

        Tinebase_FileSystem::getInstance()->clearStatCache();
        Tinebase_FileSystem::getInstance()->clearDeletedFilesFromFilesystem();
    }

    /**
     * test search nodes (personal)
     */
    public function testSearchWithMessageFilter()
    {
        $filter = array(array(
            'field' => 'path',
            'operator' => 'equals',
            'value' => '/'
        ), array(
            'field' => 'to',
            'operator' => 'contains',
            'value' => 'vagrant'
        ));
        $result = $this->_json->searchNodes($filter, array());
        self::assertEquals(2, count($result['filter']));
        // TODO is it correct to show the root nodes even if "to" filter is set?
        self::assertEquals(3, $result['totalcount']);
    }

    /**
     * create container in personal folder
     *
     * @return array created node
     */
    public function testCreateContainerNodeInPersonalFolder($containerName = 'testcontainer')
    {
        $testPath = '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/' . $containerName;
        $result = $this->_json->createNodes($testPath, Tinebase_Model_Tree_Node::TYPE_FOLDER, array(), FALSE);
        $createdNode = $result[0];

        $this->_objects['containerids'][] = $createdNode['name']['id'];

        self::assertTrue(is_array($createdNode['name']));
        self::assertEquals($containerName, $createdNode['name']['name']);
        self::assertEquals(Tinebase_Core::getUser()->getId(), $createdNode['created_by']['accountId']);

        return $createdNode;
    }

    /**
     * test move eml node
     */
    public function testMoveNode()
    {
        $node1 = $this->testCreateContainerNodeInPersonalFolder('testcontainer1');
        $node2 = $this->testCreateContainerNodeInPersonalFolder('testcontainer2');

        $tempFilename = Tinebase_TempFile::getTempPath();
        file_put_contents($tempFilename, 'my eml content');
        $tempFile = Tinebase_TempFile::getInstance()->createTempFile($tempFilename);
        $filePath = $node1['path'] . '/my.eml';
        MailFiler_Controller_Node::getInstance()->createNodes(
            array($filePath),
            Tinebase_Model_Tree_Node::TYPE_FILE,
            array($tempFile->getId()),
            /* $_forceOverwrite */ true
        )->getFirstRecord();

        // move to testcontainer2
        $targetFilePath = $node2['path'] . '/my.eml';
        $result = $this->_json->moveNodes(array($filePath), array($targetFilePath), FALSE);

        self::assertEquals(1, count($result));
        self::assertEquals($targetFilePath, $result[0]['path']);
    }

    /**
     * testAttachTagToFolderNode
     *
     * @see 0012370: tags not working
     */
    public function testAttachTagToFolderNode()
    {
        $node = $this->testCreateContainerNodeInPersonalFolder();
        $node['tags'] = array(array(
            'type'          => Tinebase_Model_Tag::TYPE_PERSONAL,
            'name'          => 'file tag',
        ));
        $node['name'] = $node['name']['id'];
        $updatedNode = $this->_json->saveNode($node);

        $this->assertEquals(1, count($updatedNode['tags']));
    }
}
