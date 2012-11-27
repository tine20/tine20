<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo make testLoginAndLogout work (needs to run in separate process)
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Controller
 */
class Tinebase_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * controller instance
     * 
     * @var Tinebase_Controller
     */
    protected $_instance = NULL;
    
    /**
     * run
     * 
     * @see http://matthewturland.com/2010/08/19/process-isolation-in-phpunit/
     * @param $result
     */
//    public function run(PHPUnit_Framework_TestResult $result = NULL)
//    {
//        $this->setPreserveGlobalState(false);
//        return parent::run($result);
//    }
        
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_instance = Tinebase_Controller::getInstance();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }
    
    /**
     * test login and logout in separate process
     * 
     * @runInSeparateProcess
     */
//    public function testLoginAndLogout()
//    {
//        $config = Zend_Registry::get('testConfig');
//        
//        $configData = @include('phpunitconfig.inc.php');
//        $config = new Zend_Config($configData);
//        
//        $result = $this->_instance->login($config->username, $config->password, $config->ip, 'TineUnittest2');
//        
//        $this->assertTrue($result);
//        
//        // just call change pw for fun and coverage ;)
//        $result = $this->_instance->changePassword($config->password, $config->password);
//        
//        $result = $this->_instance->logout($config->ip);
//        
//        $this->assertEquals('', session_id());
//    }

    /**
     * testCleanupCache
     */
    public function testCleanupCache()
    {
        $this->_instance->cleanupCache(Zend_Cache::CLEANING_MODE_ALL);
        
        $cache = Tinebase_Core::getCache();
        $oldLifetime = $cache->getOption('lifetime');
        $cache->setLifetime(1);
        $cacheId = convertCacheId('testCleanupCache');
        $cache->save('value', $cacheId);
        sleep(3);
        
        // cleanup with CLEANING_MODE_OLD
        $this->_instance->cleanupCache();
        $cache->setLifetime($oldLifetime);
        
        $this->assertFalse($cache->load($cacheId));
        
        // check for cache files
        $config = Tinebase_Core::getConfig();
        
        if ($config->caching && $config->caching->backend == 'File' && $config->caching->path) {
            $cacheFile = $this->_lookForCacheFile($config->caching->path);
            $this->assertEquals(NULL, $cacheFile, 'found cache file: ' . $cacheFile);
        }
    }
    
    /**
     * look for cache files
     * 
     * @param string $_path
     * @param boolean $_firstLevel
     * @return string|NULL
     */
    protected function _lookForCacheFile($_path, $_firstLevel = TRUE)
    {
        foreach (new DirectoryIterator($_path) as $item) {
            if ($item->isDir() && preg_match('/^zend_cache/', $item->getFileName())) {
                //echo 'scanning ' . $item->getFileName();
                if ($this->_lookForCacheFile($item->getPathname(), FALSE)) {
                    // file found in subdir
                    return $item->getPathname();
                }
            } else if ($item->isFile() && ! $_firstLevel) {
                // file found
                return $item->getPathname();
            }
        }
        
        return NULL;
    }
}
