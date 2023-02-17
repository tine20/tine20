<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Sales Csv generation class tests
 *
 * @package     Sales
 * @subpackage  Export
 */
class Sales_Export_ProductTest extends TestCase
{
    protected function _genericExportTest($_config)
    {
        Sales_Controller_Product::getInstance()->create(new Sales_Model_Product(array(
            'name'   => [[
                Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => 'de',
                Tinebase_Record_PropertyLocalization::FLD_TEXT => 'Ein neues produkt',
            ]],
            'salesprice'           => 0.55,
        )));
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Product::class, [
            ['field' => 'salesprice', 'operator' => 'equals', 'value' => 0.55]
        ]);
        $_config['app'] = 'Sales';
        return $this->_genericCsvExport($_config, $filter);
    }

    public function testNewCsvExport()
    {
        $fh = $this->_genericExportTest([
            'definition' => __DIR__ . '/definitions/sales_product_csv_test.xml',
        ]);
        try {
            rewind($fh);

            $row = fgetcsv($fh, 0, ";", '"');
            static::assertTrue(is_array($row), 'could not read csv ');
            static::assertEquals('Name', $row[1]);
            $row = fgetcsv($fh, 0, ";", '"');
            static::assertTrue(is_array($row), 'could not read csv ');
            static::assertEquals('Ein neues produkt', $row[1], 'row: ' . print_r($row, true));
            static::assertEquals('0.55 ' . Tinebase_Config::getInstance()->{Tinebase_Config::CURRENCY_SYMBOL}, $row[2]);
        } finally {
            fclose($fh);
        }
    }
}
