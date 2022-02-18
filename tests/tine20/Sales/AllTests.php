<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Sales_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Sales All Tests');
        $suite->addTestSuite('Sales_Backend_ContractTest');
        $suite->addTestSuite('Sales_Backend_NumberTest');
        $suite->addTestSuite('Sales_Backend_CostCenterTest');
        $suite->addTestSuite('Sales_ControllerTest');
        $suite->addTestSuite('Sales_JsonTest');
        $suite->addTestSuite('Sales_SuppliersTest');
        $suite->addTestSuite('Sales_PurchaseInvoiceTest');
        $suite->addTestSuite('Sales_CustomFieldTest');
        $suite->addTestSuite('Sales_InvoiceControllerTests');
        $suite->addTestSuite('Sales_InvoiceJsonTests');
        $suite->addTestSuite('Sales_InvoiceExportTests');
        $suite->addTestSuite('Sales_OrderConfirmationControllerTests');
        $suite->addTestSuite('Sales_OfferControllerTests');
        $suite->addTestSuite('Sales_Import_AllTests');
        $suite->addTestSuite(Sales_BoilerplateControllerTest::class);
        $suite->addTestSuite(Sales_Document_ControllerTest::class);
        $suite->addTestSuite(Sales_Document_ExportTest::class);
        $suite->addTestSuite(Sales_Document_JsonTest::class);
        $suite->addTestSuite(Sales_Export_ProductTest::class);

        return $suite;
    }
}
