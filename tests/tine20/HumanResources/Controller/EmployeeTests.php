<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test class for HumanResources Controller
 */
class HumanResources_Controller_EmployeeTests extends HumanResources_TestCase
{
    public function testAccountGrants()
    {
        $employeeController = HumanResources_Controller_Employee::getInstance();

        $employee1 = $this->_getEmployee('pwulf');
        $employee1->health_insurance = 'TKK';
        $employee1 = $employeeController->create($employee1);
        $division1 = HumanResources_Controller_Division::getInstance()->get($employee1->division_id);

        $employee2 = $this->_getEmployee('rwright');
        $employeeController->create($employee2);

        $accountController = HumanResources_Controller_Account::getInstance();
        $year = date('Y');

        $result = $accountController->search(new HumanResources_Model_AccountFilter([
            ['field' => 'year', 'operator' => 'equals', 'value' => $year],
            ['field' => 'employee_id', 'operator' => 'equals', 'value' => $employee1->getId()],
        ]));
        $this->assertEquals(1, $result->count());

        $result = $accountController->search(new HumanResources_Model_AccountFilter([
            ['field' => 'year', 'operator' => 'equals', 'value' => $year],
        ]));
        $this->assertEquals(2, $result->count());

        Tinebase_Core::setUser($this->_personas['pwulf']);
        $result = $accountController->search(new HumanResources_Model_AccountFilter([
            ['field' => 'year', 'operator' => 'equals', 'value' => $year],
        ]));
        $this->assertEquals(0, $result->count());

        Tinebase_Container::getInstance()->addGrants($division1->container_id, Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            $this->_personas['pwulf']->getId(), [HumanResources_Model_DivisionGrants::READ_OWN_DATA], true);

        $result = $accountController->search(new HumanResources_Model_AccountFilter([
            ['field' => 'year', 'operator' => 'equals', 'value' => $year],
        ]));
        $this->assertEquals(1, $result->count());
    }

