<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Syncope_Command_Sync
 * 
 * @package     Backend
 */
class Syncope_Backend_ContentTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syncope_Model_Device
     */
    protected $_device;
    
    /**
     * @var Syncope_Model_Folder
     */
    protected $_folder;
    
    /**
     * @var Syncope_Backend_Content
     */
    protected $_contentBackend;
    
    /**
     * @var Syncope_Backend_Device
     */
    protected $_deviceBackend;

    /**
     * @var Syncope_Backend_Folder
     */
    protected $_folderBackend;
    
    /**
     * @var Syncope_Backend_SyncState
     */
    protected $_syncStateBackend;
    
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Syncope Content backend tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->_db = getTestDatabase();
        
        $this->_db->beginTransaction();

        $this->_contentBackend   = new Syncope_Backend_Content($this->_db);
        $this->_deviceBackend    = new Syncope_Backend_Device($this->_db);
        $this->_folderBackend    = new Syncope_Backend_Folder($this->_db);
        $this->_syncStateBackend = new Syncope_Backend_SyncState($this->_db);

        $this->_device = $this->_deviceBackend->create(
            Syncope_Backend_DeviceTests::getTestDevice()
        );
        $this->_folder = $this->_folderBackend->create(
            Syncope_Backend_FolderTests::getTestFolder($this->_device)
        );
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $this->_db->rollBack();
    }
    
    /**
     * @return Syncope_Model_ISyncState
     */
    public function testCreate()
    {
        $content = self::getTestContent($this->_device, $this->_folder);
        
        $content = $this->_contentBackend->create($content);
        
        $this->assertTrue($content->creation_time instanceof DateTime);
        
        return $content;
    }
    
    /**
     * 
     */
    public function testDelete()
    {
        $content = $this->testCreate();
        
        $this->_contentBackend->delete($content);
        
        $content = $this->_contentBackend->get($content->id);
        
        $this->assertTrue($content instanceof Syncope_Model_IContent);
        $this->assertEquals(1, $content->is_deleted);
        $this->assertTrue($content->creation_time instanceof DateTime);
    }  

    public function testGetFolderState()
    {
        $content = $this->testCreate();
        
        $state = $this->_contentBackend->getFolderState($this->_device, $this->_folder);
        
        $this->assertContains($content->contentid, $state);
    }
    
    public function testResetState()
    {
        $content = $this->testCreate();
    
        $this->_contentBackend->resetState($this->_device, $this->_folder);
        $state = $this->_contentBackend->getFolderState($this->_device, $this->_folder);
    
        $this->assertTrue(empty($state));
    }
    
    public function testGetExceptionNotFound()
    {
        $this->setExpectedException('Syncope_Exception_NotFound');
    
        $this->_contentBackend->get('invalidId');
    }
    
    public function testGetContentStateExceptionNotFound()
    {
        $this->setExpectedException('Syncope_Exception_NotFound');
    
        $this->_contentBackend->getContentState('invalidId', 'invalidId', 'invalidId');
    }
    
    public static function getTestContent(Syncope_Model_IDevice $_device, Syncope_Model_IFolder $_folder)
    {
        return new Syncope_Model_Content(array(
            'device_id'        => $_device,
            'folder_id'        => $_folder,
            'contentid'        => 'abc1234',
            'creation_time'    => new DateTime(null, new DateTimeZone('utc')),
            'creation_synckey' => 1
        ));
    }
}
