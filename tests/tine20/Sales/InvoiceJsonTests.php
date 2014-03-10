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
class Sales_InvoiceJsonTests extends Sales_InvoiceTestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Sales Invoice Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * checks if all properties are resolved and saved properly
     */
    public function testCRUD()
    {
        $json = new Sales_Frontend_Json();
        $customer = $json->getCustomer($this->_customerRecords->filter('name', 'Customer3')->getFirstRecord()->getId());
        
        $this->assertTrue(is_array($customer['postal_id']));
        $this->assertEquals($customer['adr_id'], $customer['postal_id']['id']);
        
        $this->assertTrue(is_array($customer['billing']));
        $this->assertEquals(1, count($customer['billing']));
        
        $this->assertTrue(is_array($customer['delivery']));
        $this->assertEquals(1, count($customer['delivery']));
        
        $this->assertTrue(is_array($customer['relations']));
        $this->assertTrue(is_array($customer['relations'][0]['related_record']));
        $this->assertEquals(1, count($customer['relations']));
        $this->assertEquals('CUSTOMER', $customer['relations'][0]['type']);
        
        $customer['billing'][1] = array('prefix1' => 'Dr.', 'prefix2' => 'George Harbottle', 'street' => 'Glasgow Str. 432', 'postalcode' => '532 45', 'locality' => 'Birmingham', 'type' => 'billing');
        $customer['delivery'][1] = array('prefix1' => 'Mr.', 'prefix2' => 'Peter Harbottle', 'street' => 'London Str. 123', 'postalcode' => '532 23', 'locality' => 'Birmingham', 'type' => 'delivery');
        
        $customer = $json->saveCustomer($customer);
        
        $this->assertEquals(2, count($customer['billing']));
        $this->assertEquals(2, count($customer['delivery']));
        
        $json->deleteCustomers(array($customer['id']));
        
        $this->setExpectedException('Tinebase_Exception_NotFound');

        $json->getCustomer($customer['id']);
    }
}
