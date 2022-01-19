<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Sales_ControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Sales_Controller_Contract
     */
    protected $_backend = array();
    
    /**
     * the costcenter number used for the tests
     * 
     * @var string
     */
    protected $_costCenterNumber;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        Sales_Config::getInstance()->set(Sales_Config::CONTRACT_NUMBER_VALIDATION, 'text');
        
        $this->_backend = Sales_Controller_Contract::getInstance();
        
        $this->_backend->setNumberPrefix();
        $this->_backend->setNumberZerofill();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
    }
    
    /**
     * tests for the costcenter controller
     */
    public function testCostCenterController()
    {
        $cc = $this->_getCostCenter();
        $ccRet = Sales_Controller_CostCenter::getInstance()->create($cc);
        
        $this->assertEquals($cc->id, $ccRet->id);
        $this->assertEquals($cc->number, $ccRet->number);
        $this->assertEquals($cc->remark, $ccRet->remark);

        // check uniquity
        $cc1 = $this->_getCostCenter();
        
        $this->expectException('Tinebase_Exception_Duplicate');

        Sales_Controller_CostCenter::getInstance()->create($cc1);
    }
    
    /**
     * get cost center
     *
     * @return Sales_Model_CostCenter
     */
    protected function _getCostCenter()
    {
        $this->_costCenterNumber = $this->_costCenterNumber ? $this->_costCenterNumber : Tinebase_Record_Abstract::generateUID();

        $cc = new Sales_Model_CostCenter(array(
            'id'      => Tinebase_Record_Abstract::generateUID(),
            'number'  => $this->_costCenterNumber,
            'remark'  => 'blabla'
        ), TRUE);
        return $cc;
    }
    
    /**
     * try to add a contract
     *
     */
    public function testAddContract()
    {
        $contractData = $this->_getContract();
        $contract = $this->_backend->create($contractData);
        
        // checks
        $this->assertEquals($contractData->getId(), $contract->getId());
        $this->assertGreaterThan(0, $contract->number);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contract->created_by);
        
        // cleanup
        $this->_backend->delete($contract->getId());
        $this->_decreaseNumber();
    }
    
    /**
     * try to get a contract
     *
     */
    public function testGetContract()
    {
        $contractData = $this->_getContract();
        $this->_backend->create($contractData);
        $contract = $this->_backend->get($contractData->getId());
        
        // checks
        $this->assertEquals($contractData->getId(), $contract->getId());
        $this->assertGreaterThan(0, $contract->number);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contract->created_by);
        
        // cleanup
        $this->_backend->delete($contract->getId());
        $this->_decreaseNumber();
    }
    
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
            'id'            => Tinebase_Record_Abstract::generateUID()
        ), TRUE);
    }
    
    /**
     * decrease contracts number
     */
    protected function _decreaseNumber()
    {
        $numberBackend = new Sales_Backend_Number();
        $number = $numberBackend->getNext('Sales_Model_Contract', Tinebase_Core::getUser()->getId());
        
        // reset or delete old number
        if ($number->number == 2) {
            $numberBackend->delete($number);
        } else {
            $number->number -= 2;
            $numberBackend->update($number);
        }
    }
    
    /**
     * tests resolving the fulltext property of the address
     */
    public function testResolveVirtualFields()
    {
        $address = array(
            'prefix1' => 'Meister',
            'prefix2' => 'Eder',
            'street' => 'Brunnengässla 4',
            'postalcode' => '80331',
            'locality' => 'Munich',
            'region' => 'Bavaria',
            'countryname' => 'DE',
            'custom1' => 'de-234',
            'type' => 'billing',
        );
        
        $i18nTypeString = Tinebase_Translation::getTranslation('Sales')->_('billing');
        
        $result = Sales_Controller_Address::getInstance()->resolveVirtualFields($address);
        $this->assertEquals($result['fulltext'], "Meister Eder, Brunnengässla 4, 80331 Munich ($i18nTypeString - de-234)");
    }

    /**
     * tests adding and removing of products to a contract
     */
    public function testAddDeleteProducts()
    {
        $prodTest = new Sales_ProductControllerTest();
        $productOne = $prodTest->testCreateProduct();
        $productTwo = $prodTest->testCreateProduct();

        $contractData = $this->_getContract();
        $contractData->products = array(
            array(
                'product_id' => $productOne->getId(),
                'quantity' => 1,
                'interval' => 1,
                'billing_point' => 1,
                'json_attributes' => [
                    'assignedAccountables' => [[
                            'model' => 'a',
                            'id'    => 1,
                        ], [
                            'model' => 'a',
                            'id'    => 1,
                        ]],
                    ],
            ),
            array(
                'product_id' => $productTwo->getId(),
                'quantity' => 1,
                'interval' => 1,
                'billing_point' => 1,
            ),
        );
        $this->_backend->create($contractData);
        $contract = $this->_backend->get($contractData->getId());

        // checks
        $this->assertEquals($contractData->getId(), $contract->getId());
        $this->assertGreaterThan(0, $contract->number);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contract->created_by);

        // check count of product aggregates
        $filter = new Sales_Model_ProductAggregateFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'contract_id', 'operator' => 'equals', 'value' => $contract->getId())
        ));
        $productAggregates = Sales_Controller_ProductAggregate::getInstance()->search($filter);
        $this->assertEquals(2, count($productAggregates));
        static::assertEquals(1, count($productAggregates->find('product_id', $productOne->getId())
            ->json_attributes['assignedAccountables']));

        $contractData->products = array(
            array(
                'product_id' => $productOne->getId(),
                'quantity' => 1,
                'interval' => 1,
                'billing_point' => 1,
                'json_attributes' => [
                    'assignedAccountables' => [[
                        'model' => 'a',
                        'id'    => 1,
                    ], [
                        'model' => 'a',
                        'id'    => 1,
                    ]],
                ],
            ),
        );
        $this->_backend->update($contractData);
        $contract = $this->_backend->get($contractData->getId());

        // check count of product aggregates
        $filter = new Sales_Model_ProductAggregateFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'contract_id', 'operator' => 'equals', 'value' => $contract->getId())
        ));
        $productAggregates = Sales_Controller_ProductAggregate::getInstance()->search($filter);
        $this->assertEquals(1, count($productAggregates));
        static::assertEquals(1, count($productAggregates->find('product_id', $productOne->getId())
            ->json_attributes['assignedAccountables']));

        $contractData->products = array(
            array(
                'product_id' => $productOne->getId(),
                'quantity' => 1,
                'interval' => 1,
                'billing_point' => 1,
                'json_attributes' => [
                    'assignedAccountables' => '',
                ],
            ),
        );
        $this->_backend->update($contractData);
        $contract = $this->_backend->get($contractData->getId());

        // check count of product aggregates
        $filter = new Sales_Model_ProductAggregateFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'contract_id', 'operator' => 'equals', 'value' => $contract->getId())
        ));
        $productAggregates = Sales_Controller_ProductAggregate::getInstance()->search($filter);
        $this->assertEquals(1, count($productAggregates));
        static::assertFalse(isset($productAggregates->find('product_id', $productOne->getId())
            ->json_attributes['assignedAccountables']));

        // cleanup
        $this->_backend->delete($contract->getId());
        $this->_decreaseNumber();
        $prodTest->getUit()->delete(array($productOne->getId(), $productTwo->getId()));
    }

    /**
     * Creates a contact and relates it to the customer. Checks if updating the contact address also updates the Customer postal Address
     */
    public function testContactUpdatesCustomerPostal()
    {
        $adbController = Addressbook_Controller_Contact::getInstance();
        
        $contact = $adbController->create(new Addressbook_Model_Contact(array(
            'n_family' => 'test customer contact',
            'adr_one_street' => 'test Str. 1',
            'adr_one_postalcode' => '1234',
            'adr_one_locality' => 'Test City'
        )));
        
        $customer = Sales_Controller_Customer::getInstance()->create(new Sales_Model_Customer(array(
            'name' => Tinebase_Record_Abstract::generateUID(),
        )));

        $relationData = [
            'own_model'         => Addressbook_Model_Contact::class,
            'own_id'            => $contact->getId(),
            'related_degree'    => Tinebase_Model_Relation::DEGREE_CHILD,
            'related_model'     => Sales_Model_Customer::class,
            'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'related_id'        => $customer->getId(),
            'type'              => 'CONTACTCUSTOMER'
        ];
        Tinebase_Relations::getInstance()->addRelation(new Tinebase_Model_Relation($relationData), $contact);
        $contact = $adbController->get($contact->getId());
        
        $contact->adr_one_locality = 'Foobar';
        $contact = $adbController->update($contact);
        
        $relations = Tinebase_Relations::getInstance()->getRelations(Addressbook_Model_Contact::class,'Sql', $contact->getId());
        $customerRelation = $relations->filter('type', 'CONTACTCUSTOMER')->getFirstRecord();
        
        self::assertEquals($customer->getId(), $customerRelation->related_id);
        
        $postal = Sales_Controller_Address::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Address::class, [
            ['field' => Sales_Model_Address::FLD_CUSTOMER_ID, 'operator' => 'equals', 'value' => $customerRelation->related_id],
            ['field' => Sales_Model_Address::FLD_TYPE, 'operator' => 'equals', 'value' => 'postal'],
        ]))->getFirstRecord();
        
        self::assertEquals($contact->adr_one_locality, $postal->locality);
    }
}
