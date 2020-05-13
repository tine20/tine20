<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_TempFileTest
 */
class Tinebase_TempFileTest extends TestCase
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
        $this->_instance = Tinebase_TempFile::getInstance();
        parent::setUp();
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

    /**
     * @see 0011156: big files can't be uploaded
     */
    public function testCreateTempFileWithBigSize()
    {
        if ($this->_dbIsPgsql()) {
            $this->markTestSkipped('TODO fix this for pgsql');
        }

        $size = (double) (3.8 * 1024.0 * 1024.0 * 1024.0);
        $tempFile = new Tinebase_Model_TempFile(array(
            'id'          => '123',
            'session_id'  => 'abc',
            'time'        => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
            'path'        => '/tmp/tmpfile',
            'name'        => 'tmpfile',
            'type'        => 'unknown',
            'error'       => 0,
            'size'        => $size, // 3.8 GB
        ));
        $createdTempFile = $this->_instance->create($tempFile);
        $this->assertEquals(4080218931, $createdTempFile->size);
    }

    public function testJoinTempFiles()
    {
        $records = new Tinebase_Record_RecordSet(Tinebase_Model_TempFile::class);
        $records->addRecord($this->_getTempFile());
        $records->addRecord($this->_getTempFile());

        $joined = $this->_instance->joinTempFiles($records);
        self::assertEquals(34, $joined->size);
    }
}
