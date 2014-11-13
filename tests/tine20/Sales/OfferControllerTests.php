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
 * Test class for Sales Offer Controller
 */
class Sales_OfferControllerTests extends TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Sales Offer Controller Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * checks if the number is always set to the correct value
     */
    public function testNumberable()
    {
        $controller = Sales_Controller_Offer::getInstance();
        
        $record = $controller->create(new Sales_Model_Offer(array('title' => 'auto1')));
        
        $this->assertEquals('AN-00001', $record->number);
        
        $record = $controller->create(new Sales_Model_Offer(array('title' => 'auto2')));
        
        $this->assertEquals('AN-00002', $record->number);
        
        // set number to 4, should return the formatted number
        $record = $controller->create(new Sales_Model_Offer(array('title' => 'manu1', 'number' => 4)));
        $this->assertEquals('AN-00004', $record->number);
        
        // the next number should be a number after the manual number
        $record = $controller->create(new Sales_Model_Offer(array('title' => 'auto3')));
        $this->assertEquals('AN-00005', $record->number);
        
        // the user manually set this numer, so this should be corrected
        $record = $controller->create(new Sales_Model_Offer(array('title' => 'manu1', 'number' => 'AN-100')));
        $this->assertEquals('AN-00100', $record->number);
    }
}
