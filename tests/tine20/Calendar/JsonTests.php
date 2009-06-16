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
        
        $this->assertEquals($eventData['summary'], $loadedEventData['summary'], 'failed to create/load event');
        
        // container, assert attendee, tags, relations
        $this->assertTrue(is_array($loadedEventData['container_id']), 'failed to "resolve" container');
        $this->assertTrue(is_array($loadedEventData['container_id']['account_grants']), 'failed to "resolve" container account_grants');
        $this->assertEquals(2, count($loadedEventData['attendee']), 'faild to append attendee');
        $this->assertEquals(1, count($loadedEventData['tags']), 'faild to append tag');
        $this->assertEquals(1, count($loadedEventData['notes']), 'faild to create note');
        
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
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_JsonTests::main') {
    Calendar_JsonTests::main();
}