    public function testGrants()
    {
        $employeeController = HumanResources_Controller_Employee::getInstance();

        $employee1 = $this->_getEmployee('pwulf');
        $employee1->health_insurance = 'TKK';
        $employee1 = $employeeController->create($employee1);
        $this->assertEquals('TKK', $employee1->health_insurance);
        $division1 = HumanResources_Controller_Division::getInstance()->get($employee1->division_id);

        $division2 = HumanResources_Controller_Division::getInstance()->create(
            new HumanResources_Model_Division(['title' => 'other division']));
        $employee2 = $this->_getEmployee('rwright');
        $employee2->division_id = $division2->getId();
        $employee2 = $employeeController->create($employee2);

        $filter = new HumanResources_Model_EmployeeFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => $employee1->n_given],
        ]);
        $result = $employeeController->search($filter);
        $this->assertEquals(1, $result->count());
        $this->assertEquals($employee1->n_given, $result->getFirstRecord()->n_given);
        $this->assertEquals($employee1->health_insurance, $result->getFirstRecord()->health_insurance);

        $filter = new HumanResources_Model_EmployeeFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => $employee2->n_given],
        ]);
        $result = $employeeController->search($filter);
        $this->assertEquals(1, $result->count());
        $this->assertEquals($employee2->n_given, $result->getFirstRecord()->n_given);

        Tinebase_Core::setUser($this->_personas['pwulf']);
        $filter = new HumanResources_Model_EmployeeFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => $employee1->n_given],
        ]);
        $result = $employeeController->search($filter);
        $this->assertEquals(0, $result->count());

        $filter = new HumanResources_Model_EmployeeFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => $employee2->n_given],
        ]);
        $result = $employeeController->search($filter);
        $this->assertEquals(0, $result->count());

        Tinebase_Container::getInstance()->addGrants($division1->container_id, Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            $this->_personas['rwright']->getId(), [HumanResources_Model_DivisionGrants::READ_BASIC_EMPLOYEE_DATA], true);

        Tinebase_Core::setUser($this->_personas['rwright']);

        $filter = new HumanResources_Model_EmployeeFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => $employee1->n_given],
        ]);
        $result = $employeeController->search($filter);
        $this->assertEquals(1, $result->count());
        $this->assertNull($result->getFirstRecord()->health_insurance);

        Tinebase_Container::getInstance()->addGrants($division1->container_id, Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            $this->_personas['pwulf']->getId(), [HumanResources_Model_DivisionGrants::READ_OWN_DATA], true);

        Tinebase_Core::setUser($this->_personas['pwulf']);
        $filter = new HumanResources_Model_EmployeeFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => $employee1->n_given],
        ]);
        $result = $employeeController->search($filter);
        $this->assertEquals(1, $result->count());
        $this->assertSame($employee1->health_insurance, $result->getFirstRecord()->health_insurance);

        $filter = new HumanResources_Model_EmployeeFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => $employee2->n_given],
        ]);
        $result = $employeeController->search($filter);
        $this->assertEquals(0, $result->count());

        $this->expectException(Tinebase_Exception_AccessDenied::class);
        $employeeController->update($employee1);
    }

    /**
     * tests if the filter for the employee model gets created properly
     */
    public function testFilters()
    {
        // prepare dates
        $today = new Tinebase_DateTime();
        $oneMonthAgo = clone $today;
        $oneMonthAgo->subMonth(1);
        $oneMonthAhead = clone $today;
        $oneMonthAhead->addMonth(1);
        $twoMonthsAgo = clone $oneMonthAgo;
        $twoMonthsAgo->subMonth(1);

        $employeeController = HumanResources_Controller_Employee::getInstance();

        $employee1 = $this->_getEmployee('pwulf');
        $employee1->employment_begin = $oneMonthAgo;
        $employee1->employment_end = $oneMonthAhead;
        $employee1 = $employeeController->create($employee1);

        $employee2 = $this->_getEmployee('rwright');
        $employee2->employment_begin = $oneMonthAgo;
        $employee2 = $employeeController->create($employee2);

        $filter = new HumanResources_Model_EmployeeFilter(array(
            array('field' => 'n_given', 'operator' => 'equals', 'value' => 'Paul'),
            ['field' => 'last_modified_time', 'operator' => 'after', 'value' => ''] // this line should be ignored by the filter
        ));
        $result = $employeeController->search($filter);

        $this->assertEquals(1, $result->count());
        $this->assertEquals('Paul', $result->getFirstRecord()->n_given);

        // test employed filter

        // employee3 is not yet employed
        $employee3 = $this->_getEmployee('jmcblack');
        $employee3->employment_begin = $oneMonthAhead;
        $employee3 = $employeeController->create($employee3);

        $filter = new HumanResources_Model_EmployeeFilter(array(
            array('field' => 'employment_end', 'operator' => 'after', 'value' => $oneMonthAhead)
        ));
        $result = $employeeController->search($filter);
        $msg = 'jmcblack and rwright should have been found';
        $this->assertEquals(2, $result->count(), $msg);
        $names = $result->n_fn;
        // just jmcblack and rwright should have been found
        $this->assertContains('Roberta Wright', $names, $msg);
        $this->assertContains('James McBlack', $names, $msg);

        
        // employee4 has been employed
        $employee4 = $this->_getEmployee('jsmith');
        $employee4->employment_begin = $twoMonthsAgo;
        $employee4->employment_end = $oneMonthAgo;
        $employee4 = $employeeController->create($employee4);

        $this->assertEquals('Photographer', $employee4->position);
        
        $filter = new HumanResources_Model_EmployeeFilter(array(
            array('field' => 'is_employed', 'operator' => 'equals', 'value' => TRUE)
        ));
        $result = $employeeController->search($filter, new Tinebase_Model_Pagination([
            'sort' => 'account_id',
            'dir' => 'ASC',
            'model' => HumanResources_Model_Employee::class,
        ]));
        $msg = 'rwright and pwulf should have been found';
        $this->assertEquals(2, $result->count(), $msg);
        $this->assertSame('Roberta Wright', $result->getFirstRecord()->n_fn);
        $this->assertSame('Paul Wulf', $result->getLastRecord()->n_fn);


        $filter = new HumanResources_Model_EmployeeFilter(array(
            array('field' => 'is_employed', 'operator' => 'equals', 'value' => FALSE)
        ));
        $result = $employeeController->search($filter, new Tinebase_Model_Pagination([
            'sort' => 'account_id',
            'dir' => 'DESC',
            'model' => HumanResources_Model_Employee::class,
        ]));

        $msg = 'jsmith and jmcblack should have been found';
        $this->assertEquals(2, $result->count(), $msg);
        $this->assertSame('John Smith', $result->getFirstRecord()->n_fn);
        $this->assertSame('James McBlack', $result->getLastRecord()->n_fn);
    }
}
