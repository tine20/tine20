<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Resources related stuff
 * 
 * @package     Calendar
 */
class Calendar_Controller_ResourceTest extends Calendar_TestCase
{
    public function testCreateResource($grants = null)
    {
        $resource = $this->_getResource($grants);
        
        $persistentResource = Calendar_Controller_Resource::getInstance()->create($resource);
        
        $this->assertEquals($resource->name, $persistentResource->name);
        
        // assert autocreated resource container
        $resourceContainer = Tinebase_Container::getInstance()->getContainerById($resource->container_id);
        $this->assertEquals($resource->name, $resourceContainer->name);
        $this->assertEquals(Tinebase_Model_Container::TYPE_SHARED, $resourceContainer->type);
        $this->assertEquals('Calendar_Model_Event', $resourceContainer->model);
        $this->assertEquals('Meeting Room', $persistentResource->name);

        return $persistentResource;
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

        $found = false;
        foreach($result as $container) {
            if ($container['id'] != $createResource->container_id) {
                continue;
            }
            static::assertEquals($container['name'], 'Other Room');
            $found = true;
            $container = Tinebase_Container::getInstance()->get($container['id']);
            static::assertTrue(is_array($container->xprops), 'xprops is not an array');
            static::assertTrue(isset($container->xprops['Calendar']['Resource']['resource_id']),
                'xprops Calendar Resource resource_id is missing');
            static::assertTrue(isset($container->xprops['Calendar']['Resource']['resource_type']),
                'xprops Calendar Resource resource_type is missing');
            break;
        }
        static::assertTrue($found, 'did not find resources shared container');
    }

    public function testRenameContainer()
    {
        $resource = $this->_getResource();
        $createResource = Calendar_Controller_Resource::getInstance()->create($resource);

        $container = Tinebase_Container::getInstance()->get($createResource->container_id);
        $container->name = 'UNIT/' . $container->name;
        // don't dare to use the rename fn :)
        Tinebase_Container::getInstance()->update($container);

        $resource = Calendar_Controller_Resource::getInstance()->get($createResource->getId());
        $this->assertEquals($container->name, $resource->name, 'resource name not updated');
    }

    public function testDeleteContainer()
    {
        $resource = $this->_getResource();
        $createResource = Calendar_Controller_Resource::getInstance()->create($resource);

        Tinebase_Container::getInstance()->delete((string)$createResource->container_id);

        $this->setExpectedException('Tinebase_Exception_NotFound');
        Calendar_Controller_Resource::getInstance()->get((string)$createResource->container_id);
    }

    public function testManageResourceRightCreate()
    {
        $this->_removeRoleRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES);

