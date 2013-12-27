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
 * Base test class for Sales-Invoice
 */
class Sales_InvoiceTestCase extends TestCase
{
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_customerRecords = NULL;
    
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_addressRecords = NULL;
    
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_costcenterRecords = NULL;
    
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_contractRecords = NULL;
    
    /**
     * the referrence date all tests should depend on
     * @var Tinebase_DateTime
     */
    protected $_referenceDate = NULL;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_createFixtures();
        $this->_testFixtures();
    }
    
    protected function _createFixtures()
    {
        $this->_referenceDate = Tinebase_DateTime::now();
        $this->_referenceDate->setTimezone('UTC');
        $this->_referenceDate->subYear(1);
        $this->_referenceDate->setDate($this->_referenceDate->format('Y'), 1 ,1);
        $this->_referenceDate->setTime(0,0,0);
        
        // addresses
        $csvFile = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'tine20'  . DIRECTORY_SEPARATOR . 'Addressbook' . DIRECTORY_SEPARATOR . 'Setup' . DIRECTORY_SEPARATOR . 'DemoData' . DIRECTORY_SEPARATOR . 'out1000.csv';
        
        if (! file_exists($csvFile)) {
            die('File does not exist: ' . $csvFile . PHP_EOL);
        }
        $fhcsv = fopen($csvFile, 'r');
        
        $i = 0;
    
        $indexes = fgetcsv($fhcsv);
    
        $contactController = Addressbook_Controller_Contact::getInstance();
        $addresses = array();
        
        while ($row = fgetcsv($fhcsv)) {
            if ($i >= 15) {
                break;
            }
            
            foreach($row as $index => $field) {
                if ($indexes[$index] == 'gender') {
                    if ($field == 'male') {
                        $isMan = true;
                        $addresses[$i]['salutation'] = 'MR';
                    } else {
                        $isMan = false;
                        $addresses[$i]['salutation'] = 'MRS';
                    }
                } else {
                    $addresses[$i][$indexes[$index]] = $field;
                }
            }
            
            $i++;
        }
        fclose($fhcsv);
        
        $container = Tinebase_Container::getInstance()->addContainer(
            new Tinebase_Model_Container(
                array(
                    'name'           => 'TEST SALES INVOICES',
                    'type'           => Tinebase_Model_Container::TYPE_SHARED,
                    'owner_id'       => Tinebase_Core::getUser(),
                    'backend'        => 'SQL',
                    'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
                    'model'          => 'Addressbook_Model_Contact',
                    'color'          => '#000000'
            )), NULL, TRUE
        );
        
        $cid = $container->getId();
        
        $this->_addressRecords = new Tinebase_Record_RecordSet('Sales_Model_Address');
        $this->_contactRecords = new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        
        foreach($addresses as $address) {
            $data = array_merge($address, array('container_id' => $cid));
            $this->_contactRecords->addRecord($contactController->create(new Addressbook_Model_Contact($data, TRUE), FALSE));
        }
        
        // customers
        $customers = array(
            array(
                'name' => 'Customer1',
                'url' => 'www.customer1.de',
                'discount' => 0
            ),
            array(
                'name' => 'Customer2',
                'url' => 'www.customer2.de',
                'discount' => 5
            ),
            array(
                'name' => 'Customer3',
                'url' => 'www.customer3.de',
                'discount' => 10
            )
        );
        
        $i=0;
        
        $customerController = Sales_Controller_Customer::getInstance();
        $this->_customerRecords = new Tinebase_Record_RecordSet('Sales_Model_Customer');
        $addressController = Sales_Controller_Address::getInstance();
        
        foreach ($customers as $customer) {
            $customer['cpextern_id'] = $this->_contactRecords->getByIndex($i)->getId();
            $i++;
            $customer['cpintern_id'] = $this->_contactRecords->getByIndex($i)->getId();
            $i++;
            $customer['iban'] = Tinebase_Record_Abstract::generateUID(20);
            $customer['bic'] = Tinebase_Record_Abstract::generateUID(12);
            $customer['credit_term'] = 30;
            $customer['currency'] = 'EUR';
            $customer['currency_trans_rate'] = 1;
        
            $this->_customerRecords->addRecord($customerController->create(new Sales_Model_Customer($customer)));
        }
        
        foreach($this->_customerRecords as $customer) {
            foreach(array('postal', 'billing', 'delivery') as $type) {
                $caddress = $this->_contactRecords->getByIndex($i);
                $address = new Sales_Model_Address(array(
                    'customer_id' => $customer->getId(),
                    'type'        => $type,
                    'prefix1'     => $caddress->title,
                    'prefix2'     => $caddress->n_fn,
                    'street'      => $caddress->adr_two_street,
                    'postalcode'  => $caddress->adr_two_postalcode,
                    'locality'    => $caddress->adr_two_locality,
                    'region'      => $caddress->adr_two_region,
                    'countryname' => $caddress->adr_two_countryname,
                    'custom1'     => ($type == 'billing') ? Tinebase_Record_Abstract::generateUID(5) : NULL
                ));
        
                $this->_addressRecords->addRecord($addressController->create($address));
        
                $i++;
            }
        }
        
        // cost centers
        
        $costcenterController = Sales_Controller_CostCenter::getInstance();
        
        $this->_costcenterRecords = new Tinebase_Record_RecordSet('Sales_Model_CostCenter');
        $ccs = array('unittest1', 'unittest2', 'unittest3');
        
        $id = 1;
        
        foreach($ccs as $title) {
            $cc = new Sales_Model_CostCenter(
                array('remark' => $title, 'number' => $id)
            );
    
            $this->_costcenterRecords->addRecord($costcenterController->create($cc));
    
            $id++;
        }
        
        // contracts
        $contractController = Sales_Controller_Contract::getInstance();
        $container = $contractController->getSharedContractsContainer();
        $cid = $container->getId();
        
        $i = 0;
        
        $customer1 = $this->_customerRecords->filter('name', 'Customer1')->getFirstRecord();
        $customer2 = $this->_customerRecords->filter('name', 'Customer2')->getFirstRecord();
        $customer3 = $this->_customerRecords->filter('name', 'Customer3')->getFirstRecord();
        
        $this->_contractRecords = new Tinebase_Record_RecordSet('Sales_Model_Contract');
        // 1.1.20xx
        $startDate = clone $this->_referenceDate;
        $endDate   = clone $startDate;
        // 1.8.20xx
        $endDate->addMonth(7);
        
        $contractData = array(
            // 13 invoices should be created from 1.1.2013 - 1.1.2014
            array(
                'number'       => 1,
                'title'        => Tinebase_Record_Abstract::generateUID(),
                'description'  => '1 unittest begin',
                'container_id' => $cid,
                'billing_point' => 'begin',
                'billing_address_id' => $this->_addressRecords->filter('customer_id', $customer1->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
                'interval' => 1,
                'start_date' => $startDate,
                'end_date' => NULL,
            ),
            
            // 2 invoices should be created on 1.5 and 1.8
            array(
                'number'       => 2,
                'title'        => Tinebase_Record_Abstract::generateUID(),
                'description'  => '2 unittest end',
                'container_id' => $cid,
                'billing_point' => 'end',
                'billing_address_id' => $this->_addressRecords->filter('customer_id', $customer2->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
                'interval' => 4,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ),
            // 4 invoices should be created on 1.4.2013, 1.7.2013, 1.10.2013 and 1.1.2014
            array(
                'number'       => 3,
                'title'        => Tinebase_Record_Abstract::generateUID(),
                'description'  => '3 unittest end',
                'container_id' => $cid,
                'billing_point' => 'end',
                'billing_address_id' => $this->_addressRecords->filter('customer_id', $customer3->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
                'interval' => 3,
                'start_date' => $startDate,
                'end_date' => NULL,
            )
        );
        
        $i = 0;
        foreach($contractData as $cd) {
            $costcenter = $this->_costcenterRecords->getByIndex($i);
            $customer   = $this->_customerRecords->getByIndex($i);
            
            $i++;
            $contract = new Sales_Model_Contract($cd);
            $contract->relations = array(
                array(
                    'own_model'              => 'Sales_Model_Contract',
                    'own_backend'            => Tasks_Backend_Factory::SQL,
                    'own_id'                 => NULL,
                    'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_model'          => 'Sales_Model_CostCenter',
                    'related_backend'        => Tasks_Backend_Factory::SQL,
                    'related_id'             => $costcenter->getId(),
                    'type'                   => 'LEAD_COST_CENTER'
                ),
                array(
                    'own_model'              => 'Sales_Model_Contract',
                    'own_backend'            => Tasks_Backend_Factory::SQL,
                    'own_id'                 => NULL,
                    'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_model'          => 'Sales_Model_Customer',
                    'related_backend'        => Tasks_Backend_Factory::SQL,
                    'related_id'             => $customer->getId(),
                    'type'                   => 'CUSTOMER'
                )
            );
            
            $this->_contractRecords->addRecord($contractController->create($contract));
        }
        
        // add contract not to bill
        $this->_contractRecords->addRecord($contractController->create(new Sales_Model_Contract(array(
            'number'       => 4,
            'title'        => Tinebase_Record_Abstract::generateUID(),
            'description'  => '4 unittest no auto',
            'container_id' => $cid,
            'billing_point' => 'end',
            'billing_address_id' => $this->_addressRecords->filter('customer_id', $customer3->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
            'interval' => 0,
            'start_date' => $startDate,
            'end_date' => NULL,
        ))));
        
        // add contract without customer
        $contract = new Sales_Model_Contract(array(
            'number'       => 5,
            'title'        => Tinebase_Record_Abstract::generateUID(),
            'description'  => '5 unittest auto not possible',
            'container_id' => $cid,
            'interval' => 1,
            'start_date' => $startDate,
            'end_date' => NULL,
            'billing_address_id' => $this->_addressRecords->filter('customer_id', $customer3->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
        ));
        
        $contract->relations = array(
            array(
                'own_model'              => 'Sales_Model_Contract',
                'own_backend'            => Tasks_Backend_Factory::SQL,
                'own_id'                 => NULL,
                'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Sales_Model_CostCenter',
                'related_backend'        => Tasks_Backend_Factory::SQL,
                'related_id'             => $costcenter->getId(),
                'type'                   => 'LEAD_COST_CENTER'
            ),
        );
        
        $this->_contractRecords->addRecord($contractController->create($contract));
        
        // add contract without address
        $contract = new Sales_Model_Contract(array(
            'number'       => 5,
            'title'        => Tinebase_Record_Abstract::generateUID(),
            'description'  => '6 unittest auto not possible',
            'container_id' => $cid,
            'interval' => 1,
            'start_date' => $startDate,
            'end_date' => NULL,
        ));
        
        $this->_contractRecords->addRecord($contractController->create($contract));
    }
    
    protected function _testFixtures()
    {
        $this->assertEquals(3, $this->_customerRecords->count());
        $this->assertEquals(9, $this->_addressRecords->count());
        $this->assertEquals(6, $this->_contractRecords->count());
        $this->assertEquals(15, $this->_contactRecords->count());
    }
}
