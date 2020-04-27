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

        $importContainer = $this->_importDemoData('Calendar', 'Calendar_Model_Event', 'cal_import_event_csv');
        $cmd = realpath(__DIR__ . "/../../../../tine20/tine20.php") . ' --method Calendar.exportVCalendar';

        $cmd = TestServer::assembleCliCommand($cmd, TRUE, 'container_id=' .
            $importContainer->getId());
        exec($cmd, $output);
        $result = implode(',', $output);

        self::assertContains('Anforderungsanalyse', $result);
        self::assertContains('BEGIN:VCALENDAR', $result);
        self::assertContains('BEGIN:VTIMEZONE', $result);
    }
}
