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
abstract class Syncope_Command_ATestCase extends PHPUnit_Framework_TestCase
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
    
    protected $_logPriority = Zend_Log::ALERT;
    
    /**
     * (non-PHPdoc)
     * @see Syncope/Syncope_TestCase::setUp()
     */
    protected function setUp()
    {
        Syncope_Registry::setDatabase(getTestDatabase());
        Syncope_Registry::setTransactionManager(Syncope_TransactionManager::getInstance());
        
        Syncope_Registry::getTransactionManager()->startTransaction(Syncope_Registry::getDatabase());
        
        #$writer = new Zend_Log_Writer_Null();
        $writer = new Zend_Log_Writer_Stream('php://output');
        $writer->addFilter(new Zend_Log_Filter_Priority($this->_logPriority));
        
        $logger = new Zend_Log($writer);
        
        $this->_deviceBackend       = new Syncope_Backend_Device(Syncope_Registry::getDatabase());
        $this->_folderBackend       = new Syncope_Backend_Folder(Syncope_Registry::getDatabase());
        $this->_syncStateBackend    = new Syncope_Backend_SyncState(Syncope_Registry::getDatabase());
        $this->_contentStateBackend = new Syncope_Backend_Content(Syncope_Registry::getDatabase());
        
        $this->_device = $this->_deviceBackend->create(
            Syncope_Backend_DeviceTests::getTestDevice()
        );
        
        Syncope_Registry::set('deviceBackend',       $this->_deviceBackend);
        Syncope_Registry::set('folderStateBackend',  $this->_folderBackend);
        Syncope_Registry::set('syncStateBackend',    $this->_syncStateBackend);
        Syncope_Registry::set('contentStateBackend', $this->_contentStateBackend);
        Syncope_Registry::set('loggerBackend',       $logger);
        
        Syncope_Registry::setContactsDataClass('Syncope_Data_Contacts');
        Syncope_Registry::setCalendarDataClass('Syncope_Data_Calendar');
        Syncope_Registry::setEmailDataClass('Syncope_Data_Email');
        Syncope_Registry::setTasksDataClass('Syncope_Data_Tasks');
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Syncope_Registry::getTransactionManager()->rollBack();
    }
}
