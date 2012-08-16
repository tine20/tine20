<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

#if (!defined('PHPUnit_MAIN_METHOD')) {
#    define('PHPUnit_MAIN_METHOD', 'ActiveSync_Backend_DeviceTests::main');
#}

/**
 * Test class for ActiveSync_Backend_Policy
 * 
 * @package     ActiveSync
 */
class ActiveSync_Backend_PolicyTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var ActiveSync_Backend_Policy
     */
    protected $_policyBackend;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync backend policy tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_policyBackend = new ActiveSync_Backend_Policy();
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
    
    /**
    * test sync with non existing collection id
    */
    public function testCreatePolicy()
    {
        $policy = $this->_policyBackend->create(new ActiveSync_Model_Policy(array(
            'name' => 'test policy',
            'description' => 'this is a test policy',
            'policy_key' => Tinebase_Record_Abstract::generateUID()
        )));
    
        #var_dump($policy);
    
        return $policy;
    }
    
    public function testDeletePolicy()
    {
        $policy = $this->testCreatePolicy();
    
        $this->_policyBackend->delete($policy);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $this->_policyBackend->get($policy);
    }
}
