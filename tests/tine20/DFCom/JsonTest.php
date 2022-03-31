<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     DFCom
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for DFCom_JsonTest
 */
class DFCom_JsonTest extends TestCase
{
    /**
     * test Device and DeviceRecord API
     */
    public function testDeviceRecordApi()
    {
        $container = $this->_getTestContainer('DFCom', 'DFCom_Model_Device');
        $device = $this->_testSimpleRecordApi(
            'Device',
            /* $nameField  */
            'name',
            /* $descriptionField */
            null,
            /* $delete */
            false,
            [
                'container_id' => $container->getId(),
                'deviceString' => Tinebase_Record_Abstract::generateUID(20),
                'serialNumber' => 22,
                'timezone' => Tinebase_Record_Abstract::generateUID(20),
                'fwVersion' => Tinebase_Record_Abstract::generateUID(11),
                'setupVersion' => Tinebase_Record_Abstract::generateUID(20),
                'setupStatus' => '0000',
                'authKey' => Tinebase_Record_Abstract::generateUID(20),
            ],
            false
        );
        $this->_testSimpleRecordApi(
            'DeviceRecord',
            /* $nameField  */
            'device_table',
            /* $descriptionField */
            null,
            /* $delete */
            true,
            [
                'data' => [
                    'mykey' => 'myvalue',
                ],
                'device_id' => $device['id']
            ],
            false
        );
    }
}
