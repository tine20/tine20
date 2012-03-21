<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_FileSystem
 */
class Tinebase_FileSystemTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * @var Tinebase_FileSystem
     */
    protected $_controller;
    
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 filesystem tests');
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
        if (empty(Tinebase_Core::getConfig()->filesdir)) {
            $this->markTestSkipped('filesystem base path not found');
        }
        
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_controller = new Tinebase_FileSystem();
        $this->_basePath   = '/' . Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId() . '/' . Tinebase_Model_Container::TYPE_SHARED;
        
        $this->_controller->initializeApplication(Tinebase_Application::getInstance()->getApplicationByName('Tinebase'));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        Tinebase_FileSystem::getInstance()->clearStatCache();
        Tinebase_FileSystem::getInstance()->clearDeletedFiles();
    }
    
    public function testMkdir()
    {
        $testPath = $this->_basePath . '/PHPUNIT';
        $this->_controller->mkdir($testPath);
        
        $this->assertTrue($this->_controller->fileExists($testPath), 'path created by mkdir not found');
        $this->assertTrue($this->_controller->isDir($testPath),      'path created by mkdir is not a directory');
        
        return $testPath;
    }

    public function testRename()
    {
        $testPath = $this->testMkdir();
        $this->testCreateFile();
    
        $testPath2 = $testPath . '/RENAMED';
        Tinebase_FileSystem::getInstance()->mkdir($testPath2);
    
        Tinebase_FileSystem::getInstance()->rename($testPath . '/phpunit.txt', $testPath2 . '/phpunit2.txt');
    
        $nameOfChildren = Tinebase_FileSystem::getInstance()->scandir($testPath)->name;
        $this->assertFalse(in_array('phpunit.txt', $nameOfChildren));

        $nameOfChildren = Tinebase_FileSystem::getInstance()->scandir($testPath2)->name;
        $this->assertTrue(in_array('phpunit2.txt', $nameOfChildren));
    }
    
    
    public function testRmdir()
    {
        $testPath = $this->testMkdir();
    
        $result = $this->_controller->rmdir($testPath);
    
        $this->assertTrue($result,                                    'wrong result for rmdir command');
        $this->assertFalse($this->_controller->fileExists($testPath), 'failed to delete directory');
    }
    
    public function testScandir()
    {
        $this->testMkdir();
        
        $children = $this->_controller->scanDir($this->_basePath)->name;
        
        $this->assertTrue(in_array('PHPUNIT', $children));
    }
    
    public function testStat()
    {
        $this->testCreateFile();
    
        $node = $this->_controller->stat($this->_basePath . '/PHPUNIT/phpunit.txt');
    
        $this->assertEquals(Tinebase_Model_Tree_FileObject::TYPE_FILE, $node->type);
        $this->assertEquals('phpunit.txt', $node->name);
        $this->assertEquals(7, $node->size);
    }
    
    /**
     * test for isDir with existing directory 
     */
    public function testIsDir()
    {
        $this->testMkdir();
        
        $result = $this->_controller->isDir($this->_basePath . '/PHPUNIT');
        
        $this->assertTrue($result);

        $result = $this->_controller->isFile($this->_basePath . '/PHPUNIT');
    
        $this->assertFalse($result);
    }
    
    /**
     * test for isDir with non existing directory
     */
    public function testIsDirNotExisting()
    {
        $result = $this->_controller->isDir($this->_basePath . '/PHPUNITNotExisting');
        
        $this->assertFalse($result);
    }
    
    public function testCreateFile()
    {
        $this->testMkdir();
        
        $handle = $this->_controller->fopen($this->_basePath . '/PHPUNIT/phpunit.txt', 'x');
        
        $this->assertEquals('resource', gettype($handle), 'opening file failed');
        
        $written = fwrite($handle, 'phpunit');
        
        $this->assertEquals(7, $written);
        
        $this->_controller->fclose($handle);
        
        $children = $this->_controller->scanDir($this->_basePath . '/PHPUNIT')->name;
        
        $this->assertTrue(in_array('phpunit.txt', $children));
    }
    
    /**
     * test for isDir with existing directory
     */
    public function testIsFile()
    {
        $this->testCreateFile();
    
        $result = $this->_controller->isFile($this->_basePath . '/PHPUNIT/phpunit.txt');
    
        $this->assertTrue($result);

        $result = $this->_controller->isDir($this->_basePath . '/PHPUNIT/phpunit.txt');
    
        $this->assertFalse($result);
    }
    
    public function testOpenFile()
    {
        $this->testCreateFile();
        
        $handle = $this->_controller->fopen($this->_basePath . '/PHPUNIT/phpunit.txt', 'r');
        
        $this->assertEquals('phpunit', stream_get_contents($handle), 'file content mismatch');
        
        $this->_controller->fclose($handle);
    }
    
    public function testDeleteFile()
    {
        $this->testCreateFile();
        
        $this->_controller->unlink($this->_basePath . '/PHPUNIT/phpunit.txt');
        
        $children = $this->_controller->scanDir($this->_basePath . '/PHPUNIT')->name;
        
        $this->assertTrue(!in_array('phpunit.txt', $children));
    }
    
    public function testGetFileSize()
    {
        $this->testCreateFile();
        
        $filesize = $this->_controller->filesize($this->_basePath . '/PHPUNIT/phpunit.txt');
        
        $this->assertEquals(7, $filesize);
    }
    
    /**
     * test get content type
     */
    public function testGetContentType()
    {
        $this->testCreateFile();
        
        $contentType = $this->_controller->getContentType($this->_basePath . '/PHPUNIT/phpunit.txt');
        
        // finfo_open() for content type detection is only available in php versions >= 5.3.0'
        $expectedContentType = (version_compare(PHP_VERSION, '5.3.0', '>=')) ? 'text/plain' : 'application/octet-stream';
        
        $this->assertEquals($expectedContentType, $contentType);
    }
    
    public function testGetMTime()
    {
        $now = Tinebase_DateTime::now()->getTimestamp();
        
        $this->testCreateFile();
        
        $timestamp = $this->_controller->getMTime($this->_basePath . '/PHPUNIT/phpunit.txt');
        
        $this->assertGreaterThanOrEqual(sprintf('%u', $now), sprintf('%u', $timestamp));
    }
    
    public function testGetEtag()
    {
        $this->testCreateFile();
        
        $node = $this->_controller->stat($this->_basePath . '/PHPUNIT/phpunit.txt');
        
        $etag = $this->_controller->getETag($this->_basePath . '/PHPUNIT/phpunit.txt');
        
        $this->assertEquals($node->hash, $etag);
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

