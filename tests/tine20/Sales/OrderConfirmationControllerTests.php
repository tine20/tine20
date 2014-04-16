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
 * Test class for Sales OrderConfirmation Controller
 */
class Sales_OrderConfirmationControllerTests extends TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Sales OrderConfirmation Controller Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * checks if the number is always set to the correct value
     */
    public function testNumberable()
    {
        $controller = Sales_Controller_OrderConfirmation::getInstance();
        
        $record = $controller->create(new Sales_Model_OrderConfirmation(array('title' => 'auto1')));
        
        $this->assertEquals('AB-000001', $record->number);
        
        $record = $controller->create(new Sales_Model_OrderConfirmation(array('title' => 'auto2')));
        
        $this->assertEquals('AB-000002', $record->number);
        
        // set number to 4, should return the formatted number
        $record = $controller->create(new Sales_Model_OrderConfirmation(array('title' => 'manu1', 'number' => 4)));
        $this->assertEquals('AB-000004', $record->number);
        
        // the next number should be a number after the manual number
        $record = $controller->create(new Sales_Model_OrderConfirmation(array('title' => 'auto3')));
        $this->assertEquals('AB-000005', $record->number);
    }
}
