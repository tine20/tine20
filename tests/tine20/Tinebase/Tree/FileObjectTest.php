<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Tree_FileObjectTest::main');
}

/**
 * Test class for Tinebase_User
 */
class Tinebase_Tree_FileObjectTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 fileobject backend tests');
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
        $this->_backend = new Tinebase_Tree_FileObject();
        
        $this->objects['objects'] = array();
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
        
        foreach ($this->objects['objects'] as $object) {
            $this->_backend->delete($object->getId());
        } 
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
    }
    
    /**
     * try to add a filesystem root node
     *
     * @return Tinebase_Model_Tree_FileObject
     */
    public function testCreateDirectoryObject()
    {
        $object = $this->getTestRecord();
        $object->type = Tinebase_Model_Tree_FileObject::TYPE_FOLDER;
        #var_dump($object->toArray());
        
        $testObject = $this->_backend->create($object);
        $this->objects['objects'][] =  $testObject;
        #var_dump($testObject->toArray());
        
        $this->assertEquals(Tinebase_Model_Tree_FileObject::TYPE_FOLDER, $testObject->type);
        $this->assertEquals(Tinebase_Core::getUser()->getId(),   $testObject->created_by);
        
        return $testObject;
    }
    
    /**
     * try to add a filesystem root node
     *
     * @return Tinebase_Model_Tree_FileObject
     */
    public function testUpdateDirectoryObject()
    {
        $object = $this->testCreateDirectoryObject();
        
        $testObject = $this->_backend->update($object);
        #var_dump($testObject->toArray());
        
        $this->assertEquals(Tinebase_Model_Tree_FileObject::TYPE_FOLDER, $testObject->type);
        $this->assertEquals(Tinebase_Core::getUser()->getId(),   $testObject->created_by);
        
        return $testObject;
    }
    
    /**
     * try to add a filesystem root node
     *
     * @return Tinebase_Model_Tree_FileObject
     */
    public function testCreateFileObject()
    {
        $object = $this->getTestRecord();
        #var_dump($object->toArray());
        
        $testObject = $this->_backend->create($object);
        $this->objects['objects'][] =  $testObject;
        #var_dump($testObject->toArray());
        
        $this->assertEquals('application/octet-stream',         $testObject->contenttype , 'contenttype mismatch');
        $this->assertEquals(1,                                   $testObject->revision    , 'revision mismatch');
        $this->assertEquals(Tinebase_Model_Tree_FileObject::TYPE_FILE, $testObject->type        , 'filetype mismatch');
        $this->assertEquals(Tinebase_Core::getUser()->getId(),   $testObject->created_by);
        
        return $testObject;
    }

    /**
     * try to add a filesystem root node
     *
     * @return Tinebase_Model_Tree_FileObject
     */
    public function testUpdateFileObject()
    {
        $object = $this->testCreateFileObject();
        $object->hash = hash_file('sha1', dirname(__FILE__) . '/../files/tine_logo_setup.png');
        $object->size = filesize(dirname(__FILE__) . '/../files/tine_logo_setup.png');
        
        $testObject = $this->_backend->update($object);
        #var_dump($testObject->toArray());
        
        $this->assertEquals('application/octet-stream',         $testObject->contenttype);
        $this->assertEquals(2,                                   $testObject->revision);
        $this->assertEquals(Tinebase_Model_Tree_FileObject::TYPE_FILE, $testObject->type);
        $this->assertEquals(Tinebase_Core::getUser()->getId(),   $testObject->created_by);
        
        return $testObject;
    }
    
    /**
     * try to add a filesystem root node
     *
     * @return Tinebase_Model_Tree_FileObject
     */
    public function _testPerformance()
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $time_start = microtime(true);
        for ($i=0; $i < 100; $i++) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create new file object');
            $object = $this->testCreateFileObject();
            
            // add 10 revisions
            for ($ii=0; $ii < 10; $ii++) {
                
                $object->hash = hash_file('sha1', dirname(__FILE__) . '/../files/tine_logo_setup.png');
                $object->size = filesize(dirname(__FILE__) . '/../files/tine_logo_setup.png');
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' change file object ' . $object->hash);
                $testObject = $this->_backend->update($object);
                
                
                $object->hash = hash_file('sha1', dirname(__FILE__) . '/../files/tine_logo.png');
                $object->size = filesize(dirname(__FILE__) . '/../files/tine_logo.png');
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' change file object ' . $object->hash);
                
                $testObject = $this->_backend->update($object);
            }
        }        
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        echo "TIME: $time" . PHP_EOL;
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        $time_start = microtime(true);
        $testObject = $this->_backend->get($object);
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        echo "TIME: $time" . PHP_EOL;
    }
    
    /**
     * @return Filemanager_Model_Directory
     */
    public static function getTestRecord()
    {
        $object  = new Tinebase_Model_Tree_FileObject(array(
            'type'          => Tinebase_Model_Tree_FileObject::TYPE_FILE,
            'creation_time' => Tinebase_DateTime::now(),
            'created_by'    => Tinebase_Core::getUser()->getId(),
            'hash'          => hash_file('sha1', dirname(__FILE__) . '/../files/tine_logo.png'),
            'size'          => filesize(dirname(__FILE__) . '/../files/tine_logo.png')
        ));
        
        return $object;
    }
}        
    

if (PHPUnit_MAIN_METHOD == 'Tinebase_Tree_FileObjectTest::main') {
    Tinebase_Tree_FileObjectTest::main();
}
