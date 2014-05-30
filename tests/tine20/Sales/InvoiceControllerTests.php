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
        
        $invoice->credit_term = 20;
        $this->setExpectedException('Sales_Exception_InvoiceAlreadyClearedEdit');
        
        $c->update($invoice);
    }
    
    public function testDeleteInvoice()
    {
        $invoiceController = Sales_Controller_Invoice::getInstance();
        $date = clone $this->_referenceDate;
        $i = 0;
        
        // the whole year, 12 months
        while ($i < 12) {
            $result = $invoiceController->createAutoInvoices($date);
            $date->addMonth(1);
            $i++;
        }
        
        $taController = Timetracker_Controller_Timeaccount::getInstance();
        $tsController = Timetracker_Controller_Timesheet::getInstance();
        
        $allInvoices = $invoiceController->getAll();
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
        
        $invoiceController->delete($allInvoices->getId());
        
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
     * make sure timeaccounts won't be billed if they shouln't
     */
    public function testBudgetTimeaccountBilled()
    {
        $invoiceController = Sales_Controller_Invoice::getInstance();
        $date = clone $this->_referenceDate;
        $i = 0;
        
        // do not set to bill, this ta has a budget
        $customer1Timeaccount = $this->_timeaccounts->filter('title', 'TA-for-Customer1')->getFirstRecord();
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
        
        $result = $invoiceController->createAutoInvoices($date);
        
        $this->assertEquals(1, count($result['created']));
        
        $customer1Timeaccount->status = 'to bill';
        $taController->update($customer1Timeaccount);
        
        $result = $invoiceController->createAutoInvoices($date);
        $this->assertEquals(1, count($result['created']));
        
        $invoiceId = $result['created'][0];
        $invoice = $invoiceController->get($invoiceId);
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
        $invoiceController = Sales_Controller_Invoice::getInstance();
        $customer = $this->_customerRecords->filter('name', 'Customer1')->getFirstRecord();
        $invoice = $invoiceController->create(new Sales_Model_Invoice(array(
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
            $invoice = $invoiceController->create(new Sales_Model_Invoice(array(
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
        
        try {
            $invoice = $invoiceController->create(new Sales_Model_Invoice(array(
                'number' => 'R-3001',
                'customer_id' => $customer->getId(),
                'description' => 'Manual Forbidden',
                'address_id' => $this->_addressRecords->filter('customer_id', $customer->getId())->getFirstRecord()->getId(),
                'costcenter_id' => $this->_costcenterRecords->getFirstRecord()->getId()
            )));
        } catch (Exception $e) {
        }
        
        $this->assertTrue(get_class($e) == 'Tinebase_Exception_AccessDenied');
        $this->assertTrue($e->getMessage() == 'You have no right to set the invoice number!');
    }
}
