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
     * Relation data as they come from e.g. JSON update request
     *
     * @var array
     */
    private $_relationData = array(
        array(
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => 'SQL',
            'own_id'                 => '268d586e46aad336de8fa2530b5b8faf921e494d',
            'own_degree'             => Tinebase_Relation_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Tasks_Model_Task',
            'related_backend'        => Tasks_Backend_Factory::SQL,
            'related_id'             => '8a572723e867dd73dd68d1740dd94f586eff5432',
            'type'                   => 'CRM_TASK'
        ),
        array(
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => 'SQL',
            'own_id'                 => '268d586e46aad336de8fa2530b5b8faf921e494d',
            'own_degree'             => Tinebase_Relation_Model_Relation::DEGREE_PARENT,
            'related_model'          => 'Tasks_Model_Task',
            'related_backend'        => '',
            'related_id'             => '',
            'related_record'           => array(
                'summary'              => 'phpunit test task for relations from crm',
                'description'          => 'This task was created by phpunit when testing relations',
                'due'                  => '2010-06-11T15:47:40+02:00',
            ),
            'type'                   => 'CRM_TASK',
        ),
        array(
            'own_model'              => '',
            'own_backend'            => '',
            'own_id'                 => '',
            'own_degree'             => Tinebase_Relation_Model_Relation::DEGREE_PARENT,
            'related_model'          => 'Addressbook_Model_Contact',
            'related_backend'        => '',
            'related_id'             => '',
            'related_record'           => array(
                'n_family'              => 'Weiss',
                'n_given'               => 'Cornelius',
                'bday'                  => '1979-06-05T00:00:00+02:00',
                'owner'                 => '',
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
        $relations[0]->type = 'UPDATETEST';
        
        $this->_object->setRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id'], $relations->toArray());
        $updatedRelations = $this->_object->getRelations($this->_crmId['model'], $this->_crmId['backend'], $this->_crmId['id']);
        
        $this->assertEquals('UPDATETEST', $updatedRelations[0]->type);
        //$updatedRelations->related_record = '';
        //print_r($updatedRelations->toArray());
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
    }
}

