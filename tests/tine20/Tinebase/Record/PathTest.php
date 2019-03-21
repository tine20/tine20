<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Record path test class
 */
class Tinebase_Record_PathTest extends TestCase
{
    /**
     * @var Addressbook_Model_Contact
     */
    protected $_fatherRecord = null;

    protected $_oldConfig = null;

    /**
     * @var Tinebase_Record_Path $_uit
     */
    protected $_uit;

    /**
     * @throws Exception
     * @throws Setup_Exception
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function setUp(): void
    {
        $this->_uit = Tinebase_Record_Path::getInstance();

        if (true !== Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH)) {
            $features = Tinebase_Cache_PerRequest::getInstance()->load('Tinebase_Config_Abstract', 'Tinebase_Config_Abstract::featureEnabled', 'Tinebase');
            $features->{Tinebase_Config::FEATURE_SEARCH_PATH} = true;

            Addressbook_Controller_Contact::destroyInstance();
            Addressbook_Controller_List::destroyInstance();
            Addressbook_Controller_ListRole::destroyInstance();
        }

        if (true !== Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH)) {
            throw new Exception('was not able to activate the feature search path');
        }
        
        parent::setUp();
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Exception
     */
    public function testBuildPathFromNonPathGeneratingRecord()
    {
        static::assertTrue(Addressbook_Model_Contact::generatesPaths(), Addressbook_Model_Contact::class
            . ' does not generate paths, test needs to be rewriten');
        static::assertFalse(Calendar_Model_Resource::generatesPaths(), Calendar_Model_Resource::class
            . ' does generate paths, test needs to be rewriten');

        $adbController= Addressbook_Controller_Contact::getInstance();
        $contact = $adbController->create(new Addressbook_Model_Contact(['n_family' => 'tester']));

        $resourceController = Calendar_Controller_Resource::getInstance();
        $resource = $resourceController->create(new Calendar_Model_Resource([
            'name'                 => 'Meeting Room',
            'description'          => 'Our main meeting room',
            'email'                => 'room@example.com',
            'is_location'          => true,
            'relations'            => [
                [
                    'related_model'     => Addressbook_Model_Contact::class,
                    'related_id'        => $contact->getId(),
                    'related_degree'    => Tinebase_Model_Relation::DEGREE_CHILD,
                    'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'type'              => 't'
                ]
            ],
            'grants'               => [[
                    'account_id'      => Tinebase_Core::getUser()->getId(),
                    'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                    Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
                ]]
        ]));

        $result = $this->_uit->getPathsForRecord($resource);
        $this->assertEquals(1, count($result), 'should find 1 path for record. paths:' . print_r($result->toArray(), true));
        $resourcePath = $result->getFirstRecord();
        $this->assertEquals('/Meeting Room{t}/tester', $resourcePath->path);

        $resource->name = 'meeting';
        $resourceController->update($resource);

        $result = $this->_uit->getPathsForRecord($resource);
        $this->assertEquals(1, count($result), 'should find 1 path for record. paths:' . print_r($result->toArray(), true));
        $resourcePath = $result->getFirstRecord();
        $this->assertEquals('/meeting{t}/tester', $resourcePath->path);

        $resource->relations->getFirstRecord()->related_degree = Tinebase_Model_Relation::DEGREE_SIBLING;
        $resource->relations = $resource->relations->toArray();
        $resource = $resourceController->update($resource);

        $result = $this->_uit->getPathsForRecord($resource);
        $this->assertEquals(0, count($result), 'should find 0 path for record. paths:' . print_r($result->toArray(), true));

        $resource->relations->getFirstRecord()->related_degree = Tinebase_Model_Relation::DEGREE_CHILD;
        $resource->relations = $resource->relations->toArray();
        $resourceController->update($resource);

        $result = $this->_uit->getPathsForRecord($contact);
        $this->assertEquals(1, count($result), 'should find 1 path for record. paths:' . print_r($result->toArray(), true));
        $contactPath = $result->getFirstRecord();
        $this->assertEquals('/meeting{t}/tester', $contactPath->path);

        $resourceController->delete($resource);

        $result = $this->_uit->getPathsForRecord($contact);
        $this->assertEquals(0, count($result), 'should find 0 path for record. paths:' . print_r($result->toArray(), true));
    }
    /**
     * testBuildRelationPathForRecord
     *
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testBuildRelationPathForRecord()
    {
        $contact = $this->_createFatherMotherChild();

        $result = $this->_uit->getPathsForRecord($this->_fatherRecord);
        $this->assertEquals(1, count($result), 'should find 1 path for record. paths:' . print_r($result->toArray(), true));
        $fatherPath = $result->getFirstRecord();
        $this->assertEquals('/grandparent{t}/father{t}/tester', $fatherPath->path);


        $result = $this->_uit->getPathsForRecord($contact);
        $this->assertTrue($result instanceof Tinebase_Record_RecordSet);
        $this->assertEquals(2, count($result), 'should find 2 paths for record. paths:' . print_r($result->toArray(), true));

        // check both paths
        $expectedPaths = array('/grandparent{t}/father{t}/tester', '/mother{t}/tester');
        foreach ($expectedPaths as $expectedPath) {
            $this->assertTrue(in_array($expectedPath, $result->path), 'could not find path ' . $expectedPath . ' in '
                . print_r($result->toArray(), true));
        }

        // check father and tester path are the same one
        $testerPath = $result->filter('path', '/grandparent{t}/father{t}/tester')->getFirstRecord();
        $this->assertEquals($fatherPath->getId(), $testerPath->getId(), 'father and tester path are not the same one! ' . print_r($fatherPath, true) . PHP_EOL . print_r($testerPath, true));
    }

    /**
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _createFatherMotherChild()
    {
        // create some parent / child relations for record
        $this->_fatherRecord = $this->_getFatherWithGrandfather();
        $motherRecord = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family' => 'mother',
        )));
        $relation1 = $this->_getParentRelationArray($this->_fatherRecord);
        $relation2 = $this->_getParentRelationArray($motherRecord);
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family' => 'tester',
            'relations' => array($relation1, $relation2)
        )));

        return $contact;
    }

    /**
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _getFatherWithGrandfather()
    {
        $grandParentRecord = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family' => 'grandparent'
        )));
        $relation = $this->_getParentRelationArray($grandParentRecord);
        $this->_fatherRecord = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family' => 'father',
            'relations' => array($relation)
        )));

        return $this->_fatherRecord;
    }

    /**
     * @param Tinebase_Record_Interface $record
     * @return array
     */
    protected function _getParentRelationArray($record)
    {
        return array(
            'own_model'              => 'Addressbook_Model_Contact',
            'own_backend'            => 'Sql',
            'own_id'                 => 0,
            'related_degree'         => Tinebase_Model_Relation::DEGREE_PARENT,
            'type'                   => 't',
            'related_backend'        => 'Sql',
            'related_id'             => $record->getId(),
            'related_model'          => 'Addressbook_Model_Contact',
            'remark'                 => NULL,
        );
    }