        $this->setExpectedException('Tinebase_Exception_AccessDenied', 'No Permission.');
        $this->testCreateResource();
    }

    public function testManageResourceRightDelete()
    {
        $resource = $this->testCreateResource();
        $this->_removeRoleRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES);

        $this->setExpectedException('Tinebase_Exception_AccessDenied', 'No Permission.');
        Calendar_Controller_Resource::getInstance()->delete($resource);
    }

    public function testResourceConflict()
    {
        $resource = $this->testCreateResource([
            Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            Calendar_Model_ResourceGrants::EVENTS_READ => true,
        ]);
        
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
     * testDeleteResourceAttendee
     *
     * @see https://github.com/tine20/tine20/issues/48
     */
    public function testDeleteResourceAttendee()
    {
        $resource = $this->testCreateResource();

        // add resource to an event
        $event = $this->_getEvent();
        $event->attendee->addRecord(new Calendar_Model_Attender([
            'user_type' => Calendar_Model_Attender::USERTYPE_RESOURCE,
            'user_id'   => $resource->id
        ]));
        $newEvent = Calendar_Controller_Event::getInstance()->create($event);

        Calendar_Controller_Resource::getInstance()->delete($resource->getId());

        // check if resource attendee is removed
        $updatedEvent = Calendar_Controller_Event::getInstance()->get($newEvent->getId());
        self::assertEquals(2, count($updatedEvent->attendee), 'resource attender should be removed! '
            . print_r($updatedEvent->toArray(), true));
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

    /**
     * @param bool $_removeRoleRight
     * @return Calendar_Model_Resource
     */
    protected function _prepareTestResourceAcl($_removeRoleRight = true)
    {
        // create resource with acl for sclever
        $resource = $this->_getResource([Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true]);
        $sclever = $this->_getPersona('sclever');
        $grants = $resource->grants;
        $grants[1] = $this->_getAllCalendarGrants($sclever);
        $grants[1] = array_merge($grants[1], array_fill_keys(Calendar_Model_ResourceGrants::getAllGrants(), true));
        $resource->grants = $grants;
        $persistentResource = Calendar_Controller_Resource::getInstance()->create($resource);

        if (true === $_removeRoleRight) {
            // remove manage_resource right
            $this->_removeRoleRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES);
        }

        return $persistentResource;
    }

    /**
     * @see 0013348: improve resource permission handling
     */
    public function testResourceAclUpdateName()
    {
        $persistentResource = $this->_prepareTestResourceAcl();

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['pwulf']);
        // we will get a AccessDenied when we try to update the containers name
        $persistentResource->name = 'try to overwrite name';
        try {
            $persistentResourceUpdated = Calendar_Controller_Resource::getInstance()->update($persistentResource);
            self::fail('should not be allowed to edit resource: ' . print_r($persistentResourceUpdated->toArray(),
                    true));
        } catch (Tinebase_Exception_AccessDenied $tead) {}

        // attention we created a roll back! so _prepareTestResourceAcl data is gone now! that is why the test ends here
    }

    /**
     * @see 0013348: improve resource permission handling
     */
    public function testResourceAclUpdateDescription()
    {
        $persistentResource = $this->_prepareTestResourceAcl();

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['pwulf']);
        // now we will not update the container, but go into abstract update, which will do a get => Tinebase_Exception_AccessDenied
        $persistentResource->description = 'shalalala';
        try {
            $persistentResourceUpdated = Calendar_Controller_Resource::getInstance()->update($persistentResource);
            self::fail('should not be allowed to edit resource: ' . print_r($persistentResourceUpdated->toArray(),
                    true));
        } catch (Tinebase_Exception_AccessDenied $tead) {}

        // attention we created a roll back! so _prepareTestResourceAcl data is gone now! that is why the test ends here
    }

    /**
     * @see 0013348: improve resource permission handling
     */
    public function testResourceAclSearchWebDav()
    {
        $this->_prepareTestResourceAcl();

        $calendarShared = \Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/shared';
        if (isset($_SERVER['REQUEST_URI'])) {
            $oldRequestUri = $_SERVER['REQUEST_URI'];
        } else {
            $oldRequestUri = null;
        }
        $_SERVER['REQUEST_URI'] = '/tine20/' . $calendarShared;
        $collection = new Calendar_Frontend_WebDAV($calendarShared);
        $children = $collection->getChildren();

        static::assertTrue(is_array($children));
        /** @var Calendar_Frontend_WebDAV $child */
        foreach ($children as $child) {
            static::assertNotEquals('Meeting Room', $child->getName(), 'should not find resource in WebDav!');
        }

        // try with sclever
        Tinebase_Core::set(Tinebase_Core::USER, $this->_getPersona('sclever'));

        $collection = new Calendar_Frontend_WebDAV($calendarShared);
        $children = $collection->getChildren();

        $this->assertTrue(is_array($children));
        $this->assertTrue(count($children) > 0);
        $this->assertTrue($children[0] instanceof Calendar_Frontend_WebDAV_Container);

        $found = false;
        /** @var Calendar_Frontend_WebDAV $child */
        foreach ($children as $child) {
            if ('Meeting Room' === $child->getName()) {
                $found = true;
            }
        }
        static::assertTrue($found, 'did not find resource in WebDav');

        if (null === $oldRequestUri) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $oldRequestUri;
        }
    }

    /**
     * @see 0013348: improve resource permission handling
     */
    public function testResourceAclSearchAttenders()
    {
        $resource = $this->_prepareTestResourceAcl();

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['pwulf']);
        $filter = array(array(
            'field' => 'type',
            'operator' => 'equals',
            'value' => array(Calendar_Model_Attender::USERTYPE_RESOURCE)
        ), array(
            'field' => 'query',
            'operator' => 'contains',
            'value' => 'Meeting'
        ));
        $json = new Calendar_Frontend_Json();
        $result = $json->searchAttenders($filter);
        self::assertEquals(0, $result['resource']['totalcount'], 'should not find resource');

        // try with sclever
        Tinebase_Core::set(Tinebase_Core::USER, $this->_getPersona('sclever'));
        $this->_removeRoleRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES);

        $result = $json->searchAttenders($filter);
        self::assertEquals(1, $result['resource']['totalcount'], 'resource not found');

        $filter[] = ['field' => 'resourceFilter', 'value' => [
            ['field' => 'requireFreeBusyGrant', 'value' => 1],
        ]];
        $result = $json->searchAttenders($filter);
        self::assertEquals(1, $result['resource']['totalcount'], 'resource not found');

        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($resource->container_id, true);
        $grant = $grants->find('account_id', $this->_getPersona('sclever')->getId());
        $grant->{Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY} = false;
        $grant->{Calendar_Model_ResourceGrants::RESOURCE_INVITE} = false;
        $grant->{Calendar_Model_ResourceGrants::EVENTS_FREEBUSY} = false;
        Tinebase_Container::getInstance()->setGrants($resource->container_id, $grants, true, false);

        $result = $json->searchAttenders($filter);
        self::assertEquals(0, $result['resource']['totalcount'], 'should not find resource');
    }

    /**
     * @see 0013348: improve resource permission handling
     */
    public function testResourceAclSearchTinebase()
    {
        $this->_prepareTestResourceAcl();

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['pwulf']);
        $filter = array(array(
            'field' => 'type',
            'operator' => 'equals',
            'value' => Tinebase_Model_Container::TYPE_SHARED,
        ), array(
            'field' => 'name',
            'operator' => 'contains',
            'value' => 'Meeting'
        ), array(
            'field' => 'application_id',
            'operator' => 'equals',
            'value' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()
        ));
        $json = new Tinebase_Frontend_Json_Container();

        $containers = $json->searchContainers($filter, null);

        $this->assertTrue(is_array($containers) && isset($containers['results']) && is_array($containers['results']));
        foreach ($containers['results'] as $container) {
            if ('Meeting Room' === $container['name']) {
                static::fail('should not find resource in shared containers!');
            }
        }

        // try with sclever
        Tinebase_Core::set(Tinebase_Core::USER, $this->_getPersona('sclever'));
        $this->_removeRoleRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES);
        $containers = $json->searchContainers($filter, null);

        $this->assertTrue(is_array($containers) && isset($containers['results']) && is_array($containers['results']));
        $this->assertTrue(count($containers['results']) > 0, 'did not find resource in shared containers!');

        $found = false;
        foreach ($containers['results'] as $container) {
            if ('Meeting Room' === $container['name']) {
                $found = true;
            }
        }
        static::assertTrue($found, 'did not find resource in shared containers!');
    }

    /**
     * @see 0013348: improve resource permission handling
     */
    public function testResourceAclGetTinebase()
    {
        $resource = $this->_prepareTestResourceAcl();

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['pwulf']);
        $json = new Tinebase_Frontend_Json_Container();
        $containers = $json->getContainer('Calendar', Tinebase_Model_Container::TYPE_SHARED, null);

        $this->assertTrue(is_array($containers));
        foreach ($containers as $container) {
            if ('Meeting Room' === $container['name']) {
                static::fail('should not find resource in shared containers!');
            }
        }

        // try with sclever
        Tinebase_Core::set(Tinebase_Core::USER, $this->_getPersona('sclever'));
        $this->_removeRoleRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES);
        $containers = $json->getContainer('Calendar', Tinebase_Model_Container::TYPE_SHARED, null);

        $this->assertTrue(is_array($containers));
        $this->assertTrue(count($containers) > 0);

        $found = false;
        /** @var Tinebase_Model_Container $child */
        foreach ($containers as $container) {
            if ('Meeting Room' === $container['name']) {
                $found = true;
                break;
            }
        }
        static::assertTrue($found, 'did not find resource in shared containers!');
        static::assertTrue(isset($container['xprops']['Calendar']['Resource']['resource_id']) &&
            $container['xprops']['Calendar']['Resource']['resource_id'] === $resource->getId(),
            'xprops are not properly set on container: ' . print_r($container, true));
        static::assertTrue(isset($container['xprops']['Tinebase']['Container']['GrantsModel']) &&
            $container['xprops']['Tinebase']['Container']['GrantsModel'] === Calendar_Model_ResourceGrants::class,
            'xprops are not properly set on container: ' . print_r($container, true));
    }

    public function testUpdateEventLocations()
    {
        // create event with resource
        $resource = $this->testCreateResource();
        $ct = new Calendar_Controller_EventTests();
        $ct->setUp();
        $event = $ct->_getEvent(true);
        $event->location = $resource->name;
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_RESOURCE,
            'user_id'   => $resource->getId()
        ))));
        Calendar_Controller_Event::getInstance()->create($event);

        // rename resource
        $resource->name = 'testUpdateEventLocations';
        Calendar_Controller_Resource::getInstance()->update($resource);

        // fetch event
        $hopefullyUpdatedEvent = Calendar_Controller_Event::getInstance()->get($event->getId());


        $this->assertEquals($hopefullyUpdatedEvent->location, $resource->name);

        $ct->tearDown();
    }
}
