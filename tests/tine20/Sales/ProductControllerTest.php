<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Sales_Controller_Product
 */
class Sales_ProductControllerTest extends TestCase
{
    /**
     * lazy init of uit
     *
     * @return Sales_Controller_Product
     */
    protected function _getUit()
    {
        if ($this->_uit === null) {
            $this->_uit = Sales_Controller_Product::getInstance();
        }
        
        return $this->_uit;
    }
    
    /**
     * testUpdateProductLifespan
     * 
     * @see 0010766: set product lifespan
     */
    public function testUpdateProductLifespan()
    {
        $product1 = $this->_getUit()->create(new Sales_Model_Product(array(
            'name' => 'product activates in future',
            'lifespan_start' => Tinebase_DateTime::now()->addDay(1)
        )));
        $product2 = $this->_getUit()->create(new Sales_Model_Product(array(
            'name' => 'product lifespan ended',
            'lifespan_end' => Tinebase_DateTime::now()->subDay(1)
        )));
        $product3 = $this->_getUit()->create(new Sales_Model_Product(array(
            'is_active' => 0,
            'name' => 'product lifespan started',
            'lifespan_start' => Tinebase_DateTime::now()->subDay(1)
        )));
        $product4 = $this->_getUit()->create(new Sales_Model_Product(array(
            'is_active' => 0,
            'name' => 'product lifespan not yet ended',
            'lifespan_end' => Tinebase_DateTime::now()->addDay(1)
        )));
        
        $productsToTest = array(
            array('expectedIsActive' => 0, 'product' => $product1),
            array('expectedIsActive' => 0, 'product' => $product2),
            array('expectedIsActive' => 1, 'product' => $product3),
            array('expectedIsActive' => 1, 'product' => $product4),
        );
        
        $this->_getUit()->updateProductLifespan();
        
        foreach ($productsToTest as $product) {
            $updatedProduct = $this->_getUit()->get($product['product']);
            $this->assertEquals($product['expectedIsActive'], $updatedProduct->is_active, print_r($product['product']->toArray(), true));
        }
    }
}
