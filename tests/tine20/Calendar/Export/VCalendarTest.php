<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Calendar_Export_VCalendar
 */
class Calendar_Export_VCalendarTest extends Calendar_TestCase
{
    public function testExportPersonalContainer()
    {
        $this->_testNeedsTransaction();

        $this->_importDemoData(
            'Calendar',
            Calendar_Model_Event::class, [
                'definition' => 'cal_import_event_csv',
                'file' => 'event.csv'
            ], $this->_getTestCalendar()
        );
        $result = $this->_export();

        self::assertContains('Anforderungsanalyse', $result);
        self::assertContains('BEGIN:VCALENDAR', $result);
        self::assertContains('BEGIN:VTIMEZONE', $result);
        // 4 events + 1 time in header
        self::assertEquals(5, substr_count($result, 'X-CALENDARSERVER-ACCESS:PUBLIC'),
            'X-CALENDARSERVER-ACCESS:PUBLIC should appear once in header');
    }

    protected function _export($params = '', $addContainerid = true)
    {
        $cmd = realpath(__DIR__ . "/../../../../tine20/tine20.php") . ' --method Calendar.exportVCalendar';
        $args = $addContainerid ? 'container_id=' .
            $this->_getTestCalendar()->getId() : '';
        if (! empty($params)) {
            $args .= ' ' . $params;
        }
        $cmd = TestServer::assembleCliCommand($cmd, TRUE,  $args);
        exec($cmd, $output);
        return implode(',', $output);
    }

    public function testExportRecurEvent()
    {
        $this->_testNeedsTransaction();

        $event = $this->_getRecurEvent();
        Calendar_Controller_Event::getInstance()->create($event);

        $result = $this->_export();

        self::assertContains('hard working man needs some silence', $result);
        self::assertContains('RRULE:FREQ=DAILY', $result);
    }

    public function testExportEventWithAttachment()
    {
        $this->_testNeedsTransaction();

        $event = $this->_getEvent();
        Calendar_Controller_Event::getInstance()->create($event);
        $tempFile = $this->_getTempFile();
        Tinebase_FileSystem_RecordAttachments::getInstance()->addRecordAttachment(
            $event, $tempFile->name, $tempFile);

        $result = $this->_export();

        self::assertContains('Early to bed and early to rise', $result);
        self::assertContains('ATTACH;ENCODING=BASE64;X-FILENAME=tempfile.tmp;', $result);
        self::assertContains('VALUE=BINARY:dGVzdCBmaWxlIGN, vbnRlbnQ=', $result);
    }

    public function testExportIntoFile()
    {
        $this->_testNeedsTransaction();

        $this->_importDemoData(
            'Calendar',
            Calendar_Model_Event::class, [
                'definition' => 'cal_import_event_csv'
            ], $this->_getTestCalendar()
        );
        $filename = '/tmp/export.ics';
        $this->_export('filename=' . $filename);
        self::assertTrue(file_exists($filename), 'export file does not exist');
        $result = file_get_contents($filename);
        unlink($filename);
        self::assertContains('Anforderungsanalyse', $result);
        self::assertContains('SUMMARY:Mittag', $result);
        self::assertContains('BEGIN:VCALENDAR', $result);
        self::assertContains('BEGIN:VTIMEZONE', $result);
        self::assertContains('END:VCALENDAR', $result);
    }

    public function testExportAllCalendars()
    {
        $this->_testNeedsTransaction();

        $this->_importDemoData(
            'Calendar',
            Calendar_Model_Event::class, [
                'definition' => 'cal_import_event_csv'
            ], $this->_getTestCalendar()
        );

        $path = Tinebase_Core::getTempDir() . DIRECTORY_SEPARATOR . 'tine20_export_' . Tinebase_Record_Abstract::generateUID(8);
        mkdir($path);
        $output = $this->_export('path=' . $path . ' type=personal', false);

        self::assertContains('Exported container ' . $this->_getTestCalendar()->getId() . ' into file', $output);

        // loop files in export dir
        $exportFilesFound = 0;
        $fh = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($fh as $splFileInfo) {
            /** @var SplFileInfo $splFileInfo */
            $filename = $splFileInfo->getFilename();
            if ($filename === '.' || $filename === '..') {
                continue;
            }
            self::assertContains(Tinebase_Core::getUser()->accountLoginName, $filename);
            $result = file_get_contents($splFileInfo->getPathname());
            self::assertContains('END:VCALENDAR', $result);
            $exportFilesFound++;
            unlink($splFileInfo->getPathname());
        }
        self::assertGreaterThan(0, $exportFilesFound);

        rmdir($path);
    }
}
