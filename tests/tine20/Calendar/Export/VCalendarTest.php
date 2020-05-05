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
    }

    protected function _export($params = '')
    {
        $cmd = realpath(__DIR__ . "/../../../../tine20/tine20.php") . ' --method Calendar.exportVCalendar';
        $cmd = TestServer::assembleCliCommand($cmd, TRUE, 'container_id=' .
            $this->_getTestCalendar()->getId() . ' ' . $params);
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
        self::assertContains('ATTACH', $result);
        self::assertContains('FILENAME=tempfile.tmp', $result);
        self::assertContains('ENCODING=BASE64;VALUE="BINARY:dGVzdCBmaWx, lIGNvbnRlbnQ="', $result);
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
        $result = file_get_contents($filename);
        unlink($filename);
        self::assertContains('Anforderungsanalyse', $result);
        self::assertContains('BEGIN:VCALENDAR', $result);
        self::assertContains('BEGIN:VTIMEZONE', $result);
    }

    public function testExportAllCalendars()
    {
        // TODO implement
    }
}
