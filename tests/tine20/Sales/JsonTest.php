<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Sales_Frontend_Json
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
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contractData['created_by']['accountId']);
        
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
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contractData['created_by']['accountId']);
    }

    /**
     * Tests multiple update with relations
     */
    public function testUpdateMultipleWithRelations()
    {
        $contract1 = $this->_getContract('contract 1');
        $contract2 = $this->_getContract('contract 2');
        $contract1 = Sales_Controller_Contract::getInstance()->create($contract1);
        $contract2 = Sales_Controller_Contract::getInstance()->create($contract2);
        
        // peter, bob, laura, lisa
        list($contact1, $contact2, $contact3, $contact4) = $this->_createContacts();
        
        // add contact2 as customer relation to contract2
        $this->_setContractRelations($contract2, array($contact2), 'CUSTOMER');

        $ids = array($contract1->id, $contract2->id);

        $tbJson = new Tinebase_Frontend_Json();
        // add Responsible contact1 to both contracts
        $response = $tbJson->updateMultipleRecords('Sales', 'Contract',
            array(array('name' => '%RESPONSIBLE-Addressbook_Model_Contact', 'value' => $contact1->getId())),
            array(array('field' => 'id', 'operator' => 'in', 'value' => $ids))
        );

        $this->assertEquals(2, count($response['results']));
        
        $contract1re = $this->_instance->getContract($contract1->getId());
        $contract2re = $this->_instance->getContract($contract2->getId());

        // only one CUSTOMER relation is allowed, contract2 still has related contact2
        $this->assertEquals(1, count($contract1re['relations']), 'contract1 relations count failed: ' . print_r($contract1re, true));
        $this->assertEquals(2, count($contract2re['relations']), 'contract2 relations count failed: ' . print_r($contract2re, true));

        $this->assertEquals($contact1->getId(), $contract1re['relations'][0]['related_id']);
        
        if ($contract2re['relations'][1]['related_id'] == $contact1->getId()) {
            $this->assertEquals($contact1->getId(), $contract2re['relations'][1]['related_id']);
            $this->assertEquals($contact2->getId(), $contract2re['relations'][0]['related_id']);
        } else {
            $this->assertEquals($contact2->getId(), $contract2re['relations'][1]['related_id']);
            $this->assertEquals($contact1->getId(), $contract2re['relations'][0]['related_id']);
        }
        
        // update customer to contact3 and add responsible to contact4, so contract1 and 2 will have 2 relations
        $response = $tbJson->updateMultipleRecords('Sales', 'Contract',
            array(
                array('name' => '%CUSTOMER-Addressbook_Model_Contact', 'value' => $contact3->getId()),
                array('name' => '%RESPONSIBLE-Addressbook_Model_Contact', 'value' => $contact4->getId())
                ),
            array(array('field' => 'id', 'operator' => 'in', 'value' => $ids))
        );
        $this->assertEquals(count($response['results']), 2);
        
        $contract1re = $this->_instance->getContract($contract1->getId());
        $contract2re = $this->_instance->getContract($contract2->getId());
        
        $this->assertEquals(2, count($contract1re['relations']));
        $this->assertEquals(2, count($contract2re['relations']));
        
        // remove customer
        $response = $tbJson->updateMultipleRecords('Sales', 'Contract',
            array(array('name' => '%CUSTOMER-Addressbook_Model_Contact', 'value' => '')),
            array(array('field' => 'id', 'operator' => 'in', 'value' => $ids))
        );
        
        $this->assertEquals(2, count($response['results']));
        
        $contract1res = $this->_instance->getContract($contract1->getId());
        $contract2res = $this->_instance->getContract($contract2->getId());

        $this->assertEquals(1, count($contract1res['relations']));
        $this->assertEquals(1, count($contract2res['relations']));
        // TODO: make this from other side work
        // add type RESPONSIBLE from the other side, so each contract gets 2 responsibles, but only one is allowed
//         $this->setExpectedException('Tinebase_Exception_InvalidRelationConstraints');
        
//         $response = $tbJson->updateMultipleRecords('Addressbook', 'Contact',
//             array(array('name' => '%RESPONSIBLE-Sales_Model_Contract', 'value' => $contract1->getId())),
//             array(array('field' => 'id', 'operator' => 'in', 'value' => array($contact1->getId(), $contact2->getId())))
//         );
        
    }
    
    /**
     * create some contacts
     * 
     * @param integer $number 
     * @return array
     */
    protected function _createContacts($number = 4)
    {
        $contact1 = new Addressbook_Model_Contact(array(
           'n_given' => 'peter', 'n_family' => 'wolf',
        ));
        
        for ($i = 0; $i < $number; $i++) {
            $contact = clone $contact1;
            switch ($i) {
                case 0:
                    break;
                case 1:
                    $contact->n_given = 'bob';
                    break;
                case 2:
                    $contact->n_given = 'laura';
                    break;
                case 3:
                    $contact->n_given = 'lisa';
                    break;
                default:
                    $contact->n_given = Tinebase_Record_Abstract::generateUID(20);
            }
            $contact = Addressbook_Controller_Contact::getInstance()->create($contact, false);
            $result[] = $contact;
        }
        
        return $result;
    }
    
    /**
     * set relations for contract
     * 
     * @param array|Sales_Model_Contract $contract
     * @param array $contacts
     * @param string $type
     */
    protected function _setContractRelations($contract, $contacts, $type = 'PARTNER')
    {
        $relationData = array();
        foreach ($contacts as $contact) {
            $relationData[] = array(
                'own_degree' => 'sibling',
                'related_degree' => 'sibling',
                'related_model' => 'Addressbook_Model_Contact',
                'related_backend' => 'Sql',
                'related_id' => $contact->getId(),
                'type' => $type
            );
        }
        $contractId = ($contract instanceof Sales_Model_Contract) ? $contract->getId() : $contract['id'];
        Tinebase_Relations::getInstance()->setRelations('Sales_Model_Contract', 'Sql', $contractId, $relationData);
    }

    /**
     * try to get an empty contract
     */
    public function testGetEmptyContract()
    {
        $contractData = $this->_instance->getContract(0);

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
        $contractUpdated = $this->_instance->saveContract($contractData);

        $this->assertEquals($contractData['id'], $contractUpdated['id']);
        $this->assertGreaterThan(0, count($contractUpdated['relations']));
        $this->assertEquals(2, count($contractUpdated['relations']));
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
    
    /**
     * testSaveContractWithManyRelations
     * 
     * @group longrunning
     * 
     * @see 0008586: when saving record with too many relations, modlog breaks
     * @see 0008712: testSaveContractWithManyRelations test lasts too long
     * @see 0009152: saving of record fails because of too many relations
     */
    public function testSaveContractWithManyRelations()
    {
        $contractArray = $this->testAddContract();
        $contacts = $this->_createContacts(500);
        $this->_setContractRelations($contractArray, $contacts);
        
        sleep(1);
        $updatedContractArray = $this->_instance->getContract($contractArray['id']);
        $updatedAgainContractArray = $this->_instance->saveContract($updatedContractArray);
        
        $this->assertEquals(500, count($updatedAgainContractArray['relations']));
        $this->assertTrue(empty($updatedAgainContractArray['relations'][0]['last_modified_time']), 'relation changed');
        
        $updatedAgainContractArray['relations'][0]['related_backend'] = 'sql';
        $updatedAgainContractArray['relations'][0]['related_record']['adr_one_locality'] = 'örtchen';
        $updatedAgainContractArray = $this->_instance->saveContract($updatedAgainContractArray);
        $this->assertEquals(500, count($updatedAgainContractArray['relations']));
    }
    
    /************ protected helper funcs *************/

    /**
     * get contract
     *
     * @return Sales_Model_Contract
     */
    protected function _getContract($title = 'phpunit contract', $desc = 'blabla')
    {
        return new Sales_Model_Contract(array(
            'title'         => $title,
            'description'   => $desc,
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
    
    /**
     * tests CostCenter CRUD Methods
     */
    public function testAllCostCenterMethods()
    {
        $this->markTestSkipped('0009550: fix Sales_JsonTest.testAllCostCenterMethods');
        
        $remark = Tinebase_Record_Abstract::generateUID(10);
        $number = Tinebase_DateTime::now()->getTimestamp();
        
        $cc = $this->_instance->saveCostCenter(
            array('number' => $number, 'remark' => $remark)
        );
        
        $this->assertEquals(40, strlen($cc['id']));
        
        $cc = $this->_instance->getCostCenter($cc['id']);
        
        $this->assertEquals($number, $cc['number']);
        $this->assertEquals($remark, $cc['remark']);
        
        $cc['remark'] = $cc['remark'] . '_unittest';
        $cc['number'] = $number - 5000;
        
        $cc = $this->_instance->saveCostCenter($cc);
        
        $this->assertEquals($remark . '_unittest', $cc['remark']);
        $this->assertEquals($number - 5000, $cc['number']);
        
        $accountId = Tinebase_Core::getUser()->getId();

        $this->assertEquals($accountId, $cc['created_by']['accountId']);
        $this->assertEquals($accountId, $cc['last_modified_by']['accountId']);
        $this->assertEquals(NULL, $cc['deleted_by']);
        $this->assertEquals(NULL, $cc['deleted_time']);
        $this->assertEquals(2, $cc['seq']);
        $this->assertEquals(0, $cc['is_deleted']);
        
        $ccs = $this->_instance->searchCostCenters(array(array('field' => 'remark', 'operator' => 'equals', 'value' => $remark . '_unittest')), array());
        
        $this->assertEquals(1, $ccs['totalcount']);
        $this->assertEquals($remark . '_unittest', $ccs['results'][0]['remark']);
        
        $this->_instance->deleteCostCenters($cc['id']);
        
        $ccs = $this->_instance->searchCostCenters(array(array('field' => 'number', 'operator' => 'equals', 'value' => $number - 5000)), array());
        
        $this->assertEquals(1, $ccs['totalcount']);
        $this->assertEquals(1, $ccs['results'][0]['is_deleted']);
    }

    
    /**
     * tests crud methods of division
     */
    public function testAllDivisionMethods()
    {
        $title = Tinebase_Record_Abstract::generateUID(10);
        $d = $this->_instance->saveDivision(
            array('title' => $title)
        );
        
        $this->assertEquals(40, strlen($d['id']));
        $this->assertEquals($title, $d['title']);
        
        $d = $this->_instance->getDivision($d['id']);
        
        $this->assertEquals(40, strlen($d['id']));
        $this->assertEquals($title, $d['title']);
        
        $title = Tinebase_Record_Abstract::generateUID(10);
        $d['title'] = $title;
        
        $d = $this->_instance->saveDivision($d);
        
        $this->assertEquals(40, strlen($d['id']));
        $this->assertEquals($title, $d['title']);
        
        $this->_instance->deleteDivisions(array($d['id']));
        
        $this->setExpectedException('Exception');
        
        $d = $this->_instance->getDivision($d['id']);
    }
        
    /**
     * @see https://forge.tine20.org/mantisbt/view.php?id=8840
     */
    public function testRelationConstraintsOwnSide()
    {
        $contract = Sales_Controller_Contract::getInstance()->create($this->_getContract());
    
        list($contact1, $contact2, $contact3, $contact4) = $this->_createContacts();
        
        $this->setExpectedException('Tinebase_Exception_InvalidRelationConstraints');
        
        $this->_setContractRelations($contract, array($contact1, $contact2), 'CUSTOMER');
    }
    
    /**
     * @see https://forge.tine20.org/mantisbt/view.php?id=8840
     */
    public function testRelationConstraintsOtherSide()
    {
        $contract = Sales_Controller_Contract::getInstance()->create($this->_getContract());
        
        list($contact1, $contact2, $contact3, $contact4) = $this->_createContacts(4);
        
        $this->_setContractRelations($contract, array($contact1), 'RESPONSIBLE');
        
        Addressbook_Controller_Contact::getInstance()->update($contact1);
        $contact1 = Addressbook_Controller_Contact::getInstance()->get($contact1->getId(), NULL, TRUE);
        $this->assertEquals(1, count($contact1->relations));
        
        // a partner may be added
        $relation = new Tinebase_Model_Relation(array(
            'own_degree' => 'sibling',
            'own_model'  => 'Addressbook_Model_Contact',
            'own_backend' => 'Sql',
            'own_id' => $contact2->getId(),
            'related_degree' => 'sibling',
            'related_model' => 'Sales_Model_Contract',
            'related_backend' => 'Sql',
            'related_id' => $contract->getId(),
            'type' => 'PARTNER'
        ));
        
        $contact2->relations = array($relation);
    
        $contact2 = Addressbook_Controller_Contact::getInstance()->update($contact2);
        $contact2 = Addressbook_Controller_Contact::getInstance()->get($contact2->getId(), NULL, TRUE);
        $this->assertEquals(1, count($contact2->relations));
        
        // a second partner may be added also
        $relation = new Tinebase_Model_Relation(array(
            'own_degree' => 'sibling',
            'own_model'  => 'Addressbook_Model_Contact',
            'own_backend' => 'Sql',
            'own_id' => $contact3->getId(),
            'related_degree' => 'sibling',
            'related_model' => 'Sales_Model_Contract',
            'related_backend' => 'Sql',
            'related_id' => $contract->getId(),
            'type' => 'PARTNER'
        ));
        
        $contact3->relations = array($relation);
        Addressbook_Controller_Contact::getInstance()->update($contact3);
        $contact3 = Addressbook_Controller_Contact::getInstance()->get($contact3->getId(), NULL, TRUE);
        $this->assertEquals(1, count($contact3->relations));
        
        $contract = Sales_Controller_Contract::getInstance()->get($contract->getId(), NULL, TRUE);
        $this->assertEquals(3, count($contract->relations));

        // but a second responsible must not be added
        $relation = new Tinebase_Model_Relation(array(
            'own_degree' => 'sibling',
            'own_model'  => 'Addressbook_Model_Contact',
            'own_backend' => 'Sql',
            'own_id' => $contact4->getId(),
            'related_degree' => 'sibling',
            'related_model' => 'Sales_Model_Contract',
            'related_backend' => 'Sql',
            'related_id' => $contract->getId(),
            'type' => 'RESPONSIBLE'
        ));
        
        $contact4->relations = array($relation);
        
        $this->setExpectedException('Tinebase_Exception_InvalidRelationConstraints');

        $contact4 = Addressbook_Controller_Contact::getInstance()->update($contact4);
    }
}
