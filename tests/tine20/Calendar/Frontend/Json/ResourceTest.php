<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 20018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Calendar Json Frontend Resource related functions
 *
 * @package     Calendar
 */
class Calendar_Frontend_Json_ResourceTest extends Calendar_TestCase
{
    /**
     * @var Calendar_Frontend_Json
     */
    protected $jsonFE;

    /**
     * properties on this to be reset
     *
     * @var array
     */
    protected $selfReset = [];

    /**
     * set up tests
     */
    public function setUp()
    {
        parent::setUp();

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        $this->jsonFE = new Calendar_Frontend_Json();
        Tinebase_Container::getInstance()->resetClassCache();
    }

    /**
     * tear down tests
     */
    public function tearDown()
    {
        foreach ($this->selfReset as $prop => $val) {
            $this->{$prop} = $val;
        }

        parent::tearDown();
    }

    /**
     * @param null|Tinebase_Model_User $_user
     * @param null|array $_grants
     * @return array
     */
    protected function _prepareTestResourceAcl($_user = null, $_grants = null)
    {
        // create resource with acl for sclever
        $resource = $this->_getResource();
        $user = $_user ?: $this->_getPersona('sclever');
        if (null !== $_grants) {
            $grants = $_grants;
        } else {
            $grants = [[]];
            foreach (Calendar_Model_ResourceGrants::getAllGrants() as $grant) {
                $grants[0][$grant] = true;
            }
        }
        if (null === $_grants || (!empty($_grants) && !isset($grants[0]['account_id']))) {
            $grants[0]['account_id'] = $user->getId();
            $grants[0]['account_type'] = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
        }
        $grants[1]['account_id'] = $this->_getPersona('pwulf')->getId();
        $grants[1]['account_type'] = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
        $grants[1][Calendar_Model_ResourceGrants::RESOURCE_ADMIN] = true;
        $grants[1][Calendar_Model_ResourceGrants::EVENTS_ADD] = true;
        $grants[1][Calendar_Model_ResourceGrants::EVENTS_EDIT] = true;
        $grants[1][Calendar_Model_ResourceGrants::EVENTS_READ] = true;
        $resource->grants = $grants;

        $oldUser = Tinebase_Core::getUser();
        try {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_getPersona('pwulf'));
            return $this->jsonFE->saveResource($resource->toArray(true));
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
        }
    }

    protected function _checkResourceGrants($_containerId, $_grant)
    {
        $expectedGrants = [$_grant];
        switch ($_grant) {
            case Calendar_Model_ResourceGrants::RESOURCE_EDIT:
                $expectedGrants = [
                    Calendar_Model_ResourceGrants::RESOURCE_READ,
                    Calendar_Model_ResourceGrants::RESOURCE_EDIT,
                ];
                break;
            case Calendar_Model_ResourceGrants::RESOURCE_ADMIN:
                $expectedGrants = [
                    Calendar_Model_ResourceGrants::RESOURCE_ADMIN,
                    Calendar_Model_ResourceGrants::RESOURCE_EDIT,
                    Calendar_Model_ResourceGrants::RESOURCE_EXPORT,
                    Calendar_Model_ResourceGrants::RESOURCE_INVITE,
                    Calendar_Model_ResourceGrants::RESOURCE_READ,
                    Calendar_Model_ResourceGrants::RESOURCE_SYNC,
                ];
                break;
            case Calendar_Model_ResourceGrants::EVENTS_ADD:
                $expectedGrants = [
                    Calendar_Model_ResourceGrants::EVENTS_ADD,
                    Calendar_Model_ResourceGrants::GRANT_ADD,
                ];
                break;
            case Calendar_Model_ResourceGrants::EVENTS_DELETE:
                $expectedGrants = [
                    Calendar_Model_ResourceGrants::EVENTS_DELETE,
                    Calendar_Model_ResourceGrants::GRANT_DELETE,
                ];
                break;
            case Calendar_Model_ResourceGrants::EVENTS_EDIT:
                $expectedGrants = [
                    Calendar_Model_ResourceGrants::EVENTS_EDIT,
                    Calendar_Model_ResourceGrants::GRANT_EDIT,
                ];
                break;
            case Calendar_Model_ResourceGrants::EVENTS_EXPORT:
                $expectedGrants = [
                    Calendar_Model_ResourceGrants::EVENTS_EXPORT,
                    Calendar_Model_ResourceGrants::GRANT_EXPORT,
                ];
                break;
            case Calendar_Model_ResourceGrants::EVENTS_FREEBUSY:
                $expectedGrants = [
                    Calendar_Model_ResourceGrants::EVENTS_FREEBUSY,
                    Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY,
                ];
                break;
            case Calendar_Model_ResourceGrants::EVENTS_READ:
                $expectedGrants = [
                    Calendar_Model_ResourceGrants::EVENTS_READ,
                    Calendar_Model_ResourceGrants::GRANT_READ,
                ];
                break;
            case Calendar_Model_ResourceGrants::EVENTS_SYNC:
                $expectedGrants = [
                    Calendar_Model_ResourceGrants::EVENTS_SYNC,
                    Calendar_Model_ResourceGrants::GRANT_SYNC,
                ];
                break;
        }

        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($_containerId, true);
        $grants->removeRecord($grants->find('account_id', $this->_getPersona('pwulf')->getId()));
        static::assertEquals(1, $grants->count());
        /** @var Calendar_Model_ResourceGrants $currentUserGrants */
        $currentUserGrants = $grants->getFirstRecord();
        static::assertEquals(Tinebase_Core::getUser()->getId(), $currentUserGrants->account_id);
        foreach (Calendar_Model_ResourceGrants::getAllGrants() as $grant) {
            if (in_array($grant, $expectedGrants)) {
                static::assertTrue($currentUserGrants->{$grant});
            } else {
                static::assertFalse($currentUserGrants->{$grant});
            }
        }
    }

    protected function _checkSearchResourceAttenderResult($_result, $_resultCount)
    {
        static::assertEquals(3, count($_result), 'result should be array with 3 keys: ' . print_r($_result, true));
        static::assertTrue(isset($_result['results']) && isset($_result['totalcount']) && isset($_result['filter']),
            'result should be array with 3 keys: ' . print_r($_result, true));
        static::assertEquals($_resultCount, $_result['totalcount'], 'totalcount not 1: ' . print_r($_result, true));
    }

    protected function _searchAttender($_resourceId, $_resultCount)
    {
        $result = $this->jsonFE->searchAttenders([
            ['field' => 'type', 'value' => [Calendar_Model_Attender::USERTYPE_RESOURCE]],
            ['field' => 'resourceFilter', 'value' =>
                ['field' => 'id', 'operator' => 'equals', 'value' => $_resourceId]
            ],
        ]);
        static::assertTrue(isset($result[Calendar_Model_Attender::USERTYPE_RESOURCE]), print_r($result, true));
        $result = $result[Calendar_Model_Attender::USERTYPE_RESOURCE];

        $this->_checkSearchResourceAttenderResult($result, $_resultCount);
    }

    protected function _searchResource($_resourceId, $_resultCount)
    {
        $result = $this->jsonFE->searchResources([
            ['field' => 'id', 'operator' => 'equals', 'value' => $_resourceId],
        ], null);

        $this->_checkSearchResourceAttenderResult($result, $_resultCount);
    }

    protected function _createEventInResourceContainer($resourceContainerId, $shouldSucceed)
    {
        $event = $this->_getEvent(true);
        $event->container_id = $resourceContainerId;

        try {
            $createdEventInResourceContainer = $this->jsonFE->saveEvent($event->toArray());
            if (!$shouldSucceed) {
                static::fail('we should not be able to create an event in the resource container');
            }
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if ($shouldSucceed) throw $tead;
        }
        if ($shouldSucceed) {
            static::assertEquals($resourceContainerId, $createdEventInResourceContainer['container_id']['id']);
            return $createdEventInResourceContainer;
        }
    }

    protected function _checkStatusAuthKey($attenders, $resourceId, $shouldHaveAuthKey)
    {
        $found = false;
        foreach ($attenders as $attendee) {
            if (Calendar_Model_Attender::USERTYPE_RESOURCE === $attendee['user_type'] &&
                    $resourceId === $attendee['user_id']['id']) {
                $found = true;
                if ($shouldHaveAuthKey) {
                    static::assertTrue(isset($attendee['status_authkey']) && !empty($attendee['status_authkey']),
                        'status_authkey is missing');
                } else {
                    static::assertFalse(isset($attendee['status_authkey']) && !empty($attendee['status_authkey']),
                        'status_authkey must not be set');
                }
                break;
            }
        }
        static::assertTrue($found, 'resource attendee not found');
    }

    protected function _createEventWithResourceAttendee($resourceId, $shouldSucceed, $shouldHaveAuthKey)
    {
        $event = $this->_getEvent(true);
        $event->attendee->addRecord($this->_createAttender($resourceId, Calendar_Model_Attender::USERTYPE_RESOURCE));
        $createdEventWithResourceAttender = null;
        $currentUser = Tinebase_Core::getUser();
        $testUser = $this->_getTestUser();
        if ($currentUser->getId() !== $testUser->getId()) {
            $event->organizer = $testUser->contact_id;
            if (null !== ($attendee = $event->attendee->find('user_id', $currentUser->contact_id))) {
                $event->attendee->removeRecord($attendee);
            }
        }

        try {
            $createdEventWithResourceAttender = $this->jsonFE->saveEvent($event->toArray());
            if (!$shouldSucceed) {
                static::fail('we should not be able to invite a resource');
            }
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if ($shouldSucceed) throw $tead;
        }
        if (null !== $shouldHaveAuthKey) {
            $this->_checkStatusAuthKey($createdEventWithResourceAttender['attendee'], $resourceId, $shouldHaveAuthKey);
        }
        return $createdEventWithResourceAttender;
    }

    protected function _getLocalEvent($event, $shouldSucceed)
    {
        try {
            $event = $this->jsonFE->getEvent($event['id']);
            if (!$shouldSucceed) {
                static::fail('we should not have permission to read this event');
            }
            return $event;
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if ($shouldSucceed) throw $tead;
        }
    }

    protected function _rescheduleEvent($event, $resourceId, $shouldSucceed, $shouldHaveAuthKey)
    {
        $event['dtstart'] = (new Tinebase_DateTime($event['dtstart']))->addHour(1)->toString();
        $event['dtend'] = (new Tinebase_DateTime($event['dtend']))->addHour(1)->toString();
        $createdEvent = null;

        try {
            $createdEvent = $this->jsonFE->saveEvent($event);
            if (!$shouldSucceed) {
                static::fail('we should not have permission to update this event');
            }
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if ($shouldSucceed) throw $tead;
        }
        if (null !== $shouldHaveAuthKey) {
            $this->_checkStatusAuthKey($createdEvent['attendee'], $resourceId, $shouldHaveAuthKey);
        }

        return $createdEvent;
    }

    protected function _changeResourceAttendeeStatus($event, $resourceId, $shouldSucceed)
    {
        $found = false;

        foreach ($event['attendee'] as &$attendee) {
            if (Calendar_Model_Attender::USERTYPE_RESOURCE === $attendee['user_type'] &&
                    $resourceId === $attendee['user_id']['id']) {
                $found = true;
                if (Calendar_Model_Attender::STATUS_TENTATIVE === $attendee['status']) {
                    $attendee['status'] = Calendar_Model_Attender::STATUS_ACCEPTED;
                } else {
                    $attendee['status'] = Calendar_Model_Attender::STATUS_TENTATIVE;
                }
                break;
            }
        }
        static::assertTrue($found, 'resource attendee not found');
        $newStatus = $attendee['status'];
        unset($attendee);

        $updatedEvent = $this->jsonFE->saveEvent($event);
        foreach ($updatedEvent['attendee'] as $attendee) {
            if (Calendar_Model_Attender::USERTYPE_RESOURCE === $attendee['user_type'] &&
                    $resourceId === $attendee['user_id']['id']) {
                $found = true;
                if ($shouldSucceed) {
                    static::assertEquals($newStatus, $attendee['status']);
                } else {
                    static::assertNotEquals($newStatus, $attendee['status']);
                }
                break;
            }
        }
        static::assertTrue($found, 'resource attendee not found after update');
    }

    protected function _deleteResource($resource, $shouldSucceed)
    {
        try {
            $this->jsonFE->deleteResources([$resource['id']]);
            if (!$shouldSucceed) {
                static::fail('we should not have permission to update the resource');
            }
            try {
                $this->jsonFE->getResource($resource['id']);
                static::fail('failed to delete resource');
            } catch (Tinebase_Exception_NotFound $tenf) {}
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if ($shouldSucceed) throw $tead;
        }
    }

    protected function _updateResource($resource, $shouldSucceed)
    {
        $resource['name'] = 'newName';
        try {
            $updatedResource = $this->jsonFE->saveResource($resource);
            if (!$shouldSucceed) {
                static::fail('we should not have permission to update the resource');
            }
            static::assertEquals($resource['name'], $updatedResource['name'], 'resource update did not work');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if ($shouldSucceed) throw $tead;
        }
    }

    protected function _updateResourceAcl($resource, $shouldSucceed)
    {
        $orgGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($resource['container_id']['id'], true);
        $resource['grants'] = $orgGrants->toArray();
        $resource['grants'][] = [
            'account_id'      => '0',
            'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true,
        ];

        try {
            $this->jsonFE->saveResource($resource);
        } catch (Exception $e) {
            if ($shouldSucceed) {
                static::fail('we should have permission to update the resource ACLs: ' . $e->getMessage());
            }
        }

        $newGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($resource['container_id']['id'], true);
        $diff = $orgGrants->diff($newGrants);
        if (!$shouldSucceed) {
            static::assertTrue($diff->isEmpty(), 'we should not have permission to update the resource ACLs: '
                . print_r($diff->toArray(), true));
        } else {
            static::assertFalse($diff->isEmpty(), 'we should have permission to update the resource ACLs');
        }
    }

    protected function _preRemoveManageResourcesAndFlush()
    {
        // remove manage_resource right
        $this->_removeRoleRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES);
        // reset class cache
        Calendar_Controller_Resource::destroyInstance();
        Tinebase_Container::getInstance()->resetClassCache();
    }

    protected function _preSetTestUserEtc()
    {
        $this->selfReset['_testUserContact'] = $this->_testUserContact;
        $this->selfReset['_originalTestUser'] = $this->_originalTestUser;
        $this->selfReset['_testCalendar'] = $this->_testCalendar;
        $this->_originalTestUser = $this->_getPersona('pwulf');
        Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        $this->_testCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container([
            'name'           => 'PHPUnit shared Calender container',
            'type'           => Tinebase_Model_Container::TYPE_SHARED,
            'backend'        => 'Sql',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'          => Calendar_Model_Event::class,
        ]));
        $this->_testUserContact = null;
    }

    protected function _preResetTestUser()
    {
        $this->_testCalendar = $this->selfReset['_testCalendar'];
        $this->_testUserContact = $this->selfReset['_testUserContact'];
        $this->_originalTestUser = $this->selfReset['_originalTestUser'];
        Tinebase_Core::set(Tinebase_Core::USER, $this->selfReset['_originalTestUser']);
    }

    protected function _preRemoveACLsFromTestContainer()
    {
        Tinebase_Container::getInstance()->setGrants($this->_testCalendar, new Tinebase_Record_RecordSet(
            Tinebase_Model_Grants::class), true, false);
    }

    protected function _preAddReadGrant($resource)
    {
        $oldUser = Tinebase_Core::getUser();
        foreach ($resource['grants'] as &$grants) {
            if ($grants['account_id'] === $oldUser->getId()) {
                $grants[Calendar_Model_ResourceGrants::RESOURCE_READ] = true;
                break;
            }
        }
        try {
            // TODO make sure, pwulf has MANAGE_RESOURCES!
            Tinebase_Core::set(Tinebase_Core::USER, $this->_getPersona('pwulf'));
            $this->jsonFE->saveResource($resource);
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
        }
    }

    protected function _runGrantsTest($name, $result, $resource, $data = null)
    {
        switch ($name) {
            case '_searchResource':
                return $this->_searchResource($resource['id'], $result);

            case '_searchAttender':
                return $this->_searchAttender($resource['id'], $result);

            case '_createEventInResourceContainer':
                return $this->_createEventInResourceContainer($resource['container_id']['id'], $result);

            case '_createEventWithResourceAttendee':
                return $this->_createEventWithResourceAttendee($resource['id'], $result['succeed'],
                    isset($result['authKey']) ? $result['authKey'] : null);

            case '_getLocalEvent':
                return $this->_getLocalEvent($data, $result);

            case '_rescheduleEvent':
                return $this->_rescheduleEvent($data, $resource['id'], $result['succeed'],
                    isset($result['authKey']) ? $result['authKey'] : null);

            case '_changeResourceAttendeeStatus':
                return $this->_changeResourceAttendeeStatus($data, $resource['id'], $result);

            case '_updateResource':
                return $this->_updateResource($resource, $result);

            case '_updateResourceAcl':
                return $this->_updateResourceAcl($resource, $result);

            case '_deleteResource':
                return $this->_deleteResource($resource, $result);

            default:
                throw new Tinebase_Exception($name . ' is not a valid test');
        }
    }

    protected function _runGrantsTests($data)
    {
        foreach ($data as $grant => $tests) {
            foreach ($tests as $test) {
                $this->tearDown();
                $this->setUp();
                $resource = $this->_prepareTestResourceAcl(Tinebase_Core::getUser(), [[$grant => true]]);
                $this->_checkResourceGrants($resource['container_id']['id'], $grant);

                try {
                    if (isset($test['pre'])) {
                        foreach ($test['pre'] as $pre) {
                            $this->{$pre}($resource);
                        }
                    }

                    $result = $this->_runGrantsTest($test['name'], $test['result'], $resource);

                    if (isset($test['post'])) {
                        foreach ($test['post'] as $pre) {
                            $this->{$pre}($resource);
                        }
                    }

                    if (isset($test['followUps'])) {
                        foreach ($test['followUps'] as $followUp) {
                            $this->_runGrantsTest($followUp['name'], $followUp['result'], $resource, $result);
                        }
                    }
                } catch (Tinebase_Exception $te) {
                    static::fail(print_r($test, true) . ' failed with exception ' . get_class($te) . ' '
                        . $te->getMessage() . PHP_EOL . $te->getTraceAsString());
                } catch (PHPUnit_Framework_AssertionFailedError $e) {
                    static::fail(print_r($test, true) . ' failed with message ' . $e->getMessage());
                }
            }
        }
    }

    public function testResourceInviteGrant()
    {
        $this->_runGrantsTests([Calendar_Model_ResourceGrants::RESOURCE_INVITE => [
            // manage resource rights -> _updateResource must not work
            ['name' => '_updateResource', 'result' => false],

            // manage resource rights -> _deleteResource still needs read grant -> failure
            ['name' => '_deleteResource', 'result' => false],

            // manage resource rights -> _updateResourceAcl must not work
            ['name' => '_updateResourceAcl', 'result' => false],

            // resource_invite grant => searchResource must not work
            ['name' => '_searchResource', 'result' => 0],

            // resource_invite grant -> searchAttender must work
            ['name' => '_searchAttender', 'result' => 1],

            // set user to pwulf, create event in resource container (having event_add grant)
            // set user back, get previously created event should fail
            ['pre' => ['_preSetTestUserEtc'], 'name' => '_createEventInResourceContainer', 'result' => true,
                'post' => ['_preResetTestUser'], 'followUps' => [['name' => '_getLocalEvent', 'result' => false]]],

            // create event in resource container should fail with manage_resources right / resource_invite grant
            ['name' => '_createEventInResourceContainer', 'result' => false],

            // invite resource attender should succeed with resource_invite grant, no authkey returned
            ['name' => '_createEventWithResourceAttendee', 'result' => ['succeed' => true, 'authKey' => false]],

            // set user to pwulf, invite resource attender
            // set user back, remove container grants, reading event should not work (event_read required)
            ['pre' => ['_preSetTestUserEtc'],
                'name' => '_createEventWithResourceAttendee', 'result' => ['succeed' => true, 'authKey' => true],
                'post' => ['_preRemoveACLsFromTestContainer', '_preResetTestUser'],
                'followUps' => [['name' => '_getLocalEvent', 'result' => false]]
            ],
        ]]);
    }

    public function testResourceReadGrant()
    {
        $this->_runGrantsTests([Calendar_Model_ResourceGrants::RESOURCE_READ => [
            // manage resource rights -> _updateResource must not work
            ['name' => '_updateResource', 'result' => false],

            // manage resource rights and read grant -> success
            ['name' => '_deleteResource', 'result' => true],

            // no manage resource rights -> _deleteResource should not work
            ['pre' => ['_preRemoveManageResourcesAndFlush'], 'name' => '_deleteResource', 'result' => false],

            // manage resource rights -> _updateResourceAcl must not work
            ['name' => '_updateResourceAcl', 'result' => false],

            // resource_read grant => searchResource must work
            ['name' => '_searchResource', 'result' => 1],

            // resource_read grant -> searchAttender must not work
            ['name' => '_searchAttender', 'result' => 0],

            // set user to pwulf, create event in resource container (having event_add grant)
            // set user back, get previously created event should fail
            ['pre' => ['_preSetTestUserEtc'], 'name' => '_createEventInResourceContainer', 'result' => true,
                'post' => ['_preResetTestUser'], 'followUps' => [['name' => '_getLocalEvent', 'result' => false]]],

            // create event in resource container should fail with manage_resources right / resource_read grant
            ['name' => '_createEventInResourceContainer', 'result' => false],

            // invite resource attender should not succeed with resource_read grant
            ['name' => '_createEventWithResourceAttendee', 'result' => ['succeed' => false]],

            // invite resource attender with pwulf
            // then switch back to vagrant user and try to reschedule (which should be possible)
            ['pre' => ['_preSetTestUserEtc'], 'name' => '_createEventWithResourceAttendee',
                'result' => ['succeed' => true, 'authKey' => true], 'post' => ['_preResetTestUser'], 'followUps' => [
                    ['name' => '_rescheduleEvent', 'result' => ['succeed' => true, 'authKey' => false]]
            ]],

            // set user to pwulf, invite resource attender
            // set user back, remove container grants, reading event should not work (event_read required)
            ['pre' => ['_preSetTestUserEtc'],
                'name' => '_createEventWithResourceAttendee', 'result' => ['succeed' => true, 'authKey' => true],
                'post' => ['_preRemoveACLsFromTestContainer', '_preResetTestUser'],
                'followUps' => [['name' => '_getLocalEvent', 'result' => false]]
            ],
        ]]);
    }

    public function testResourceEditGrant()
    {
        $this->_runGrantsTests([Calendar_Model_ResourceGrants::RESOURCE_EDIT => [
            // edit grant implies read grant -> works
            ['name' => '_updateResource', 'result' => true],

            // manage resource rights and implied read grant -> success
            ['name' => '_deleteResource', 'result' => true],

            // no manage resource rights -> fail
            ['pre' => ['_preRemoveManageResourcesAndFlush'],'name' => '_deleteResource', 'result' => false],

            // manage resource rights -> _updateResourceAcl must not work
            ['name' => '_updateResourceAcl', 'result' => false],

            // implied read grant => searchResource must work
            ['name' => '_searchResource', 'result' => 1],

            // no resource_invite grant -> searchAttender must not work
            ['name' => '_searchAttender', 'result' => 0],

            // set user to pwulf, create event in resource container (having event_add grant)
            // set user back, get previously created event should fail
            ['pre' => ['_preSetTestUserEtc'], 'name' => '_createEventInResourceContainer', 'result' => true,
                'post' => ['_preResetTestUser'], 'followUps' => [['name' => '_getLocalEvent', 'result' => false]]],

            // create event in resource container should fail with manage_resources right / resource_edit grant
            ['name' => '_createEventInResourceContainer', 'result' => false],

            // invite resource attender should not succeed with resource_edit grant
            ['name' => '_createEventWithResourceAttendee', 'result' => ['succeed' => false]],

            // invite resource attender with pwulf
            // then switch back to vagrant user and try to reschedule (which should be possible)
            ['pre' => ['_preSetTestUserEtc'], 'name' => '_createEventWithResourceAttendee',
                'result' => ['succeed' => true, 'authKey' => true], 'post' => ['_preResetTestUser'], 'followUps' => [
                ['name' => '_rescheduleEvent', 'result' => ['succeed' => true, 'authKey' => false]]
            ]],

            // set user to pwulf, invite resource attender
            // set user back, remove container grants, reading event should not work (event_read required)
            ['pre' => ['_preSetTestUserEtc'],
                'name' => '_createEventWithResourceAttendee', 'result' => ['succeed' => true, 'authKey' => true],
                'post' => ['_preRemoveACLsFromTestContainer', '_preResetTestUser'],
                'followUps' => [['name' => '_getLocalEvent', 'result' => false]]
            ],
        ]]);
    }

    public function testResourceAdminGrant()
    {
        $this->_runGrantsTests([Calendar_Model_ResourceGrants::RESOURCE_ADMIN => [
            // admin grant -> works
            ['name' => '_updateResource', 'result' => true],

            // manage resource rights and admin grant -> works
            ['name' => '_deleteResource', 'result' => true],

            // no manage resource rights -> fail
            ['pre' => ['_preRemoveManageResourcesAndFlush'],'name' => '_deleteResource', 'result' => false],

            // admin grant -> works
            ['name' => '_updateResourceAcl', 'result' => true],

            // admin grant -> works
            ['name' => '_searchResource', 'result' => 1],

            // admin grant -> works
            ['name' => '_searchAttender', 'result' => 1],

            // set user to pwulf, create event in resource container (having event_add grant)
            // set user back, get previously created event should fail
            ['pre' => ['_preSetTestUserEtc'], 'name' => '_createEventInResourceContainer', 'result' => true,
                'post' => ['_preResetTestUser'], 'followUps' => [['name' => '_getLocalEvent', 'result' => false]]],

            // create event in resource container should fail with manage_resources right / resource_admin grant
            ['name' => '_createEventInResourceContainer', 'result' => false],

            // admin grant -> works
            ['name' => '_createEventWithResourceAttendee', 'result' => ['succeed' => true, 'authKey' => false]],

            // set user to pwulf, invite resource attender
            // set user back, remove container grants, reading event should not work (event_read required)
            ['pre' => ['_preSetTestUserEtc'],
                'name' => '_createEventWithResourceAttendee', 'result' => ['succeed' => true, 'authKey' => true],
                'post' => ['_preRemoveACLsFromTestContainer', '_preResetTestUser'],
                'followUps' => [['name' => '_getLocalEvent', 'result' => false]]
            ],
        ]]);
    }

    public function testEventsReadGrant()
    {
        $this->_runGrantsTests([Calendar_Model_ResourceGrants::EVENTS_READ => [
            ['name' => '_updateResource', 'result' => false],

            // manage resource rights -> _deleteResource still needs read grant -> failure
            ['name' => '_deleteResource', 'result' => false],

            ['name' => '_updateResourceAcl', 'result' => false],

            ['name' => '_searchResource', 'result' => 0],

            ['name' => '_searchAttender', 'result' => 0],

            // set user to pwulf, create event in resource container (having event_add grant)
            // set user back, get previously created event should succeed
            ['pre' => ['_preSetTestUserEtc'], 'name' => '_createEventInResourceContainer', 'result' => true,
                'post' => ['_preResetTestUser'], 'followUps' => [['name' => '_getLocalEvent', 'result' => true]]],

            // create event in resource container should fail with manage_resources right
            ['name' => '_createEventInResourceContainer', 'result' => false],

            ['name' => '_createEventWithResourceAttendee', 'result' => ['succeed' => false]],

            // set user to pwulf, invite resource attender
            // set user back, remove container grants, reading event should work
            ['pre' => ['_preSetTestUserEtc'],
                'name' => '_createEventWithResourceAttendee', 'result' => ['succeed' => true, 'authKey' => true],
                'post' => ['_preRemoveACLsFromTestContainer', '_preResetTestUser'],
                'followUps' => [['name' => '_getLocalEvent', 'result' => true]]
            ],
        ]]);
    }

    public function testUpdateGrants()
    {
        $resource = $this->_prepareTestResourceAcl(Tinebase_Core::getUser());
        $orgGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($resource['container_id'], true);
        static::assertEquals(2, $orgGrants->count());

        $resource['grants'] = null;
        $resource['name'] = 'shoo';
        $resource = $this->jsonFE->saveResource($resource);
        $updateGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($resource['container_id'], true);
        static::assertEquals(2, $updateGrants->count());
        static::assertTrue($updateGrants->diff($orgGrants)->isEmpty(), 'grants are not the same');

        $resource['grants'] = [];
        try {
            $this->jsonFE->saveResource($resource);
            static::fail('empty grants are not allowed');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            static::assertEquals('No Permission.', $tead->getMessage());
        }
    }
}