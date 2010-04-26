<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_performanceTests::main');
}

/**
 * Test class for Json Frontend
 * 
 * @package     Calendar
 */
class Calendar_performanceTests extends PHPUnit_Framework_TestCase
{
    /**
     * Calendar Json Object
     *
     * @var Calendar_Frontend_Json
     */
    protected $_json = null;
    
    public function setUp()
    {
        // invalidate all caches
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        if (!$cache || !$cache->getOption('caching')) {
            return;
        }
        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        
        Tinebase_Core::getDb()->query("FLUSH TABLES;");
        
        $this->_json = new Calendar_Frontend_Json();
    }
    
    public function testSearchEvents()
    {
        $allUsers = Tinebase_User::getInstance()->getFullUsers('');
        
        $numSearches = 0;
        foreach ($allUsers as $user) {
            if ($numSearches > 5) {
                break;
            }
            
            echo "getting month view for {$user->accountDisplayName}\n";
            $filterData = array(
                array('field' => 'container_id', 'operator' => 'in', 'value' => array(
                    '/personal/' . $user->getId(),
                    '/shared'
                )),
                array('field' => 'period', 'operator' => 'within', 'value' => array(
                    'from'  => '2010-03-01 00:00:00',
                    'until' => '2010-04-01 00:00:00'
                )),
            );
            
            //$filter = new Calendar_Model_EventFilter($filterData);
            //Calendar_Controller_Event::getInstance()->search($filter, NULL, FALSE);
            $this->_json->searchEvents($filterData, array());
            $numSearches += 1;
            
        }
        Tinebase_Core::getDbProfiling();
    }
    
    public function _testTasks()
    {
        $allUsers = Tinebase_User::getInstance()->getFullUsers('');
        
        $numSearches = 0;
        foreach ($allUsers as $user) {
            if ($numSearches > 120) {
                break;
            }
            
            echo ".";
            $filterData = array(
                array('field' => 'container_id', 'operator' => 'in', 'value' => '/'),
            );
            
            $filter = new Tasks_Model_TaskFilter($filterData);
            Tasks_Controller_Task::getInstance()->search($filter, NULL, FALSE);
            //$json = new Tasks_Frontend_Json();
            //$json->searchTasks($filterData, array());
            $numSearches += 1;
        }
    }
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_performanceTests::main') {
    Calendar_performanceTests::main();
}
