<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
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
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    

    /**
     * try to add a contract
     * 
     * @return array
     */
    public function testAddContract()
    {
        $contract = $this->_getContract();
        $contractData = $this->_instance->saveContract($contract->toArray());

        // checks
        $this->assertGreaterThan(0, $contractData['number']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contractData['created_by']);
        
        return $contractData;
    }

    /**
     * try to get a contract
     */
    public function testGetContract()
    {
        $contract = $this->_getContract();
        $contractData = $this->_instance->saveContract($contract->toArray());
        $contractData = $this->_instance->getContract($contractData['id']);

        // checks
        $this->assertGreaterThan(0, $contractData['number']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contractData['created_by']);
    }

    /**
     * Tests multiple update with relations
     */
    public function testUpdateMultipleWithRelations()
    {
        $contract1 = $this->_getContract();
        $contract2 = $this->_getContract();

        $contact1 = new Addressbook_Model_Contact(array(
           'n_given' => 'peter', 'n_family' => 'wolf',
        ));
        
        $contact2 = clone $contact1;
        $contact2->n_given = 'bob';

        $contact3 = clone $contact1;
        $contact3->n_given = 'laura';

        $contact4 = clone $contact1;
        $contact4->n_given = 'lisa';

        $contact1 = Addressbook_Controller_Contact::getInstance()->create($contact1, false);
        $contact2 = Addressbook_Controller_Contact::getInstance()->create($contact2, false);
        $contact3 = Addressbook_Controller_Contact::getInstance()->create($contact3, false);
        $contact4 = Addressbook_Controller_Contact::getInstance()->create($contact4, false);
        
        $relationData = array(array(
            'own_degree' => 'sibling',
            'related_degree' => 'sibling',
            'related_model' => 'Addressbook_Model_Contact',
            'related_backend' => 'Sql',
            'related_id' => $contact2->getId(),
            'type' => 'PARTNER'
        ));
        
        $contract1 = Sales_Controller_Contract::getInstance()->create($contract1);
        $contract2 = Sales_Controller_Contract::getInstance()->create($contract2);
        
        // add contact2 as customer relation to contract2
        Tinebase_Relations::getInstance()->setRelations('Sales_Model_Contract', 'Sql', $contract2->getId(), $relationData);

        $ids = array($contract1->id, $contract2->id);

        $json = new Tinebase_Frontend_Json();
        // add Responsible contact1 to both contracts
        $response = $json->updateMultipleRecords('Sales', 'Contract',
            array(array('name' => '%CUSTOMER-Addressbook_Model_Contact', 'value' => $contact1->getId())),
            array(array('field' => 'id', 'operator' => 'in', 'value' => $ids))
        );

        $this->assertEquals(count($response['results']), 2);
        
        $contract1re = $this->_instance->getContract($contract1->getId());
        $contract2re = $this->_instance->getContract($contract2->getId());

        $this->assertEquals(count($contract1re['relations']), 1);
        $this->assertEquals(count($contract2re['relations']), 2);

        $this->assertEquals($contract1re['relations'][0]['related_id'], $contact1->getId());
        
        if($contract2re['relations'][1]['related_id'] == $contact1->getId()) {
            $this->assertEquals($contract2re['relations'][1]['related_id'], $contact1->getId());
            $this->assertEquals($contract2re['relations'][0]['related_id'], $contact2->getId());
        } else {
            $this->assertEquals($contract2re['relations'][1]['related_id'], $contact2->getId());
            $this->assertEquals($contract2re['relations'][0]['related_id'], $contact1->getId());
        }
        

        // update customer to contact3 and add responsible contact4
        $response = $json->updateMultipleRecords('Sales', 'Contract',
            array(
                array('name' => '%CUSTOMER-Addressbook_Model_Contact', 'value' => $contact3->getId()),
                array('name' => '%RESPONSIBLE-Addressbook_Model_Contact', 'value' => $contact4->getId())
                ),
            array(array('field' => 'id', 'operator' => 'in', 'value' => $ids))
        );
        $this->assertEquals(count($response['results']), 2);
        
        $contract1re = $this->_instance->getContract($contract1->getId());
        $contract2re = $this->_instance->getContract($contract2->getId());
        
        $this->assertEquals(count($contract1re['relations']), 2);
        $this->assertEquals(count($contract2re['relations']), 3);
        
        // remove customer
        $response = $json->updateMultipleRecords('Sales', 'Contract',
            array(array('name' => '%CUSTOMER-Addressbook_Model_Contact', 'value' => '')),
            array(array('field' => 'id', 'operator' => 'in', 'value' => $ids))
        );
        
        $this->assertEquals(count($response['results']), 2);
        
        $contract1res = $this->_instance->getContract($contract1->getId());
        $contract2res = $this->_instance->getContract($contract2->getId());
        
        $this->assertEquals(count($contract1res['relations']), 1);
        $this->assertEquals(count($contract2res['relations']), 2);
    }

    /**
     * try to get an empty contract
     */
    public function testGetEmptyContract()
    {
        $contractData = $this->_instance->getContract(0);

        // checks
        $this->assertEquals(Sales_Controller_Contract::getSharedContractsContainer()->getId(), $contractData['container_id']['id']);
    }

    /**
     * try to update a contract (with relations)
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
        $this->assertEquals(2, count($contractUpdated['relations']));

        // cleanup
        $this->_instance->deleteContracts($contractData['id']);
        Addressbook_Controller_Contact::getInstance()->delete($contractUpdated['relations'][0]['related_id']);
        Addressbook_Controller_Contact::getInstance()->delete($contractUpdated['relations'][1]['related_id']);
    }

    /**
     * try to get a contract
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
    }

    /**
     * test product json api
     *
     * @todo generalize this
     */
    public function testAddGetSearchDeleteProduct()
    {
        $savedProduct = $this->_addProduct();
        $getProduct = $this->_instance->getProduct($savedProduct['id']);
        $searchProducts = $this->_instance->searchProducts($this->_getProductFilter(), '');

        //print_r($getProduct);

        // assertions
        $this->assertEquals($getProduct, $savedProduct);
        $this->assertTrue(count($getProduct['notes']) > 0, 'no notes found');
        $this->assertEquals('phpunit test note', $getProduct['notes'][0]['note']);
        $this->assertTrue($searchProducts['totalcount'] > 0);
        $this->assertEquals($savedProduct['description'], $searchProducts['results'][0]['description']);

        // delete all
        $this->_instance->deleteProducts($savedProduct['id']);

        // check if delete worked
        $result = $this->_instance->searchProducts($this->_getProductFilter(), '');
        $this->assertEquals(0, $result['totalcount']);
    }
    
    /**
     * save product with note
     * 
     * @return array
     */
    protected function _addProduct($withNote = TRUE)
    {
        $product    = $this->_getProduct();
        $productData = $product->toArray();
        if ($withNote) {
            $note = array(
                'note' => 'phpunit test note',
            );
            $productData['notes'] = array($note);
        }
        $savedProduct = $this->_instance->saveProduct($productData);
        
        return $savedProduct;
    }

    /**
     * testGetRegistryData (shared contracts container)
     */
    public function testGetRegistryData()
    {
        $data = $this->_instance->getRegistryData();

        $this->assertTrue(isset($data['defaultContainer']));
        $this->assertTrue(isset($data['defaultContainer']['path']));
        $this->assertTrue(isset($data['defaultContainer']['account_grants']));
        $this->assertTrue(is_array($data['defaultContainer']['account_grants']));
    }

    /**
     * testNoteConcurrencyManagement
     * 
     * @see 0006278: concurrency conflict when saving product
     */
    public function testNoteConcurrencyManagement()
    {
        $savedProduct = $this->_addProduct(FALSE);
        $savedProduct['notes'][] = array(
            'note' => 'another phpunit test note',
        );
        $savedProduct = $this->_instance->saveProduct($savedProduct);
        
        $savedProduct['name'] = 'changed name';
        $savedProductNameChanged = $this->_instance->saveProduct($savedProduct);
        
        $savedProductNameChanged['name'] = 'PHPUnit test product';
        $savedProductNameChangedAgain = $this->_instance->saveProduct($savedProductNameChanged);
        
        $this->assertEquals('PHPUnit test product', $savedProductNameChangedAgain['name']);
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
            Tinebase_Model_Grants::GRANT_EDIT
        );

        $currentUser = Tinebase_Core::getUser();

        return array(
            array(
                'type'              => Sales_Model_Contract::RELATION_TYPE_CUSTOMER,
                'related_record'    => array(
                    'org_name'         => 'phpunit erp test customer',
                    'container_id'  => $personalContainer[0]->getId(),
                ),
                'related_model' => 'Addressbook_Model_Contact',
                'own_degree'    => 'sibling'
            ),
            array(
                'type'              => Sales_Model_Contract::RELATION_TYPE_RESPONSIBLE,
                'related_record'    => array(
                    'org_name'         => 'phpunit erp test responsible',
                    'container_id'  => $personalContainer[0]->getId(),
                ),
                'related_model' => 'Addressbook_Model_Contact',
                'own_degree'    => 'sibling'
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
}
