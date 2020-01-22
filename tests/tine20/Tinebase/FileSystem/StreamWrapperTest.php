<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_User
 */
class Tinebase_FileSystem_StreamWrapperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 filesystem streamwrapper tests');
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
        
        $this->_basePath = 'tine20:///' . Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId() . '/folders/phpunit';
        
        Tinebase_FileSystem::getInstance()->initializeApplication(Tinebase_Application::getInstance()->getApplicationByName('Tinebase'));
        
        clearstatcache();
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
        Tinebase_FileSystem::getInstance()->clearDeletedFilesFromFilesystem(false);
    }
    
    public function testMkdir()
    {
        $testPath = $this->_basePath . '/PHPUNIT-VIA-STREAM';

        $this->assertTrue(mkdir($testPath, 0777, true), 'mkdir failed');
        
        $this->assertTrue(file_exists($testPath), 'path created by mkdir not found');
        $this->assertTrue(is_dir($testPath)     , 'path created by mkdir is not a directory');
        
        return $testPath;
    }

    public function testMkdirFail()
    {
        try {
            mkdir('tine20:///' . Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId() .
                '/notAllowedHere');
            static::fail('mkdir should throw exception');
        } catch (Tinebase_Exception_InvalidArgument $teia) {}
    }
    
    public function testRmdir()
    {
        $path = $this->testMkdir();

        $result = rmdir($path);
        clearstatcache();
        
        $this->assertTrue($result,             'wrong result for rmdir command');
        $this->assertFalse(file_exists($path), 'failed to delete directory');
    }
    
    public function testCreateFile()
    {
        $testPath = $this->testMkdir()  . '/phpunit.txt';
        
        $fp = fopen($testPath, 'x');
        static::assertEquals(7, fwrite($fp, 'phpunit'));
        fclose($fp);
        
        $this->assertTrue(file_exists($testPath) ,  'failed to create file');
        $this->assertTrue(is_file($testPath)     ,  'path created by mkdir is not a directory');
        $this->assertEquals(7, filesize($testPath), 'failed to write content to file');
        
        return $testPath;
    }
    
    public function testReadFile()
    {
        $testPath = $this->testMkdir()  . '/phpunit.txt';
        
        $fp = fopen($testPath, 'x');
        static::assertEquals(7, fwrite($fp, 'phpunit'));
        fclose($fp);

        $fp = fopen($testPath, 'r');
        $content = fread($fp, 1024);
        fclose($fp);
        
        $this->assertEquals('phpunit', $content, 'failed to read content from file');
    }
    
    public function testUpdateFile()
    {
        $testPath = $this->testMkdir()  . '/phpunit.txt';

        static::assertEquals(11, file_put_contents($testPath, 'phpunit1234'));
        
        $this->assertTrue(file_exists($testPath) ,  'failed to create file');
        $this->assertTrue(is_file($testPath)     ,  'path created by mkdir is not a directory');
        $this->assertEquals(11, filesize($testPath), 'failed to write content to file');
        
        clearstatcache();

        static::assertEquals(9, file_put_contents($testPath, 'phpunit78'));
        $this->assertEquals(9, filesize($testPath), 'failed to update content of file');
    }

    public function testDeleteFile()
    {
        $testPath = $this->testCreateFile();

        static::assertTrue(unlink($testPath));
        clearstatcache();
        
        $this->assertFalse(file_exists($testPath) ,  'failed to unlink file');
    }
    
    public function testScandir()
    {
        $testPath = $this->testCreateFile();
                
        $children = scandir(dirname($testPath));
        
        $this->assertTrue(in_array('phpunit.txt', $children), print_r($children, true));
    }
    
    public function testRename()
    {
        $testPath = $this->testMkdir();
        $this->testCreateFile();
        
        $testPath2 = $testPath . '/RENAMED';
        static::assertTrue(mkdir($testPath2, 0777, true));

        static::assertTrue(rename($testPath . '/phpunit.txt', $testPath2 . '/phpunit2.txt'));
        
        $children = scandir($testPath2);
        
        $this->assertTrue(in_array('phpunit2.txt', $children));
    }
}
