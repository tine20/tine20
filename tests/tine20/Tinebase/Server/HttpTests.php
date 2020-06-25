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
     * @param string $definitionName
     * @param string $fm_path
     * @param array $additionalSources
     * @param array $additionalOptions
     * @return string
     */
    public function testExportEvents(
        $download = false,
        $returnFileLocation = false,
        $definitionName = 'cal_default_vcalendar_report',
        $fm_path = '/shared/unittestexport',
        $additionalSources = [],
        $additionalOptions = []
    ) {
        $server = new Tinebase_Server_Http();
        $request = $this->_getRequest();
        $nodePath = Tinebase_Model_Tree_Node_Path::createFromRealPath($fm_path,
            Tinebase_Application::getInstance()->getApplicationByName('Filemanager'));

        $calendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class);
        Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event([
            'summary' => 'Get Up!',
            'dtstart'     => '2020-03-25 06:00:00',
            'dtend'       => '2020-03-25 06:15:00',
            'container_id' => $calendar->getId(),
        ]));
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName($definitionName);

        $options = array_merge([
            'definitionId' => $definition->getId(),
            'returnFileLocation' => $returnFileLocation,
        ], $additionalOptions);
        $filter = [];
        switch ($definitionName) {
            case 'cal_default_vcalendar_report':
                Tinebase_FileSystem::getInstance()->mkdir($nodePath->statpath);
                $fileLocation = new Tinebase_Model_Tree_FileLocation([
                    Tinebase_Model_Tree_FileLocation::FLD_TYPE => $download
                        ? Tinebase_Model_Tree_FileLocation::TYPE_DOWNLOAD
                        : Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE,
                    Tinebase_Model_Tree_FileLocation::FLD_FM_PATH => $fm_path,
                ]);
                $options = array_merge($options, [
                    'sources' => array_merge($additionalSources, [
                        $calendar->toArray()
                    ]),
                    'target' => $fileLocation->toArray(),
                    'format' => 'csv', // client sends this ...
                ]);
                break;
            case 'cal_default_vcalendar':
            case 'cal_default_ods':
                $filter = [
                    ['field' => 'container_id', 'operator' => 'equals', 'value' => $calendar->getId()]
                ];
                break;
            default:
                throw new Tinebase_Exception_NotImplemented($definitionName . ' test not implemented');
        }

        // set method & params
        $_REQUEST['method'] = 'Calendar.exportEvents';
        $_REQUEST['filter'] = Zend_Json::encode($filter);
        $_REQUEST['options'] = Zend_Json::encode($options);
        ob_start();
        $server->handle($request);
        $out = ob_get_clean();

        // check if file exists in path and has the right contents
        if (! $download && $definitionName === 'cal_default_vcalendar_report') {
            $exportFilenamePath = $nodePath->streamwrapperpath . '/0_'
                . str_replace([' ', DIRECTORY_SEPARATOR], '', $calendar->name . '.ics');
            $ics = file_get_contents($exportFilenamePath);
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
        $calendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class,
            false, 'ics export container');
        Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event([
            'summary' => 'Get Down!',
            'dtstart'     => '2020-03-25 22:00:00',
            'dtend'       => '2020-03-25 22:15:00',
            'container_id' => $calendar->getId(),
        ]));

        $out = $this->testExportEvents(true,
            true,
            'cal_default_vcalendar_report',
            '/shared/unittestexport',
            [$calendar->toArray()]
        );

        if (preg_match('/"tempfile_id":"([a-z0-9]+)"/', $out, $matches)) {
            $tempfile = Tinebase_TempFile::getInstance()->getTempFile($matches[1]);
            self::assertEquals('export_calendar_vcalendar_report.zip', $tempfile->name);
        } else {
            self::fail('could not extract tempfile_id');
        }

        $tmpfileContent = file_get_contents($tempfile->path);
        $icsFilename =  str_replace([' ', DIRECTORY_SEPARATOR], '', $calendar->name . '.ics');
        $content = $this->_unzipContent($tmpfileContent, $icsFilename);
        self::assertContains('BEGIN:VCALENDAR', $content);
        self::assertContains('Get Down!', $content);
    }

    public function testExportEventsDownloadZipSplit()
    {
        $calendar = $this->_testCalendarWithTwoEvents();
        $out = $this->testExportEvents(true,
            true,
            'cal_default_vcalendar_report',
            '/shared/unittestexport',
            [$calendar->toArray()],
            ['maxfilesize' => 1] // split after each event!
        );

        if (preg_match('/"tempfile_id":"([a-z0-9]+)"/', $out, $matches)) {
            $tempfile = Tinebase_TempFile::getInstance()->getTempFile($matches[1]);
            self::assertEquals('export_calendar_vcalendar_report.zip', $tempfile->name);
        } else {
            self::fail('could not extract tempfile_id');
        }

        $tmpfileContent = file_get_contents($tempfile->path);
        $icsFilename =  str_replace([' ', DIRECTORY_SEPARATOR], '', '0_' . $calendar->name . '.ics');
        $content = $this->_unzipContent($tmpfileContent, $icsFilename);
        self::assertContains('BEGIN:VCALENDAR', $content);
        self::assertContains('Get Down!', $content);

        $icsFilename =  str_replace([' ', DIRECTORY_SEPARATOR], '', '1_' . $calendar->name . '.ics');
        $content = $this->_unzipContent($tmpfileContent, $icsFilename);
        self::assertContains('BEGIN:VCALENDAR', $content);
        self::assertContains('Get A Downer!', $content);
    }

    protected function _testCalendarWithTwoEvents()
    {
        $calendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class,
            false, 'ics export container');
        Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event([
            'summary' => 'Get Down!',
            'dtstart'     => '2020-03-25 22:00:00',
            'dtend'       => '2020-03-25 22:15:00',
            'container_id' => $calendar->getId(),
        ]));
        Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event([
            'summary' => 'Get A Downer!',
            'dtstart'     => '2020-03-25 23:00:00',
            'dtend'       => '2020-03-25 23:15:00',
            'container_id' => $calendar->getId(),
        ]));
        return $calendar;
    }

    public function testExportEventsFilemanagerSplit()
    {
        $calendar = $this->_testCalendarWithTwoEvents();
        $out = $this->testExportEvents(false,
            true,
            'cal_default_vcalendar_report',
            '/shared/unittestexport',
            [$calendar->toArray()],
            ['maxfilesize' => 1] // split after each event!
        );
        $this->assertTrue(!empty($out), 'request should not be empty');
        $this->assertContains('{"success":true,"file_location":{"type":"fm_node","fm_path":"\/shared\/unittestexport"}}', $out);
    }

    public function testExportEventsDownloadReturnFileLocation()
    {
        $out = $this->testExportEvents(true, true);

        $this->assertTrue(!empty($out), 'request should not be empty');
        $this->assertContains('{"success":true,"file_location":{"type":"download","tempfile_id":"', $out);
    }

    public function testExportEventsToFilemanagerSharedReturnFileLocation()
    {
        $out = $this->testExportEvents(false, true);

        $this->assertTrue(!empty($out), 'request should not be empty');
        $this->assertContains('{"success":true,"file_location":{"type":"fm_node","fm_path":"\/shared\/unittestexport"}}', $out);
    }

    public function testExportEventsToFilemanagerPersonalReturnFileLocation()
    {
        $path = '/personal/' . Tinebase_Core::getUser()->accountLoginName . '/mypersonal';
        $out = $this->testExportEvents(false, true, 'cal_default_vcalendar_report', $path);

        $this->assertTrue(!empty($out), 'request should not be empty');
        $this->assertContains('{"success":true,"file_location":{"type":"fm_node","fm_path":"'
            . str_replace('/', '\/', $path) . '"}}', $out);
    }

    public function testExportEventsDownloadReturnFileLocationVCalendar()
    {
        $out = $this->testExportEvents(true, true, 'cal_default_vcalendar');

        $this->assertTrue(!empty($out), 'request should not be empty');
        $this->assertContains('{"success":true,"file_location":{"type":"download","tempfile_id":"', $out);
        // check download filename in Tempfile
        if (preg_match('/"tempfile_id":"([a-z0-9]+)"/', $out, $matches)) {
            $tempfile = Tinebase_TempFile::getInstance()->getTempFile($matches[1]);
            self::assertEquals('export_calendar_vcalendar_ics.ics', $tempfile->name);
        } else {
            self::fail('could not extract tempfile_id');
        }
    }

    public function testExportEventsDownloadReturnFileLocationOds()
    {
        $out = $this->testExportEvents(true, true, 'cal_default_ods');

        $this->assertTrue(!empty($out), 'request should not be empty');
        $this->assertContains('{"success":true,"file_location":{"type":"download","tempfile_id":"', $out);
        // check download filename in Tempfile
        if (preg_match('/"tempfile_id":"([a-z0-9]+)"/', $out, $matches)) {
            $tempfile = Tinebase_TempFile::getInstance()->getTempFile($matches[1]);
            self::assertEquals('export_calendar.ods', $tempfile->name);
        } else {
            self::fail('could not extract tempfile_id');
        }
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
