<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ActiveSync_Controller_DeviceTests::main');
}

/**
 * Test class for Calendar_Controller_Event
 * 
 * @package     Calendar
 */
class ActiveSync_Controller_DeviceTests extends PHPUnit_Framework_TestCase
{
    
    /**
     * @var ActiveSync_Controller_Device controller
     */
    protected $_controller;
    
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Controller Device Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    protected function setUp()
    {   	
        ########### define test device
        $testDevice = ActiveSync_Backend_DeviceTests::getTestDevice();
        
        $this->objects['device'] = ActiveSync_Controller_Device::getInstance()->create($testDevice);
        
        ########### define test filter
        $filterBackend = new Tinebase_PersistentFilter_Backend_Sql();
        
        try {
            $filter = $filterBackend->getByProperty('Sync Test', 'name');
        } catch (Tinebase_Exception_NotFound $e) {
            $filter = new Tinebase_Model_PersistentFilter(array(
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
                'account_id'        => Tinebase_Core::getUser()->getId(),
                'model'             => 'Addressbook_Model_ContactFilter',
                'filters'           => array(array(
                    'field' => 'query', 
                    'operator' => 'contains', 
                    'value' => 'blabla'
                )),
                'name'              => 'Sync Test',
                'description'       => 'Created by unit test'
            ));
            
            $filter = $filterBackend->create($filter);
        }
        
        $this->objects['filter'] = $filter;
    }
    
    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        ActiveSync_Controller_Device::getInstance()->delete($this->objects['device']);
        
        $filterBackend = new Tinebase_PersistentFilter_Backend_Sql();
        $filterBackend->delete($this->objects['filter']->getId());
    }
    
    
    /**
     * test get device
     */
    public function testGetDevice()
    {
        $device = ActiveSync_Controller_Device::getInstance()->get($this->objects['device']->getId());
        
        $this->assertEquals($device->id, $this->objects['device']->id);
    }
    
    /**
     * test setting content filter
     */
    public function testSetDeviceContentFilter()
    {        
        ActiveSync_Controller_Device::getInstance()->setDeviceContentFilter(
            $this->objects['device']->getId(), 
            ActiveSync_Controller::CLASS_CONTACTS, 
            $this->objects['filter']->getId());
            
        $device = ActiveSync_Controller_Device::getInstance()->get($this->objects['device']->getId());
        
        $this->assertEquals($device->contactsfilter_id, $this->objects['filter']->getId());
    }
}
    
if (PHPUnit_MAIN_METHOD == 'ActiveSync_Controller_Device::main') {
    ActiveSync_Controller_Device::main();
}
