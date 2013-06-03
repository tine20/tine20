<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_TempFileTest
 */
class Tinebase_TempFileTest extends PHPUnit_Framework_TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_TempFile
     */
    protected $_instance;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $this->_instance = Tinebase_TempFile::getInstance();
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
    }
    
    /**
     * testCreateTempFileWithNonUTF8Filename
     * 
     * @see 0008184: files with umlauts in filename cannot be attached with safari
     */
    public function testCreateTempFileWithNonUTF8Filename()
    {
        $filename = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'brokenname.txt');
        $path = tempnam(Tinebase_Core::getTempDir(), 'tine_tempfile_test_');
        
        $tempFile = $this->_instance->createTempFile($path, $filename);
        $this->assertEquals("_tüt", $tempFile->name);
    }
}
