<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Tinebase_Relations
 */
class Tinebase_Relation_RelationTest extends TestCase
{
    /**
     * @var    Tinebase_Relations
     */
    protected $_object;
    
    /**
     * crm lead identifiers we make relations to
     * @var array
     */
    private $_crmId = NULL;
    
    /**
     * a second crm lead identifiers we make relations to
     * @var array
     */
    private $_crmId2 = NULL;
    
    /**
     * Relation data as they come from e.g. JSON update request
     *
     * @var array
     */
    private $_relationData = NULL;
    
    /**
     * relation objects
     *
     * @var array
     */
    private $_relations = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Relation_RelationTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        Sales_Controller_Contract::getInstance()->setNumberPrefix();
        Sales_Controller_Contract::getInstance()->setNumberZerofill();
        
        $this->_object = Tinebase_Relations::getInstance();
        $this->_relations = array();
        
        $this->_crmId = array(
            'model'   => 'Crm_Model_Lead',
            'backend' => 'SQL',
            'id'      => Tinebase_Record_Abstract::generateUID()
        );
        
        $this->_crmId2 = array(
            'model'   => 'Crm_Model_Lead',
            'backend' => 'SQL',
            'id'      => Tinebase_Record_Abstract::generateUID()
        );
        
        $this->_relationData = array(
            array(
                'own_model'              => 'Crm_Model_Lead',
                'own_backend'            => 'SQL',
                'own_id'                 => $this->_crmId['id'],
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Tasks_Model_Task',
                'related_backend'        => Tasks_Backend_Factory::SQL,
                'related_id'             => Tinebase_Record_Abstract::generateUID(),//'8a572723e867dd73dd68d1740dd94f586eff5432',
                'type'                   => 'CRM_TASK'
            ),
            array(
                'own_model'              => 'Crm_Model_Lead',
                'own_backend'            => 'SQL',
                'own_id'                 => $this->_crmId['id'],
                'related_degree'         => Tinebase_Model_Relation::DEGREE_PARENT,
                'related_model'          => 'Tasks_Model_Task',
                'related_backend'        => '',
                'related_id'             => '',
                'related_record'           => array(
                    'summary'              => 'phpunit test task for relations from crm',
                    'description'          => 'This task was created by phpunit when testing relations',
                    'due'                  => '2010-06-11T15:47:40',
                ),
                'type'                   => 'CRM_TASK',
            ),
            array(
                'own_model'              => '',
                'own_backend'            => '',
                'own_id'                 => '',
                'related_degree'         => Tinebase_Model_Relation::DEGREE_PARENT,
                'related_model'          => 'Addressbook_Model_Contact',
                'related_backend'        => '',
                'related_id'             => '',
                'related_record'           => array(
                    'n_family'              => 'Weiss',
                    'n_given'               => 'Cornelius',
                    'bday'                  => '1979-06-05T00:00:00',
                    'container_id'                 => '',
                ),
                'type'                   => 'PARTNER',
            ),
        );
        
