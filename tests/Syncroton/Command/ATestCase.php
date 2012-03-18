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
abstract class Syncroton_Command_ATestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syncroton_Model_IDevice
     */
    protected $_device;
    
    /**
     * @var Syncroton_Backend_IDevice
     */
    protected $_deviceBackend;

    /**
     * @var Syncroton_Backend_IFolder
     */
    protected $_folderBackend;
    
    /**
     * @var Syncroton_Backend_ISyncState
     */
    protected $_syncStateBackend;
    
    /**
     * @var Syncroton_Backend_IContent
     */
    protected $_contentStateBackend;
    
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    protected $_logPriority = Zend_Log::ALERT;
    
    /**
     * (non-PHPdoc)
     * @see Syncroton/Syncroton_TestCase::setUp()
     */
    protected function setUp()
    {
        Syncroton_Registry::setDatabase(getTestDatabase());
        Syncroton_Registry::setTransactionManager(Syncroton_TransactionManager::getInstance());
        
        Syncroton_Registry::getTransactionManager()->startTransaction(Syncroton_Registry::getDatabase());
        
        #$writer = new Zend_Log_Writer_Null();
        $writer = new Zend_Log_Writer_Stream('php://output');
        $writer->addFilter(new Zend_Log_Filter_Priority($this->_logPriority));
        
        $logger = new Zend_Log($writer);
        
        $this->_deviceBackend       = new Syncroton_Backend_Device(Syncroton_Registry::getDatabase());
        $this->_folderBackend       = new Syncroton_Backend_Folder(Syncroton_Registry::getDatabase());
        $this->_syncStateBackend    = new Syncroton_Backend_SyncState(Syncroton_Registry::getDatabase());
        $this->_contentStateBackend = new Syncroton_Backend_Content(Syncroton_Registry::getDatabase());
        
        $this->_device = $this->_deviceBackend->create(
            Syncroton_Backend_DeviceTests::getTestDevice()
        );
        
        Syncroton_Registry::set('deviceBackend',       $this->_deviceBackend);
        Syncroton_Registry::set('folderStateBackend',  $this->_folderBackend);
        Syncroton_Registry::set('syncStateBackend',    $this->_syncStateBackend);
        Syncroton_Registry::set('contentStateBackend', $this->_contentStateBackend);
        Syncroton_Registry::set('loggerBackend',       $logger);
        
        Syncroton_Registry::setContactsDataClass('Syncroton_Data_Contacts');
        Syncroton_Registry::setCalendarDataClass('Syncroton_Data_Calendar');
        Syncroton_Registry::setEmailDataClass('Syncroton_Data_Email');
        Syncroton_Registry::setTasksDataClass('Syncroton_Data_Tasks');
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Syncroton_Registry::getTransactionManager()->rollBack();
    }
}
