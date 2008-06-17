<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 * @todo rewrite test for Crm_Backend_Products
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_Backend_LeadsProductsTest::main');
}

/**
 * Test class for Crm_Backend_Products
 */
class Crm_Backend_ProductsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    protected $testContainer;
    
    protected $backend;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm Products Backend Tests');
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
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Crm', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Container::GRANT_EDIT
        );
        
        if($personalContainer->count() === 0) {
            $this->testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->testContainer = $personalContainer[0];
        }
        
        $this->objects['initialLead'] = new Crm_Model_Lead(array(
            'id'            => 120,
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container'     => $this->testContainer->id,
            'start'         => Zend_Date::now(),
            'description'   => 'Description',
            'end'           => Zend_Date::now(),
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => Zend_Date::now(),
        )); 
        
        $this->objects['updatedLead'] = new Crm_Model_Lead(array(
            'id'            => 120,
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container'     => $this->testContainer->id,
            'start'         => Zend_Date::now(),
            'description'   => 'Description updated',
            'end'           => NULL,
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => NULL,
        )); 
        
        $this->backend = Crm_Backend_Products::getInstance();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     */
    protected function tearDown()
    {
	   #Tinebase_Container::getInstance()->deleteContainer($this->testContainer->id);
    }
    
    /**
     * try to get all products
     *
     */
    public function testGetProducts()
    {
        $products = $this->backend->getProducts();
        
        #$this->assertTrue(count($types) >= 3);
    }
    
    /**
     * try to get one product
     *
     */
    public function testGetProduct()
    {
        $products = $this->backend->getProducts();
        
        #$product = $this->backend->getProduct($products[0]->id);
        
        #$this->assertType('Crm_Model_Product', $product);
        #$this->assertTrue($product->isValid());
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Crm_Backend_ProductsTest::main') {
    Crm_Backend_ProductsTest::main();
}
