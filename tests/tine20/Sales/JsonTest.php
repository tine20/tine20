<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Sales_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Sales_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sales_Frontend_Json
     */
    protected $_instance = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Sales Json Tests');
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
        $this->_instance = new Sales_Frontend_Json();        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {        
    }
    
    /**
     * try to add a contract
     *
     */
    public function testAddContract()
    {
        $contract = $this->_getContract();
        $contractData = $this->_instance->saveContract($contract->toArray());
        
        // checks
        $this->assertGreaterThan(0, $contractData['number']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contractData['created_by']);
        
        // cleanup
        $this->_instance->deleteContracts($contractData['id']);
        $this->_decreaseNumber();
    }
    
    /**
     * try to get a contract
     *
     */
    public function testGetContract()
    {
        $contract = $this->_getContract();
        $contractData = $this->_instance->saveContract($contract->toArray());
        $contractData = $this->_instance->getContract($contractData['id']);
        
        // checks
        $this->assertGreaterThan(0, $contractData['number']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contractData['created_by']);
        
        // cleanup
        $this->_instance->deleteContracts($contractData['id']);
        $this->_decreaseNumber();
    }

    /**
     * try to get an empty contract
     *
     */
    public function testGetEmptyContract()
    {
        $contractData = $this->_instance->getContract(0);
        
        // checks
        $this->assertEquals(Tinebase_Container::getInstance()->getContainerByName('Sales', 'Shared Contracts', 'shared')->getId(), $contractData['container_id']['id']);        
    }
    
    /**
     * try to update a contract (with relations)
     *
     */
    public function testUpdateContract()
    {
        $contract = $this->_getContract();
        $contractData = $this->_instance->saveContract($contract->toArray());
        $contractData = $this->_instance->getContract($contractData['id']);
        
        // add account and contact + update contract
        $contractData['relations'] = $this->_getRelations();

        //print_r($contractData);
        
        $contractUpdated = $this->_instance->saveContract($contractData);
        
        //print_r($contractUpdated);
        
        // check
        $this->assertEquals($contractData['id'], $contractUpdated['id']);
        $this->assertGreaterThan(0, count($contractUpdated['relations']));
        $this->assertEquals('Addressbook_Model_Contact', $contractUpdated['relations'][0]['related_model']);
        $this->assertEquals(Sales_Model_Contract::RELATION_TYPE_CUSTOMER, $contractUpdated['relations'][0]['type']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contractUpdated['relations'][1]['related_id']);
        $this->assertEquals(Sales_Model_Contract::RELATION_TYPE_ACCOUNT, $contractUpdated['relations'][1]['type']);
        
        // cleanup
        $this->_instance->deleteContracts($contractData['id']);
        Addressbook_Controller_Contact::getInstance()->delete($contractUpdated['relations'][0]['related_id']);
        $this->_decreaseNumber();
    }
    
    /**
     * try to get a contract
     *
     */
    public function testSearchContracts()
    {
        // create
        $contract = $this->_getContract();
        $contractData = $this->_instance->saveContract($contract->toArray());
        
        // search & check
        $search = $this->_instance->searchContracts($this->_getFilter(), $this->_getPaging());
        $this->assertEquals($contract->title, $search['results'][0]['title']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_instance->deleteContracts($contractData['id']);
        $this->_decreaseNumber();        
    }
    
    /**
     * test product json api
     * 
     * @todo generalize this
     */
    public function testAddGetSearchDeleteProduct()
    {
        $product    = $this->_getProduct();
        $productData = $product->toArray();
        // add note
        $note = array(
            'note'              => 'phpunit test note',            
        );
        $productData['notes'] = array($note);        
        
        $savedProduct = $this->_instance->saveProduct($productData);
        $getProduct = $this->_instance->getProduct($savedProduct['id']);
        $searchProducts = $this->_instance->searchProducts($this->_getProductFilter(), '');
        
        //print_r($getProduct);
        
        // assertions
        $this->assertEquals($getProduct, $savedProduct);
        $this->assertTrue(count($getProduct['notes']) > 0, 'no notes found');
        $this->assertEquals($note['note'], $getProduct['notes'][0]['note']);
        $this->assertTrue($searchProducts['totalcount'] > 0);
        $this->assertEquals($product->description, $searchProducts['results'][0]['description']);

        // delete all
        $this->_instance->deleteProducts($savedProduct['id']);
        
        // check if delete worked
        $result = $this->_instance->searchProducts($this->_getProductFilter(), '');
        $this->assertEquals(0, $result['totalcount']);   
    }
    
    /************ protected helper funcs *************/
    
    /**
     * get contract
     *
     * @return Sales_Model_Contract
     */
    protected function _getContract()
    {
        return new Sales_Model_Contract(array(
            'title'         => 'phpunit contract',
            'description'   => 'blabla',
        ), TRUE);
    }

    /**
     * get paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'number',
            'dir' => 'ASC',
        );
    }

    /**
     * get filter
     *
     * @return array
     */
    protected function _getFilter()
    {
        return array(
            array('field' => 'query', 'operator' => 'contains', 'value' => 'blabla'),     
        );        
    }
    
    /**
     * get relations
     *
     * @return array
     */
    protected function _getRelations()
    {
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::EDITGRANT
        );
        
        $currentUser = Tinebase_Core::getUser();
        
        return array(
            array(
                'type'              => Sales_Model_Contract::RELATION_TYPE_CUSTOMER,
                'related_record'    => array(
                    'org_name'         => 'phpunit erp test company',
                    'container_id'  => $personalContainer[0]->getId(),
                )
            ),
            array(
                'type'              => Sales_Model_Contract::RELATION_TYPE_ACCOUNT,
                'related_id'        => $currentUser->getId(),
                'related_record'    => $currentUser->toArray()
            ),
        );        
    }
    
    /**
     * get product
     * 
     * @return Sales_Model_Product
     */
    protected function _getProduct()
    {
        return new Sales_Model_Product(array(
            'name'          => 'PHPUnit test product',
            'price'         => 10000,
            'description'   => 'test product description'   
        ));
    }
    
    /**
     * get product filter
     * 
     * @return array
     */
    protected function _getProductFilter()
    {
        return array(
            array('field' => 'query',           'operator' => 'contains',       'value' => 'PHPUnit'),
        );
    }
    
    /**
     * decrease contracts number
     *
     */
    protected function _decreaseNumber()
    {
        $numberBackend = new Sales_Backend_Number();
        $number = $numberBackend->getNext(Sales_Model_Number::TYPE_CONTRACT, Tinebase_Core::getUser()->getId());
        // reset or delete old number
        if ($number->number == 2) {
            $numberBackend->delete($number);
        } else {
            $number->number -= 2;
            $numberBackend->update($number);
        }
    }
}
