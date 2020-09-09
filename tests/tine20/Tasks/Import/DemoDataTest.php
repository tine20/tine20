<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Inventory
 */
class Tasks_Import_DemoDataTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function tearDown()
    {
        Tasks_Controller_Task::getInstance()->deleteByFilter(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel('Tasks_Model_Task', [
                    ['field' => 'creation_time', 'operator' => 'within', 'value' => 'dayThis']
                ]
            ));

        parent::tearDown();
    }

    public function testImportDemoData()
    {
        $this->_importContainer = $this->_getTestContainer('Tasks', 'Tasks_Model_Task');
        $importer = new Tinebase_Setup_DemoData_Import('Tasks_Model_Task', [
            'container_id' => $this->_importContainer->getId(),
            'definition' => 'tasks_import_demo_csv',
            'file' => 'task.csv'
        ]);
        $importer->importDemodata();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Tasks_Model_Task', [
            ['field' => 'containerProperty', 'operator' => 'equals', 'value' => $this->_importContainer->getId()]
        ]);
        $result = Tasks_Controller_Task::getInstance()->search($filter);
        self::assertEquals(2, count($result));
    }
}