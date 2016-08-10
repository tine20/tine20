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
    /**
     * @group ServerTests
     */
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
                    'type' => 'array',
                    'optional' => false,
                    'name' => 'recordData'
                )

            ),
            'returns' => 'array'
        ), $smdArray['services']['Inventory.saveInventoryItem'], 'saveInventoryItem smd mismatch');
        $this->assertEquals(array
        (
            'envelope' => 'JSON-RPC-2.0',
            'transport' => 'POST',
            'parameters' => array
            (
                array
                (
                    'type' => 'array',
                    'optional' => false,
                    'name' => 'ids'
                )

            ),
            'returns' => 'array'
        ), $smdArray['services']['Inventory.deleteInventoryItems']);
    }

    /**
     * @group ServerTests
     */
    public function testGetAnonServiceMap()
    {
        // unset registry (and the user object)
        Zend_Registry::_unsetInstance();

        $smd = Tinebase_Server_Json::getServiceMap();
        $smdArray = $smd->toArray();
        $this->assertTrue(isset($smdArray['services']['Tinebase.ping']));
    }

    /**
     * @group ServerTests
     *
     * @see  0011760: create smd from model definition
     */
    public function testHandleRequestForDynamicAPI()
    {
        // handle jsonkey check
        $jsonkey = 'myawsomejsonkey';
        $_SERVER['HTTP_X_TINE20_JSONKEY'] = $jsonkey;
        $coreSession = Tinebase_Session::getSessionNamespace();
        $coreSession->jsonKey = $jsonkey;

        $server = new Tinebase_Server_Json();
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
POST /index.php?requestType=JSON HTTP/1.1
Host: localhost
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7
Content-Type: application/json
X-Tine20-Transactionid: 18da265bc0eb66a36081bfd42689c1675ed68bab
X-Requested-With: XMLHttpRequest
Accept: */*
Referer: http://tine20.vagrant/
Accept-Encoding: gzip, deflate
Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4

{"jsonrpc":"2.0","method":"Inventory.searchInventoryItems","params":{"filter":[], "paging":{}},"id":6}
EOS
        );
        ob_start();
        $server->handle($request);
        $out = ob_get_clean();
        //echo $out;

        $this->assertTrue(! empty($out), 'request should not be empty');
        $this->assertNotContains('Not Authorised', $out);
        $this->assertNotContains('Method not found', $out);
        $this->assertNotContains('No Application Controller found', $out);
        $this->assertNotContains('"error"', $out);
        $this->assertNotContains('PHP Fatal error', $out);
        $this->assertContains('"result"', $out);
    }
}
