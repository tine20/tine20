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
        
        $customer1Invoices = $all->filter('costcenter_id', $cc1->getId())->sort('start_date');
        $customer2Invoices = $all->filter('costcenter_id', $cc2->getId())->sort('start_date');
        $customer3Invoices = $all->filter('costcenter_id', $cc3->getId())->sort('start_date');
        
        $this->assertEquals(13, $customer1Invoices->count());
        $this->assertEquals(2, $customer2Invoices->count());
        $this->assertEquals(4, $customer3Invoices->count());
        
        $year = $this->_referenceDate->format('Y');
        
        // contract 1 gets billed at the begin of the period
        $c1IArray = $customer1Invoices->start_date;
        $this->assertEquals($year . '-01-01 00:00:00', $c1IArray[0]->toString());
        $this->assertEquals($year . '-02-01 00:00:00', $c1IArray[1]->toString());
        $this->assertEquals($year + 1 . '-01-01 00:00:00', $c1IArray[12]->toString());
        
        // find out if year is a leap year
        if (($year % 400) == 0 || (($year % 4) == 0 && ($year % 100) != 0)) {
            $lastFebruaryDay = 29;
        } else {
            $lastFebruaryDay = 28;
        }
        
        $c1IArray = $customer1Invoices->end_date;
        $this->assertEquals($year . '-01-31 23:59:59', $c1IArray[0]->toString());
        $this->assertEquals($year . '-02-' . $lastFebruaryDay . ' 23:59:59', $c1IArray[1]->toString());
        $this->assertEquals($year+1 . '-01-31 23:59:59', $c1IArray[12]->toString());
        
        // contract 2 gets billed at the end of the period, and the second period ends at 1.8.20xx
        $c2IsArray = $customer2Invoices->start_date;
        $c2IeArray = $customer2Invoices->end_date;
        
        $this->assertEquals($year . '-01-01 00:00:00', $c2IsArray[0]->toString());
        $this->assertEquals($year . '-04-30 23:59:59', $c2IeArray[0]->toString());
        
        $this->assertEquals($year . '-05-01 00:00:00', $c2IsArray[1]->toString());
        $this->assertEquals($year . '-07-31 23:59:59', $c2IeArray[1]->toString());
        
        $c2IsArray = $customer2Invoices->start_date;
        $c2IeArray = $customer2Invoices->end_date;
    }
    
    public function testGetBillableContracts()
    {
        $date = clone $this->_referenceDate;
        // 1.4.2013
        $date->addMonth(3);
        
        $c = Sales_Controller_Contract::getInstance();
        $result = $c->getBillableContracts($date);
        $this->assertEquals(4, $result->count());
        
        // 1.5.2013
        $date->addMonth(1);
        $this->assertEquals(4, $result->count());
    }
}