    /**
     * testBuildGroupMemberPathForContact
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testBuildGroupMemberPathForContact()
    {
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family' => 'tester',
            'email'    => 'somemail@example.ru',
        )));
        $adbJson = new Addressbook_Frontend_Json();
        $listRole = $adbJson->saveListRole(array(
            'name'          => 'my role',
            'description'   => 'my test description'
        ));
        $listRole2 = $adbJson->saveListRole(array(
            'name'          => 'my second role',
            'description'   => 'my test description'
        ));

        $memberroles = array(array(
            'contact_id'   => $contact->getId(),
            'list_role_id' => $listRole['id'],
        ), array(
            'contact_id'   => $contact->getId(),
            'list_role_id' => $listRole2['id'],
        ));
        $adbJson->saveList(array(
            'name'                  => 'my test group',
            'description'           => '',
            'members'               => array($contact->getId()),
            'memberroles'           => $memberroles,
            'type'                  => Addressbook_Model_List::LISTTYPE_LIST,
        ));

        $recordPaths = $this->_uit->getPathsForRecord($contact);
        $this->assertTrue($recordPaths instanceof Tinebase_Record_RecordSet);
        $this->assertEquals(2, count($recordPaths), 'should find 2 path for record. paths:' . print_r($recordPaths->toArray(), true));
        $expectedPaths = array('/my test group/my role/tester', '/my test group/my second role/tester');
        foreach ($expectedPaths as $expectedPath) {
            $this->assertTrue(in_array($expectedPath, $recordPaths->path), 'could not find path ' . $expectedPath . ' in '
                . print_r($recordPaths->toArray(), true));
        }

        $fakeContact = new Addressbook_Model_Contact(['id' => substr($contact->getId(), 0, 1)], true);
        $recordPaths = $this->_uit->getPathsForRecord($fakeContact);
        $this->assertSame(0, $recordPaths->count());

        return $contact;
    }

    /**
     * testRebuildPathForRecords
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testTriggerRebuildPathForRecords()
    {
        $this->_fatherRecord = $this->_getFatherWithGrandfather();
        $relation1 = $this->_getParentRelationArray($this->_fatherRecord);
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family' => 'tester',
            'relations' => array($relation1)
        )));

        $recordPaths = $this->_uit->getPathsForRecord($contact);
        $this->assertEquals(1, count($recordPaths));

        $motherRecord = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family' => 'mother',
        )));
        $relations = $contact->relations->toArray();
        $relation2 = $this->_getParentRelationArray($motherRecord);
        $relation2['own_id'] = $contact->getId();
        $relations[] = $relation2;
        $contact->relations = $relations;
        Addressbook_Controller_Contact::getInstance()->update($contact);

        $recordPaths = $this->_uit->getPathsForRecord($contact);
        $this->assertEquals(2, count($recordPaths));

        // check both paths
        $expectedPaths = array('/grandparent{t}/father{t}/tester', '/mother{t}/tester');
        foreach ($expectedPaths as $expectedPath) {
            $this->assertTrue(in_array($expectedPath, $recordPaths->path), 'could not find path ' . $expectedPath . ' in '
                . print_r($recordPaths->toArray(), true));
        }

        return $contact;
    }

    /**
     * testTriggerRebuildIfFatherChanged
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testTriggerRebuildIfFatherChanged()
    {
        $contact = $this->testTriggerRebuildPathForRecords();

        // change contact name and check path in related records
        $this->_fatherRecord->n_family = 'stepfather';
        // don't update relations
        unset($this->_fatherRecord->relations);
        Addressbook_Controller_Contact::getInstance()->update($this->_fatherRecord);

        $recordPaths = $this->_uit->getPathsForRecord($contact);
        $this->assertEquals(2, count($recordPaths));

        // check both paths again
        $expectedPaths = array('/grandparent{t}/stepfather{t}/tester', '/mother{t}/tester');
        foreach ($expectedPaths as $expectedPath) {
            $this->assertTrue(in_array($expectedPath, $recordPaths->path), 'could not find path ' . $expectedPath . ' in '
                . print_r($recordPaths->toArray(), true));
        }

        // TODO we should clean up here?!?
    }

    /**
     * testTriggerRebuildIfFatherRemovedChild
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testTriggerRebuildIfFatherRemovedChild()
    {
        $contact = $this->testTriggerRebuildPathForRecords();

        // remove child relation from father and check paths of child records
        $father = Addressbook_Controller_Contact::getInstance()->get($this->_fatherRecord->getId());

        foreach($father->relations as $relation) {
            if ($relation->related_degree === Tinebase_Model_Relation::DEGREE_CHILD) {
                $father->relations->removeRecord($relation);
                break;
            }
        }

        //workaround as _setRelatedData expects an array!?!
        $father->relations = $father->relations->toArray();

        Addressbook_Controller_Contact::getInstance()->update($father);

        $recordPaths = $this->_uit->getPathsForRecord($contact);
        $this->assertEquals(1, count($recordPaths));

        // check remaining path again
        $expectedPaths = array('/mother{t}/tester');
        foreach ($expectedPaths as $expectedPath) {
            $this->assertTrue(in_array($expectedPath, $recordPaths->path), 'could not find path ' . $expectedPath . ' in '
                . print_r($recordPaths->toArray(), true));
        }
    }

    /**
     * testPathFilter
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testPathFilter()
    {
        $this->testBuildGroupMemberPathForContact();

        $filterValues = array(
            'my test group' => 1,
            'my role' => 1,
            'somemail@example.ru' => 1
        );
        foreach ($filterValues as $value => $expectedCount) {

            $filter = new Addressbook_Model_ContactFilter($this->_getPathFilterArray($value));
            $result = Addressbook_Controller_Contact::getInstance()->search($filter);
            $this->assertEquals($expectedCount, count($result),
                'search string: ' . $value . ' / result: ' .
                    print_r($result->toArray(), true));
        }
    }

    /**
     * @param string $value
     * @return array
     */
    protected function _getPathFilterArray($value)
    {
        return array(
            array(
                'condition' => 'OR',
                'filters' => array(
                    array('field' => 'query', 'operator' => 'contains', 'value' => $value),
                    array('field' => 'path', 'operator' => 'contains', 'value' => $value)
                )
            )
        );
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testPathResolvingForContacts()
    {
        $this->testBuildGroupMemberPathForContact();

        $adbJson = new Addressbook_Frontend_Json();
        $filter = $this->_getPathFilterArray('my role');

        $result = $adbJson->searchContacts($filter, array());

        $this->assertEquals(1, $result['totalcount'], print_r($result['results'], true));
        $firstRecord = $result['results'][0];
        $this->assertTrue(isset($firstRecord['paths']), 'paths should be set in record' . print_r($firstRecord, true));
        // sometimes only 1 path is resolved. this is a little bit strange ...
        $this->assertGreaterThan(0, count($firstRecord['paths']), print_r($firstRecord['paths'], true));
        $this->assertStringContainsString('/my test group', $firstRecord['paths'][0]['path'], 'could not find my test group in paths of record' . print_r($firstRecord, true));
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testPathWithDifferentTypeRelations()
    {
        $contact = $this->_createFatherMotherChild();

        // add another relation to same record with different type
        $relations = $contact->relations->toArray();
        $relation2 = $this->_getParentRelationArray($this->_fatherRecord);
        $relation2['own_id'] = $contact->getId();
        $relation2['type'] = 'type';
        $relations[] = $relation2;
        $contact->relations = $relations;

        $updatedContact = Addressbook_Controller_Contact::getInstance()->update($contact);

        $this->assertEquals(3, count($updatedContact->relations), print_r($updatedContact->relations->toArray(), true));

        $recordPaths = $this->_uit->getPathsForRecord($contact);

        // check the 3 paths
        $this->assertEquals(3, count($recordPaths), 'paths: ' . print_r($recordPaths->toArray(), true));
        $expectedPaths = array('/grandparent{t}/father{t}/tester', '/mother{t}/tester', '/grandparent{t}/father{type}/tester');
        foreach ($expectedPaths as $expectedPath) {
            $this->assertTrue(in_array($expectedPath, $recordPaths->path), 'could not find path ' . $expectedPath . ' in '
                . print_r($recordPaths->toArray(), true));
        }
    }

    public function testPathPartDelegator()
    {
        $contact = new Addressbook_Model_Contact(array('n_family' => 'test'), true);
        $this->assertEquals('/test', $contact->getPathPart(), 'default behaviour of getPathPart for Addressbook_Model_Contact not as expected');

        $adbConfig = Addressbook_Config::getInstance();
        $oldDelegator = $adbConfig->get('getPathPartDelegate_Addressbook_Model_Contact');
        $adbConfig->set('getPathPartDelegate_Addressbook_Model_Contact', 'Tinebase_Record_GetPathPartDelegator');
        Tinebase_Core::clearDelegatorCache();

        $this->assertEquals('shooShoo', $contact->getPathPart(), 'delegator logic did not work!');

        $adbConfig->set('getPathPartDelegate_Addressbook_Model_Contact', $oldDelegator);
        Tinebase_Core::clearDelegatorCache();
        $this->assertEquals('/test', $contact->getPathPart(), 'resetting delegator did not work!');
    }
}
