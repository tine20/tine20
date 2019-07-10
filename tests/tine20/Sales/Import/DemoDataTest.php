<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl<c.feitl@metaways.de>
 */

/**
 * Test helper
 */

require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Sales_Import_DemoDataTest
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Sales All Import Tests');
        $suite->addTestSuite('Sales_Import_CostCenter');
        $suite->addTestSuite('Sales_Import_ProductTest'); 
        $suite->addTestSuite('Sales_Import_ContractTest'); 
        $suite->addTestSuite('Sales_Import_SupplierTest'); 
        $suite->addTestSuite('Sales_Import_OfferTest'); 
        $suite->addTestSuite('Sales_Import_CustomerTest'); 
        $suite->addTestSuite('Sales_Import_OrderConfirmationTest'); 
        $suite->addTestSuite('Sales_Import_PurchaseInvoiceTest'); 
        $suite->addTestSuite('Sales_Import_InvoiceTest');  
        $suite->addTestSuite('Sales_Import_Division');
        
        return $suite;
    }
}
