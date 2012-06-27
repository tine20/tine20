<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Redis
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * event hooks
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'CustomEventHooks.php';

/**
 * redis worker
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'RedisWorker.php';

/**
 * Test class for Tinebase_Redis_Queue
 */
class Tinebase_Redis_QueueTest extends PHPUnit_Framework_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Redis_QueueTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Redis queue
     *
     * @var Tinebase_Redis_Queue
     */
    protected $_redis = NULL;

    /**
     * redis config
     * 
     * @var array
     */
    protected $_redisConfig = NULL;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('redis extension not found');
        }
        
        $config = Tinebase_Config::getInstance()->get('redis', NULL);
        if ($config === NULL) {
            $this->markTestSkipped('redis not configured');
        }
        $this->_redisConfig = $config->toArray();
        
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $this->_redis = new Tinebase_Redis_Queue($this->_redisConfig);
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
     * testAddUserEvent
     */
    public function testAddUserEvent()
    {
        $queueSizeBefore = $this->_redis->getQueueSize();
        $user = $this->_createUser();
        $queueSizeAfter = $this->_redis->getQueueSize();
        $this->assertEquals($queueSizeBefore + 1, $queueSizeAfter);
        
        $event = $this->_redis->pop();
        $this->assertEquals('create', $event->action);
        $this->assertTrue(isset($event->user));
        $this->assertEquals($user->accountLoginName, $event->user->accountLoginName);
    }
    
    /**
     * create user
     * 
     * @return Tinebase_Model_FullUser
     */
    protected function _createUser()
    {
        return Admin_Controller_User::getInstance()->create(new Tinebase_Model_FullUser(array(
            'accountLoginName'     => 'redissepp',
            'accountPrimaryGroup'  => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
            'accountLastName'      => 'redis',
            'accountFullName'      => 'sepp',
        )), '', '');
    }
    
    /**
     * test redis worker
     */
    public function testWorker()
    {
        $user = $this->_createUser();
        
        $worker = new RedisWorker(new Zend_Config(array(
            'redis' => $this->_redisConfig
        )));
        
        ob_start();
        $worker->run();
        $out = ob_get_clean();
        
        $this->assertEquals('handled create job.', $out);
    }
}
