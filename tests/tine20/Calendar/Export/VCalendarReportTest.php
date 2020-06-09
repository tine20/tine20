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
 * Calendar_Export_VCalendarReport
 */
class Calendar_Export_VCalendarReportTest extends Calendar_TestCase
{
    public function testExportContainerToFilemanager()
    {
        $this->_testNeedsTransaction();

        $this->_importDemoData(
            'Calendar',
            Calendar_Model_Event::class, [
            'definition' => 'cal_import_event_csv',
            'file' => 'event.csv'
        ], $this->_getTestCalendar()
        );

        // export container to filemanager path
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('cal_default_vcalendar_report');
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/unittestexport');
        $fileLocation = new Tinebase_Model_Tree_FileLocation([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE      => Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE,
            Tinebase_Model_Tree_FileLocation::FLD_FM_PATH   => '/shared/unittestexport/',
        ]);
        $exporter = Tinebase_Export::factory(null, [
            'definitionId' => $definition->getId(),
            'sources' => [
                $this->_getTestCalendar()->toArray()
            ],
            'target' => $fileLocation->toArray(),
        ], null);
        $exporter->generate();

        // check if file exists in path and has the right contents
        $exportFilenamePath = 'Filemanager/folders/shared/unittestexport/'
            . str_replace([' ', DIRECTORY_SEPARATOR], '', $this->_getTestCalendar()->name . '.ics');
        $ics = file_get_contents('tine20:///' . $exportFilenamePath);
        self::assertContains('Anforderungsanalyse', $ics);
        self::assertContains('BEGIN:VCALENDAR', $ics);
        self::assertContains('BEGIN:VTIMEZONE', $ics);
        // 4 events + 1 time in header
        self::assertEquals(5, substr_count($ics, 'X-CALENDARSERVER-ACCESS:PUBLIC'),
            'X-CALENDARSERVER-ACCESS:PUBLIC should appear once in header');
    }
}
