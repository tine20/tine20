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

        $this->assertEquals(null, $savedEmployee['contracts'][1]['end_date']);
        $date1 = new Tinebase_DateTime($savedEmployee['contracts'][0]['end_date']);
        $date2 = new Tinebase_DateTime($savedEmployee['contracts'][1]['start_date']);

        $this->assertEquals($date1->addDay(1)->toString(), $date2->toString());

        $freeTimes = $this->_json->getFeastAndFreeDays($savedEmployee['id']);

        $this->assertEquals($savedEmployee['contracts'][0]['id'], $freeTimes['contract']['id']);
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
}
