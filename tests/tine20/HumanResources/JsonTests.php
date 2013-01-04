<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class HumanResources_JsonTests extends HumanResources_TestCase
{
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_json = new HumanResources_Frontend_Json();
    }
    /**
     * Creates an employee with contracts and contact, account etc.
     * tests auto end_date of old contract
     */
    public function testEmployee()
    {
        $e = $this->_getEmployee();
        $e->contracts = array($this->_getContract()->toArray());
        $savedEmployee = $this->_json->saveEmployee($e->toArray());

        $this->assertEquals($e->n_fn, $savedEmployee['n_fn']);
        $this->assertEquals(1, count($savedEmployee['contracts']));

        $newContract = $this->_getContract();
        $newContract->start_date->addMonth(5);
        $savedEmployee['contracts'][] = $newContract->toArray();
        $savedEmployee = $this->_json->saveEmployee($savedEmployee);
        $this->assertEquals(2, count($savedEmployee['contracts']));

        $this->assertEquals(null, $savedEmployee['contracts'][1]['end_date'], 'The end_date should have a null value.');
        $this->assertEquals('2012-12-12', substr($savedEmployee['costcenters'][0]['start_date'], 0, 10));
        
        $this->assertEquals($savedEmployee['contracts'][0]['workingtime_json'], $savedEmployee['contracts'][0]['workingtime_id']['json'], 'The json definition of the contract should be the same as the corresponding wt json');
        
        $date1 = new Tinebase_DateTime($savedEmployee['contracts'][0]['end_date']);
        $date2 = new Tinebase_DateTime($savedEmployee['contracts'][1]['start_date']);

        $this->assertEquals($date1->addDay(1)->toString(), $date2->toString());

        $freeTimes = $this->_json->getFeastAndFreeDays($savedEmployee['id']);

        $this->assertEquals($savedEmployee['contracts'][0]['id'], $freeTimes['contract']['id']);
    }
    
    /**
     * test working time
     */
    public function testWorkingTimeTemplate()
    {
        $recordData = array('title' => 'lazy worker', 'type' => 'static', 'json' => '{"days":[1,1,1,1,1,0,0]}', 'working_hours' => 5);
        $savedWT = $this->_json->saveWorkingTime($recordData);
        
        $this->assertEquals($savedWT['title'], 'lazy worker');
        
        // test duplicate exception
        $this->setExpectedException('Tinebase_Exception_Duplicate');
        $this->_json->saveWorkingTime($recordData);
    }

    /**
     * Tests the duplicate check
     */
    public function testDuplicateException()
    {
        $e = $this->_getEmployee();
        $e->contracts = array($this->_getContract()->toArray());
        $savedEmployee = $this->_json->saveEmployee($e->toArray());

        $exception = new Tinebase_Exception();

        try {
            $e = $this->_getEmployee();
            $e->contracts = array($this->_getContract()->toArray());
            $savedEmployee = $this->_json->saveEmployee($e->toArray());
        } catch (Tinebase_Exception_Duplicate $exception) {
        }

        $this->assertEquals($exception->getCode(), 629);
    }

    /**
     * Tests if multiple records get resolved properly
     *
     * #6600: generic foreign record resolving method
     * https://forge.tine20.org/mantisbt/view.php?id=6600
     */
    public function testResolveMultiple()
    {
        $e = $this->_getEmployee('rwright');
        $e->contracts = array($this->_getContract()->toArray());
        $savedEmployee = $this->_json->saveEmployee($e->toArray());

        $r = $this->_json->searchContracts(
            array(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $savedEmployee['id'])), 
            array()
        );
        $this->assertEquals($r['results'][0]['employee_id']['id'], $savedEmployee['id']);

        $this->assertTrue(is_array($r['results'][0]['workingtime_id']));
        $this->assertTrue(is_array($r['results'][0]['feast_calendar_id']));

    }
}
