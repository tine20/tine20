<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Server_Json
 * 
 * @package     Tinebase
 */
class Tinebase_Server_JsonTests extends TestCase
{
    public function testGetServiceMap()
    {
        $smd = Tinebase_Server_Json::getServiceMap();
        $smdArray = $smd->toArray();

        $expectedFunctions = array(
            'Inventory.searchInventoryItems',
            'Inventory.saveInventoryItem',
            'Inventory.deleteInventoryItems',
            'Inventory.getInventoryItem',
        );

        foreach ($expectedFunctions as $function) {
            $this->assertTrue(in_array($function, array_keys($smdArray['methods'])), 'fn not in methods: ' . $function);
            $this->assertTrue(in_array($function, array_keys($smdArray['services'])), 'fn not in services: ' . $function);
        }

        $this->assertEquals(array
        (
            'envelope' => 'JSON-RPC-2.0',
            'transport' => 'POST',
            'parameters' => array
            (
                array
                (
                    'type' => 'any',
                    'name' => 'recordData',
                    'optional' => '',
                )

            ),
            'returns' => 'array'
        ), $smdArray['services']['Inventory.saveInventoryItem']);
        $this->assertEquals(array
        (
            'envelope' => 'JSON-RPC-2.0',
            'transport' => 'POST',
            'parameters' => array
            (
                array
                (
                    'type' => 'array',
                    'name' => 'ids',
                    'optional' => '',
                )

            ),
            'returns' => 'string'
        ), $smdArray['services']['Inventory.deleteInventoryItems']);
    }
}
