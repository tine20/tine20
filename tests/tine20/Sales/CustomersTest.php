<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Sales_CustomersTest extends PHPUnit_Framework_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Sales Controller Tests');
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
    
    /*
     * returns relation
    */
    protected function _getRelation($contract, $ipnet) {
        $r = new Tinebase_Model_Relation();
        $ra = array(
            'own_model' => 'Sales_Model_Coustomer',
            'own_backend' => 'Sql',
            'own_id' => $ipnet->getId(),
            'own_degree' => 'sibling',
            'remark' => 'phpunit test',
            'related_model' => 'Sales_Model_Contract',
            'related_backend' => 'Sql',
            'related_id' => $contract->getId(),
            'type' => 'CONTRACT');
        $r->setFromArray($ra);
        return $r;
    }
    
    public function testLifecycleCustomer()
    {
        $container = Tinebase_Container::getInstance()->getSharedContainer(
            Tinebase_Core::getUser()->getId(), 
            Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'WRITE'
        );
        
        $containerContracts = Tinebase_Container::getInstance()->getSharedContainer(
            Tinebase_Core::getUser()->getId(),
            Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
            'WRITE'
        );
        
        $container = $container->getFirstRecord();
        
        $contactController = Addressbook_Controller_Contact::getInstance();
        $contractController = Sales_Controller_Contract::getInstance();
        
        $contact1 = $contactController->create(new Addressbook_Model_Contact(
            array('n_given' => 'Yiting', 'n_family' => 'Huang', 'container_id' => $container->getId()))
        );
        $contact2 = $contactController->create(new Addressbook_Model_Contact(
            array('n_given' => 'Hans Friedrich', 'n_family' => 'Ochs', 'container_id' => $container->getId()))
        );
        
        $contract = $contractController->create(new Sales_Model_Contract(
            array('number' => '123', 'title' => 'Testing', 'description' => 'test123', 'container_id' => $containerContracts->getId())
        ));
        
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
            'adr_pobox'   => '7777777',
            
            'billing' => array(array(
                'prefix1' => 'no prefix1',
                'prefix2' => 'no prefix2',
                'street' => 'Mao st. 1',
                'postalcode' => '2345425',
                'locality' => 'Shenzen',
                'region' => 'Sichuan',
                'countryname' => 'China',
                'pobox'   => '999999999',
                'custom1' => '123456789',
                'type' => 'billing',
                'relations' => array(
                    array(
                        'own_model' => 'Sales_Model_Address',
                        'own_backend' => 'Sql',
                        'own_degree' => 'sibling',
                        'remark' => 'phpunit test',
                        'related_model' => 'Sales_Model_Contract',
                        'related_backend' => 'Sql',
                        'related_id' => $contract->getId(),
                        'type' => 'CONTRACT'
                    )
                )
            )),
            'delivery' => array(array(
                'prefix1' => 'no prefix 1',
                'prefix2' => 'no prefix 2',
                'street' => 'Mao st. 2',
                'postalcode' => '1',
                'locality' => 'Peking',
                'region' => 'Peking',
                'countryname' => 'China',
                'pobox'   => '888888888',
                'type' => 'delivery'
            ))
        );
        
        $json = new Sales_Frontend_Json();
        $retVal = $json->saveCustomer($customerData);
        
        $this->assertEquals(4294967, $retVal["number"]);
        $this->assertEquals("Worldwide Electronics International", $retVal["name"]);
        $this->assertEquals("http://wwei.cn", $retVal["url"]);
        $this->assertEquals(NULL, $retVal['description']);
        
        $this->assertEquals('Yiting', $retVal['cpextern_id']['n_given']);
        $this->assertEquals('Huang', $retVal['cpextern_id']['n_family']);
        
        $this->assertEquals('Hans Friedrich', $retVal['cpintern_id']['n_given']);
        $this->assertEquals('Ochs', $retVal['cpintern_id']['n_family']);

        // @see: 0009378: create a test for resolving dependent records recursively
        $this->assertEquals('Sales_Model_Contract', $retVal['billing'][0]['relations'][0]['related_model']);
        $this->assertEquals('Testing', $retVal['billing'][0]['relations'][0]['related_record']['title']);
        
        // test billing and delivery addresses get resolved
        $this->assertTrue(is_array($retVal['delivery']));
        $this->assertEquals(1, count($retVal['delivery']));
        $this->assertEquals('Peking', $retVal['delivery'][0]['locality']);
        $this->assertEquals('China', $retVal['delivery'][0]['countryname']);
        
        $this->assertTrue(is_array($retVal['billing']));
        $this->assertEquals(1, count($retVal['billing']));
        $this->assertEquals('Shenzen', $retVal['billing'][0]['locality']);
        $this->assertEquals('China', $retVal['billing'][0]['countryname']);
    }
}
