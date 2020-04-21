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

class Calendar_Import_ResourceTest extends TestCase
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
        self::markTestSkipped('FIXME: this fails in UPDATE test');

        $now = Tinebase_DateTime::now();
        $this->_importContainer = $this->_getTestContainer('Calendar', 'Calendar_Model_Resource');
        $importer = new Tinebase_Setup_DemoData_Import('Calendar_Model_Resource', [
            'container_id' => $this->_importContainer->getId(),
            'definition' => 'cal_import_resource_csv',
            'file' => 'resource.csv',
        ]);
        $importer->importDemodata();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Calendar_Model_Resource', [
            ['field' => 'creation_time', 'operator' => 'after_or_equals', 'value' => $now]
        ]);
        $result = Calendar_Controller_Resource::getInstance()->search($filter);
        self::assertEquals(1, count($result));
    }
}