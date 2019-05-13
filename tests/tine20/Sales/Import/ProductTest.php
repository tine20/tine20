<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl<c.feitl@metawas.de>
 */

/**
 * Test class for Sales
 */
class Sales_Import_ProductTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function tearDown()
    {
        parent::tearDown();
        self::clear('Sales', 'Product');

    }

    public function testImportDemoData()
    {
        self::clear('Sales', 'Product');
        $now = Tinebase_DateTime::now();
        $importer = new Tinebase_Setup_DemoData_Import('Sales_Model_Product', [
            'definition' => 'sales_import_product_csv',
            'file' => 'product.csv',
        ]);
        $importer->importDemodata();
        $filter = Sales_Model_ProductFilter::getFilterForModel('Sales_Model_Product', [
            ['field' => 'creation_time', 'operator' => 'after_or_equals', 'value' => $now]
        ]);
        $result = Sales_Controller_Product::getInstance()->search($filter);
        self::assertEquals(2, count($result));
    }
}