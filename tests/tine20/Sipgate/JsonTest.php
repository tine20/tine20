<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <alex@stintzing.net>
 */

/**
 * Test class for Sipgate_Frontend_Json
 */
class Sipgate_JsonTest extends Sipgate_AbstractTest
{
    /**
     * @var Sipgate_Frontend_Json
     */
    protected $_instance = NULL;
    protected $_controller = NULL;
    protected $_createdId = NULL;
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Sipgate Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_instance = new Sipgate_Frontend_Json();
        parent::setUp();
    }
    
    /**
     * try to add a contract
     *
     */
    public function testAccount()
    {
        try {
            // test duplicate check
            // test save
            $record = $this->_instance->saveAccount($this->_testConfig);
        } catch (Tinebase_Exception_Duplicate $e) {
            // already in system
            $this->assertEquals(629, $e->getCode());
            $record = $e->getData()->getFirstRecord()->toArray(true);
        }

        $this->assertEquals(40, strlen($record['id']));
        // test get
        $record = $this->_instance->getAccount($record['id']);
        $this->assertEquals(40, strlen($record['id']));

        try {
            // test duplicate check again if not in already
            $this->_instance->saveAccount($this->_testConfig);
        } catch (Tinebase_Exception_Duplicate $e) {
            // good
            $this->assertEquals(629, $e->getCode());
        }

    }
}
