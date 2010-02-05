<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: ControllerTest.php 5171 2008-10-31 13:44:14Z p.schuele@metaways.de $
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Sales_ControllerTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Sales_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sales_Controller_Contract
     */
    protected $_backend = array();
    
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
        $this->_backend = Sales_Controller_Contract::getInstance();
        
        /*
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Crm', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        if($personalContainer->count() === 0) {
            $this->_testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->_testContainer = $personalContainer[0];
        }
        
        $this->_objects['initialLead'] = new Crm_Model_Lead(array(
            'id'            => 20,
            'contract_name'     => 'PHPUnit',
            'contractstate_id'  => 1,
            'contracttype_id'   => 1,
            'contractsource_id' => 1,
            'container_id'     => $this->_testContainer->id,
            'start'         => Zend_Date::now(),
            'description'   => 'Description',
            'end'           => Zend_Date::now(),
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => Zend_Date::now(),
        )); 
        
        $this->_objects['updatedLead'] = new Crm_Model_Lead(array(
            'id'            => 20,
            'contract_name'     => 'PHPUnit',
            'contractstate_id'  => 1,
            'contracttype_id'   => 1,
            'contractsource_id' => 1,
            'container_id'     => $this->_testContainer->id,
            'start'         => Zend_Date::now(),
            'description'   => 'Description updated',
            'end'           => NULL,
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => NULL,
        )); 

        $addressbookPersonalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        $addressbookContainer = $addressbookPersonalContainer[0];
        
        $this->_objects['user'] = new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'assistent'             => 'Cornelius Weiß',
            'bday'                  => '1975-01-02 03:04:05', // new Zend_Date???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'id'                    => 120,
            'note'                  => 'Bla Bla Bla',
            'container_id'                 => $addressbookContainer->id,
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Lars',
            'n_middle'              => 'no middle name',
            'n_prefix'              => 'no prefix',
            'n_suffix'              => 'no suffix',
            'org_name'              => 'Metaways Infosystems GmbH',
            'org_unit'              => 'Tine 2.0',
            'tel_assistent'         => '+49TELASSISTENT',
            'tel_car'               => '+49TELCAR',
            'tel_cell'              => '+49TELCELL',
            'tel_cell_private'      => '+49TELCELLPRIVATE',
            'tel_fax'               => '+49TELFAX',
            'tel_fax_home'          => '+49TELFAXHOME',
            'tel_home'              => '+49TELHOME',
            'tel_pager'             => '+49TELPAGER',
            'tel_work'              => '+49TELWORK',
        )); 

        $tasksPersonalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Tasks', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        $tasksContainer = $tasksPersonalContainer[0];
        
        // create test task
        $this->_objects['task'] = new Tasks_Model_Task(array(
            // tine record fields
            'container_id'         => $tasksContainer->id,
            'created_by'           => Zend_Registry::get('currentAccount')->getId(),
            'creation_time'        => Zend_Date::now(),
            'percent'              => 70,
            'due'                  => Zend_Date::now()->addMonth(1),
            'summary'              => 'phpunit: crm test task',        
        ));
        
        // some products
        $this->_objects['someProducts'] = array(
                new Crm_Model_Product(array(
                    'id' => 1001,
                    'productsource' => 'Just a phpunit test product #1',
                    'price' => '47.11')),
                new Crm_Model_Product(array(
                    'id' => 1002,
                    'productsource' => 'Just a phpunit test product #2',
                    'price' => '18.05')),
                new Crm_Model_Product(array(
                    'id' => 1003,
                    'productsource' => 'Just a phpunit test product #3',
                    'price' => '19.78')),
                new Crm_Model_Product(array(
                    'id' => 1004,
                    'productsource' => 'Just a phpunit test product #4',
                    'price' => '20.07'))
        );
        
        // products to update
        $this->_objects['someProductsToUpdate'] = array(
                new Crm_Model_Product(array(
                    'id' => 1002,
                    'productsource' => 'Just a phpunit test product #2 UPDATED',
                    'price' => '18.05')),
                new Crm_Model_Product(array(
                    'id' => 1003,
                    'productsource' => 'Just a phpunit test product #3 UPDATED',
                    'price' => '19.78'))
        );
        
        // some contract types
        $this->_objects['someLeadTypes'] = array(
                new Crm_Model_Leadtype(array(
                    'id' => 1001,
                    'contracttype' => 'Just a phpunit test contract type #1',
                    'contracttype_translate' => 0)),
                new Crm_Model_Leadtype(array(
                    'id' => 1002,
                    'contracttype' => 'Just a phpunit test contract type #2',
                    'contracttype_translate' => 0)),
                new Crm_Model_Leadtype(array(
                    'id' => 1003,
                    'contracttype' => 'Just a phpunit test contract type #3',
                    'contracttype_translate' => 0)),
                new Crm_Model_Leadtype(array(
                    'id' => 1004,
                    'contracttype' => 'Just a phpunit test contract type #4',
                    'contracttype_translate' => 0))
        );
        
        // some contract types to update
        $this->_objects['someLeadTypesToUpdate'] = array(
                new Crm_Model_Leadtype(array(
                    'id' => 1002,
                    'contracttype' => 'Just a phpunit test contract type #2 UPDATED',
                    'contracttype_translate' => 0)),
                new Crm_Model_Leadtype(array(
                    'id' => 1003,
                    'contracttype' => 'Just a phpunit test contract type #3 UPDATED',
                    'contracttype_translate' => 0))
        );
        
        // some contract sources
        $this->_objects['someLeadSources'] = array(
                new Crm_Model_Leadsource(array(
                    'id' => 1001,
                    'contractsource' => 'Just a phpunit test contract source #1',
                    'translate' => 0)),
                new Crm_Model_Leadsource(array(
                    'id' => 1002,
                    'contractsource' => 'Just a phpunit test contract source #2',
                    'translate' => 0)),
                new Crm_Model_Leadsource(array(
                    'id' => 1003,
                    'contractsource' => 'Just a phpunit test contract source #3',
                    'translate' => 0)),
                new Crm_Model_Leadsource(array(
                    'id' => 1004,
                    'contractsource' => 'Just a phpunit test contract source #4',
                    'translate' => 0))
        );
        
        // some contract sources to update
        $this->_objects['someLeadSourcesToUpdate'] = array(
                new Crm_Model_Leadsource(array(
                    'id' => 1002,
                    'contractsource' => 'Just a phpunit test contract source #2 UPDATED',
                    'translate' => 0)),
                new Crm_Model_Leadsource(array(
                    'id' => 1003,
                    'contractsource' => 'Just a phpunit test contract source #3 UPDATED',
                    'translate' => 0))
        );
        
        // some contract states
        $this->_objects['someLeadStates'] = array(
                new Crm_Model_Leadstate(array(
                    'id' => 1001,
                    'contractstate' => 'Just a phpunit test contract state #1',
                    'probability' => 10,
                    'endscontract' => 0,
                    'translate' => 0)),
                new Crm_Model_Leadstate(array(
                    'id' => 1002,
                    'contractstate' => 'Just a phpunit test contract state #2',
                    'probability' => 10,
                    'endscontract' => 0,
                    'translate' => 0)),
                new Crm_Model_Leadstate(array(
                    'id' => 1003,
                    'contractstate' => 'Just a phpunit test contract state #3',
                    'probability' => 10,
                    'endscontract' => 0,
                    'translate' => 0)),
                new Crm_Model_Leadstate(array(
                    'id' => 1004,
                    'contractstate' => 'Just a phpunit test contract state #4',
                    'probability' => 10,
                    'endscontract' => 0,
                    'translate' => 0)),
                new Crm_Model_Leadstate(array(
                    'id' => 1005,
                    'contractstate' => 'Just a phpunit test contract state #5',
                    'probability' => 10,
                    'endscontract' => 0,
                    'translate' => 0))
                
        );
        
        // some contract states to update
        $this->_objects['someLeadStatesToUpdate'] = array(
                new Crm_Model_Leadstate(array(
                    'id' => 1002,
                    'contractstate' => 'Just a phpunit test contract state #2 UPDATED',
                    'probability' => 10,
                    'endscontract' => 0,
                    'translate' => 0)),
                new Crm_Model_Leadstate(array(
                    'id' => 1003,
                    'contractstate' => 'Just a phpunit test contract state #3 UPDATED',
                    'probability' => 10,
                    'endscontract' => 0,
                    'translate' => 0))
        );

        $this->objects['note'] = new Tinebase_Model_Note(array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',    
        ));
        */
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
