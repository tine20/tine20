<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl<c.feitl@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Timetracker_Import_TimesheetTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    public function testImportDemoData()
    {
        $tat = new Timetracker_Import_TimeaccountTest();
        $tat->importDemoData();

        // NOTE: needs timeaccount-demodata!
        if (Tinebase_DateTime::now()->setTimezone('UTC')->format('d') !== Tinebase_DateTime::now()->setTimezone(
                Tinebase_Core::getUserTimezone())->format('d')) {
            static::markTestSkipped('utc / usertimezone have a different date, test would fail');
        }

        $importer_timesheet = new Tinebase_Setup_DemoData_Import('Timetracker_Model_Timesheet', [
            'definition' => 'time_import_timesheet_csv',
            'file' => 'timesheet.csv',
        ]);

        $importer_timesheet->importDemodata();

        $filter_timesheet = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Timetracker_Model_Timesheet', [
            ['field' => 'creation_time', 'operator' => 'within', 'value' => 'dayThis']
        ]);
        $result_timesheet = Timetracker_Controller_TimeSheet::getInstance()->search($filter_timesheet);

        self::assertEquals(4, count($result_timesheet));
        return $result_timesheet;
    }
}
