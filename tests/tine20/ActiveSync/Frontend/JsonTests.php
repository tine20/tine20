<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for ActiveSync_Frontend_Json
 * 
 * @package     ActiveSync
 */
class ActiveSync_Frontend_JsonTests extends ActiveSync_TestCase
{
    /**
     * lazy init of uit
     *
     * @return ActiveSync_Frontend_Json
     */
    protected function _getUit()
    {
        /** @var ActiveSync_Frontend_Json $uit */
        $uit = parent::_getUit();
        return $uit;
    }
    
    /**
     * Search for records matching given arguments
     */
    public function testSearchSyncDevices()
    {
        $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE);
        
        $result = $this->_getUit()->searchSyncDevices(array(), array());
        
        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals('iphone-abcd', $result['results'][0]['deviceid'], print_r($result['results'], true));
    }
    
    /**
     * deletes existing records
     */
    public function testDeleteSyncDevices()
    {
        $device = $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE);
        $this->_getUit()->deleteSyncDevices(array($device->id));
        $result = $this->_getUit()->searchSyncDevices(array(), array());
        
        $this->assertEquals(0, $result['totalcount']);
    }
    
    /**
     * Return a single record
     * 
     * @return array
     */
    public function testGetSyncDevice()
    {
        $device = $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE);
        $fetchedDevice = $this->_getUit()->getSyncDevice($device->id);
        
        $this->assertTrue(is_array($fetchedDevice['owner_id']), print_r($fetchedDevice, true));
        $this->assertEquals($this->_testUser->getId(), $fetchedDevice['owner_id']['accountId'], print_r($fetchedDevice['owner_id'], true));
        
        return $fetchedDevice;
    }
    
    /**
     * updates a record
     */
    public function testSaveSyncDevice()
    {
        $uit = $this->_getUit();
        $device = $this->testGetSyncDevice();
        
        $device['friendlyname'] = 'Very friendly name';
        $device['owner_id'] = $device['owner_id']['accountId'];
        $device['remotewipe'] = 1;
        $updatedDevice = $uit->saveSyncDevice($device);

        static::assertEquals($device['friendlyname'], $updatedDevice['friendlyname']);
        static::assertEquals(0, $updatedDevice['remotewipe']);


        $result = $uit->remoteResetDevices(array($updatedDevice['id']));
        static::assertTrue(is_array($result) && isset($result['success']) && true === $result['success'] &&
            count($result) === 1, 'result of remoteResetDevices should be array(\'success\' => true)');

        $fetchedDevice = $uit->getSyncDevice($updatedDevice['id']);
        static::assertEquals(1, $fetchedDevice['remotewipe'], 'remote wipe not set successfully!');

        $catched = false;
        try {
            unset($updatedDevice['id']);
            $uit->saveSyncDevice($updatedDevice);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $catched = true;
        }
        static::assertTrue($catched, 'misuse of saveSyncDevice to create a new device must not be possible');
    }
}
