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
 * Test class for Sales Invoice Json
 */
class Sales_InvoiceJsonTests extends Sales_InvoiceTestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Sales Invoice Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * checks if all properties are resolved and saved properly
     */
    public function testCRUD()
    {
        $this->_createCustomers();
        $this->_createContracts();
        
        $json = new Sales_Frontend_Json();
        $customer = $json->getCustomer($this->_customerRecords->filter('name', 'Customer3')->getFirstRecord()->getId());
        
        $this->assertTrue(is_array($customer['postal_id']));
        $this->assertEquals($customer['adr_id'], $customer['postal_id']['id']);
        
        $this->assertTrue(is_array($customer['billing']));
        $this->assertEquals(1, count($customer['billing']));
        
        $this->assertTrue(is_array($customer['delivery']));
        $this->assertEquals(1, count($customer['delivery']));
        
        $this->assertTrue(is_array($customer['relations']));
        $this->assertTrue(is_array($customer['relations'][0]['related_record']));
        
        $this->assertEquals(1, count($customer['relations']));
        $this->assertEquals('CUSTOMER', $customer['relations'][0]['type']);
        
        $customer['billing'][1] = array('prefix1' => 'Dr.', 'prefix2' => 'George Harbottle', 'street' => 'Glasgow Str. 432', 'postalcode' => '532 45', 'locality' => 'Birmingham', 'type' => 'billing');
        $customer['delivery'][1] = array('prefix1' => 'Mr.', 'prefix2' => 'Peter Harbottle', 'street' => 'London Str. 123', 'postalcode' => '532 23', 'locality' => 'Birmingham', 'type' => 'delivery');
        
        $customer = $json->saveCustomer($customer);
        
        $this->assertEquals(2, count($customer['billing']));
        $this->assertEquals(2, count($customer['delivery']));
        
        $json->deleteCustomers(array($customer['id']));
        
        $this->setExpectedException('Tinebase_Exception_NotFound');

        $json->getCustomer($customer['id']);
    }
    
    /**
     * tests if all relations and positions are resolved properly
     */
    public function testResolving()
    {
        $this->_createFullFixtures();
        
        $date = clone $this->_referenceDate;
        $date->addSecond(1);
        
        $this->_invoiceController->createAutoInvoices($date);
        
        $json = new Sales_Frontend_Json();
        $invoices = $json->searchInvoices(array(), array());
        
        $this->assertEquals(2, $invoices['totalcount']);
        $c4Invoice = $c1Invoice = NULL;
        
        foreach($invoices['results'] as $invoice) {
            
            // fetch invoice by get to have all relations set
            $invoice = $json->getInvoice($invoice['id']);
            
            // first invoice for customer 4
            if (count($invoice['relations']) == 4) {
                
                $this->assertEquals(9, count($invoice['positions']));
                
                foreach($invoice['relations'] as $relation) {
                    switch ($relation['type']) {
                        case 'INVOICE_ITEM':
                            $this->assertEquals('Sales_Model_ProductAggregate', $relation['related_model']);
                            break;
                        case 'CUSTOMER':
                            $this->assertEquals('Sales_Model_Customer', $relation['related_model']);
                            $this->assertEquals('Customer4', $relation['related_record']['name']);
                            $c4Invoice = $invoice;
                            break;
                        case 'CONTRACT':
                            $this->assertEquals('Sales_Model_Contract', $relation['related_model']);
                            break;
                    }
                }
            } else {
                // first invoice for customer 1
                $this->assertEquals(3, count($invoice['relations']));
                $this->assertEquals(1, count($invoice['positions']));
                
                foreach($invoice['relations'] as $relation) {
                    switch ($relation['type']) {
                        case 'INVOICE_ITEM':
                            $this->assertEquals('Timetracker_Model_Timeaccount', $relation['related_model']);
                            break;
                        case 'CUSTOMER':
                            $this->assertEquals('Sales_Model_Customer', $relation['related_model']);
                            $this->assertEquals('Customer1', $relation['related_record']['name']);
                            $c1Invoice = $invoice;
                            break;
                        case 'CONTRACT':
                            $this->assertEquals('Sales_Model_Contract', $relation['related_model']);
                            break;
                    }
                }
            }
        }
        
        $this->assertTrue(is_array($c1Invoice));
        $this->assertTrue(is_array($c4Invoice));
    }
    
    /**
     * tests if timeaccounts/timesheets get cleared if the invoice get billed
     */
    public function testClearing()
    {
        $this->_createFullFixtures();
        
        // the whole year, 12 months
        $i = 0;
        $date = clone $this->_referenceDate;
        
        while ($i < 12) {
            $result = $this->_invoiceController->createAutoInvoices($date);
            $date->addMonth(1);
            $i++;
        }
        
        $json = new Sales_Frontend_Json();
        
        // test if timesheets get cleared
        $invoices = $json->searchInvoices(array(
            array('field' => 'foreignRecord', 'operator' => 'AND', 'value' => array(
                'appName' => 'Sales',
                'linkType' => 'relation',
                'modelName' => 'Customer',
                'filters' => array(
                    array('field' => 'name', 'operator' => 'equals', 'value' => 'Customer3')
                )
            ))
        
        ), array());
        
        $invoiceIds = array();
        
        $this->assertEquals(2, $invoices['totalcount']);
        
        foreach($invoices['results'] as $invoice) {
            $invoiceIds[] = $invoice['id'];
            // fetch invoice by get to have all relations set
            $invoice = $json->getInvoice($invoice['id']);
            $invoice['cleared'] = 'CLEARED';
            $json->saveInvoice($invoice);
        }
        
        $tsController = Timetracker_Controller_Timesheet::getInstance();
        $timesheets = $tsController->getAll();
        
        foreach($timesheets as $timesheet) {
            $this->assertTrue(in_array($timesheet->invoice_id, $invoiceIds), 'the invoice id must be set!');
            $this->assertEquals(1, $timesheet->is_cleared);
        }
        
        // test if timeaccounts get cleared
        $invoices = $json->searchInvoices(array(
            array('field' => 'foreignRecord', 'operator' => 'AND', 'value' => array(
                'appName' => 'Sales',
                'linkType' => 'relation',
                'modelName' => 'Customer',
                'filters' => array(
                    array('field' => 'name', 'operator' => 'equals', 'value' => 'Customer1')
                )
            ))
        
        ), array());
        
        $invoiceIds = array();
        
        foreach($invoices['results'] as $invoice) {
            $invoiceIds[] = $invoice['id'];
            // fetch invoice by get to have all relations set
            $invoice = $json->getInvoice($invoice['id']);
            $invoice['cleared'] = 'CLEARED';
            $json->saveInvoice($invoice);
        }
        
        $taController = Timetracker_Controller_Timeaccount::getInstance();
        $filter = new Timetracker_Model_TimeaccountFilter(array(
            array('field' => 'budget', 'operator' => 'greater', 'value' => 0),
            array('field' => 'is_open', 'operator' => 'equals', 'value' => 0)
        ));
        
        $timeaccounts = $taController->search($filter);
        
        $this->assertEquals(1, $timeaccounts->count());
        
        foreach($timeaccounts as $ta) {
            $this->assertTrue(in_array($ta->invoice_id, $invoiceIds), 'the invoice id id must be set!');
            $this->assertEquals('billed', $ta->status);
        }
    }
    
    /**
     * tests if product_id gets converted to string
     */
    public function testSanitizingProductId()
    {
        $this->_createProducts();
        $this->_createContracts();
        
        $json = new Sales_Frontend_Json();
        
        $firstContract = $this->_contractRecords->filter('number', 4)->getFirstRecord();
        
        // TODO: fix test
        if ($firstContract == null) {
            $this->markTestSkipped('TODO');
        }
        
        $contract = $json->getContract($firstContract->getId());
        
        $this->assertTrue(is_array($contract['products'][0]['product_id']));
        
        $json->saveContract($contract);
    }
    
    /**
     * tests if invoice id gets removed from the billables if the invoice gets deleted
     */
    public function testRemoveInvoiceFromBillables()
    {
        $this->_createFullFixtures();
        
        $i = 0;
        $date = clone $this->_referenceDate;
        $date->addSecond(1);
        
        $result = $this->_invoiceController->createAutoInvoices($date);
        
        $json = new Sales_Frontend_Json();
        $invoices = $json->searchInvoices(array(), array());
        $this->assertEquals(2, $invoices['totalcount']);
        
        foreach($invoices['results'] as $result) {
            $ids[] = $result['id'];
        }
        
        $json->deleteInvoices($ids);
        
        $taJson = new Timetracker_Frontend_Json();
        $tas = $taJson->searchTimeaccounts(array(), array());
        $tss = $taJson->searchTimesheets(array(), array());
        
        foreach($tas['results'] as $t) {
            $this->assertEquals(NULL, $t['invoice_id']);
        }
        
        foreach($tss['results'] as $t) {
            $this->assertEquals(NULL, $t['invoice_id']);
        }
    }
    
    /**
     * test constraints after changing relation
     */
    public function testTimeaccountRelation()
    {
        $sjson = new Sales_Frontend_Json();
        $tjson = new Timetracker_Frontend_Json();
        
        $ta = $tjson->saveTimeaccount(array('number' => 43379, 'title' => 'bla'));
        
        $c1 = $sjson->saveContract(array('number' => '1', 'description' => 'blub bla', 'title' => 'blub'));
        $c2 = $sjson->saveContract(array('number' => '2', 'description' => 'bla blub', 'title' => 'bla'));
        
        $c1['relations'] = array(array(
            'related_model' => 'Timetracker_Model_Timeaccount',
            'related_id'    => $ta['id'],
            'own_degree'    => 'sibling',
            'type'          => 'TIME_ACCOUNT',
            'remark'        => 'unittest',
            'related_backend' => 'Sql'
        ));
        
        $c1 = $sjson->saveContract($c1);
        $c1Id = $c1['id'];
        
        // delete timeaccount relation from the first contract
        $c1 = $sjson->getContract($c1Id);
        $c1['relations'] = array();
        $c1 = $sjson->saveContract($c1);
        
        // save second contract having the timeaccount related
        $c2['relations'] = array(array(
            'related_model'   => 'Timetracker_Model_Timeaccount',
            'related_id'      => $ta['id'],
            'own_degree'      => 'sibling',
            'type'            => 'TIME_ACCOUNT',
            'remark'          => 'unittest',
            'related_backend' => 'Sql'
        ));
        
        $c2 = $sjson->saveContract($c2);
        
        $this->assertEquals(1, count($c2['relations']));
    }
}
