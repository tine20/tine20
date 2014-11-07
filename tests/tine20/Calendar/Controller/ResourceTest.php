<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

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
        
        // assert autocreated resource container
        $resourceContainer = Tinebase_Container::getInstance()->getContainerById($resource->container_id);
        $this->assertEquals($resource->name, $resourceContainer->name);
        $this->assertEquals(Tinebase_Model_Container::TYPE_SHARED, $resourceContainer->type);
        $this->assertEquals('Calendar_Model_Event', $resourceContainer->model);
        
        return $resource;
    }
    
    /**
     * testRenameResource
     * 
     * @see 0010106: rename resource does not update container name
     */
    public function testRenameResource()
    {
        $resource = $this->_getResource();
        $createResource = Calendar_Controller_Resource::getInstance()->create($resource);
        
        $calenderFrontend = new Calendar_Frontend_Json();
        $resourceArrayFromDB = $calenderFrontend->getResource($createResource->getId());
        $resourceArrayFromDB['name'] = 'Other Room';
        
        $calenderFrontend->saveResource($resourceArrayFromDB);
        
        $containerFrontend = new Tinebase_Frontend_Json_Container();
        $result = $containerFrontend->getContainer('Calendar', Tinebase_Model_Container::TYPE_SHARED, '');
        
        foreach($result as $container) {
            if ($container['id'] != $createResource->container_id) {
                continue;
            }
            $this->assertEquals($container['name'], 'Other Room');
        }
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
    
    /**
     * testDeleteResource
     * 
     * @param Calendar_Model_Resource $resource
     */
    public function testDeleteResource($resource = null)
    {
        if ($resource === null) {
            $resource = $this->testCreateResource();
        }
        
        Calendar_Controller_Resource::getInstance()->delete($resource->getId());
        
        $this->assertEquals(0, count(Calendar_Controller_Resource::getInstance()->getMultiple(array($resource->getId()))));
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Tinebase_Container::getInstance()->getContainerById($resource->container_id);
    }
    
    /**
     * testDeleteResourceWithmissingContainer
     * 
     * @see 0010421: could not delete resource if resource container already got deleted
     */
    public function testDeleteResourceMissingContainer()
    {
        $resource = $this->testCreateResource();
        
        Tinebase_Container::getInstance()->deleteContainer($resource->container_id);
        
        $this->testDeleteResource($resource);
    }
}
