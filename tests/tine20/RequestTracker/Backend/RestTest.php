<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'RequestTracker_Backend_RestTests::main');
}

/**
 * Test class for RequestTracker_Backend_Rest
 * 
 * @package     RequestTracker
 */
class RequestTracker_Backend_RestTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var RequestTracker_Backend_Rest SQL Backend in test
     */
    protected $_backend;
    
    
    public function setUp()
    {
        $this->_backend = new RequestTracker_Backend_Rest();
    }
    
    public function tearDown()
    {
        
    }
    
    public function testGetQueues()
    {
        $availableQueues = $this->_backend->getQueues();
        print_r($availableQueues);
        $this->assertGreaterThan(0, $availableQueues);
    }
    
    public function testSearch()
    {
        $filter = new RequestTracker_Model_TicketFilter(array(
            array('field' => 'queue',  'operator' => 'equals', 'value' => 'service'),
            array('field' => 'status', 'operator' => 'equals', 'value' => 'new'),
        ));
        
        $tickets = $this->_backend->search($filter);
        $this->assertEquals('Tinebase_Record_RecordSet', get_class($tickets), 'wrong type');
        $this->assertGreaterThan(0, count($tickets));
        
        return $tickets;
    }
    
    public function testGet()
    {
        $ticket = $this->_backend->get(11270);
        
    }
}
    

if (PHPUnit_MAIN_METHOD == 'RequestTracker_Backend_RestTests::main') {
    RequestTracker_Backend_RestTests::main();
}
