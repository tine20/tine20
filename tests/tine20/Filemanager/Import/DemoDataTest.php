<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

/**
 * Test class for Inventory
 */
class Filemanager_Import_DemoDataTest extends TestCase
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
        $this->_importContainer = $this->_getTestContainer('Filemanager', 'Filemanager_Model_Node');
        $importer = new Tinebase_Setup_DemoData_Import('Filemanager_Model_Node', [
            'container_id' => $this->_importContainer->getId(),
            'definition' => 'filemanager_struktur_import_csv',
            'file' => 'filemanager.csv',
        ]);
        $importer->importDemodata();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Filemanager_Model_Node', [
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_importContainer->getId()]
        ]);
        $result = Filemanager_Controller_Node::getInstance()->search($filter);
        self::assertEquals(3, count($result));
    }
}