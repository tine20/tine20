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
    define('PHPUnit_MAIN_METHOD', 'Calendar_JsonTests::main');
}

/**
 * Test class for Json Frontend
 * 
 * @package     Calendar
 */
class Calendar_JsonTests extends Calendar_TestCase
{
    /**
     * Calendar Json Object
     *
     * @var Calendar_Frontend_Json
     */
    protected $_uit = null;
    
    public function setUp()
    {
        parent::setUp();
        $this->_uit = new Calendar_Frontend_Json();
    }
    
    public function testGetRegistryData()
    {
        $registryData = $this->_uit->getRegistryData();
        
        $this->assertTrue(is_array($registryData['defaultCalendar']['account_grants']));
    }
    
    public function testCreateEvent()
    {
        $eventData = $this->_getEvent()->toArray();
        
        $tag = Tinebase_Tags::getInstance()->createTag(new Tinebase_Model_Tag(array(
            'name' => 'phpunit-' . substr(Tinebase_Record_Abstract::generateUID(), 0, 10),
            'type' => Tinebase_Model_Tag::TYPE_PERSONAL
        )));
        $eventData['tags'] = array($tag->toArray());
        
        $note = new Tinebase_Model_Note(array(
            'note'         => 'very important note!',
            'note_type_id' => Tinebase_Notes::getInstance()->getNoteTypes()->getFirstRecord()->getId(),
        ));
        $eventData['notes'] = array($note->toArray());
        
        $persistentEventData = $this->_uit->saveEvent(Zend_Json::encode($eventData));
        $loadedEventData = $this->_uit->getEvent($persistentEventData['id']);
        
        $this->_assertJsonEvent($eventData, $loadedEventData, 'failed to create/load event');
        
        return $loadedEventData;
    }
    
    public function testUpdteEvent()
    {
        $event = new Calendar_Model_Event($this->testCreateEvent(), true);
        $event->dtstart->addHour(5);
        $event->dtend->addHour(5);
        $event->description = 'are you kidding?';
        
        $eventData = $event->toArray();
        unset($eventData['attendee'][1]);
        
        $updatedEventData = $this->_uit->saveEvent(Zend_Json::encode($eventData));
        //print_r($updatedEventData);
        $this->_assertJsonEvent($eventData, $updatedEventData, 'failed to update event');
        
        return $updatedEventData;
    }
    
    public function testDeleteEvent() {
        $eventData = $this->testCreateEvent();
        
        $this->_uit->deleteEvents(Zend_Json::encode(array($eventData['id'])));
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_uit->getEvent($eventData['id']);
    }
    
    public function testSearchEvents()
    {
        $eventData = $this->testCreateEvent();
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        );
        
        $searchResultData = $this->_uit->searchEvents(Zend_Json::encode($filter), Zend_Json::encode(array()));
        $resultEventData = $searchResultData['results'][0];
        
        $this->_assertJsonEvent($eventData, $resultEventData, 'failed to search event');
        return $searchResultData;
    }
    
    public function testSetAttenderStatus()
    {
        $eventData = $this->testCreateEvent();
        $numAttendee = count($eventData['attendee']);
        $eventData['attendee'][$numAttendee] = array(
            'user_id' => $this->_personas['pwulf']->getId(),
        );
        
        $updatedEventData = $this->_uit->saveEvent(Zend_Json::encode($eventData));
        $pwulf = $this->_findAttender($updatedEventData['attendee'], 'pwulf');
        
        $updatedEventData['container_id'] = $updatedEventData['container_id']['id'];
        
        $pwulf['status'] = Calendar_Model_Attender::STATUS_ACCEPTED;
        $this->_uit->setAttenderStatus(Zend_Json::encode($updatedEventData), Zend_Json::encode($pwulf), $pwulf['status_authkey']);
        
        $loadedEventData = $this->_uit->getEvent($eventData['id']);
        $loadedPwulf = $this->_findAttender($loadedEventData['attendee'], 'pwulf');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $loadedPwulf['status']);
    }
    
    public function testSetAttenderStatusViaSaveEvent()
    {
        $eventData = $this->testCreateEvent();
        $eventData['container_id'] = $eventData['container_id']['id'];
        
        // should be ok to only test test user
        $eventData['attendee'][0]['status'] = Calendar_Model_Attender::STATUS_TENTATIVE;
        $eventData['summary'] = 'This text must not be saved!';
        
        // force attendee saving w.o. event saving  
        $eventData['editGrant'] = false;
        
        $updatedEventData = $this->_uit->saveEvent(Zend_Json::encode($eventData));
        
        $loadedEventData = $this->_uit->getEvent($eventData['id']);
        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $eventData['attendee'][0]['status']);
        $this->assertNotEquals($eventData['summary'], $loadedEventData['summary'], 'event must not be updated!');
        
    }
    
    public function testUpdateRecurExceptionsFromSeriesOverDstMove()
    {
        /*
         * 1. create recur event 1 day befor dst move
         * 2. create an exception and exdate
         * 3. move dtstart from 1 over dst boundary
         * 4. test recurid and exdate by calculating series
         */
    }
    
    protected function _assertJsonEvent($expectedEventData, $eventData, $msg) {
        $this->assertEquals($expectedEventData['summary'], $eventData['summary'], $msg . ': failed to create/load event');
        
        // container, assert attendee, tags, relations
        $this->assertEquals($expectedEventData['dtstart'], $eventData['dtstart'], $msg . ': dtstart mismatch');
        $this->assertTrue(is_array($eventData['container_id']), $msg . ': failed to "resolve" container');
        $this->assertTrue(is_array($eventData['container_id']['account_grants']), $msg . ': failed to "resolve" container account_grants');
        $this->assertEquals(count($eventData['attendee']), count($expectedEventData['attendee']), $msg . ': faild to append attendee');
        $this->assertTrue(is_array($eventData['attendee'][0]['user_id']), $msg . ': failed to resolve attendee user_id');
        // NOTE: due to sorting isshues $eventData['attendee'][0] may be a non resolvable container (due to rights restrictions)
        $this->assertTrue(is_array($eventData['attendee'][0]['displaycontainer_id']) || (isset($eventData['attendee'][1]) && is_array($eventData['attendee'][1]['displaycontainer_id'])), $msg . ': failed to resolve attendee displaycontainer_id');
        $this->assertEquals(count($expectedEventData['tags']), count($eventData['tags']), $msg . ': faild to append tag');
        $this->assertEquals(count($expectedEventData['notes']), count($eventData['notes']), $msg . ': faild to create note');
    }
    
    protected function _findAttender($attendeeData, $name) {
        $attenderData = false;
        $searchedId = $this->_personas[$name]->getId();
        
        foreach ($attendeeData as $key => $attender) {
            if ($attender['user_type'] == Calendar_Model_Attender::USERTYPE_USER) {
                if (is_array($attender['user_id']) && array_key_exists('accountId', $attender['user_id'])) {
                    if ($attender['user_id']['accountId'] == $searchedId) {
                        $attenderData = $attendeeData[$key];
                    }
                }
            }
        }
        
        return $attenderData;
    }
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_JsonTests::main') {
    Calendar_JsonTests::main();
}
