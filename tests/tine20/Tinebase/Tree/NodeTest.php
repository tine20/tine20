<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Tree_NodeTest::main');
}

/**
 * Test class for Tinebase_User
 */
class Tinebase_Tree_NodeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Backend
     *
     * @var Filemanager_Backend_Node
     */
    protected $_backend;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 filemanager directory backend tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
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
    protected function tearDown()
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
        #var_dump($object->toArray());
        
        $testTreeNode = $this->_treeNodeBackend->create($treeNode);
        $this->objects['nodes'][] =  $testTreeNode;
        #var_dump($testTreeNode->toArray());
        
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
        #var_dump($treeNode->toArray());
        
        $testTreeNode = $this->_treeNodeBackend->update($treeNode);
        #var_dump($testTreeNode->toArray());
        
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
        
        #var_dump($node->toArray());
        $this->assertEquals($treeNode->name, $node->name);
        #$this->assertEquals($treeNode->getId(), $node->getId());
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
    

if (PHPUnit_MAIN_METHOD == 'Tinebase_Tree_NodeTest::main') {
    Tinebase_Tree_NodeTest::main();
}
