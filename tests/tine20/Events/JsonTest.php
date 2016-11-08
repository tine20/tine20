<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Events
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Events_JsonTest
 */
class Events_JsonTest extends Events_TestCase
{
    /**
     * @var Events_Frontend_Json
     */
    protected $_json = array();

    /**
     * Backend
     *
     * @var Events_Frontend_Json
     */
    public function setUp()
    {
        // enable Events app
        Tinebase_Application::getInstance()->setApplicationState(array(
            Tinebase_Application::getInstance()->getApplicationByName('Events')->getId()
        ), Tinebase_Application::ENABLED);

        parent::setUp();
        $this->_json = new Events_Frontend_Json();
    }

    /**
     * tests if model gets created properly
     */
    public function testModelCreation()
    {
        $fields = Events_Model_Event::getConfiguration()->getFields();
        $this->assertArrayHasKey('container_id', $fields);

        $filters = Events_Model_Event::getConfiguration()->getFilterModel();
        $this->assertArrayHasKey('container_id', $filters['_filterModel']);
    }

    /**
     * test creation of an Event
     */
    public function testCreateEvent()
    {
        $Event = $this->_getEvent();

        $this->assertTrue($Event instanceof Events_Model_Event, 'We have no record the record is instance of wrong object');

        $EventArray = $Event->toArray();
        $this->assertTrue(is_array($EventArray), '$EventArray is not an array');

        $returnedRecord = $this->_json->saveEvent($EventArray);

        $returnedGet = $this->_json->getEvent($returnedRecord['id'], 0, '');
        $this->assertEquals($Event['title'], $returnedGet['title']);

        return $returnedRecord;
    }

