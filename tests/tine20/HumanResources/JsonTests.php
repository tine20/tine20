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
     */
    public function testCreateEmployee()
    {
        $e = $this->_getEmployee();
        $e->contracts = array($this->_getContract()->toArray());
        $savedEmployee = $this->_json->saveEmployee($e->toArray());
        $this->assertEquals($e->n_fn, $savedEmployee['n_fn']);
    }
}
