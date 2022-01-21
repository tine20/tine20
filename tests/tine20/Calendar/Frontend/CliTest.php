<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Calendar_Frontend_Cli
 * 
 * @package     Calendar
 */
class Calendar_Frontend_CliTest extends Calendar_TestCase
{
    /**
     * Backend
     *
     * @var Calendar_Frontend_Cli
     */
    protected $_cli;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_cli = new Calendar_Frontend_Cli();
    }

    /**
     * testSharedCalendarReport
     */
    public function testSharedCalendarReport()
    {
        $calendar = $this->_getTestContainer('Calendar', 'Calendar_Model_Event');
        $userGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $this->_setPersonaGrantsForTestContainer($calendar, 'sclever', false, true, [
            [
                'account_id'    => $userGroup->getId(),
                'account_type'  => 'group',
                Tinebase_Model_Grants::GRANT_READ     => true,
                Tinebase_Model_Grants::GRANT_ADD      => true,
                Tinebase_Model_Grants::GRANT_EDIT     => true,
                Tinebase_Model_Grants::GRANT_DELETE   => false,
                Tinebase_Model_Grants::GRANT_ADMIN    => false,
            ]
        ]);

        $opts = new Zend_Console_Getopt('abp:');

        ob_start();
        $this->_cli->sharedCalendarReport($opts);
        $out = ob_get_clean();

        $expectedStrings = [
            '{"' . Tinebase_Core::getUser()->accountLoginName . '":{' => '',
            '{"PHPUnit Calendar_Model_Event container":' => 'container expected',
            '"readGrant":true' => '',
            '"editGrant":true' => '',
            '"addGrant":true' => '',
            '"deleteGrant":false' => '',
            '"privateGrant":false' => '',
            '"exportGrant":false' => '',
            '"syncGrant":false' => '',
            '"adminGrant":false' => '',
            '"freebusyGrant":false' => '',
            '"downloadGrant":false' => '',
            '"publishGrant":false' => '',
            '"account_id":"' . $this->_personas['sclever']->getId() => '',
            '"accountName":"sclever"' => '',
            ',"accountName":{"name":"' . $userGroup->name . '"' => 'user group name expected',
            ',"members":["' => 'member names expected'
        ];
        foreach ($expectedStrings as $expected => $failMessage) {
            self::assertStringContainsString($expected, $out, $failMessage);
        }
    }

    public function testExportVCalendar()
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
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/unittestexport');

        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments([
            'type=personal',
            'fm_path=/shared/unittestexport/',
        ]);

        ob_start();
        $this->_cli->exportVCalendar($opts);
        $out = ob_get_clean();

        // check if file exists in path and has the right contents
        $filename = Tinebase_Core::getUser()->accountLoginName . '_' . substr($this->_getTestCalendar()->getId(), 0, 8) . '.ics';
        $exportFilenamePath = 'Filemanager/folders/shared/unittestexport/'
            . $filename;
        $ics = file_get_contents('tine20:///' . $exportFilenamePath);
        self::assertStringContainsString('Anforderungsanalyse', $ics, 'output: ' . $out);
        self::assertStringContainsString('BEGIN:VCALENDAR', $ics, 'output: ' . $out);
        self::assertStringContainsString('BEGIN:VTIMEZONE', $ics, 'output: ' . $out);
        // 4 events + 1 time in header
        self::assertEquals(5, substr_count($ics, 'X-CALENDARSERVER-ACCESS:PUBLIC'),
            'X-CALENDARSERVER-ACCESS:PUBLIC should appear once in header');
    }
}
