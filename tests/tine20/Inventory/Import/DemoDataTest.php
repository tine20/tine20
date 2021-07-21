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
class Inventory_Import_DemoDataTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function tearDown(): void
{
        parent::tearDown();
    }

    public function testImportDemoData()
    {
        $this->_importContainer = $this->_getTestContainer('Inventory', 'Inventory_Model_InventoryItem');
        $importer = new Tinebase_Setup_DemoData_Import('Inventory_Model_InventoryItem', [
            'container_id' => $this->_importContainer->getId(),
            'definition' => 'inv_tine_import_csv',
        ]);
        $importer->importDemodata();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Inventory_Model_InventoryItem', [
           ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_importContainer->getId()]
        ]);
        $result = Inventory_Controller_InventoryItem::getInstance()->search($filter);
        self::assertEquals(18, count($result));
    }

    public function testImportDemoDataViaCli()
    {
        self::markTestSkipped('FIXME: this fails at random');

        $this->_cli = new Inventory_Frontend_Cli();
        $out = $this->_appCliHelper('Inventory', 'createDemoData', []);
        echo $out;

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Inventory_Model_InventoryItem', [
            ['field' => 'creation_time', 'operator' => 'within', 'value' => 'dayThis']
        ]);
        $result = Inventory_Controller_InventoryItem::getInstance()->search($filter);
        self::assertGreaterThanOrEqual(18, count($result));
    }
}
