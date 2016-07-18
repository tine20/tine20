<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
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

    protected function setUp()
    {
        if (!Setup_Backend_Factory::factory()->supports('mysql >= 5.6.4')) {
            $this->markTestSkipped('mysql 5.6.4 or higher required');
        }

        $this->_uit = Tinebase_Record_Path::getInstance();

        if (true !== Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH)) {
            $features = Tinebase_Cache_PerRequest::getInstance()->load('Tinebase_Config_Abstract', 'Tinebase_Config_Abstract::featureEnabled', 'Tinebase');
            $features->{Tinebase_Config::FEATURE_SEARCH_PATH} = true;
        }

        if (true !== Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH)) {
            throw new Exception('was not able to activate the feature search path');
        }
        
        parent::setUp();
    }

    /**
     * testBuildRelationPathForRecord
     */
    public function testBuildRelationPathForRecord()
    {
        $contact = $this->_createFatherMotherChild();
        $result = $this->_uit->generatePathForRecord($contact, true);
        $this->assertTrue($result instanceof Tinebase_Record_RecordSet);
        $this->assertEquals(2, count($result), 'should find 2 paths for record. paths:' . print_r($result->toArray(), true));

        // check both paths
        $expectedPaths = array('/grandparent{t}/father{t}/tester', '/mother{t}/tester');
        foreach ($expectedPaths as $expectedPath) {
            $this->assertTrue(in_array($expectedPath, $result->path), 'could not find path ' . $expectedPath . ' in '
                . print_r($result->toArray(), true));
        }

        $result = $this->_uit->generatePathForRecord($this->_fatherRecord);
        $this->assertEquals(1, count($result), 'should find 1 path for record. paths:' . print_r($result->toArray(), true));
        $this->assertEquals('/grandparent{t}/father', $result->getFirstRecord()->path);
    }

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
     * @param $record
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

        $recordPaths = $this->_uit->generatePathForRecord($contact, true);
        $this->assertTrue($recordPaths instanceof Tinebase_Record_RecordSet);
        $this->assertEquals(2, count($recordPaths), 'should find 2 path for record. paths:' . print_r($recordPaths->toArray(), true));
        $expectedPaths = array('/my test group/my role/tester', '/my test group/my second role/tester');
        foreach ($expectedPaths as $expectedPath) {
            $this->assertTrue(in_array($expectedPath, $recordPaths->path), 'could not find path ' . $expectedPath . ' in '
                . print_r($recordPaths->toArray(), true));
        }

        return $contact;
    }

    /**
     * testRebuildPathForRecords
     */
    public function testTriggerRebuildPathForRecords()
    {
        $this->_fatherRecord = $this->_getFatherWithGrandfather();
        $relation1 = $this->_getParentRelationArray($this->_fatherRecord);
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family' => 'tester',
            'relations' => array($relation1)
        )));

        $recordPaths = $this->_uit->getPathsForRecords($contact, true);
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

        $recordPaths = $this->_uit->getPathsForRecords($contact);
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
     */
    public function testTriggerRebuildIfFatherChanged()
    {
        $contact = $this->testTriggerRebuildPathForRecords();

        // due to full text we need to commit here!
        //Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        //$this->_transactionId = null;

        // change contact name and check path in related records
        $this->_fatherRecord->n_family = 'stepfather';
        Addressbook_Controller_Contact::getInstance()->update($this->_fatherRecord);

        $recordPaths = $this->_uit->getPathsForRecords($contact);
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

        $recordPaths = $this->_uit->getPathsForRecords($contact);
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
        $this->assertContains('/my test group', $firstRecord['paths'][0]['path'], 'could not find my test group in paths of record' . print_r($firstRecord, true));
    }

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

        $recordPaths = $this->_uit->getPathsForRecords($contact);

        // check the 3 paths
        $this->assertEquals(3, count($recordPaths), 'paths: ' . print_r($recordPaths->toArray(), true));
        $expectedPaths = array('/grandparent{t}/father{t}/tester', '/mother{t}/tester', '/grandparent{t}/father{type}/tester');
        foreach ($expectedPaths as $expectedPath) {
            $this->assertTrue(in_array($expectedPath, $recordPaths->path), 'could not find path ' . $expectedPath . ' in '
                . print_r($recordPaths->toArray(), true));
        }
    }
}
