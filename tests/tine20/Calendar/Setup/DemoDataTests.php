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
class Calendar_Setup_DemoDataTests extends TestCase
{
    public function testCreateDemoCalendars()
    {
        ob_start();
        Calendar_Setup_DemoData::getInstance()->createDemoData(array('locale' => 'en'));
        ob_end_clean();
        
        $pwulf = Tinebase_User::getInstance()->getFullUserByLoginName('pwulf');
        
        $businessCalendar = Tinebase_Container::getInstance()->getContainerByName(
            Calendar_Model_Event::class, 'Business', Tinebase_Model_Container::TYPE_PERSONAL, $pwulf->getId());
        $sharedCalendar = Tinebase_Container::getInstance()->getContainerByName(
            Calendar_Model_Event::class, 'Shared Calendar', Tinebase_Model_Container::TYPE_SHARED);

        static::assertSame(Calendar_Setup_DemoData::getInstance()->getSharedCalendar()->getId(),
            $sharedCalendar->getId());

        $cce = Calendar_Controller_Event::getInstance();
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $businessCalendar->getId())
        ),'AND');
        $businessEvents = $cce->search($filter);
        $cce->deleteByFilter($filter);
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $sharedCalendar->getId())
        ),'AND');
        $sharedEvents = $cce->search($filter);
        $cce->deleteByFilter($filter);
        
        $this->assertEquals($businessEvents->count(), 1);
        $this->assertEquals($sharedEvents->count(), 10);
    }
}
