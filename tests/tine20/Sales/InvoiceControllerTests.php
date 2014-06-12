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
    protected $_testUser = NULL;
    
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
        // switch back to admin user
        if ($this->_testUser) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_testUser);
        }
        
        parent::tearDown();
        
    }
    
    protected function _createFailingContracts()
    {
        // add contract not to bill
        $this->_contractRecords->addRecord($this->_contractController->create(new Sales_Model_Contract(array(
            'number'       => 4,
            'title'        => Tinebase_Record_Abstract::generateUID(),
            'description'  => '4 unittest no auto',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_point' => 'end',
            'billing_address_id' => $this->_addressRecords->filter('customer_id', $this->_customerRecords->filter('name', 'Customer3')->getFirstRecord()->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
            'interval' => 0,
            'start_date' => $this->_referenceDate,
            'end_date' => NULL,
        ))));
        
        // add contract without customer
        $contract = new Sales_Model_Contract(array(
            'number'       => 5,
            'title'        => Tinebase_Record_Abstract::generateUID(),
            'description'  => '5 unittest auto not possible',
            'container_id' => $this->_sharedContractsContainerId,
            'interval' => 1,
            'start_date' => $this->_referenceDate,
            'end_date' => NULL,
            'billing_address_id' => $this->_addressRecords->filter('customer_id', $this->_customerRecords->filter('name', 'Customer3')->getFirstRecord()->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
        ));
        
        $contract->relations = array(
            array(
                'own_model'              => 'Sales_Model_Contract',
                'own_backend'            => Tasks_Backend_Factory::SQL,
                'own_id'                 => NULL,
                'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Sales_Model_CostCenter',
                'related_backend'        => Tasks_Backend_Factory::SQL,
                'related_id'             => $this->_costcenterRecords->getFirstRecord()->getId(),
                'type'                   => 'LEAD_COST_CENTER'
            ),
        );
        
        $this->_contractRecords->addRecord($this->_contractController->create($contract));
        
        // add contract without address
        $contract = new Sales_Model_Contract(array(
            'number'       => 5,
            'title'        => Tinebase_Record_Abstract::generateUID(),
            'description'  => '6 unittest auto not possible',
            'container_id' => $this->_sharedContractsContainerId,
            'interval' => 1,
            'start_date' => $this->_referenceDate,
            'end_date' => NULL,
        ));
        
        $this->_contractRecords->addRecord($this->_contractController->create($contract));
    }
    
    /**
     * tests auto invoice creation
     */
    public function testAutoInvoice()
    {
        $this->_createFullFixtures();
        $this->_createFailingContracts();
        
        $this->assertEquals(7, $this->_contractRecords->count());
        
        $date = clone $this->_referenceDate;
        
        $i = 0;
        
        // the whole year, 12 months
        while ($i < 12) {
            $result = $this->_invoiceController->createAutoInvoices($date);
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
        $this->_invoiceController->createAutoInvoices($date);
        $date->addHour(1);
        $this->_invoiceController->createAutoInvoices($date);
        
        $all = $this->_invoiceController->getAll();
        
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
        
        // contract 1 gets billed at the begin of the period
        $c1IArray = $customer1Invoices->start_date;
        
        $this->assertEquals($this->_referenceYear . '-02-01 00:00:00', $c1IArray[0]->toString());
        
        $c1IArray = $customer1Invoices->end_date;
        $this->assertEquals($this->_referenceYear . '-02-' . ($this->_isLeapYear ? '29' : '28') . ' 23:59:59', $c1IArray[0]->toString());
        
        // contract 2 gets billed at the end of the period, and the second period ends at 1.8.20xx
        $c2IsArray = $customer2Invoices->start_date;
        $c2IeArray = $customer2Invoices->end_date;
        
        $this->assertEquals($this->_referenceYear . '-06-01 00:00:00', $c2IsArray[0]->toString());
        $this->assertEquals($this->_referenceYear . '-06-30 23:59:59', $c2IeArray[0]->toString());
        
        // test correct timesheet handling of customer 3
        $c3IsArray = $customer3Invoices->start_date;
        $c3IeArray = $customer3Invoices->end_date;
        
        $this->assertEquals($this->_referenceYear . '-05-01 00:00:00', $c3IsArray[0]->toString());
        $this->assertEquals($this->_referenceYear . '-05-31 23:59:59', $c3IeArray[0]->toString());
        
        $this->assertEquals($this->_referenceYear . '-09-01 00:00:00', $c3IsArray[1]->toString());
        $this->assertEquals($this->_referenceYear . '-09-30 23:59:59', $c3IeArray[1]->toString());
        
        // test customer 4 having products only
        $c4IsArray = $customer4Invoices->start_date;
        $c4IeArray = $customer4Invoices->end_date;
        
        // should contain billeachquarter & billhalfyearly
        $this->assertEquals($this->_referenceYear . '-01-01 00:00:00', $c4IsArray[0]->toString());
        $this->assertEquals($this->_referenceYear . '-06-30 23:59:59', $c4IeArray[0]->toString());
        
        // should contain billeachquarter
        $this->assertEquals($this->_referenceYear . '-04-01 00:00:00', $c4IsArray[1]->toString());
        $this->assertEquals($this->_referenceYear . '-06-30 23:59:59', $c4IeArray[1]->toString());
        
        // should contain billeachquarter & billhalfyearly
        $this->assertEquals($this->_referenceYear . '-07-01 00:00:00', $c4IsArray[2]->toString());
        $this->assertEquals($this->_referenceYear . '-12-31 23:59:59', $c4IeArray[2]->toString());
        
        // should contain billeachquarter
        $this->assertEquals($this->_referenceYear . '-10-01 00:00:00', $c4IsArray[3]->toString());
        $this->assertEquals($this->_referenceYear . '-12-31 23:59:59', $c4IeArray[3]->toString());
        
        // should contain billeachquarter & billhalfyearly
        $this->assertEquals($this->_referenceYear + 1 . '-01-01 00:00:00', $c4IsArray[4]->toString());
        $this->assertEquals($this->_referenceYear + 1 . '-06-30 23:59:59', $c4IeArray[4]->toString());
        
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
        $invoice = $this->_invoiceController->update($invoice);
        
        // check correct number generation
        $this->assertEquals("R-000001", $invoice->number);
        
        $invoice = $customer2Invoices->getFirstRecord();
        $invoice->relations = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Invoice', 'Sql', $invoice->getId())->toArray();
        $filter = new Sales_Model_InvoicePositionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice['id'])));
        $invoice->positions = Sales_Controller_InvoicePosition::getInstance()->search($filter);
        
        $invoice->cleared = 'CLEARED';
        $invoice = $this->_invoiceController->update($invoice);
        
        $this->assertEquals("R-000002", $invoice->number);
        
        // check disallow editing invoice after clearing
        $invoice->credit_term = 20;
        $this->setExpectedException('Sales_Exception_InvoiceAlreadyClearedEdit');
        
        $this->_invoiceController->update($invoice);
    }
    
    public function testDeleteInvoice()
    {
        $this->_createFullFixtures();
        
        $date = clone $this->_referenceDate;
        $i = 0;
        
        // the whole year, 12 months
        while ($i < 12) {
            $result = $this->_invoiceController->createAutoInvoices($date);
            $date->addMonth(1);
            $i++;
        }
        
        $paController = Sales_Controller_ProductAggregate::getInstance();
        $productAggregates = $paController->getAll();
        $contracts = $this->_contractController->getAll();
        $contracts->sort('id', 'DESC');
        
        $c1 = $contracts->getFirstRecord();
        
        $this->assertEquals(2, $productAggregates->count());
        
        $taController = Timetracker_Controller_Timeaccount::getInstance();
        $tsController = Timetracker_Controller_Timesheet::getInstance();
        
        $allInvoices = $this->_invoiceController->getAll();
        $allTimesheets = $tsController->getAll();
        $allTimeaccounts = $taController->getAll();
        
        foreach($allTimesheets as $ts) {
            $this->assertTrue($ts->invoice_id != NULL);
        }
        
        foreach($allTimeaccounts as $ta) {
            if (intval($ta->budget) < 0) {
                $this->assertTrue($ta->invoice_id != NULL);
            }
        }
        
        $allInvoices->sort('start_date', 'DESC');
        
        foreach($allInvoices as $invoice) {
            $this->_invoiceController->delete(array($invoice->getId()));
        }
        
        $allTimesheets = $tsController->getAll();
        $allTimeaccounts = $taController->getAll();
        
        foreach($allTimeaccounts as $ta) {
            $this->assertTrue($ta->invoice_id == NULL);
        }
        
        foreach($allTimesheets as $ts) {
            $this->assertTrue($ts->invoice_id == NULL);
        }
    }
    
    /**
     * @see: rt127444
     * 
     * make sure timeaccounts won't be billed if they shouldn't
     */
    public function testBudgetTimeaccountBilled()
    {
        $this->_createFullFixtures();
        
        $date = clone $this->_referenceDate;
        $i = 0;
        
        // do not set to bill, this ta has a budget
        $customer1Timeaccount = $this->_timeaccountRecords->filter('title', 'TA-for-Customer1')->getFirstRecord();
        $customer1Timeaccount->status = 'not yet billed';
        
        $tsController = Timetracker_Controller_Timesheet::getInstance();
        $taController = Timetracker_Controller_Timeaccount::getInstance();
        $taController->update($customer1Timeaccount);
        
        // this is a ts on 20xx-01-17
        $timesheet = new Timetracker_Model_Timesheet(array(
            'account_id' => Tinebase_Core::getUser()->getId(),
            'timeaccount_id' => $customer1Timeaccount->getId(),
            'start_date' => $date->addDay(17),
            'duration' => 120,
            'description' => 'ts from ' . (string) $date,
        ));
        
        $tsController->create($timesheet);
        
        // this is a ts on 20xx-02-03
        $timesheet->id = NULL;
        $timesheet->start_date  = $date->addDay(17);
        $timesheet->description = 'ts from ' . (string) $date;
        
        $tsController->create($timesheet);
        
        $date = clone $this->_referenceDate;
        $date->addMonth(1);
        
        $result = $this->_invoiceController->createAutoInvoices($date);
        
        $this->assertEquals(1, count($result['created']));
        
        $customer1Timeaccount->status = 'to bill';
        $taController->update($customer1Timeaccount);
        
        $date->addSecond(1);
        
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(1, count($result['created']));
        
        $invoiceId = $result['created'][0];
        $invoice = $this->_invoiceController->get($invoiceId);
        $found = FALSE;
        
        foreach($invoice->relations as $relation) {
            if ($relation->related_model == 'Timetracker_Model_Timeaccount') {
                $this->assertEquals('TA-for-Customer1',     $relation->related_record->title);
                $found = TRUE;
            }
        }

        $this->assertTrue($found, 'the timeaccount could not be found in the invoice!');
    }
    

    /**
     * tests if the rights work: Sales_Acl_Rights::SET_INVOICE_NUMBER, Sales_Acl_Rights::MANAGE_INVOICES
     */
    public function testSetManualNumberRight()
    {
        $this->_createCustomers();
        $this->_createCostCenters();
        
        $customer = $this->_customerRecords->filter('name', 'Customer1')->getFirstRecord();
        $invoice = $this->_invoiceController->create(new Sales_Model_Invoice(array(
            'number' => 'R-3000',
            'customer_id' => $customer->getId(),
            'description' => 'Manual',
            'address_id' => $this->_addressRecords->filter('customer_id', $customer->getId())->getFirstRecord()->getId(),
            'costcenter_id' => $this->_costcenterRecords->getFirstRecord()->getId()
        )));
        
        // fetch user group
        $group   = Tinebase_Group::getInstance()->getGroupByName('Users');
        $groupId = $group->getId();
        
        // create new user
        $user = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'testuser',
            'accountPrimaryGroup'   => $groupId,
            'accountDisplayName'    => 'Test User',
            'accountLastName'       => 'User',
            'accountFirstName'      => 'Test',
            'accountFullName'       => 'Test User',
            'accountEmailAddress'   => 'unittestx8@tine20.org',
        ));
        
        $user = Admin_Controller_User::getInstance()->create($user, 'pw', 'pw');
        $this->_testUser = Tinebase_Core::getUser();

        Tinebase_Core::set(Tinebase_Core::USER, $user);
        
        $e = new Exception('No Message');
        
        try {
            $invoice = $this->_invoiceController->create(new Sales_Model_Invoice(array(
                'number' => 'R-3001',
                'customer_id' => $customer->getId(),
                'description' => 'Manual Forbidden',
                'address_id' => $this->_addressRecords->filter('customer_id', $customer->getId())->getFirstRecord()->getId(),
                'costcenter_id' => $this->_costcenterRecords->getFirstRecord()->getId()
            )));
        } catch (Exception $e) {
        }
        
        $this->assertTrue(get_class($e) == 'Tinebase_Exception_AccessDenied');
        $this->assertTrue($e->getMessage() == 'You don\'t have the right to manage invoices!');
        
        Tinebase_Core::set(Tinebase_Core::USER, $this->_testUser);
        
        $fe = new Admin_Frontend_Json();
        $userRoles = $fe->getRoles('user', array(), array(), 0, 1);
        $userRole = $fe->getRole($userRoles['results'][0]['id']);
        
        $roleRights = $fe->getRoleRights($userRole['id']);
        $roleMembers = $fe->getRoleMembers($userRole['id']);
        $roleMembers['results'][] = array('name' => 'testuser', 'type' => 'user', 'id' => $user->accountId);
        
        $app = Tinebase_Application::getInstance()->getApplicationByName('Sales');
        
        $roleRights['results'][] = array('application_id' => $app->getId(), 'right' => Sales_Acl_Rights::MANAGE_INVOICES);
        $fe->saveRole($userRole, $roleMembers['results'], $roleRights['results']);
        
        Tinebase_Core::set(Tinebase_Core::USER, $user);
        
        $e = new Exception('No Message');
        
        try {
            $invoice = $this->_invoiceController->create(new Sales_Model_Invoice(array(
                'number' => 'R-3001',
                'customer_id' => $customer->getId(),
                'description' => 'Manual Forbidden',
                'address_id' => $this->_addressRecords->filter('customer_id', $customer->getId())->getFirstRecord()->getId(),
                'costcenter_id' => $this->_costcenterRecords->getFirstRecord()->getId()
            )));
        } catch (Exception $e) {
        }
        
        $this->assertEquals('Tinebase_Exception_AccessDenied', get_class($e));
        $this->assertEquals('You have no right to set the invoice number!', $e->getMessage());
    }
    
    /**
     * tests if a product aggregate gets billed in the correct periods
     */
    public function testOneProductContractInterval()
    {
        $startDate = clone $this->_referenceDate;
        
        $this->_createProducts();
        
        $this->_createCustomers(1);
        $this->_createCostCenters();
        
        $monthBack = clone $this->_referenceDate;
        $monthBack->subMonth(1);
        
        // this contract begins 6 months before the first invoice will be created
        $this->_createContracts(array(array(
            'number'       => 100,
            'title'        => 'MyContract',
            'description'  => 'unittest',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_point' => 'begin',
            'billing_address_id' => $this->_addressRecords->filter(
                'customer_id', $this->_customerRecords->filter(
                    'name', 'Customer1')->getFirstRecord()->getId())->filter(
                        'type', 'billing')->getFirstRecord()->getId(),
            
            'interval' => 1,
            'start_date' => $startDate->subMonth(6),
            'last_autobill' => clone $this->_referenceDate,
            'end_date' => NULL,
            'products' => array(
                array('product_id' => $this->_productRecords->getByIndex(0)->getId(), 'quantity' => 1, 'interval' => 1, 'last_autobill' => $monthBack),
            )
        )));
        
        $startDate = clone $this->_referenceDate;
        // the whole year, 12 months
        $i=0;
        
        $startDate->addDay(5);
        
        while ($i < 24) {
            $startDate->addMonth(1);
            $result = $this->_invoiceController->createAutoInvoices($startDate);
            $this->assertEquals(1, $result['created_count']);
            $i++;
        }
        
        $invoices = $this->_invoiceController->getAll('start_date');
        $this->assertEquals('0101', $invoices->getFirstRecord()->start_date->format('md'));
        
        $this->assertEquals(24, $invoices->count());
        $invoicePositions = Sales_Controller_InvoicePosition::getInstance()->getAll('month');
        
        // get sure each invoice positions has the same month as the invoice and the start_date is the first
        foreach($invoices as $invoice) {
            $month = (int) $invoice->start_date->format('n');
            $index = $month - 1;
            
            $this->assertEquals('01', $invoice->start_date->format('d'));
            $this->assertEquals($this->_lastMonthDays[$index], $invoice->end_date->format('d'), print_r($invoice->toArray(), 1));
            
            $this->assertEquals(1, $invoice->start_date->format('d'));
            $pos = $invoicePositions->filter('invoice_id', $invoice->getId())->getFirstRecord();
            $this->assertEquals($invoice->start_date->format('Y-m'), $pos->month);
            $this->assertEquals($invoice->end_date->format('Y-m'), $pos->month);
        }
        
        $this->assertEquals(24, $invoicePositions->count());
        
        $this->assertEquals($this->_referenceYear . '-01', $invoicePositions->getFirstRecord()->month);
        
        $invoicePositions->sort('month', 'DESC');
        
        $this->assertEquals($this->_referenceYear + 1 . '-12', $invoicePositions->getFirstRecord()->month);
    }
    
    /**
     * test product only contract setting last_autobill and resetting last_autobill on delete
     */
    public function testLastAutobillAfterDeleteInvoice()
    {
        $startDate = clone $this->_referenceDate;
        $lab = clone $this->_referenceDate;
        $lab->subMonth(1);
        $this->_createProducts();
        
        $this->_createCustomers(1);
        $this->_createCostCenters();
        
        // has budget, is to bill
        $ta = $this->_createTimeaccounts()->getFirstRecord();
        
        // has timeaccounts and products
        $this->_createContracts(array(array(
            'number'       => 100,
            'title'        => 'MyContract',
            'description'  => 'unittest',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_point' => 'begin',
            'billing_address_id' => $this->_addressRecords->filter(
                'customer_id', $this->_customerRecords->filter(
                    'name', 'Customer1')->getFirstRecord()->getId())->filter(
                        'type', 'billing')->getFirstRecord()->getId(),
        
            'interval' => 1,
            'start_date' => $startDate,
            'last_autobill' => NULL,
            'end_date' => NULL,
            'products' => array(
                array('product_id' => $this->_productRecords->getByIndex(0)->getId(), 
                    'quantity' => 1, 'interval' => 1, 'last_autobill' => $lab),
            )
        )));
        
        $startDate = clone $this->_referenceDate;
        $i=0;
        
        $myDate = clone $startDate;
        $myDate->addHour(3);
        
        $result = $this->_invoiceController->createAutoInvoices($myDate);
        $this->assertEquals(1, $result['created_count']);
        $contract = $this->_contractController->getAll()->getFirstRecord();
        $contract->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));

        // there has been some volatile effort, so do set last_autobill
        $this->assertEquals($startDate, $contract->start_date);
        $this->assertEquals($startDate, $contract->last_autobill);
        
        $paController = Sales_Controller_ProductAggregate::getInstance();
        $productAggregate = $paController->getAll()->getFirstRecord();
        $productAggregate->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $this->assertEquals($startDate, $productAggregate->last_autobill);
        
        $startDate->addMonth(1);
        $myDate = clone $startDate;
        $myDate->addHour(3);
        $result = $this->_invoiceController->createAutoInvoices($myDate);
        $this->assertEquals(1, $result['created_count']);
        
        $startDate->addMonth(1);
        $myDate = clone $startDate;
        $myDate->addHour(3);
        $result = $this->_invoiceController->createAutoInvoices($myDate);
        
        $this->assertEquals(1, $result['created_count']);
        
        $productAggregate = $paController->getAll()->getFirstRecord();
        $productAggregate->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $this->assertEquals($startDate, $productAggregate->last_autobill);
        
        // the last invoice gets deleted
        $startDate->subMonth(1);
        
        $lastInvoice = $this->_invoiceController->get($result['created'][0]);
        $allInvoices = $this->_invoiceController->getAll('start_date');
        $this->_invoiceController->delete($lastInvoice->getId());
        
        $productAggregate = $paController->getAll()->getFirstRecord();
        $productAggregate->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $this->assertEquals($startDate, $productAggregate->last_autobill);
        
        $contract = $this->_contractController->getAll()->getFirstRecord();
        $contract->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        
        $this->assertEquals($startDate, $contract->last_autobill);
    }
    
    /**
     * @see step2 FS0012
     * 
     * products must be billed on the beginning of a period
     * the first dates must fit
     */
    public function testProductWithOneYearInterval()
    {
        // last year, the 1.1 in usertimezone
        $date      = clone $this->_referenceDate;
        
        $startDateContract = clone $this->_referenceDate;
        $startDateContract->subMonth(13);
        
        // this has been billed for the last year.
        $startDateProduct = clone $this->_referenceDate;
        $startDateProduct->subMonth(12);
        
        $this->_createProducts();
        
        $this->_createCustomers(1);
        $this->_createCostCenters();
        
        // the contract has an interval of 0, but it has to be billed
        $this->_createContracts(array(array(
            'number'       => 100,
            'title'        => 'MyContract',
            'description'  => 'unittest',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_point' => 'begin',
            'billing_address_id' => $this->_addressRecords->filter(
                'customer_id', $this->_customerRecords->filter(
                    'name', 'Customer1')->getFirstRecord()->getId())->filter(
                        'type', 'billing')->getFirstRecord()->getId(),
        
            'interval' => 0,
            'start_date' => $startDateContract,
            'last_autobill' => clone $this->_referenceDate,
            'end_date' => NULL,
            'products' => array(
                array('product_id' => $this->_productRecords->getByIndex(0)->getId(),
                    'quantity' => 1, 'interval' => 12, 'last_autobill' => $startDateProduct),
            )
        )));
        
        $startDate = clone $this->_referenceDate;
        $startDate->addHour(3);
        
        $i=0;
        
        $result = $this->_invoiceController->createAutoInvoices($startDate);
        $this->assertEquals(1, $result['created_count']);
        
        $invoice = $this->_invoiceController->get($result['created'][0]);
        
        $invoicePositions = Sales_Controller_InvoicePosition::getInstance()->getAll('month');
        $this->assertEquals(12, $invoicePositions->count());
        
        $i = 1;
        
        foreach($invoicePositions as $ipo) {
            $this->assertEquals($this->_referenceYear . '-' . ($i > 9 ? '' : '0') . $i, $ipo->month, print_r($invoicePositions->toArray(), 1));
            $this->assertEquals($ipo->invoice_id, $invoice->getId());
            $i++;
        }
    }
    
    /**
     * make sure that timesheets get created for the right month
     */
    public function testTimesheetOnMonthEndAndBegin()
    {
        $dates = array(
            clone $this->_referenceDate,
            clone $this->_referenceDate,
            clone $this->_referenceDate,
            clone $this->_referenceDate
        );
        // 0: 1.1.xxxx, 1: 31.1.xxxx, 2: 1.2.xxxx, 3: 28/29.2.xxxx
        $dates[1]->addMonth(1)->subDay(1);
        $dates[2]->addMonth(1);
        $dates[3]->addMonth(2)->subDay(1);
        
        $customer = $this->_createCustomers(1)->getFirstRecord();
        $this->_createCostCenters();
        
        // has no budget
        $ta = $this->_createTimeaccounts(array(array(
            'title'         => 'TaTest',
            'description'   => 'blabla',
            'is_open'       => 1,
            'status'        => 'not yet billed',
            'budget'        => null
        )))->getFirstRecord();
        
        foreach($dates as $date) {
            $this->_createTimesheets(array(
                array(
                    'account_id' => Tinebase_Core::getUser()->getId(),
                    'timeaccount_id' => $ta->getId(),
                    'start_date' => $date,
                    'duration' => 105,
                    'description' => 'ts from ' . (string) $date,
                ))
            );
        }
        
        $this->assertEquals(4, $this->_timesheetRecords->count());
        
        $csDate = clone $this->_referenceDate;
        $csDate->subMonth(10);
        
        $lab = clone $this->_referenceDate;
        // set start position (must be manually set on introducing the invoice module)
        $lab->subMonth(1);
        
        $this->_createContracts(array(array(
            'number'       => 100,
            'title'        => 'MyContract',
            'description'  => 'unittest',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_point' => 'begin',
            'billing_address_id' => $this->_addressRecords->filter(
                'customer_id', $customer->getId())->filter(
                        'type', 'billing')->getFirstRecord()->getId(),
        
            'interval' => 1,
            'start_date' => $csDate,
            'last_autobill' => $lab,
            'billing_point' => 'end',
            'end_date' => NULL,
            'products' => NULL
        )));
        
        $json = new Sales_Frontend_Json();

        $date = clone $this->_referenceDate;
        // this is set by cli if called by cli
        $date->setTime(3,0,0);
        
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(0, $result['created_count'], (string) $date);
        
        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(1, $result['created_count'], (string) $date);
        $invoice1Id = $result['created'][0];
        $invoice = $json->getInvoice($invoice1Id);
        $this->assertEquals(1, count($invoice['positions']), print_r($invoice['positions'], 1));
        
        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(1, $result['created_count'], (string) $date);
        $invoice2Id = $result['created'][0];
        $invoice = $json->getInvoice($invoice2Id);
        $this->assertEquals(1, count($invoice['positions']));

        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(0, $result['created_count'], (string) $date);
        
        $filter = new Timetracker_Model_TimesheetFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice1Id)));
        $timesheets = $this->_timesheetController->search($filter);
        $this->assertEquals(2, $timesheets->count());
        
        $filter = new Timetracker_Model_TimesheetFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice2Id)));
        $timesheets = $this->_timesheetController->search($filter);
        $this->assertEquals(2, $timesheets->count());
    }
    
    /**
     * make sure that timesheets get created for the right month
     */
    public function test2MonthIntervalTimesheetOnMonthEndAndBegin()
    {
        $dates = array(
            clone $this->_referenceDate,
            clone $this->_referenceDate,
            clone $this->_referenceDate,
            clone $this->_referenceDate
        );
        // 0: 1.1.xxxx, 1: 31.1.xxxx, 2: 1.2.xxxx, 3: 28/29.2.xxxx
        $dates[1]->addMonth(1)->subDay(1);
        $dates[2]->addMonth(1);
        $dates[3]->addMonth(2)->subDay(1);
    
        $customer = $this->_createCustomers(1)->getFirstRecord();
        $this->_createCostCenters();
    
        // has no budget
        $ta = $this->_createTimeaccounts(array(array(
            'title'         => 'TaTest',
            'description'   => 'blabla',
            'is_open'       => 1,
            'status'        => 'not yet billed',
            'budget'        => null
        )))->getFirstRecord();
    
        foreach($dates as $date) {
            $this->_createTimesheets(array(
                array(
                    'account_id' => Tinebase_Core::getUser()->getId(),
                    'timeaccount_id' => $ta->getId(),
                    'start_date' => $date,
                    'duration' => 105,
                    'description' => 'ts from ' . (string) $date,
                ))
            );
        }
    
        $this->assertEquals(4, $this->_timesheetRecords->count());
    
        $csDate = clone $this->_referenceDate;
        $csDate->subMonth(10);
    
        $lab = clone $this->_referenceDate;
        $this->_createContracts(array(array(
            'number'       => 100,
            'title'        => 'MyContract',
            'description'  => 'unittest',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_point' => 'begin',
            'billing_address_id' => $this->_addressRecords->filter(
                'customer_id', $customer->getId())->filter(
                    'type', 'billing')->getFirstRecord()->getId(),
    
            'interval' => 2,
            'start_date' => $csDate,
            'last_autobill' => $lab,
            'billing_point' => 'end',
            'end_date' => NULL,
            'products' => NULL
        )));
    
        $json = new Sales_Frontend_Json();
    
        $date = clone $this->_referenceDate;
        // this is set by cli if called by cli
        $date->setTime(3,0,0);
    
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(0, $result['created_count'], (string) $date);
    
        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(0, $result['created_count'], (string) $date);
        
        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(1, $result['created_count'], (string) $date);
        $invoice2Id = $result['created'][0];
        $invoice = $json->getInvoice($invoice2Id);
        $this->assertEquals(2, count($invoice['positions']));
    
        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(0, $result['created_count']);
    
        $filter = new Timetracker_Model_TimesheetFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice2Id)));
        $timesheets = $this->_timesheetController->search($filter);
        $this->assertEquals(4, $timesheets->count());
    }
}
