<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     DFCom
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once 'TestHelper.php';

class DFCom_HTTPAPIv1Test extends TestCase
{

    /**
     * @var Tinebase_Server_UnittestEmitter
     */
    public $emitter;

    /**
     * set up tests
     */
    protected function setUp(): void
{
        $this->emitter = new Tinebase_Server_UnittestEmitter();
        $this->server = new Tinebase_Server_Expressive($this->emitter);
        /** @var \Symfony\Component\DependencyInjection\Container $this->container */
        $this->container = Tinebase_Core::getPreCompiledContainer();
        Tinebase_Core::setContainer($this->container);

        DFCom_Controller_Device::$unAuthSleepTime = 0;

        parent::setUp();
    }

    public function testWrongApiLevel()
    {
        $request = Tinebase_Http_Request::fromString(self::getTestRecordRequestData('alive', [
            'df_api' => 0,
        ]));

        $this->container->set(\Psr\Http\Message\RequestInterface::class, \Zend\Psr7Bridge\Psr7ServerRequest::fromZend($request));
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);

        $this->server->handle();

        $this->emitter->response->getBody()->rewind();
        $body = $this->emitter->response->getBody()->getContents();

        $this->assertEquals(406, $this->emitter->response->getStatusCode());
    }

    public function testUnauthDevice()
    {
        $request = Tinebase_Http_Request::fromString(self::getTestRecordRequestData('alive', [
            'df_col_authKey' => Tinebase_Record_Abstract::generateUID(20),
        ]));

        $this->container->set(\Psr\Http\Message\RequestInterface::class, \Zend\Psr7Bridge\Psr7ServerRequest::fromZend($request));
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);

        $this->server->handle();

        $this->emitter->response->getBody()->rewind();
        $body = $this->emitter->response->getBody()->getContents();

        $this->assertEquals(401, $this->emitter->response->getStatusCode());
    }


    public function testDispatchAliveRecord()
    {
        $setupAuthKey = DFCom_Config::getInstance()->get(DFCom_Config::SETUP_AUTH_KEY);

        $request = Tinebase_Http_Request::fromString(self::getTestRecordRequestData('alive'));

        $this->container->set(\Psr\Http\Message\RequestInterface::class, \Zend\Psr7Bridge\Psr7ServerRequest::fromZend($request));
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);

        $this->server->handle();

        $this->emitter->response->getBody()->rewind();
        $headers = $this->emitter->response->getHeaders();
        $body = $this->emitter->response->getBody()->getContents();

        $this->assertEquals('application/x-www-form-urlencoded; charset: iso-8859-1', $headers['Content-Type'][0]);

        $this->assertTrue(!!preg_match('/setup\.authKey,([a-f0-9]{20})/', $body, $matches));
        $authKey = $matches[1];

        $this->assertTrue(!!preg_match('/df_time=(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/', $body, $matches));

        /** @var DFCom_Model_Device $device */
        $device = DFCom_Controller_Device::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(DFCom_Model_Device::class, [
            ['field' => 'authKey', 'operator' => 'equals', 'value' => $authKey],
        ]))->getFirstRecord();

        $this->assertNotNull($device->lastSeen);
        $this->assertSame('EVO-Line 4.3', $device->deviceString, 'deviceString');
        $this->assertSame('1111', $device->serialNumber, 'serialNumber');
        $this->assertSame('0', $device->digitalStatus, 'digitalStatus');

        // default lists
        $this->assertTrue(!!preg_match('/df_setup_list=(.*),(.*' . $authKey . ')/', $body, $matches), 'df_setup_list missing');
        list(,$listName, $link)  = $matches;
        list(,,,,,,$listId,$authKey) = explode('/', urldecode($link));
        $this->assertEquals($device->authKey, $authKey, 'list authkey wrong');
        $list = DFCom_Controller_DeviceList::getInstance()->get($listId);
        $this->assertEquals($device->id, $list->device_id);

        return $device;
    }

    public function testGetDeviceList()
    {
        $device = $this->testDispatchAliveRecord();
        $employeeExportDefinition = Tinebase_ImportExportDefinition::getInstance()->getByName('DFCom_device_list_employee');

        $list = DFCom_Controller_DeviceList::getInstance()->create(new DFCom_Model_DeviceList([
            'device_id' => $device->getId(),
            'name' => $employeeExportDefinition->label,
            'export_definition_id' => $employeeExportDefinition->getId()
        ]));

        // have some demo data
        $HRTest = new HumanResources_Controller_ContractTests();
        $HRTest->testContract(true);

        $request = Tinebase_Http_Request::fromString("GET /DFCom/v1/device/{$device->getId()}/list/{$list->getId()}/$device->authKey" .
            ' HTTP/1.1' . "\r\n"
            . 'Host: 10.133.2.144:10080' . "\r\n"
            . 'Accept: application/x-www-form-urlencoded, text/html' . "\r\n"
            . 'Accept-Charset: ISO 8859-1' . "\r\n"
            . "\r\n"
        );

        $this->container->set(\Psr\Http\Message\RequestInterface::class, \Zend\Psr7Bridge\Psr7ServerRequest::fromZend($request));
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);

        $this->server->handle();

        $this->assertEquals(200, $this->emitter->response->getStatusCode());

        $headers = $this->emitter->response->getHeaders();
        $this->assertGreaterThanOrEqual(22, $headers['Content-Length'][0]);

        $this->emitter->response->getBody()->rewind();
        $body = $this->emitter->response->getBody()->getContents();

        $this->assertTrue(!!strstr($body, "1\t36118993923739652\tRoberta Wright"), $body);

        /** @var DFCom_Model_DeviceList $loadedList */
        $loadedList = DFCom_Controller_DeviceList::getInstance()->get($list->getId());
        $this->assertNotNull($loadedList->list_version);
        $this->assertEquals(-1, $loadedList->list_status);

        $device = DFCom_Controller_Device::getInstance()->get($device->id);

        return $loadedList;
    }

    public function testDispatchListFeedbackRecord()
    {
        // prepare list state
        /** @var DFCom_Model_DeviceList $list */
        $list = $this->testGetDeviceList();
        /** @var DFCom_Model_Device $device */
        $device = DFCom_Controller_Device::getInstance()->get($list->device_id);

        $request = Tinebase_Http_Request::fromString(self::getTestRecordRequestData('listFeedback', [
            'df_col_authKey' => $device->authKey,
            'df_col_reason' => 0,
        ]));

        $this->container->set(\Psr\Http\Message\RequestInterface::class, \Zend\Psr7Bridge\Psr7ServerRequest::fromZend($request));
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);

        $this->server->handle();

        $this->assertEquals(200, $this->emitter->response->getStatusCode());

        /** @var DFCom_Model_DeviceList $loadedList */
        $loadedList = DFCom_Controller_DeviceList::getInstance()->get($list->getId());
        $this->assertEquals(0, $loadedList->list_status);

        $headers = $this->emitter->response->getHeaders();
        $this->assertEquals('application/x-www-form-urlencoded; charset: iso-8859-1', $headers['Content-Type'][0]);
    }

    public function testDispatchTimeaccountingRecord()
    {
        // have some demo data
        $HRTest = new HumanResources_Controller_ContractTests();
        $HRTest->testContract(true);

        /** @var DFCom_Model_Device $device */
        $device = $this->testDispatchAliveRecord();

        $request = Tinebase_Http_Request::fromString(self::getTestRecordRequestData('timeAccounting', [
            'df_col_authKey' => $device->authKey,
        ]));

        $this->container->set(\Psr\Http\Message\RequestInterface::class, \Zend\Psr7Bridge\Psr7ServerRequest::fromZend($request));
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);

        $this->server->handle();

        $this->assertEquals(200, $this->emitter->response->getStatusCode());

        HumanResources_Controller_AttendanceRecorder::runBLPipes();

        // assert deviceRecord
        // @TODO: improve me
