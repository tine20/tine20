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
 * Test class for Tinebase_Group
 */
class Sipgate_ControllerTest extends Sipgate_AbstractTest
{
    /**
     * @var Sipgate_Controller_Account
     */
    protected $_instance = NULL;
    protected $_startRecordId = NULL;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Sipgate Controller Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    public function testAccountController()
    {
        $this->_instance = Sipgate_Controller_Account::getInstance();
        $newRecord = $this->_instance->create(new Sipgate_Model_Account($this->_testConfig), false);
        $this->assertEquals($newRecord->password, NULL);
        $record = $this->_instance->get($newRecord->getId());
        $this->assertEquals($record->__get('password'), NULL);
    }
}
