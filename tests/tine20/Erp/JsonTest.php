<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Erp
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add tests for linked contacts/accoutns
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Erp_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Erp_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Erp_Frontend_Json
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
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Erp Json Tests');
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
        $this->_backend = new Erp_Frontend_Json();        
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
        $contractData = $this->_backend->saveContract(Zend_Json::encode($contract->toArray()));
        
        // checks
        $this->assertEquals($contractData['id'], $contract->getId());
        $this->assertGreaterThan(0, $contractData['number']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contractData['created_by']);
        
        // cleanup
        $this->_backend->deleteContracts($contractData['id']);
        $this->_decreaseNumber();
    }
    
    /**
     * try to get a contract
     *
     */
    public function testGetContract()
    {
        $contract = $this->_getContract();
        $contractData = $this->_backend->saveContract(Zend_Json::encode($contract->toArray()));
        $contractData = $this->_backend->getContract($contractData['id']);
        
        // checks
        $this->assertEquals($contractData['id'], $contract->getId());
        $this->assertGreaterThan(0, $contractData['number']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contractData['created_by']);
        
        // cleanup
        $this->_backend->deleteContracts($contractData['id']);
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
        $contractData = $this->_backend->saveContract(Zend_Json::encode($contract->toArray()));
        
        // search & check
        $search = $this->_backend->searchContracts(Zend_Json::encode($this->_getFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($contract->title, $search['results'][0]['title']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_backend->deleteContracts($contractData['id']);
        $this->_decreaseNumber();        
    }
    
    /************ protected helper funcs *************/
    
    /**
     * get contract
     *
     * @return Erp_Model_Contract
     */
    protected function _getContract()
    {
        return new Erp_Model_Contract(array(
            'title'         => 'phpunit contract',
            'description'   => 'blabla',
            'id'            => Tinebase_Record_Abstract::generateUID()
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
            'query' => 'blabla'     
        );        
    }
    
    /**
     * decrease contracts number
     *
     */
    protected function _decreaseNumber()
    {
        $numberBackend = new Erp_Backend_Number();
        $number = $numberBackend->getNext(Erp_Model_Number::TYPE_CONTRACT, Tinebase_Core::getUser()->getId());
        // reset or delete old number
        if ($number->number == 2) {
            $numberBackend->delete($number);
        } else {
            $number->number -= 2;
            $numberBackend->update($number);
        }
    }
}
