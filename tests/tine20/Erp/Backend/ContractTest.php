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
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Erp_Backend_ContractTest::main');
}

/**
 * Test class for Erp_Backend_ContractTest
 */
class Erp_Backend_ContractTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * the contract backend
     *
     * @var Erp_Backend_Contract
     */
    protected $_backend;
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Erp Contract Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->_backend = new Erp_Backend_Contract();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     */
    protected function tearDown()
    {
    }
    
    /**
     * create new contract
     *
     */
    public function testCreateContract()
    {
        $contract = $this->_getContract();
        $created = $this->_backend->create($contract);
        
        $this->assertEquals($created->title, $contract->title);
        $this->assertGreaterThan(0, $created->number);
        $this->assertEquals($created->container_id, Tinebase_Container::getInstance()->getContainerByName('Erp', 'Shared Contracts', 'shared')->getId());
        
        $this->_backend->delete($contract);
        $this->_decreaseNumber();
    }

    /**
     * get contract
     *
     * @return Erp_Model_Contract
     */
    protected function _getContract()
    {
        $contract = new Erp_Model_Contract(array(
            'title'         => 'phpunit contract',
            'description'   => 'blabla'
        ), TRUE);
        
        // add container
        $contract->container_id = Tinebase_Container::getInstance()->getContainerByName('Erp', 'Shared Contracts', 'shared')->getId();
        
        // add number
        $numberBackend = new Erp_Backend_Number();
        $number = $numberBackend->getNext(Erp_Model_Number::TYPE_CONTRACT, Tinebase_Core::getUser()->getId());
        $contract->number = $number->number;

        return $contract;
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
