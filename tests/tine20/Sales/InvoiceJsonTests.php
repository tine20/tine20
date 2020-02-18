<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 */

/**
 * Test class for Sales Invoice Json
 */
class Sales_InvoiceJsonTests extends Sales_InvoiceTestCase
{
    /**
     * @var Sales_Frontend_Json
     */
    protected $_uit = null;

    /**
     * setUp
     */
    protected function setUp()
    {
        if (! Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_INVOICES_MODULE)) {
            $this->markTestSkipped('needs enabled invoices module');
        }
        
        $this->_uit = new Sales_Frontend_Json();

        parent::setUp();
    }

    /**
     * checks if all properties are resolved and saved properly
     */
    public function testCRUD()
    {
        $this->_createCustomers();
        $this->_createContracts();
        
        $customer = $this->_uit->getCustomer($this->_customerRecords->filter('name', 'Customer3')->getFirstRecord()->getId());
        
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
        
        $customer = $this->_uit->saveCustomer($customer);
        
        $this->assertEquals(2, count($customer['billing']));
        $this->assertEquals(2, count($customer['delivery']));
        
        // remove contracts, otherwise deleting customers having an contract-assigned billing address would fail
        $this->_contractController->delete($this->_contractRecords->getId());
        
        $this->_uit->deleteCustomers(array($customer['id']));
        
        $this->setExpectedException('Tinebase_Exception_NotFound');

        $this->_uit->getCustomer($customer['id']);
    }
    
    /**
     * tests if all relations and positions are resolved properly
     */
    public function testResolving()
    {
        if ($this->_dbIsPgsql()) {
            $this->markTestSkipped('0011670: fix Sales_Invoices Tests with postgresql backend');
        }

        $this->_createFullFixtures();
        
        $date = clone $this->_referenceDate;
        $date->addHour(3);
        $date->addMonth(1);
        
        $this->_invoiceController->createAutoInvoices($date);
        
        $invoices = $this->_uit->searchInvoices(array(), array());
        
        $this->assertEquals(2, $invoices['totalcount']);
        $c4Invoice = $c1Invoice = NULL;
        
        foreach($invoices['results'] as $invoice) {
            foreach($invoice['relations'] as $relation) {
                if ($relation['type'] == 'CUSTOMER') {
                    if ($relation['related_record']['name'] == 'Customer4') {
                        $c4Invoice = $invoice;
                    } elseif ($relation['related_record']['name'] == 'Customer1') {
                        $c1Invoice = $invoice;
                    }
                }
            }
        }
        
        $this->assertTrue(is_array($c1Invoice));
        $this->assertTrue(is_array($c4Invoice));
        
        // first invoice for customer 4
        $invoice = $this->_uit->getInvoice($c4Invoice['id']);
        
        $this->assertEquals(9, count($invoice['positions']));
        
        foreach($invoice['relations'] as $relation) {
            switch ($relation['type']) {
                case 'CUSTOMER':
                    $this->assertEquals('Sales_Model_Customer', $relation['related_model']);
                    $this->assertEquals('Customer4', $relation['related_record']['name']);
                    break;
                case 'CONTRACT':
                    $this->assertEquals('Sales_Model_Contract', $relation['related_model']);
                    break;
            }
        }
        
        $invoice = $this->_uit->getInvoice($c1Invoice['id']);
        
        // first invoice for customer 1
        $this->assertEquals(3, count($invoice['relations']));

        $this->assertEquals(1, count($invoice['positions']));
        
        foreach($invoice['relations'] as $relation) {
            switch ($relation['type']) {
                case 'CUSTOMER':
                    $this->assertEquals('Sales_Model_Customer', $relation['related_model']);
                    $this->assertEquals('Customer1', $relation['related_record']['name']);
                    break;
                case 'CONTRACT':
                    $this->assertEquals('Sales_Model_Contract', $relation['related_model']);
                    break;
            }
        }
    }

    public function testReversal()
    {
        $this->_createFullFixtures();

        // the whole year, 12 months
        $date = clone $this->_referenceDate;
        $date->addMonth(12);
        $this->_invoiceController->createAutoInvoices($date);

        // test if timesheets get cleared
        $invoices = $this->_uit->searchInvoices(array(
            array('field' => 'foreignRecord', 'operator' => 'AND', 'value' => array(
                'appName' => 'Sales',
                'linkType' => 'relation',
                'modelName' => 'Customer',
                'filters' => array(
                    array('field' => 'name', 'operator' => 'equals', 'value' => 'Customer3')
                )
            ))

        ), array());

        $invoice = $invoices['results'][0];
        static::assertGreaterThan(0, count($invoice['relations']));
        unset($invoice['number']);
        unset($invoice['id']);
        $invoice['type'] = 'REVERSAL';
        foreach ($invoice['relations'] as &$rel) {
            $rel['id'] = Tinebase_Record_Abstract::generateUID();
        }

        $createInvoice = $this->_uit->saveInvoice($invoice);

        static::assertSame(count($invoice['relations']), count($createInvoice['relations']));
    }

    /**
     * tests if timeaccounts/timesheets get cleared if the invoice get billed
     */
    public function testClearing()
    {
        if ($this->_dbIsPgsql()) {
            $this->markTestSkipped('0011670: fix Sales_Invoices Tests with postgresql backend');
        }

        $this->_createFullFixtures();
        
        // the whole year, 12 months
        $date = clone $this->_referenceDate;
        $date->addMonth(12);
        $this->_invoiceController->createAutoInvoices($date);
        
        // test if timesheets get cleared
        $invoices = $this->_uit->searchInvoices(array(
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
            $invoice = $this->_uit->getInvoice($invoice['id']);
            $invoice['cleared'] = 'CLEARED';
            $this->_uit->saveInvoice($invoice);
        }
        
        $tsController = Timetracker_Controller_Timesheet::getInstance();
        $timesheets = $tsController->getAll();
        
        foreach($timesheets as $timesheet) {
            $this->assertTrue(in_array($timesheet->invoice_id, $invoiceIds), 'the invoice id must be set!');
            $this->assertEquals(1, $timesheet->is_cleared);
        }
        
        // test if timeaccounts get cleared
        $invoices = $this->_uit->searchInvoices(array(
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
            $invoice = $this->_uit->getInvoice($invoice['id']);
            $invoice['cleared'] = 'CLEARED';
            
            // check set empty number fields to an empty string
            $invoice['sales_tax'] = '';
            $invoice['price_gross'] = '';
            $invoice['price_net'] = '';
            
            $invoice = $this->_uit->saveInvoice($invoice);
            
            $this->assertEquals(0,$invoice['sales_tax']);
            $this->assertEquals(0,$invoice['price_gross']);
            $this->assertEquals(0,$invoice['price_net']);
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
        
        $firstContract = $this->_contractRecords->filter('number', 4)->getFirstRecord();
        
        // TODO: fix test
        if ($firstContract == null) {
            $this->markTestSkipped('TODO');
        }
        
        $contract = $this->_uit->getContract($firstContract->getId());
        
        $this->assertTrue(is_array($contract['products'][0]['product_id']));
        
        $this->_uit->saveContract($contract);
    }
    
    /**
     * tests if invoice id gets removed from the billables if the invoice gets deleted
     */
    public function testRemoveInvoiceFromBillables()
    {
        if ($this->_dbIsPgsql()) {
            $this->markTestSkipped('0011670: fix Sales_Invoices Tests with postgresql backend');
        }

        $this->_createFullFixtures();
        
        $i = 0;
        $date = clone $this->_referenceDate;
        $date->addHour(3);
        $date->addMonth(1);
        
        $result = $this->_invoiceController->createAutoInvoices($date);
        
        $invoices = $this->_uit->searchInvoices(array(), array());
        $this->assertEquals(2, $invoices['totalcount']);
        
        foreach($invoices['results'] as $result) {
            $ids[] = $result['id'];
        }
        
        $this->_uit->deleteInvoices($ids);
        
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
        $tjson = new Timetracker_Frontend_Json();
        
        $ta = $tjson->saveTimeaccount(array('number' => 43379, 'title' => 'bla'));
        
        $c1 = $this->_uit->saveContract(array('number' => '1', 'description' => 'blub bla', 'title' => 'blub'));
        $c2 = $this->_uit->saveContract(array('number' => '2', 'description' => 'bla blub', 'title' => 'bla'));
        
        $c1['relations'] = array(array(
            'related_model' => 'Timetracker_Model_Timeaccount',
            'related_id'    => $ta['id'],
            'related_degree'=> 'sibling',
            'type'          => 'TIME_ACCOUNT',
            'remark'        => 'unittest',
            'related_backend' => 'Sql'
        ));
        
        $c1 = $this->_uit->saveContract($c1);
        $c1Id = $c1['id'];
        
        // delete timeaccount relation from the first contract
        $c1 = $this->_uit->getContract($c1Id);
        $c1['relations'] = array();
        $c1 = $this->_uit->saveContract($c1);
        
        // save second contract having the timeaccount related
        $c2['relations'] = array(array(
            'related_model'   => 'Timetracker_Model_Timeaccount',
            'related_id'      => $ta['id'],
            'related_degree'  => 'sibling',
            'type'            => 'TIME_ACCOUNT',
            'remark'          => 'unittest',
            'related_backend' => 'Sql'
        ));
        
        $c2 = $this->_uit->saveContract($c2);
        
        $this->assertEquals(1, count($c2['relations']));
    }

    /**
     * testDateFilterUntil
     */
    public function testDateFilterUntil()
    {
        $this->_createMinimalInvoice();

        $filter = [
            ['field' => 'date', 'operator' => 'within', 'value' => [
                'from' => "2017-12-01 00:00:00",
                'until' => "2017-12-31 23:59:59"
            ]]
        ];
        $result = $this->_uit->searchInvoices($filter, []);
        self::assertEquals(0, $result['totalcount'], 'should not be found');

        $filter = [
            ['field' => 'date', 'operator' => 'within', 'value' => [
                'from' => "2017-12-01 00:00:00",
                'until' => "2018-01-01 00:00:00"
            ]]
        ];
        $result = $this->_uit->searchInvoices($filter, []);
        self::assertEquals(1, $result['totalcount'], 'should be found');
    }

    /**
     * @return Tinebase_Record_Interface
     */
    protected function _createMinimalInvoice()
    {
        $this->_createCustomers();
        $this->_createCostCenters();

        $customer = $this->_customerRecords->filter('name', 'Customer1')->getFirstRecord();
        $invoiceData = array(
            'number' => 'R-3000',
            'customer_id' => $customer->getId(),
            'description' => 'Manual',
            'address_id' => $this->_addressRecords->filter('customer_id', $customer->getId())->getFirstRecord()->getId(),
            'costcenter_id' => $this->_costcenterRecords->getFirstRecord()->getId(),
            'date' => '2018-01-01',
        );
        $invoice = $this->_invoiceController->create(new Sales_Model_Invoice($invoiceData));

        return $invoice;
    }
}
