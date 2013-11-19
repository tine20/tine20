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
class Sales_ControllerTest extends PHPUnit_Framework_TestCase
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
    protected function setUp()
    {
        $this->_backend = Sales_Controller_Contract::getInstance();
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
        
        $this->setExpectedException('Tinebase_Exception_Duplicate');

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
}
