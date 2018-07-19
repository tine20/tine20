<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

/**
 * Test class for Inventory
 */
class HumanResources_Import_DemoDataTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    /**
     * tearDown
     */
    protected function tearDown()
    {
        HumanResources_Controller_Employee::getInstance()->deleteByFilter(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel('HumanResources_Model_Employee', [
                ['field' => 'number', 'operator' => 'equals', 'value' => 567]
            ]
        ));

        parent::tearDown();
    }

    public function testImportDemoData()
    {
        $importer = new Tinebase_Setup_DemoData_Import('HumanResources_Model_Employee', [
            'definition' => 'hr_employee_import_csv',
        ]);
        $importer->importDemodata();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('HumanResources_Model_Employee', [
            ['field' => 'number', 'operator' => 'equals', 'value' => 567]
        ]);
        $result = HumanResources_Controller_Employee::getInstance()->search($filter);
        self::assertEquals(1, count($result));
    }
}
