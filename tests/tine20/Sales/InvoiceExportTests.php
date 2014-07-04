<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 */

/**
 * Test class for Sales Invoice Controller
 */
class Sales_InvoiceExportTests extends Sales_InvoiceTestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Sales Invoice Controller Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see TestCase::tearDown()
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
    
    /**
     * tests auto invoice creation
     */
    public function testExportInvoice()
    {
        $this->_createFullFixtures();
        
        $date = clone $this->_referenceDate;
        
        $i = 0;
        
        // until 1.7
        while ($i < 8) {
            $result = $this->_invoiceController->createAutoInvoices($date);
            $date->addMonth(1);
            $i++;
        }
        
        $all = $this->_invoiceController->getAll();
        
        $cc3 = $this->_costcenterRecords->filter('remark', 'unittest3')->getFirstRecord();
        $cc4 = $this->_costcenterRecords->filter('remark', 'unittest4')->getFirstRecord();
        
        $all->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        
        $customer3Invoices = $all->filter('costcenter_id', $cc3->getId())->sort('start_date');
        $customer4Invoices = $all->filter('costcenter_id', $cc4->getId())->sort('start_date');
        
        // there are timesheets in 2 intervals, so no empty invoice should be generated
        $this->assertEquals(1, $customer3Invoices->count(), 'Customer 3 must have 1 invoice!');
        
        // there are 2 products, interval 3,6 -> so every quarter in this and the first quarter of next year must be found
        $this->assertEquals(2, $customer4Invoices->count(), 'Customer 4 must have 2 invoices!');
        
        // test products export
        $definition = dirname(dirname(dirname(dirname(__FILE__)))) . '/tine20/Sales/Export/definitions/invoiceposition_default_ods.xml';
        
        $filter = new Sales_Model_InvoicePositionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $customer4Invoices->getFirstRecord()->getId())));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'model', 'operator' => 'equals', 'value' => 'Sales_Model_ProductAggregate')));
        
        $exporter = new Sales_Export_Ods_InvoicePosition($filter, Sales_Controller_InvoicePosition::getInstance(), array('definitionFilename' => $definition));
        $doc = $exporter->generate();
        
        $xml = $this->_getContentXML($doc);
        
        $ns = $xml->getNamespaces(true);
        $spreadsheetXml = $xml->children($ns['office'])->{'body'}->{'spreadsheet'};
        
        // the product should be found here
        $half = 0;
        $quarter = 0;
        
        $i = 2;
        while ($i < 11) {
            $value = (string) $spreadsheetXml->children($ns['table'])->{'table'}->{'table-row'}->{$i}->children($ns['table'])->{'table-cell'}->{0}->children($ns['text'])->{0};
            $this->assertTrue(in_array($value, array('billhalfyearly', 'billeachquarter')), $value);
            if ($value == 'billhalfyearly') {
                $half++;
            } else {
                $quarter++;
            }
            $i++;
        }
        
        $this->assertEquals(6, $half);
        $this->assertEquals(3, $quarter);
        
        unlink($doc);
        
        // test timesheets export
        $definition = dirname(dirname(dirname(dirname(__FILE__)))) . '/tine20/Timetracker/Export/definitions/ts_default_ods.xml';
        
        $filter = new Timetracker_Model_TimesheetFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $customer3Invoices->getFirstRecord()->getId())));
        
        $exporter = new Timetracker_Export_Ods_Timesheet($filter, Timetracker_Controller_Timesheet::getInstance(), array('definitionFilename' => $definition));
        $doc = $exporter->generate();
        
        $xml = $this->_getContentXML($doc);
        
        $spreadsheetXml = $xml->children($ns['office'])->{'body'}->{'spreadsheet'};
        
        $firstContentRow = $spreadsheetXml->children($ns['table'])->{'table'}->{'table-row'}->{2};
        
        // the timesheet should be found here
        $this->assertEquals($this->_referenceYear . '-05-06', (string) $firstContentRow->children($ns['table'])->{'table-cell'}->{0}->children($ns['text'])->{0});
        $this->assertEquals('ts from ' . $this->_referenceYear . '-05-06 00:00:00', (string) $firstContentRow->children($ns['table'])->{'table-cell'}->{1}->children($ns['text'])->{0});
        $this->assertEquals('TA-for-Customer3', (string) $firstContentRow->children($ns['table'])->{'table-cell'}->{3}->children($ns['text'])->{0});
        $this->assertEquals('1.75', (string) $firstContentRow->children($ns['table'])->{'table-cell'}->{5}->children($ns['text'])->{0});
        
        unlink($doc);
    }
}
