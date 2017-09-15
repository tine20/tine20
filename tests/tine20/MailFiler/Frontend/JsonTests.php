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
        $this->testCreateContainerNodeInPersonalFolder();
        $filter = array(array(
            'field' => 'path',
            'operator' => 'equals',
            'value' => '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/' . 'testcontainer'
        ), array(
            'field' => 'to',
            'operator' => 'contains',
            'value' => 'vagrant'
        ), array(
            'field' => 'flags',
            'operator' => 'in',
            'value' => array(
                '\Tine20'
            )
        ));
        $result = $this->_json->searchNodes($filter, array());
        self::assertEquals(6, count($result['filter']));
        self::assertEquals(0, $result['totalcount']);
    }

    /**
     * create container in personal folder
     *
     * @return array created node
     */
    public function testCreateContainerNodeInPersonalFolder($containerName = 'testcontainer')
    {
        $testPath = '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/' . $containerName;
        $result = $this->_json->createNodes($testPath, Tinebase_Model_Tree_FileObject::TYPE_FOLDER, array(), FALSE);
        $createdNode = $result[0];

        self::assertEquals($containerName, $createdNode['name']);
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
            Tinebase_Model_Tree_FileObject::TYPE_FILE,
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
            'type' => Tinebase_Model_Tag::TYPE_PERSONAL,
            'name' => 'file tag',
        ));
        $updatedNode = $this->_json->saveNode($node);

        $this->assertEquals(1, count($updatedNode['tags']));
    }

    /**
     * test search nodes (shared)
     */
    public function testSearchSharedNodesWithAcl()
    {
        $testNodeName = 'privatesharedtest';
        $testPath = '/' . Tinebase_Model_Container::TYPE_SHARED . '/' . $testNodeName;
        $result = $this->_json->createNodes($testPath, Tinebase_Model_Tree_FileObject::TYPE_FOLDER, array(), FALSE);
        $node = $result[0];
        // remove users grants
        $node['grants'] = array(array(
            'account_id' => Tinebase_Core::getUser()->getId(),
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            Tinebase_Model_Grants::GRANT_READ => true,
            Tinebase_Model_Grants::GRANT_ADD => true,
            Tinebase_Model_Grants::GRANT_EDIT => true,
            Tinebase_Model_Grants::GRANT_DELETE => true,
            Tinebase_Model_Grants::GRANT_EXPORT => true,
            Tinebase_Model_Grants::GRANT_SYNC => true,
            Tinebase_Model_Grants::GRANT_ADMIN => true,
            Tinebase_Model_Grants::GRANT_DOWNLOAD => true,
        ));
        $result = $this->_getUit()->saveNode($node);

        $filter = array(array(
            'field'    => 'path',
            'operator' => 'equals',
            'value'    => '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED
        ));
        $result = $this->_json->searchNodes($filter, array());
        $found = false;
        foreach ($result['results'] as $foundNode) {
            if ($foundNode['name'] === $testNodeName) {
                self::assertEquals(1, count($foundNode['grants']),
                    'should find node with 1 grant: ' . print_r($foundNode, true));
                $found = true;
            }
        }
        self::assertTrue($found, 'should find node of user');

        // switch to sclever
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        $result = $this->_json->searchNodes($filter, array());
        foreach ($result['results'] as $foundNode) {
            if ($foundNode['name'] === $testNodeName) {
                self::fail('should not find node because of acl as sclever: ' . print_r($foundNode, true));
            }
        }
    }
}
