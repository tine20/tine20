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
        
        Syncroton_Registry::getTransactionManager()->startTransaction(Syncroton_Registry::getDatabase());
        
        #$writer = new Zend_Log_Writer_Null();
        $writer = new Zend_Log_Writer_Stream('php://output');
        $writer->addFilter(new Zend_Log_Filter_Priority($this->_logPriority));
        
        Syncroton_Registry::set('loggerBackend', new Zend_Log($writer));
        
        try {
            $device = Syncroton_Registry::getDeviceBackend()->getUserDevice('1234', 'iphone-abcd');
            Syncroton_Registry::getDeviceBackend()->delete($device);
        } catch (Syncroton_Exception_NotFound $e) {
            // do nothing => it's ok
        }
        $this->_device = Syncroton_Registry::getDeviceBackend()->create(
            Syncroton_Backend_DeviceTests::getTestDevice()
        );
        
        Syncroton_Registry::setContactsDataClass('Syncroton_Data_Contacts');
        Syncroton_Registry::setCalendarDataClass('Syncroton_Data_Calendar');
        Syncroton_Registry::setEmailDataClass('Syncroton_Data_Email');
        Syncroton_Registry::setTasksDataClass('Syncroton_Data_Tasks');
        Syncroton_Registry::setGALDataClass('Syncroton_Data_Contacts');
        
        // speed up tests
        Syncroton_Registry::set(Syncroton_Registry::PING_TIMEOUT, 1);
        Syncroton_Registry::set(Syncroton_Registry::QUIET_TIME, 1);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Syncroton_Registry::set(Syncroton_Registry::PING_TIMEOUT, 60);
        Syncroton_Registry::set(Syncroton_Registry::QUIET_TIME, 120);
        
        Syncroton_Registry::getTransactionManager()->rollBack();
        Syncroton_Registry::getDatabase()->query('delete from syncroton_device');
    }
}
