<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Tests
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to test <...>
 *
 * @package     Syncroton
 * @subpackage  Tests
 */
class Syncroton_Backend_ContentTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syncroton_Model_Device
     */
    protected $_device;
    
    /**
     * @var Syncroton_Model_Folder
     */
    protected $_folder;
    
    /**
     * @var Syncroton_Backend_Content
     */
    protected $_contentBackend;
    
    /**
     * @var Syncroton_Backend_Device
     */
    protected $_deviceBackend;

    /**
     * @var Syncroton_Backend_Folder
     */
    protected $_folderBackend;
    
    /**
     * @var Syncroton_Backend_SyncState
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
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton Content backend tests');
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

        $this->_contentBackend   = new Syncroton_Backend_Content($this->_db);
        $this->_deviceBackend    = new Syncroton_Backend_Device($this->_db);
        $this->_folderBackend    = new Syncroton_Backend_Folder($this->_db);
        $this->_syncStateBackend = new Syncroton_Backend_SyncState($this->_db);

        $this->_device = $this->_deviceBackend->create(
            Syncroton_Backend_DeviceTests::getTestDevice()
        );
        $this->_folder = $this->_folderBackend->create(
            Syncroton_Backend_FolderTests::getTestFolder($this->_device)
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
     * @return Syncroton_Model_ISyncState
     */
    public function testCreate()
    {
        $content = self::getTestContent($this->_device, $this->_folder);
        
        $content = $this->_contentBackend->create($content);
        
        $this->assertTrue($content->creationTime instanceof DateTime);
        
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
        
        $this->assertTrue($content instanceof Syncroton_Model_IContent);
        $this->assertEquals(1, $content->isDeleted);
        $this->assertTrue($content->creationTime instanceof DateTime);
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
        $this->setExpectedException('Syncroton_Exception_NotFound');
    
        $this->_contentBackend->get('invalidId');
    }
    
    public function testGetContentStateExceptionNotFound()
    {
        $this->setExpectedException('Syncroton_Exception_NotFound');
    
        $this->_contentBackend->getContentState('invalidId', 'invalidId', 'invalidId');
    }
    
    public static function getTestContent(Syncroton_Model_IDevice $_device, Syncroton_Model_IFolder $_folder)
    {
        return new Syncroton_Model_Content(array(
            'deviceId'        => $_device,
            'folderId'        => $_folder,
            'contentid'        => 'abc1234',
            'creationTime'    => new DateTime(null, new DateTimeZone('utc')),
            'creation_synckey' => 1
        ));
    }
}
