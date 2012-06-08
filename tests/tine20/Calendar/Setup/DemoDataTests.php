<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Setup DemoData
 * 
 * @package     Calendar
 */
class Calendar_Setup_DemoDataTests extends PHPUnit_Framework_TestCase
{
    
    public function setUp()
    {
         Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
    }

    public function tearDown()
    {
         Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    public function testCreateDemoCalendars() {
        Calendar_Setup_DemoData::getInstance()->createDemoData('en', NULL, NULL, true, true);
        
        $pwulf = Tinebase_User::getInstance()->getFullUserByLoginName('pwulf');
        
        $businessCalendar = Tinebase_Container::getInstance()->getContainerByName('Calendar', 'Business', Tinebase_Model_Container::TYPE_PERSONAL, $pwulf->getId());
        $sharedCalendar = Tinebase_Container::getInstance()->getContainerByName('Calendar', 'Shared Calendar', Tinebase_Model_Container::TYPE_SHARED);
        
        $filter = new Calendar_Model_EventFilter(array(array('field' => 'container_id', 'operator' => 'equals', 'value' => $businessCalendar->getId())),'AND');
        $businessEvents = Calendar_Controller_Event::getInstance()->search($filter);
        
        $filter = new Calendar_Model_EventFilter(array(array('field' => 'container_id', 'operator' => 'equals', 'value' => $sharedCalendar->getId())),'AND');
        $sharedEvents = Calendar_Controller_Event::getInstance()->search($filter);
        
        $this->assertEquals($businessEvents->count(), 1);
        $this->assertEquals($sharedEvents->count(), 10);
    }
}
