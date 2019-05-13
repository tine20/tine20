<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Sales_SuppliersTest
 */
class Sales_SuppliersTest extends TestCase
{
    /**
     * 
     * @var Addressbook_Controller_Contact
     */
    protected $_contactController;
    
    /**
     * 
     * @var Sales_Frontend_Json
     */
    protected $_json;

    public function publicSetUp()
    {
        $this->setUp();
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->_contactController  = Addressbook_Controller_Contact::getInstance();
        $this->_json               = new Sales_Frontend_Json();
    }

    /**
     * 
     * @return array
     */
    public function _createSupplier()
    {
        $container = Tinebase_Container::getInstance()->getSharedContainer(
            Tinebase_Core::getUser()->getId(),
            'Addressbook_Model_Contact',
            'WRITE'
        );
        
        $containerContracts = Tinebase_Container::getInstance()->getSharedContainer(
            Tinebase_Core::getUser()->getId(),
            'Sales_Model_Contract',
            'WRITE'
        );
        
        $container = $container->getFirstRecord();
        
        $contact1 = $this->_contactController->create(new Addressbook_Model_Contact(
            array('n_given' => 'Yiting', 'n_family' => 'Huang', 'container_id' => $container->getId()))
        );
        $contact2 = $this->_contactController->create(new Addressbook_Model_Contact(
            array('n_given' => 'Hans Friedrich', 'n_family' => 'Ochs', 'container_id' => $container->getId()))
        );
        
        $customerData = array(
            'name' => 'Worldwide Electronics International',
            'cpextern_id' => $contact1->getId(),
            'cpintern_id' => $contact2->getId(),
            'number'      => 4294967,
        
            'iban'        => 'CN09234098324098234598',
            'bic'         => '0239580429570923432444',
            'url'         => 'http://wwei.cn',
            'vatid'       => '239rc9mwqe9c2q',
            'credit_term' => '30',
            'currency'    => 'EUR',
            'curreny_trans_rate' => 7.034,
            'discount'    => 12.5,
        
            'adr_prefix1' => 'no prefix 1',
            'adr_prefix2' => 'no prefix 2',
            'adr_street' => 'Mao st. 2000',
            'adr_postalcode' => '1',
            'adr_locality' => 'Shanghai',
            'adr_region' => 'Shanghai',
            'adr_countryname' => 'China',
            'adr_pobox'   => '7777777'
        );
        
        return $this->_json->saveSupplier($customerData);
    }
    
    public function testLifecycleSupplier()
    {
        $retVal = $this->_createSupplier();
        
        $this->assertEquals(4294967, $retVal["number"]);
        $this->assertEquals("Worldwide Electronics International", $retVal["name"]);
        $this->assertEquals("http://wwei.cn", $retVal["url"]);
        $this->assertEquals(NULL, $retVal['description']);
        
        $this->assertEquals('Yiting', $retVal['cpextern_id']['n_given']);
        $this->assertEquals('Huang',  $retVal['cpextern_id']['n_family']);
        
        $this->assertEquals('Hans Friedrich', $retVal['cpintern_id']['n_given']);
        $this->assertEquals('Ochs', $retVal['cpintern_id']['n_family']);

        // delete record (set deleted=1) of customer and assigned addresses
        $this->_json->deleteSuppliers(array($retVal['id']));
        
        $customerBackend = new Sales_Backend_Supplier();
        $deletedSupplier = $customerBackend->get($retVal['id'], TRUE);
        $this->assertEquals(1, $deletedSupplier->is_deleted);
        
        $addressBackend = new Sales_Backend_Address();
        $deletedAddresses = $addressBackend->getMultipleByProperty($retVal['id'], 'customer_id', TRUE);

        $this->assertEquals(1, $deletedAddresses->count());
        
        foreach($deletedAddresses as $address) {
            $this->assertEquals(1, $address->is_deleted);
        }
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        return $this->_json->getSupplier($retVal['id']);
    }
    
    /**
     * checks if the number is always set to the correct value
     */
    public function testNumberable()
    {
        $controller = Sales_Controller_Supplier::getInstance();
        
        $record = $controller->create(new Sales_Model_Supplier(array('name' => 'auto1')));
        
        $this->assertGreaterThan(0, $record->number);
        $initialNumber = $record->number;
        
        $record = $controller->create(new Sales_Model_Supplier(array('name' => 'auto2')));
        
        $this->assertEquals($initialNumber + 1, $record->number);
        
        // set number to $initialNumber + 3, should return the formatted number
        $record = $controller->create(new Sales_Model_Supplier(array('name' => 'manu1', 'number' => $initialNumber + 3)));
        $this->assertEquals($initialNumber + 3, $record->number);
        
        // the next number should be a number after the manual number
        $record = $controller->create(new Sales_Model_Supplier(array('name' => 'auto3')));
        $this->assertEquals($initialNumber + 4, $record->number);
    }
}
