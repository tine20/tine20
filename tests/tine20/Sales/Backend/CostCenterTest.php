<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Sales_Backend_CostCenterTest::main');
}

/**
 * Test class for Sales_Backend_CostCenterTest
 */
class Sales_Backend_CostCenterTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * the contract backend
     *
     * @var Sales_Backend_CostCenter
     */
    protected $_backend;
    
    /**
     * the costcenter number used for the tests
     * 
     * @var string
     */
    protected $_costCenterNumber;
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Sales CostCenter Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $this->_backend = new Sales_Backend_CostCenter();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * create new cost center
     *
     */
    public function testCreateCostCenter()
    {
        $cc = $this->_getCostCenter();
        $created = $this->_backend->create($cc);
        
        $this->assertEquals($created->number, $cc->number);
        $this->assertEquals($created->remark, $cc->remark);
        
        $this->_backend->delete($cc);
    }

    /**
     * get contract
     *
     * @return Sales_Model_CostCenter
     */
    protected function _getCostCenter()
    {
        $this->_costCenterNumber = Tinebase_Record_Abstract::generateUID();
        $cc = new Sales_Model_CostCenter(array(
            'number'  => $this->_costCenterNumber,
            'remark'  => 'blabla'
        ), TRUE);
        

        return $cc;
    }
}
