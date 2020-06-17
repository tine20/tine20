<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Server_Http
 * 
 * @package     Tinebase
 */
class Tinebase_Server_HttpTests extends TestCase
{
    /**
     * @group ServerTests
     *
     * @see  0012364: generalize import/export and allow to configure via modelconfig
     *
     * @param boolean $returnFileLocation
     */
    public function testHandleRequestForDynamicAPI($returnFileLocation = false)
    {
        $server = new Tinebase_Server_Http();
        $request = $this->_getRequest();

        // set method & params
        $_REQUEST['method'] = 'ExampleApplication.exportExampleRecords';
        $_REQUEST['filter'] = Zend_Json::encode(array());
        $_REQUEST['options'] = Zend_Json::encode([
            'format' => 'csv',
            'returnFileLocation' => $returnFileLocation,
        ]);

        ob_start();
        $server->handle($request);
        $out = ob_get_clean();

        $this->assertTrue(! empty($out), 'request should not be empty');
        $this->assertNotContains('Not Authorised', $out);
        $this->assertNotContains('Method not found', $out);
        $this->assertNotContains('No Application Controller found', $out);
        $this->assertNotContains('"error"', $out);
        $this->assertNotContains('PHP Fatal error', $out);

        if ($returnFileLocation) {
            $this->assertContains('{"success":true,"file_location":{"type":"download","tempfile_id":"', $out);
        } else {
            $this->assertContains('"name","description","status","reason","number_str","number_int","datetime","relations","container_id","tags","attachments","notes","seq","tags"', $out);
        }
    }

    public function testExportExampleRecordsReturnFileLocation()
    {
        $this->testHandleRequestForDynamicAPI(true);
    }

    /**
     * @return \Zend\Http\Request
     */
    protected function _getRequest()
    {
        return Tinebase_Http_Request::fromString(
            'POST /index.php HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryZQRf6nhpOLbSRcoe' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . "\r\n"
        );
    }

    /**
     * @group ServerTests
     *
     * @see \Calendar_Export_VCalendarReportTest::testExportContainerToFilemanager
     *
     * @param bool $download
     * @param bool $returnFileLocation
     * @return string
     */
    public function testExportEvents($download = false, $returnFileLocation = false)
    {
        $server = new Tinebase_Server_Http();
        $request = $this->_getRequest();

        $calendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class);
        Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event([
            'summary' => 'Get Up!',
            'dtstart'     => '2020-03-25 06:00:00',
            'dtend'       => '2020-03-25 06:15:00',
            'container_id' => $calendar->getId(),
        ]));
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('cal_default_vcalendar_report');
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/unittestexport');
        $fileLocation = new Tinebase_Model_Tree_FileLocation([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE      => $download
                ? Tinebase_Model_Tree_FileLocation::TYPE_DOWNLOAD
                : Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE,
            Tinebase_Model_Tree_FileLocation::FLD_FM_PATH   => '/shared/unittestexport/',
        ]);
        $options = [
            'definitionId' => $definition->getId(),
            'sources' => [
                $calendar->toArray()
            ],
            'target' => $fileLocation->toArray(),
            'returnFileLocation' => $returnFileLocation,
        ];

        // set method & params
        $_REQUEST['method'] = 'Calendar.exportEvents';
        $_REQUEST['filter'] = Zend_Json::encode([]);
        $_REQUEST['options'] = Zend_Json::encode($options);
        ob_start();
        $server->handle($request);
        $out = ob_get_clean();

        // check if file exists in path and has the right contents
        if (! $download) {
            $exportFilenamePath = 'Filemanager/folders/shared/unittestexport/'
                . str_replace([' ', DIRECTORY_SEPARATOR], '', $calendar->name . '.ics');
            $ics = file_get_contents('tine20:///' . $exportFilenamePath);
            self::assertContains('Get Up!', $ics);
        }

        return $out;
    }

    public function testExportEventsDownload()
    {
        $out = $this->testExportEvents(true);
        self::assertContains('BEGIN:VCALENDAR', $out);
        self::assertContains('Get Up!', $out);
    }

    public function testExportEventsDownloadZip()
    {
//        $out = $this->testExportEvents(true, false, true);
//        // TODO unzip $out
//        $unzipped = '';
//        self::assertContains('BEGIN:VCALENDAR', $unzipped);
//        self::assertContains('Get Up!', $unzipped);
    }

    public function testExportEventsDownloadReturnFileLocation()
    {
        $out = $this->testExportEvents(true, true);

        $this->assertTrue(!empty($out), 'request should not be empty');
        $this->assertContains('{"success":true,"file_location":{"type":"download","tempfile_id":"', $out);
    }

    public function testExportEventsToFilemanagerReturnFileLocation()
    {
        $out = $this->testExportEvents(false, true);

        $this->assertTrue(!empty($out), 'request should not be empty');
        $this->assertContains('{"success":true,"file_location":{"type":"fm_node","fm_path":"\/shared\/unittestexport\/"}}', $out);
    }

    /**
     * @group ServerTests
     *
     * @see  0012364: generalize import/export and allow to configure via modelconfig
     *
     * TODO create message first?
     */
    public function testHandleRequestForReflectionAPI()
    {
        $server = new Tinebase_Server_Http();
        $request = $this->_getRequest();

        // set method & params
        $_REQUEST['method'] = 'Felamimail.downloadAttachment';
        $_REQUEST['messageId'] = '1110de84c05316e55be87beab2ae5f0fb877b35f';
        $_REQUEST['partId'] = '1.1.2';
        ob_start();
        $server->handle($request);
        $out = ob_get_clean();
        //echo $out;

        $this->assertTrue(empty($out), 'request should be empty - no message with this id + part should be found');
        $this->assertNotContains('Not Authorised', $out);
        $this->assertNotContains('Method not found', $out);
        $this->assertNotContains('No Application Controller found', $out);
        $this->assertNotContains('"error"', $out);
        $this->assertNotContains('PHP Fatal error', $out);
    }
}
