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

class Calendar_Import_EventTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testImportDemoData()
    {
        $this->_importContainer = $this->_getTestContainer('Calendar', 'Calendar_Model_Event');
        $importer = new Tinebase_Setup_DemoData_Import('Calendar_Model_Event', [
            'container_id' => $this->_importContainer->getId(),
            'definition' => 'cal_import_event_csv',
        ]);
        $importer->importDemodata();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Calendar_Model_Event', [
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_importContainer->getId()]
        ]);
        $result = Calendar_Controller_Event::getInstance()->search($filter);
        self::assertEquals(4, count($result));
    }
}