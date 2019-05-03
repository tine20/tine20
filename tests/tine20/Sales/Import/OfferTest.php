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
class Sales_Import_OfferTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function tearDown()
    {
        parent::tearDown();
        self::clear('Sales', 'Offer');
    }

    public function testImportDemoData()
    {
        self::clear('Sales', 'Offer');
        $now = Tinebase_DateTime::now();
        $importer = new Tinebase_Setup_DemoData_Import('Sales_Model_Offer', [
            'definition' => 'sales_import_offer_csv',
            'file' => 'offer.csv',
        ]);
        $importer->importDemodata();
        $filter = Sales_Model_OfferFilter::getFilterForModel('Sales_Model_Offer', [
            ['field' => 'creation_time', 'operator' => 'after_or_equals', 'value' => $now]
        ]);
        $result = Sales_Controller_Offer::getInstance()->search($filter);
        self::assertEquals(3, count($result));
    }
}