<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Calendar_Controller_Event
 * 
 * @package     Calendar
 */
class ActiveSync_Controller_DeviceTests extends TestCase
{
    /**
     * @var ActiveSync_Controller_Device controller
     */
    protected $_controller;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    protected function setUp(): void
{
        parent::setUp();

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
            Syncroton_Data_Factory::CLASS_CONTACTS, 
            $this->objects['filter']->getId());
            
        $device = ActiveSync_Controller_Device::getInstance()->get($this->objects['device']->getId());
        
        $this->assertEquals($device->contactsfilter_id, $this->objects['filter']->getId());
    }

    public function testMonitorDeviceLastPing()
    {
        $device = ActiveSync_Controller_Device::getInstance()->get($this->objects['device']->getId());
        $device->monitor_lastping = 1;
        $device->lastping = Tinebase_DateTime::now()->subDay(4); // 3 days is the default config threshold
        ActiveSync_Controller_Device::getInstance()->update($device);
        $result = ActiveSync_Controller_Device::getInstance()->monitorDeviceLastPing();
        self::assertEquals(true, $result);

        // @todo assert notification mails
    }
}
