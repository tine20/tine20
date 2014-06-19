<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Resources related stuff
 * 
 * @package     Calendar
 */
class Calendar_Controller_ResourceTest extends Calendar_TestCase
{
    /**
     * Tinebase_Record_RecordSet
     */
    protected $_toCleanup;
    
    public function setUp()
    {
        parent::setUp();
        $this->_toCleanup = new Tinebase_Record_RecordSet('Calendar_Model_Resource');
    }
    
    public function tearDown()
    {
        if (! $this->_transactionId) {
            Calendar_Controller_Resource::getInstance()->delete($this->_toCleanup->id);
        }
        
        parent::tearDown();
    }
    
    public function testCreateResource()
    {
        $resource = $this->_getResource();
        
        $persistentResource = Calendar_Controller_Resource::getInstance()->create($resource);
        $this->_toCleanup->addRecord($persistentResource);
        
        $this->assertEquals($resource->name, $persistentResource->name);
        
        return $resource;
    }
    
    public function testResourceConfict()
    {
        $resource = $this->testCreateResource();
        
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_type' => Calendar_Model_Attender::USERTYPE_RESOURCE,
                'user_id'   => $resource->id
            ),
        ));
        $persistentEvent = Calendar_Controller_Event::getInstance()->create($event);
        
        // we need to adopt conainer through backend, to bypass rights control
        $persistentEvent->container_id = $this->_getPersonasDefaultCals('rwright')->getId();
        $persistentEvent->organizer = $this->_getPersonasContacts('rwright')->getId();
        $this->_backend->update($persistentEvent);

        
        // try to search
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'attender', 'operator' => 'in', 'value' => array(
                array(
                    'user_type' => Calendar_Model_Attender::USERTYPE_RESOURCE,
                    'user_id'   => $resource->getId()
                )
            ))
        )), NULL, FALSE, FALSE);
        
        $this->assertEquals(1, count($events));
        $this->assertEquals($resource->getId(), $events[0]->attendee[0]->user_id);

        // now let's provoke a resource conflict
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_type' => Calendar_Model_Attender::USERTYPE_RESOURCE,
                'user_id'   => $resource->id
            ),
        ));
        $this->setExpectedException('Calendar_Exception_AttendeeBusy');
        $conflictingEvent = Calendar_Controller_Event::getInstance()->create($event, TRUE);
    }
}
