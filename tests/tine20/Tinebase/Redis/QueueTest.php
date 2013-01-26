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
#require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'RedisWorker.php';

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
        
        if (!isset(Tinebase_Core::getConfig()->actionqueue) || 
            !isset(Tinebase_Core::getConfig()->actionqueue->backend) || 
            ucfirst(strtolower(Tinebase_Core::getConfig()->actionqueue->backend)) != Tinebase_ActionQueue::BACKEND_REDIS) {
            $this->markTestSkipped('no redis actionqueue configured');
        }
        
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
    
    /**
     * testAddUserEvent
     */
    public function testAddUserEvent()
    {
        $queueSizeBefore = Tinebase_ActionQueue::getInstance()->getQueueSize();
        $testJobId = Tinebase_ActionQueue::getInstance()->queueAction('FooBar.test', array('key' => 'value'));
        $queueSizeAfter = Tinebase_ActionQueue::getInstance()->getQueueSize();
        
        $this->assertGreaterThan($queueSizeBefore, $queueSizeAfter);

        // loop over all jobs until testJob appears
        while($jobId = Tinebase_ActionQueue::getInstance()->waitForJob()) {
            if ($jobId != $testJobId) {
                Tinebase_ActionQueue::getInstance()->delete($jobId);
            } else {
                break;
            }
        }
        $this->assertEquals($jobId, $testJobId);
        
        $job   = Tinebase_ActionQueue::getInstance()->receive($jobId);
        Tinebase_ActionQueue::getInstance()->delete($jobId);
        
        $this->assertTrue(is_array($job));
        $this->assertEquals('FooBar.test', $job['action']);
        $this->assertEquals(array(array('key' => 'value')), $job['params']);
    }
    
    /**
     * test redis worker
     */
    public function testWorker()
    {
        $this->markTestSkipped();
        
        $user = $this->_createUser();
        
        $worker = new RedisWorker(new Zend_Config(array(
            'redis' => $this->_redisConfig
        )));
        
        ob_start();
        $worker->run();
        $out = ob_get_clean();
        
        $this->assertEquals('handled create job.', $out);
    }
    
    /**
     * create user
     * 
     * @return Tinebase_Model_FullUser
     */
    protected function _createUser()
    {
        return Tinebase_ActionQueue::getInstance()->queueAction(array('key' => 'value'));
    }
}
