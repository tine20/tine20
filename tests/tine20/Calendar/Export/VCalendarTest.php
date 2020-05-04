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

        $importContainer = $this->_importDemoData('Calendar', 'Calendar_Model_Event', [
            'definition' => 'cal_import_event_csv',
            'file' => 'event.csv'
        ]);
        $result = $this->_import();

        self::assertContains('Anforderungsanalyse', $result);
        self::assertContains('BEGIN:VCALENDAR', $result);
        self::assertContains('BEGIN:VTIMEZONE', $result);
    }

    public function testExportRecurEvent()
    {
        $this->_testNeedsTransaction();

        $event = $this->_getRecurEvent();
        Calendar_Controller_Event::getInstance()->create($event);

        $result = $this->_import();

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

        $result = $this->_import();

        self::assertContains('Early to bed and early to rise', $result);
        self::assertContains('ATTACH', $result);
        self::assertContains('FILENAME=tempfile.tmp', $result);
        self::assertContains('ENCODING=BASE64;VALUE="BINARY:dGVzdCBmaWx, lIGNvbnRlbnQ="', $result);
    }

    public function testExportIntoFile()
    {
        $this->_testNeedsTransaction();

        $this->_importDemoData('Calendar', Calendar_Model_Event::class, ['cal_import_event_csv']);
        $filename = '/tmp/export.ics';
        $this->_import('filename=' . $filename);
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
