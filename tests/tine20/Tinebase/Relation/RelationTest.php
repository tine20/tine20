<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tinebase_Relation_RelationTest::main();
}

/**
 * Test class for Tinebase_Relations
 */
class Tinebase_Relation_RelationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    Tinebase_Relation
     */
    protected $_object;
    
    /**
     * crm lead identifiers we make relations to
     * @var array
     */
    private $_crmId = array(
        'model'   => 'Crm_Model_Lead',
        'backend' => 'SQL',
        'id'      => '268d586e46aad336de8fa2530b5b8faf921e494d'
    );
    
    /**
     * a second crm lead identifiers we make relations to
     * @var array
     */
    private $_crmId2 = array(
        'model'   => 'Crm_Model_Lead',
        'backend' => 'SQL',
        'id'      => '268d586e46aad336de8fa2530b5b8faf921e495f'
    );
    
    /**
     * Relation data as they come from e.g. JSON update request
     *
     * @var array
     */
    private $_relationData = array(
        array(
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => 'SQL',
            'own_id'                 => '268d586e46aad336de8fa2530b5b8faf921e494d',
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Tasks_Model_Task',
            'related_backend'        => Tasks_Backend_Factory::SQL,
            'related_id'             => '8a572723e867dd73dd68d1740dd94f586eff5432',
            'type'                   => 'CRM_TASK'
        ),
        array(
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => 'SQL',
            'own_id'                 => '268d586e46aad336de8fa2530b5b8faf921e494d',
            'own_degree'             => Tinebase_Model_Relation::DEGREE_PARENT,
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
            'own_degree'             => Tinebase_Model_Relation::DEGREE_PARENT,
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
    
    /**
     * relation objects
     *
     * @var array
     */
    private $_relations = array();
    
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
        $this->_object = Tinebase_Relations::getInstance();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        
    }

    /**
     * testGetInstance().
     */
    public function testGetInstance()
    {
        $this->assertTrue($this->_object instanceof Tinebase_Relations);
    }
    
    public function testSetRelations()
    {
        // relations data is decoded to array from frontend server atm.
        $this->_object->setRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id'], $this->_relationData);
    }
    
    public function testGetRelations()
    {
        $relations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        
        $this->assertTrue($relations instanceof Tinebase_Record_RecordSet, 'relations are not a RecordSet');
        // NOTE: one of the related tasks in our testdata is not persistent, so we only get 2 relations back, 
        // as this missing task looks like a 'ACL not sufficient' task
        $this->assertEquals(3-1, count($relations));
        
        foreach ($relations as $relation) {
            // check each relation got an id
            $this->assertEquals(40, strlen($relation->getId()));
            // check related record got set/created
            $this->assertTrue(strlen($relation->related_backend) > 2);
            $this->assertFalse(empty($relation->related_id));
        }
    }

    /**
     * Tests if updateing succseeds when setting Relations
     *
     */
    public function testSetRelationsUpdate()
    {
        $relations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $relations->filter('related_model', 'Addressbook_Model_Contact')->getFirstRecord()->type = 'UPDATETEST';
        
        // NOTE: At the moment we need to set timezone to users timzone, as related records come as arrays and don't get
        // their dates converted in the JSON frontends
        foreach ($relations as $relation) {
            $relation->setTimezone(Zend_Registry::get('userTimeZone'));
        }
        
        $this->_object->setRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id'], $relations->toArray());
        
        $updatedRelations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $this->assertEquals('UPDATETEST', $updatedRelations->filter('related_model', 'Addressbook_Model_Contact')->getFirstRecord()->type);
    }
    
    /**
     * Test if updateing a related record works
     *
     */
    public function testSetRelationUpdateRelatedRecord()
    {
        $relations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);

        $relatedContacts = $relations->filter('related_model', 'Addressbook_Model_Contact');
        $relatedContacts->sort('related_model', 'ASC');
        $relatedContacts[0]->related_record->note = "Testing to update from relation set";
        
        // NOTE: At the moment we need to set timezone to users timzone, as related records come as arrays and don't get
        // their dates converted in the JSON frontends
        foreach ($relations as $relation) {
            $relation->setTimezone(Zend_Registry::get('userTimeZone'));
            $relation->related_record = $relation->related_record->toArray();
        }        
        $this->_object->setRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id'], $relations->toArray());
        
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
            'own_degree'             => Tinebase_Model_Relation::DEGREE_PARENT,
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
        $this->assertEquals(2, count($relations[12]), 'number of relations does not fit');
        
        
    }
    
    public function testSearchRelations()
    {
        // fetch a set of addressbook ids
        $relations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $relatedContacts = $relations->filter('related_model', 'Addressbook_Model_Contact');
        $adbFilterResult = $relatedContacts->related_id;
        
        // get all lead relations wehre the set of adbids is related to
        $filter = new Tinebase_Model_RelationFilter(array(
            array('field' => 'own_model',     'operator' => 'equals', 'value' => 'Crm_Model_Lead'),
            array('field' => 'related_model', 'operator' => 'equals', 'value' => 'Addressbook_Model_Contact'),
            array('field' => 'related_id',    'operator' => 'in'    , 'value' => $adbFilterResult)
        ));
        
        $db = Tinebase_Core::getDb();
        $db->query("\n /* fetch relations */ \n");
        $relations = $this->_object->search($filter, NULL);
        
        $this->assertEquals(count($adbFilterResult), count($relations), ' search relsult does not fit');
        
    }
    
    public function testBreakRelations()
    {
        $this->_object->setRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id'], array());
        $relations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $this->assertEquals(0, count($relations));
    }
    
    public function testCleanUp()
    {
        $backend = new Tinebase_Relation_Backend_Sql();
        $backend->purgeAllRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        $backend->purgeAllRelations($this->_crmId2['model'], $this->_crmId2['backend'], $this->_crmId2['id']);
    }
}

