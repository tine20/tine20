<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for HumanResources CLI frontend
 */
class HumanResources_CliTests extends HumanResources_TestCase
{
    /**
     * Backend
     *
     * @var HumanResources_Frontend_Cli
     */
    protected $_cli;
    
    protected $_idsToDelete = array();
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_cli = new HumanResources_Frontend_Cli();
    }
    
    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        HumanResources_Controller_Employee::getInstance()->delete($this->_idsToDelete);
    }
        
    /**
     * test employee import
     * 
     * @group longrunning
     */
    public function testImportEmployee()
    {
        $cc = $this->_getSalesCostCenter(7);
        
        $this->_doImport(true);
        
        $susan = $this->_getSusan();
        
        $this->assertEquals('Street 48', $susan->street, print_r($susan->toArray(), TRUE));
        $this->assertEquals('techniker', $susan->health_insurance, print_r($susan->toArray(), TRUE));
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        $this->assertEquals($sclever->getId(), $susan->account_id, print_r($susan->toArray(), TRUE));
        $this->assertEquals('1973-12-11 23:00:00', $susan->bday->__toString(), print_r($susan->toArray(), TRUE));
        
        $susan->contracts = HumanResources_Controller_Contract::getInstance()->getContractsByEmployeeId($susan->getId());
        $this->assertEquals(1, $susan->contracts->count(), 'no contracts found');
        
        return $susan;
    }
    
    /**
     * import helper
     */
    protected function _doImport($checkOutput = TRUE)
    {
        $opts = new Zend_Console_Getopt(array(
            'verbose|v'             => 'Output messages',
            'dry|d'                 => "Dry run - don't change anything",
        ));
        $filename = dirname(__FILE__) . '/files/employee.csv';
        
        $args = array(
            $filename,
            'feast_calendar_id=' . $this->_getFeastCalendar()->getId(),
            'working_time_model_id=' . $this->_getWorkingTime()->getId(),
            'vacation_days=30',
        );
        $opts->setArguments($args);

        ob_start();
        $result = $this->_cli->importEmployee($opts);
        $out = ob_get_clean();
        $this->assertEquals(0, $result, 'import failed: ' . $out);
        
        if ($checkOutput) {
            $this->assertContains("Imported 2 records.", $out);
        }
    }
    
    /**
     * get susan employee
     * 
     * @return HumanResources_Model_Employee
     */
    protected function _getSusan()
    {
        $employees = HumanResources_Controller_Employee::getInstance()->search(new HumanResources_Model_EmployeeFilter(array()));
        $this->_idsToDelete = $employees->getArrayOfIds();
        
        $this->assertEquals(2, count($employees), 'should import 2 employees: ' . print_r($employees->toArray(), TRUE));
        
        foreach ($employees as $employee) {
            if ($employee->n_fn === 'Hans Employed') {
                $hans = $employee;
            } else if ($employee->n_fn === 'Susan Clever') {
                $susan = $employee;
            }
        }
        
        $this->assertTrue(isset($hans), 'Could not find hans: ' . print_r($employees->toArray(), TRUE));
        $this->assertTrue(isset($susan), 'Could not find susan: ' . print_r($employees->toArray(), TRUE));
        $this->assertEquals(2, $hans->number, print_r($hans->toArray(), TRUE));
        
        return $susan;
    }

    /**
     * test employee import update
     * 
     * @group longrunning
     */
    public function testImportUpdate()
    {
        $this->_doImport();
        
        sleep(1);
        $susan = $this->_getSusan();
        $susan->bank_name = 'xyz';
        $susan->contracts = HumanResources_Controller_Contract::getInstance()->getContractsByEmployeeId($susan->getId());
        HumanResources_Controller_Employee::getInstance()->update($susan);
        
        sleep(1);
        $this->_doImport(FALSE);
        $susan = $this->_getSusan();
        $this->assertEquals('Hypo Real Estate', $susan->bank_name, print_r($susan->toArray(), TRUE));
        
        // cost center check
        $cc = $this->_getSalesCostCenter(7);
        $susan->contracts = HumanResources_Controller_Contract::getInstance()->getContractsByEmployeeId($susan->getId());
        $this->assertEquals(1, $susan->contracts->count(), 'no contracts found');
    }
    
    /**
     * tests set_contracts_end_date
     */
    public function testSetContractsEndDate()
    {
        $this->_doImport(FALSE);
        
        $cc = HumanResources_Controller_Contract::getInstance();
        $ec = HumanResources_Controller_Employee::getInstance();
        
        $user = Tinebase_Core::getUser();

        $begin = Tinebase_DateTime::now()->subYear(1);
        $end   = clone $begin;
        $end->addMonth(11);
        
        $employee = new HumanResources_Model_Employee(array(
            'number' => $this->_lastEmployeeNumber++,
            'account_id' => $user->getId(),
            'n_family' => 'TEST',
            'n_given' => 'UNIT',
            'employment_begin' => $begin, 
            'employment_end' => NULL));
        
        $employee = $ec->create($employee);
        
        $contract = new HumanResources_Model_Contract(array(
            'employee_id' => $employee->getId(),
            'start_date'  => $begin,
            'end_date'    => NULL,
            'vacation_days' => 34,
            'working_time_scheme' => $this->_getWorkingTimeScheme40()->getId(),
        ));
        
        $contract = $cc->create($contract);
        
        $this->assertEquals(NULL, $contract->end_date);
        $this->assertEquals(NULL, $employee->employment_end);
        
        $newEnd = Tinebase_DateTime::now()->subDay(1);
        
        $employee->employment_end = $newEnd;
        $newEnd->hasTime(false);
        
        $employee->contracts = array($contract->toArray());
        
        $ec->update($employee);
        $this->_cli->set_contracts_end_date();
        $allContracts = $cc->getAll();
        
        $updatedContract = $cc->get($contract->getId());
        $this->assertEquals($newEnd, $updatedContract->end_date);
    }
}