        $this->_object->setRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id'], $this->_relationData);
    }
    
    /**
     * testGetInstance().
     */
    public function testGetInstance()
    {
        $this->assertTrue($this->_object instanceof Tinebase_Relations);
    }
    
    public function testGetRelations()
    {
        $relations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        
        $this->assertTrue($relations instanceof Tinebase_Record_RecordSet, 'relations are not a RecordSet');
        // NOTE: one of the related tasks in our testdata is not persistent, but we still get 2 relations back
        // as this missing task looks like a 'ACL not sufficient' task (without related_record)
        $this->assertEquals(3, count($relations));
        
        foreach ($relations as $relation) {
            // check each relation got an id
            $this->assertEquals(40, strlen($relation->getId()));
            // check related record got set/created
            $this->assertTrue(strlen($relation->related_backend) > 2);
            $this->assertFalse(empty($relation->related_id));
        }
    }
    
    /**
     * test for 0008446: Allow getting relations for one related model only
     * https://forge.tine20.org/mantisbt/view.php?id=8446
     */
    public function testGetRelationsWithRelatedModel()
    {
        $relations = $this->_object->getMultipleRelations($this->_crmId['model'], $this->_crmId['backend'], array($this->_crmId['id']), NULL, array(), FALSE, array('Addressbook_Model_Contact'));
        $this->assertEquals(1, count($relations));
        $fr = $relations[0];
        $this->assertEquals(1, $fr->count());
        $this->assertEquals('Addressbook_Model_Contact', $fr->getFirstRecord()->related_model);
    }
    
    /**
     * Tests if updating succeeds when setting relations
     * 
     */
    public function testSetRelationsUpdate()
    {
        $relations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $relations->filter('related_model', 'Addressbook_Model_Contact')->getFirstRecord()->type = 'UPDATETEST';
        
        // NOTE: At the moment we need to set timezone to users timzone, as related records come as arrays and don't get
        // their dates converted in the JSON frontends
        foreach ($relations as $relation) {
            $relation->setTimezone(Tinebase_Core::getUserTimezone());
        }
        
        $this->_object->setRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id'], $relations->toArray());
        
        $updatedRelations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $this->assertEquals('UPDATETEST', $updatedRelations->filter('related_model', 'Addressbook_Model_Contact')->getFirstRecord()->type);
    }
    
    /**
     * Test if updating a related record works
     *
     */
    public function testSetRelationUpdateRelatedRecord()
    {
        $relations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);

        $relatedContacts = $relations->filter('related_model', 'Addressbook_Model_Contact');
        $relatedContacts->sort('related_model', 'ASC');
        $relatedContacts[0]->related_record->note = "Testing to update from relation set";
        
        // NOTE: At the moment we need to set timezone to users timezone, as related records come as arrays and don't get
        // their dates converted in the JSON frontends
        foreach ($relations as $relation) {
            $relation->setTimezone(Tinebase_Core::getUserTimezone());
            $relation->related_record = isset($relation->related_record) ? $relation->related_record->toArray() : [];
        }        
        $this->_object->setRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id'], $relations->toArray(), FALSE, TRUE);
        
        $updatedRelations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $updatedConacts = $updatedRelations->filter('related_model', 'Addressbook_Model_Contact');
        $updatedConacts->sort('related_model', 'ASC');

        $this->assertEquals("Testing to update from relation set", $updatedConacts[0]->related_record->note);
    }
    
    /**
     * test if getting multiple relations returns en empty record set for a missing record
     *
     */
    public function testGetMultipleRelationsWithMissingRecord()
    {
        // note: crmId2 is not created yet
        $relations = $this->_object->getMultipleRelations($this->_crmId['model'], $this->_crmId['backend'], array($this->_crmId['id'], $this->_crmId2['id']));

        $this->assertEquals(2, count($relations), 'number of relation sets does not fit requested number');
        $this->assertArrayHasKey(0, $relations, 'crmId is missing');
        $this->assertGreaterThanOrEqual(2, count($relations[0]), 'not enough relations found for crmId');
        $this->assertArrayHasKey(1, $relations, 'crmId2 is missing');
        $this->assertEquals(0, count($relations[1]), 'to much relations for crmId2');
    }
    
    /**
     * test getting of multiple relations for existing records
     *
     */
    public function testGetMultipleRelations()
    {
        $crmIdRelations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $sharedRelation = $crmIdRelations->filter('related_model', 'Tasks_Model_Task')->getFirstRecord();
        $sharedRelation['id'] = $sharedRelation['own_id'] = $sharedRelation['own_model'] = $sharedRelation['own_backend'] = '';
        
        // lets second entry only have one shared relation
        $relationData = array($sharedRelation, array(
            'own_model'              => '',
            'own_backend'            => '',
            'own_id'                 => '',
            'related_degree'         => Tinebase_Model_Relation::DEGREE_PARENT,
            'related_model'          => 'Addressbook_Model_Contact',
            'related_backend'        => '',
            'related_id'             => '',
            'related_record'         => array(
                'n_family'              => 'Weiss',
                'n_given'               => 'Leonie',
                'bday'                  => '2005-07-13T00:00:00+02:00',
                'container_id'          => '',
            ),
            'type'                   => 'CUSTOMER',
        ));
        $this->_object->setRelations($this->_crmId2['model'], $this->_crmId2['backend'], $this->_crmId2['id'], $relationData);
        
        $relations = $this->_object->getMultipleRelations($this->_crmId['model'], $this->_crmId['backend'], array(0 => $this->_crmId['id'], 12 => $this->_crmId2['id']));
        $this->assertEquals(2, count($relations), 'number of relation sets does not fit requested number');
        
        $this->assertArrayHasKey(0, $relations, 'crmId is missing');
        $this->assertGreaterThanOrEqual(2, count($relations[0]), 'not enough relations found for crmId');
        
        $this->assertArrayHasKey(12, $relations, 'crmId2 is missing');
        self::assertGreaterThanOrEqual(1, count($relations[12]), 'number of relations does not fit');
    }
    
    /**
     * test search relations
     */
    public function testSearchRelations()
    {
        // fetch a set of addressbook ids
        $relations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $relatedContacts = $relations->filter('related_model', 'Addressbook_Model_Contact');
        $adbFilterResult = $relatedContacts->related_id;
        
        // get all lead relations where the set of adb ids is related to
        $filter = new Tinebase_Model_RelationFilter(array(
            array('field' => 'own_model',     'operator' => 'equals', 'value' => 'Crm_Model_Lead'),
            array('field' => 'related_model', 'operator' => 'equals', 'value' => 'Addressbook_Model_Contact'),
            array('field' => 'related_id',    'operator' => 'in'    , 'value' => $adbFilterResult)
        ));
        
        $db = Tinebase_Core::getDb();
        #$db->query("\n /* fetch relations */ \n");
        $relations = $this->_object->search($filter, NULL);
        
        $this->assertEquals(count($adbFilterResult), count($relations), ' search result does not fit');
        
    }
    
    public function testBreakRelations()
    {
        $this->_object->setRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id'], array());
        $relations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $this->assertEquals(1, count($relations), 'one relation has record_removed_reason');
    }
    
    public function testCleanUp()
    {
        $backend = new Tinebase_Relation_Backend_Sql();
        $backend->purgeAllRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $backend->purgeAllRelations($this->_crmId2['model'], $this->_crmId2['backend'], $this->_crmId2['id']);
    }
    
    /**
     * testTransfer
     * 
     * @see 0009210: Allow to change relations
     *      https://forge.tine20.org/mantisbt/view.php?id=9210
     */
    public function testTransfer()
    {
        $sclever = Addressbook_Controller_Contact::getInstance()->get($this->_personas['sclever']->contact_id, null, false);
        $pwulf   = Addressbook_Controller_Contact::getInstance()->get($this->_personas['pwulf']->contact_id, null, false);
        
        $container = Tinebase_Container::getInstance()->create(new Tinebase_Model_Container(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
            'type'           => Tinebase_Model_Container::TYPE_SHARED,
            'backend'        => 'sql',
            'name'           => 'testsdf',
            'model'          => Sales_Model_Contract::class,
        )));
        
        $contract = new Sales_Model_Contract(array('number' => '23547', 'title' => 'test', 'container_id' => $container->getId()));
        $contract = Sales_Controller_Contract::getInstance()->create($contract);
        
        $contract2 = new Sales_Model_Contract(array('number' => '23347', 'title' => 'test', 'container_id' => $container->getId()));
        $contract2 = Sales_Controller_Contract::getInstance()->create($contract2);
        
        $json = new Sales_Frontend_Json();
        
        $contractJson = $contract->toArray();
        $contractJson['relations'][] = array(
            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'  => 'Addressbook_Model_Contact',
            'related_record' => $sclever->toArray(),
            'type'           => 'CUSTOMER',
        );
        
        $contractJson = $json->saveContract($contractJson);
        
        $contract2Json = $contract2->toArray();
        $contract2Json['relations'][] = array(
            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'  => 'Addressbook_Model_Contact',
            'related_record' => $sclever->toArray(),
            'type'           => 'PARTNER',
        );
        $contract2Json['relations'][] = array(
            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'  => 'Addressbook_Model_Contact',
            'related_record' => $pwulf->toArray(),
            'type'           => 'PARTNER',
        );
        $contract2Json = $json->saveContract($contract2Json);
        
        $this->assertEquals($sclever->getId(), $contractJson['relations'][0]['related_id']);
        
        $skipped = Tinebase_Relations::getInstance()->transferRelations($sclever->getId(), $pwulf->getId(), 'Addressbook_Model_Contact');
        
        $this->assertEquals(1, count($skipped));
        
        $skipped = array_pop($skipped);
        
        $this->assertEquals($sclever->getId(), $skipped['own_id']);
        
        $contractJson = $json->getContract($contract->getId());
        $this->assertEquals($pwulf->getId(), $contractJson['relations'][0]['related_id']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        Tinebase_Relations::getInstance()->transferRelations($sclever->getId(), $pwulf->getId(), 'Addressbook_Model_Contract');
    }
    
    /**
     * tests if constraints config is called properly
     * 
     * @see #8840: relations config - constraints from the other side
     *      - validate in backend
     *      
     *      https://forge.tine20.org/mantisbt/view.php?id=8840
     */
    public function testGetConstraintsConfigs() {
        $result = Tinebase_Relations::getConstraintsConfigs('Sales_Model_Contract');
        $this->assertEquals(12, count($result));
        
        foreach($result as $item) {
            if ($item['ownRecordClassName'] == 'Sales_Model_Contract' && $item['relatedRecordClassName'] == 'Timetracker_Model_Timeaccount') {
                $this->assertEquals('Contract', $item['ownModel']);
                $this->assertEquals('Timeaccount', $item['relatedModel']);
                $this->assertEquals('', $item['defaultType']);
                $this->assertEquals('TIME_ACCOUNT', $item['config'][0]['type']);
                $this->assertSame(0, $item['config'][0]['max']);
            } elseif ($item['ownRecordClassName'] == 'Timetracker_Model_Timeaccount' && $item['relatedRecordClassName'] == 'Sales_Model_Contract') {
                $this->assertEquals('Contract', $item['relatedModel']);
                $this->assertEquals('Timeaccount', $item['ownModel']);
                $this->assertEquals('TIME_ACCOUNT', $item['config'][0]['type']);
                $this->assertEquals(TRUE, $item['reverted']);
                $this->assertSame(1, $item['config'][0]['max']);
            }
        }
    }

    /**
     * testRemoveRelationsByAppACL
     *
     * - remove right to Tasks app of related record
     * - other relations should still be visible
     */
    public function testRemoveRelationsByAppACL()
    {
        Tasks_Controller_Task::unsetInstance();
        Tinebase_Core::clearAppInstanceCache();
        $this->_removeRoleRight('Tasks', Crm_Acl_Rights::RUN);
        $relations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);

        self::assertTrue($relations instanceof Tinebase_Record_RecordSet, 'relations are not a RecordSet');
        self::assertEquals(3, count($relations), print_r($relations->toArray(), true));
        self::assertEquals([null, Tinebase_Model_Relation::REMOVED_BY_ACL, Tinebase_Model_Relation::REMOVED_BY_ACL], $relations->record_removed_reason);
    }
}
