<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for FolderSync_Controller_Event
 * 
 * @package     Tests
 */
class Syncope_Command_FolderSyncTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syncope_Model_IDevice
     */
    protected $_device;
    
    /**
     * @var Syncope_Backend_IDevice
     */
    protected $_deviceBackend;

    /**
     * @var Syncope_Backend_IFolder
     */
    protected $_folderBackend;
    
    /**
     * @var Syncope_Backend_ISyncState
     */
    protected $_syncStateBackend;
    
    /**
     * @var Syncope_Backend_IContent
     */
    protected $_contentStateBackend;
    
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
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync FolderSync command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncope/Syncope_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->_db = getTestDatabase();
        
        $this->_db->beginTransaction();

        $this->_deviceBackend       = new Syncope_Backend_Device($this->_db);
        $this->_folderBackend  = new Syncope_Backend_Folder($this->_db);
        $this->_syncStateBackend    = new Syncope_Backend_SyncState($this->_db);
        $this->_contentStateBackend = new Syncope_Backend_Content($this->_db);

        $this->_device = $this->_deviceBackend->create(
            Syncope_Backend_DeviceTests::getTestDevice()
        );
        
        Zend_Registry::set('deviceBackend',       $this->_deviceBackend);
        Zend_Registry::set('folderStateBackend',  $this->_folderBackend);
        Zend_Registry::set('syncStateBackend',    $this->_syncStateBackend);
        Zend_Registry::set('contentStateBackend', $this->_contentStateBackend);
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
     * test xml generation for IPhone
     */
    public function testGetFoldersSyncKey0()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, null);
        
        $folderSync->handle();
        
        $responseDoc = $folderSync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:SyncKey');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(1, $nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:Changes/FolderHierarchy:Add');
        $this->assertGreaterThanOrEqual(1, $nodes->length, $responseDoc->saveXML());
    }
    
    /**
     * test xml generation for IPhone
     */
    public function testGetFoldersSyncKey1()
    {
        $this->testGetFoldersSyncKey0();
        
        Syncope_Data_Contacts::$folders['addressbookFolderId2'] = array(
            'folderId'    => 'addressbookFolderId2',
            'parentId'    => null,
            'displayName' => 'User created Contacts Folder',
            'type'        => Syncope_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED
        );
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>1</SyncKey></FolderSync>'
        );
    
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
    
        $folderSync->handle();
    
        $responseDoc = $folderSync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:SyncKey');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:Changes/FolderHierarchy:Add');
        $this->assertGreaterThanOrEqual(1, $nodes->length, $responseDoc->saveXML());
    }
    
    /**
     * test xml generation for IPhone
     */
    public function testGetFoldersInvalidSyncKey()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>99</SyncKey></FolderSync>'
        );
        
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        
        $folderSync->handle();
        
        $responseDoc = $folderSync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_FolderSync::STATUS_INVALID_SYNC_KEY, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());

    }
}
