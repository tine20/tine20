<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

/**
 * Test class for Sales_PurchaseInvoice
 */
class Sales_PurchaseInvoiceTest extends TestCase
{
    /**
     *
     * @var Sales_Frontend_Json
     */
    protected $_json;
    
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
                array('field' => 'query', 'operator' => 'contains', 'value' => '12345'),
        );
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
    protected function _createPurchaseInvoice()
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
                'number'      => 54321,
    
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
        
        $purchaseData = array(
                'number' => 'R-12345',
                'description' => 'test',
                'discount' => 0,
                'due_in' => 10,
                'date' => '2015-03-17 00:00:00',
                'due_at' => '2015-03-27 00:00:00',
                'price_net' => 10,
                'sales_tax' => 19,
                'price_tax' => 1.9,
                'price_gross' => 11.9,
                'price_gross2' => 1,
                'price_total' => 12.9,
                'relations' => array(array(
                        'own_model' => 'Sales_Model_PurchaseInvoice',
                        'own_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'related_model' => 'Sales_Model_Supplier',
                        'related_record' => $customerData,
                        'type' => 'SUPPLIER'
                )
            )
        );
        
        return $this->_json->savePurchaseInvoice($purchaseData);
    }
    
    /**
     * try to save a PurchaseInvoice
     */
    public function testSavePurchaseInvoice()
    {
        $purchase = $this->_createPurchaseInvoice();
        $this->assertEquals('R-12345', $purchase['number']);
        $this->assertEquals('Worldwide Electronics International', $purchase['supplier']['name']);
        $this->assertEquals('2015-03-17 00:00:00', $purchase['date']);
        $this->assertEquals('2015-03-27 00:00:00', $purchase['due_at']);

        $this->assertEquals(10, $purchase['price_net']);
        $this->assertEquals(19, $purchase['sales_tax']);
        $this->assertEquals(1.9, $purchase['price_tax']);
        $this->assertEquals(11.9, $purchase['price_gross']);
        $this->assertEquals(1, $purchase['price_gross2']);
        $this->assertEquals(12.9, $purchase['price_total']);
    }
    
    /**
     * try to update a PurchaseInvoice
     */
    public function testUpdatePurchaseInvoice()
    {
        $purchase = $this->_createPurchaseInvoice();
        $this->assertEquals('2015-03-27 00:00:00', $purchase['due_at']);
        $purchase['due_at'] = '2015-04-07 00:00:00';
        $updatedPurchase = $this->_json->savePurchaseInvoice($purchase);
        $this->assertEquals('2015-04-07 00:00:00', $updatedPurchase['due_at']);
    }

    /**
     * try to get a PurchaseInvoice
     */
    public function testSearchPurchaseInvoice()
    {
        $purchase = $this->_createPurchaseInvoice();
        
        // search & check
        $search = $this->_json->searchPurchaseInvoices($this->_getFilter(), $this->_getPaging());
        $this->assertEquals($purchase['number'], $search['results'][0]['number']);
        $this->assertEquals(1, $search['totalcount']);
    }
    
    /**
     * try to delete a PurchaseInvoice
     */
    public function testDeletePurchaseInvoice()
    {
        $purchase = $this->_createPurchaseInvoice();
        $this->assertEquals('R-12345', $purchase['number']);
        
        // delete record
        $this->_json->deletePurchaseInvoices($purchase['id']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $customerBackend = new Sales_Backend_PurchaseInvoice();
        $deletedPurchase = $customerBackend->get($purchase['id'], TRUE);
        $this->assertEquals(1, $deletedPurchase->is_deleted);
        
    }

}
