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
class Sales_InvoiceControllerTests extends Sales_InvoiceTestCase
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
     * tests auto invoice creation
     */
    public function testAutoInvoice()
    {
        $c = Sales_Controller_Invoice::getInstance();
        $date = clone $this->_referenceDate;
        $i = 0;
        
        // the whole year, 12 months
        while ($i < 12) {
            $result = $c->createAutoInvoices($date);
            $date->addMonth(1);
            $i++;
        }
        
        $this->assertEquals(2, count($result['failures']));
        if (strstr($result['failures'][0], 'no customer')) {
            $this->assertTrue(strstr($result['failures'][0], 'no customer') !== FALSE);
            $this->assertTrue(strstr($result['failures'][1], 'no billing') !== FALSE);
        } else {
            $this->assertTrue(strstr($result['failures'][1], 'no customer') !== FALSE);
            $this->assertTrue(strstr($result['failures'][0], 'no billing') !== FALSE);
        }
        
        // also add an hour to get the last end
        $date->addHour(1);
        $c->createAutoInvoices($date);
        $date->addHour(1);
        $c->createAutoInvoices($date);
        
        $all = $c->getAll();
        
        $cc1 = $this->_costcenterRecords->filter('remark', 'unittest1')->getFirstRecord();
        $cc2 = $this->_costcenterRecords->filter('remark', 'unittest2')->getFirstRecord();
        $cc3 = $this->_costcenterRecords->filter('remark', 'unittest3')->getFirstRecord();
        $cc4 = $this->_costcenterRecords->filter('remark', 'unittest4')->getFirstRecord();
        
        $all->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        
        $customer1Invoices = $all->filter('costcenter_id', $cc1->getId())->sort('start_date');
        $customer2Invoices = $all->filter('costcenter_id', $cc2->getId())->sort('start_date');
        $customer3Invoices = $all->filter('costcenter_id', $cc3->getId())->sort('start_date');
        $customer4Invoices = $all->filter('costcenter_id', $cc4->getId())->sort('start_date');
        
        // customer 1 must have one invoice (timeaccount with budget has been billed the first month)
        $this->assertEquals(1, $customer1Invoices->count(), 'Customer 1 must have 1 invoice!');
        
        // customer 2 must have one invoice (timeaccount with budget has been billed the first time)
        $this->assertEquals(1, $customer2Invoices->count(), 'Customer 2 must have 1 invoice!');
        
        // there are timesheets in 2 intervals, so no empty invoice should be generated
        $this->assertEquals(2, $customer3Invoices->count(), 'Customer 3 must have 2 invoices!');
        
        // there are 2 products, interval 3,6 -> so every quarter in this and the first quarter of next year must be found
        $this->assertEquals(5, $customer4Invoices->count(), 'Customer 4 must have 5 invoices!');
        
        // test invoice positions
        $allInvoicePositions = Sales_Controller_InvoicePosition::getInstance()->getAll();
        
        $this->assertEquals(1, $allInvoicePositions->filter('invoice_id', $customer1Invoices->getFirstRecord()->getId())->count());
        $this->assertEquals(1, $allInvoicePositions->filter('invoice_id', $customer2Invoices->getFirstRecord()->getId())->count());
        
        // each invoice should contain 1 timeaccount
        foreach($customer3Invoices as $ci) {
            $this->assertEquals(1, $allInvoicePositions->filter('invoice_id', $ci->getId())->count());
        }
        
        // we need 9,3,9,3,9 invoice positions
        $i = 1;
        foreach($customer4Invoices as $ci) {
            $this->assertEquals(($i % 2 == 1) ? 9 : 3, $allInvoicePositions->filter('invoice_id', $ci->getId())->count());
            $i++;
        }
        
        $year = $this->_referenceDate->format('Y');
        
        // contract 1 gets billed at the begin of the period
        $c1IArray = $customer1Invoices->start_date;
        $this->assertEquals($year . '-01-01 00:00:00', $c1IArray[0]->toString());
        
        // find out if year is a leap year
        if (($year % 400) == 0 || (($year % 4) == 0 && ($year % 100) != 0)) {
            $lastFebruaryDay = 29;
        } else {
            $lastFebruaryDay = 28;
        }
        
        $c1IArray = $customer1Invoices->end_date;
        $this->assertEquals($year . '-01-31 23:59:59', $c1IArray[0]->toString());
        
        // contract 2 gets billed at the end of the period, and the second period ends at 1.8.20xx
        $c2IsArray = $customer2Invoices->start_date;
        $c2IeArray = $customer2Invoices->end_date;
        
        // TODO: goon here, count is ok, but dates not
        $this->assertEquals($year . '-06-01 00:00:00', $c2IsArray[0]->toString());
        $this->assertEquals($year . '-06-30 23:59:59', $c2IeArray[0]->toString());
        
        // test correct timesheet handling of customer 3
        $c3IsArray = $customer3Invoices->start_date;
        $c3IeArray = $customer3Invoices->end_date;
        
        $this->assertEquals($year . '-05-01 00:00:00', $c3IsArray[0]->toString());
        $this->assertEquals($year . '-05-31 23:59:59', $c3IeArray[0]->toString());
        
        $this->assertEquals($year . '-09-01 00:00:00', $c3IsArray[1]->toString());
        $this->assertEquals($year . '-09-30 23:59:59', $c3IeArray[1]->toString());
        
        // test customer 4 having products only
        $c4IsArray = $customer4Invoices->start_date;
        $c4IeArray = $customer4Invoices->end_date;
        
        // should contain billeachquarter & billhalfyearly
        $this->assertEquals($year . '-01-01 00:00:00', $c4IsArray[0]->toString());
        $this->assertEquals($year . '-06-30 23:59:59', $c4IeArray[0]->toString());
        
        // should contain billeachquarter
        $this->assertEquals($year . '-04-01 00:00:00', $c4IsArray[1]->toString());
        $this->assertEquals($year . '-06-30 23:59:59', $c4IeArray[1]->toString());
        
        // should contain billeachquarter & billhalfyearly
        $this->assertEquals($year . '-07-01 00:00:00', $c4IsArray[2]->toString());
        $this->assertEquals($year . '-12-31 23:59:59', $c4IeArray[2]->toString());
        
        // should contain billeachquarter
        $this->assertEquals($year . '-10-01 00:00:00', $c4IsArray[3]->toString());
        $this->assertEquals($year . '-12-31 23:59:59', $c4IeArray[3]->toString());
        
        // should contain billeachquarter & billhalfyearly
        $this->assertEquals($year + 1 . '-01-01 00:00:00', $c4IsArray[4]->toString());
        $this->assertEquals($year + 1 . '-06-30 23:59:59', $c4IeArray[4]->toString());
        
        // look if hours of timesheets gets calculated properly
        $c3Invoice = $customer3Invoices->getFirstRecord();
        $filter = new Sales_Model_InvoicePositionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $c3Invoice->getId())));
        $c3InvoicePositions = Sales_Controller_InvoicePosition::getInstance()->search($filter);
        
        $this->assertEquals(1, $c3InvoicePositions->count());
        $this->assertEquals(3.5, $c3InvoicePositions->getFirstRecord()->quantity);
        
        $invoice = $customer1Invoices->getFirstRecord();
        $invoice->relations = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Invoice', 'Sql', $invoice->getId())->toArray();
        
        $filter = new Sales_Model_InvoicePositionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice['id'])));
        $invoice->positions = Sales_Controller_InvoicePosition::getInstance()->search($filter);
        
        $invoice->cleared = 'CLEARED';
        $invoice = $c->update($invoice);
        
        $this->assertEquals("R-000001", $invoice->number);
        
        $invoice = $customer2Invoices->getFirstRecord();
        $invoice->relations = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Invoice', 'Sql', $invoice->getId())->toArray();
        $filter = new Sales_Model_InvoicePositionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice['id'])));
        $invoice->positions = Sales_Controller_InvoicePosition::getInstance()->search($filter);
        
        $invoice->cleared = 'CLEARED';
        $invoice = $c->update($invoice);
        
        $this->assertEquals("R-000002", $invoice->number);
        
        $this->setExpectedException('Sales_Exception_InvoiceAlreadyClearedEdit');
        
        $c->update($invoice);
    }
}
