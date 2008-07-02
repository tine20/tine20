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
     * Fixtures
     * 
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * Testcontainer
     *
     * @var unknown_type
     */
    protected $_testContainer;
    
    /**
     * Backend
     *
     * @var Crm_Backend_Products
     */
    protected $_backend;

    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm Products Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
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
            $this->_testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->_testContainer = $personalContainer[0];
        }
        
        $this->_objects['initialProduct'] = new Crm_Model_Product(array(
                'id' => 1000,
                'productsource' => 'Just a phpunit test product #0',
                'price' => '47.11'));
        
        $this->_backend = new Crm_Backend_Products();
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
     * try to add a product
     */
    public function testAddProduct()
    {
        $product = $this->_backend->create($this->_objects['initialProduct']);
        
        $this->assertEquals($this->_objects['initialProduct']->id, $product->id);
    }
    
    /**
     * try to get all products
     */
    public function testGetProducts()
    {
        $products = $this->_backend->getAll();
        
        $this->assertTrue(count($products) >= 1);
    }
    
    /**
     * try to get one product
     */
    public function testGetProduct()
    {
        $products = $this->_backend->getAll();
        
        $product = $this->_backend->get($products[0]->id);
        
        $this->assertType('Crm_Model_Product', $product);
        $this->assertTrue($product->isValid());
    }
    
    /**
     * try to delete a product
     */
    public function testDeleteProduct()
    {
    	$id = $this->_objects['initialProduct']->getId();
    	
        $this->_backend->delete($id);
        $this->setExpectedException('UnderflowException');
        $this->_backend->get($id);
    }
}
	

if (PHPUnit_MAIN_METHOD == 'Crm_Backend_ProductsTest::main') {
    Crm_Backend_ProductsTest::main();
}