    /**
     * test search for Events
     */
    public function testSearchEvents()
    {
        $record = $this->testCreateEvent();
        $recordID = $record['id'];

        $searchIDFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $recordID));
        $searchDefaultFilter = $this->_getFilter();
        $mergedSearchFilter = array_merge($searchIDFilter, $searchDefaultFilter);

        $returned = $this->_json->searchEvents($mergedSearchFilter, $this->_getPaging());

        $this->assertEquals($returned['totalcount'], 1);

        $count = 0;
        foreach ($returned as $value => $key) {
            if (is_array($key)) {
                foreach ($key as $result) {
                    if (is_array($result) && isset($result['id'])) {
                        if ($result['id'] == $recordID) {
                            $count++;
                        }
                    }
                }
            }
        }
        $this->assertEquals($count, 1);
    }

    /**
     * test testSearchEvents for tags of an Event
     */
    public function testSearchEventsTags()
    {
        $EventWithTag = $this->testCreateEvent();
        $this->testCreateEvent();

        $EventWithTag['tags'] = array(array(
            'name' => 'supi',
            'type' => Tinebase_Model_Tag::TYPE_PERSONAL,
        ));
        $EventWithTag = $this->_json->saveEvent($EventWithTag);
        $EventTagID = $EventWithTag['tags'][0]['id'];

        $searchTagFilter = array(array('field' => 'tag', 'operator' => 'equals', 'value' => $EventTagID));

        $returned = $this->_json->searchEvents($searchTagFilter, $this->_getPaging());

        $this->assertEquals(1, $returned['totalcount']);
    }

    /**
     * test deletetion of an Event
     */
    public function testDeleteEvents()
    {
        $Event = $this->testCreateEvent();
        $EventID = $Event['id'];

        $returnValueDeletion = $this->_json->deleteEvents($EventID);
        $this->assertEquals($returnValueDeletion['status'], 'success');

        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_json->getEvent($EventID);
    }

    /**
     * test creating a Event with a Calendar event relation
     *
     * @param bool $checkBusyConflicts
     * @return array event with relations
     */
    public function testRelatedCalEvent($checkBusyConflicts = true)
    {
        $caljson = new Calendar_Frontend_Json();

        $event = $this->_getEvent();

        $mainCalEvent = new Calendar_Model_Event(array(
            'summary' => $event['title'],
            'dtstart' => $event['event_dtstart'],
            'dtend' => $event['event_dtend']
        ));

        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            new Calendar_Model_Attender(array(
                    'user_id' => $this->_originalTestUser->contact_id,
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'role' => Calendar_Model_Attender::ROLE_REQUIRED,
                    'status_authkey' => Tinebase_Record_Abstract::generateUID(),
                )
            )
        ));
        $assemblyCalEvent = new Calendar_Model_Event(array(
            'summary' => 'assembly: ' . $event['title'],
            'dtstart' => '2015-03-24 06:00:00',
            'dtend' => '2015-03-24 06:15:00',
            'attendee' => $attendee,
        ));

        $disassemblyCalEvent = new Calendar_Model_Event(array(
            'summary' => 'Disassembly: ' . $event['title'],
            'dtstart' => '2015-03-26 06:00:00',
            'dtend' => '2015-03-26 06:15:00',
            'attendee' => $attendee,
        ));

        $relatedEvents = array('MAIN' => $mainCalEvent, 'ASSEMBLY' => $assemblyCalEvent, 'DISASSEMBLY' => $disassemblyCalEvent);

        $relations = array();
        foreach ($relatedEvents as $type => $calEvent) {
            $calEvent['container_id'] = Events_Controller_Event::getDefaultEventsCalendar()->getId();
            $calEvent['description'] = $event['title'];
            $relations[] = array(
                'related_model' => 'Calendar_Model_Event',
                'related_degree' => 'parent',
                'type' => $type,
                'related_backend' => 'Sql',
                'related_record' => $calEvent
            );
        }
        $event['relations'] = new Tinebase_Record_RecordSet('Tinebase_Model_Relation', $relations, /* skip validation */
            true);

        $event = $this->_json->saveEvent($event->toArray(), $checkBusyConflicts);

        $this->assertEquals(3, count($event['relations']));
        $firstCalEventRelation = $event['relations'][0];
        $this->assertEquals('Calendar_Model_Event', $firstCalEventRelation['related_model']);
        $this->assertEquals('parent', $firstCalEventRelation['related_degree']);
        $this->assertEquals($event['title'], $firstCalEventRelation['related_record']['description']);

        $relatedCalEvent = $caljson->getEvent($firstCalEventRelation['related_id'], '');
        $this->assertNotEmpty($relatedCalEvent);

        return $event;
    }

    /**
     * check if container and organizer are resolved
     */
    public function testResolvingOfRelationFields()
    {
        $event = $this->testRelatedCalEvent();

        $firstCalEvent = $event['relations'][0]['related_record'];
        $resolvedFields = array('container_id', 'organizer');
        foreach ($resolvedFields as $field) {
            $this->assertTrue(is_array($firstCalEvent[$field]), $field . ' is not resolved:' . print_r($firstCalEvent, true));
        }
    }

    /**
     * check if container and organizer are resolved
     */
    public function testUpdateRelation()
    {
        $event = $this->testRelatedCalEvent();
        $descriptiontext = 'UPDATED DESCRIPTION';
        for ($i = 0; $i < count($event['relations']); $i++) {
            $event['relations'][$i]['related_record']['description'] = $descriptiontext;
            if ($event['relations'][$i]['type'] === 'ASSEMBLY') {
                $event['relations'][$i]['related_record']['dtend'] = '2015-03-24 07:30:00';
            }
        }

        $updatedEvent = $this->_json->saveEvent($event);

        $assemblyFound = false;
        for ($i = 0; $i < count($event['relations']); $i++) {
            $this->assertEquals($descriptiontext, $updatedEvent['relations'][$i]['related_record']['description'],
                'description was not updated: ' . print_r($updatedEvent['relations'][$i]['related_record'], true));
            if ($event['relations'][$i]['type'] === 'ASSEMBLY') {
                $assemblyFound = true;
                $this->assertEquals('2015-03-24 07:30:00', $updatedEvent['relations'][$i]['related_record']['dtend'],
                    'dtend was not updated: ' . print_r($updatedEvent['relations'][$i]['related_record'], true));
            }
        }

        $this->assertTrue($assemblyFound, 'did not find assembly relation');
    }

    /**
     * check if related calendar events are deleted
     *
     * @see #1111 - Sondertermine löschen
     */
    public function testDeleteRelatedEvents()
    {
        $event = $this->testRelatedCalEvent();

        $this->_json->deleteEvents(array($event['id']));

        foreach ( $event['relations'] as $relation) {
            try {
                $calEvent = Calendar_Controller_Event::getInstance()->get($relation['related_id']);
                $this->fail('related cal event should be deleted: ' . print_r($calEvent->toArray(), true));
            } catch (Tinebase_Exception_NotFound $e) {
                $this->assertTrue($e instanceof Tinebase_Exception_NotFound, 'expecting Tinebase_Exception_NotFound, got ' . $e);
            }
        }
    }

    /**
     * check if creation of parallel Events throws attendee busy exception
     */
    public function testConflict()
    {
        $this->testRelatedCalEvent();
        try {
            $this->testRelatedCalEvent();
            $this->fail('should throw Calendar_Exception_AttendeeBusy');
        } catch (Calendar_Exception_AttendeeBusy $ceab) {
            $this->assertEquals('event attendee busy conflict', $ceab->getMessage());
        }
    }

    /**
     * check if creation of parallel Events is allowed with disabled free busy check
     */
    public function testFreeBusyCheckDisabled()
    {
        $this->testRelatedCalEvent();
        // should not fail
        $this->testRelatedCalEvent(/* $checkBusyConflicts */ false);
    }
}
