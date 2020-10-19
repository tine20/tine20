<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Tinebase_User
 */
class Tinebase_Tree_NodeTest extends TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Backend
     *
     * @var Tinebase_Tree_FileObject
     */
    protected $_fileObjectBackend;

    /**
     * @var Tinebase_Tree_Node
     */
    protected $_treeNodeBackend;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        $this->_fileObjectBackend = new Tinebase_Tree_FileObject();
        $this->_treeNodeBackend   = new Tinebase_Tree_Node();
        
        $this->objects['objects'] = array();
        $this->objects['nodes']   = array();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        foreach ($this->objects['nodes'] as $node) {
            $this->_treeNodeBackend->delete($node->getId());
        } 
        foreach ($this->objects['objects'] as $object) {
            $this->_fileObjectBackend->delete($object->getId());
        } 
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
    }
    
    /**
     * try to add a filesystem root node
     *
     * @return Filemanager_Model_Tree
     */
    public function testCreateTreeNode()
    {
        $object = Tinebase_Tree_FileObjectTest::getTestRecord();
        $object = $this->_fileObjectBackend->create($object);
        $this->objects['objects'][] =  $object;
        
        $treeNode = $this->getTestRecord();
        $treeNode->object_id = $object->getId();

        $testTreeNode = $this->_treeNodeBackend->create($treeNode);
        $this->objects['nodes'][] =  $testTreeNode;

        $this->assertEquals($treeNode->name,                           $testTreeNode->name);
        $this->assertEquals(Tinebase_Model_Tree_FileObject::TYPE_FILE, $testTreeNode->type);
        
        return $testTreeNode;
    }
        
    /**
     * try to add a filesystem root node
     *
     * @return Filemanager_Model_Tree
     */
    public function testUpdateTreeNode()
    {
        $treeNode = $this->testCreateTreeNode();
        $treeNode->name = $treeNode->name . 'updated';

        $testTreeNode = $this->_treeNodeBackend->update($treeNode);

        $this->assertEquals($treeNode->name,                           $testTreeNode->name);
        $this->assertEquals(Tinebase_Model_Tree_FileObject::TYPE_FILE, $testTreeNode->type);
        
        return $testTreeNode;
    }
    
    public function testGetPathNodes()
    {
        $treeNode = $this->testCreateTreeNode();
        
        $path = $this->_treeNodeBackend->getPathNodes('/' . $treeNode->name);
        
        $this->assertEquals(1, $path->count());
    }
    
    public function testLastPathNode()
    {
        $treeNode = $this->testCreateTreeNode();
        
        $node = $this->_treeNodeBackend->getLastPathNode('/' . $treeNode->name);
        
        $this->assertEquals($treeNode->name, $node->name);
    }
    
    /**
     * @return Filemanager_Model_Directory
     */
    public static function getTestRecord()
    {
        $object  = new Tinebase_Model_Tree_Node(array(
            'name'     => 'PHPUnit test node',
        ), true);
        
        return $object;
    }
}