//        DFCom_Controller_DeviceRecord::getInstance()->search()
        $records = DFCom_Controller_DeviceRecord::getInstance()->getAll();
        $record = $records->filter('device_id', $device->getId())->getFirstRecord();
        $this->assertEquals(DFCom_RecordHandler_TimeAccounting::FUNCTION_KEY_CLOCKIN, $record->xprops('data')['functionKey']);
        $this->assertEquals('2018-10-24T17:20:16', $record->xprops('data')['dateTime']);

        // assert timesheet got created
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->getAll()
            ->filter('start_time', '17:20:16');
        $this->assertCount(1, $timesheets, 'timesheet not created');
    }

    public function testControlComands()
    {
        /** @var DFCom_Model_Device $device */
        $device = $this->testDispatchAliveRecord();
        $device->controlCommands =
            "setDeviceVariable('authKey', '1234')\n".
            "setService('10.133.1.222', '18000')";

        DFCom_Controller_Device::getInstance()->update($device);

        $request = Tinebase_Http_Request::fromString(self::getTestRecordRequestData('alive', [
            'df_col_authKey' => $device->authKey,
        ]));

        $this->container->set(\Psr\Http\Message\RequestInterface::class, \Zend\Psr7Bridge\Psr7ServerRequest::fromZend($request));
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);

        $this->server->handle();

        $this->assertEquals(200, $this->emitter->response->getStatusCode());

        $this->emitter->response->getBody()->rewind();
        $body = $this->emitter->response->getBody()->getContents();

        $this->assertTrue(!!strstr($body, "setup.authKey,1234"), $body);
        $this->assertTrue(!!strstr($body, "df_service=1,10.133.1.222,18000"), $body);
    }

    public function testUpdateSetup()
    {
        $device = $this->testDispatchAliveRecord();

        $request = Tinebase_Http_Request::fromString(self::getTestRecordRequestData('alive', [
            'df_col_authKey' => $device->authKey,
            'df_col_setupStatus' => '0000',
        ]));

        $this->container->set(\Psr\Http\Message\RequestInterface::class, \Zend\Psr7Bridge\Psr7ServerRequest::fromZend($request));
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);

        $this->server->handle();

        $this->assertEquals(200, $this->emitter->response->getStatusCode());

        $this->emitter->response->getBody()->rewind();
        $body = $this->emitter->response->getBody()->getContents();

        $this->assertTrue(!!strstr($body, "df_setup_list=absenceReasons"), $body);

        $device = DFCom_Controller_Device::getInstance()->get($device->id);
        $this->assertEquals('0.7', $device->setupVersion);
    }

    public static function getTestRecordRequestData($table = 'alive', $overwrites = [])
    {
        $setupAuthKey = DFCom_Config::getInstance()->get(DFCom_Config::SETUP_AUTH_KEY);

        $data = [
            'df_api' => 1,
            'df_table' => $table,
            'df_col_dateTime' => Tinebase_DateTime::now()->format(Tinebase_DateTime::ISO8601),
            'df_col_deviceString' => 'EVO-Line 4.3',
            'df_col_serialNumber' => 1111,
            'df_col_authKey' => $setupAuthKey,
            'df_col_fwVersion' => '04.03.16.02',
            'df_col_setupVersion' => '0.7',
            'df_col_setupStatus' => '1000',
        ];

        switch ($table) {
            case 'alive' :
                $data = array_merge($data, [
                    'df_col_cellularData' => ',,�1,',
                    'df_col_GPRSAliveCounter' => '1264',
                    'df_col_GPRSData' =>'',
                    'df_col_digitalStatus' => 0,
                ]);
                break;
            case 'listFeedback':
                $data = array_merge($data, [
                    'df_col_type' => '',
                    'df_col_group' => '',
                    'df_col_reason' => '',
                    'df_col_detail1' => '',
                    'df_col_detail2' => '',
                    'df_col_detail3' => '',
                ]);
                break;
            case 'timeAccounting':
                $data = array_merge($data, [
                    'df_col_cardId' => '36118993923739652',
                    'df_col_functionKey' => DFCom_RecordHandler_TimeAccounting::FUNCTION_KEY_CLOCKIN,
                    'df_col_dateTime' => '2018-10-24T17:20:16'
                ]);
                break;
        }

        $data = array_merge($data, $overwrites);

        return 'GET /DFCom/v1/device/dispatchRecord?' .
            http_build_query($data, '', '&', PHP_QUERY_RFC3986) .
            ' HTTP/1.1' . "\r\n"
            . 'Host: 10.133.2.144:10080' . "\r\n"
            . 'Accept: application/x-www-form-urlencoded, text/html' . "\r\n"
            . 'Accept-Charset: ISO 8859-1' . "\r\n"
            . "\r\n";
    }
}
