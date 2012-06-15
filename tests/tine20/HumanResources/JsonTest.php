<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class HumanResources_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var HumanResources_Frontend_Json
     */
    protected $_json = array();
    /**
     * test department
     * 
     * @var Tinebase_Model_Department
     */
    protected $_department = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 HumanResources Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $this->_json = new HumanResources_Frontend_Json();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    /**
     * returns the current user
     * @return Tinebase_Model_User
     */
    protected function _getAccount()
    {
        return Tinebase_Core::getUser();
    }
    /**
     * returns the contact of the current user
     * @return Addressbook_Model_Contact
     */
    protected function _getContact()
    {
        return Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
    }
    
    protected function _getEmployee() {
        $a = $this->_getAccount();
        $c = $this->_getContact();
        
        $e = new HumanResources_Model_Employee(
            array(
                'number' => 1,
                'n_fn' => $c->n_fn,
                'account_id' => $a->getId()
                )
            );
        
        return $e;
    }
    
    /**
     * try to add a Project
     *
     */
    public function testAddProject()
    {
        $project = $this->_getProjectData();
        $projectData = $this->_json->saveProject($project);
        
        // checks
        $this->assertEquals($project['description'], $projectData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $projectData['created_by'], 'Created by has not been set.');
        
        // cleanup
        $this->_json->deleteHumanResources($projectData['id']);

        // check if it got deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        HumanResources_Controller_Project::getInstance()->get($projectData['id']);
    }
    
    /**
     * try to get a Project
     *
     */
    public function testGetProject()
    {
        $project = $this->_getProjectData();
        $projectData = $this->_json->saveProject($project);
        $projectData = $this->_json->getProject($projectData['id']);
        $this->_projectsToDelete[] = $projectData['id'];
        
        // checks
        $this->assertEquals($project['description'], $projectData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $projectData['created_by']);
    }

    /**
     * try to update a Project
     *
     */
    public function testUpdateProject()
    {
        $project = $this->_getProjectData();
        $projectData = $this->_json->saveProject($project);
        $this->_projectsToDelete[] = $projectData['id'];
        
        // update Project
        $projectData['description'] = "blubbblubb";
        $projectUpdated = $this->_json->saveProject($projectData);
        
        // check
        $this->assertEquals($projectData['id'], $projectUpdated['id']);
        $this->assertEquals($projectData['description'], $projectUpdated['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $projectUpdated['last_modified_by']);
    }
    
    /**
     * try to search a Project
     *
     */
    public function testSearchHumanResources()
    {
        // create
        $project = $this->_getProjectData();
        $projectData = $this->_json->saveProject($project);
        $this->_projectsToDelete[] = $projectData['id'];
        
        // search & check
        $search = $this->_json->searchHumanResources($this->_getProjectFilter($projectData['title']), $this->_getPaging());
        $this->assertEquals($project['description'], $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
    }

    /**
     * try to search projects with contact relation
     */
    public function testSearchHumanResourcesWithContactRelation()
    {
        $projectData = $this->_createProjectWithContactRelation();
        
        // search & check
        $filter = $this->_getProjectFilter($projectData['title']);
        $filter[] = array('field' => 'contact', 'operator' => 'AND', 'value' => array(
            array('field' => ':relation_type', 'operator' => 'in', 'value' => array('COWORKER')),
            array('field' => 'id', 'operator' => 'equals', 'value' => 'currentContact'),
        ));
        $filter[] = array('field' => 'container_id', 'operator' => 'equals', 'value' => array('path' => '/'));
        $search = $this->_json->searchHumanResources($filter, $this->_getPaging());
        $this->assertEquals($projectData['description'], $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals(4, count($search['filter'][1]['value']));
        $this->assertEquals(':relation_type', $search['filter'][1]['value'][0]['field']);
        $this->assertEquals(TRUE, $search['filter'][1]['value'][2]['implicit']);
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, 
            $search['filter'][1]['value'][1]['value']['id'], 'currentContact not resolved');
        $this->assertTrue(! isset($search['filter'][2]['implicit']), 'implicit flag should not be set in container filter');
    }

    /**
     * testPersistentRelationFilter
     */
    public function testPersistentRelationFilter()
    {
        $favoriteId = Tinebase_PersistentFilter::getInstance()->getPreferenceValues('HumanResources', NULL, 'All my projects');
        $favorite = Tinebase_PersistentFilter::getInstance()->getFilterById($favoriteId);
        
        $closedStatus = HumanResources_Config::getInstance()->get(HumanResources_Config::PROJECT_STATUS)->records->filter('is_open', 0);
        $this->assertEquals(array(
            array(
                'field'     => 'contact',
                'operator'  => 'AND',
                'value'     => array(array(
                    'field'     => ':relation_type',
                    'operator'  => 'in',
                    'value'     => HumanResources_Config::getInstance()->get(HumanResources_Config::PROJECT_ATTENDEE_ROLE)->records->id
                ), array(
                    'field'     => ':id',
                    'operator'  => 'equals',
                    'value'     => Addressbook_Model_Contact::CURRENTCONTACT,
                )
            )),
            array('field' => 'status',    'operator' => 'notin',  'value' => $closedStatus->getId()),
        ), $favorite->toArray());
    }

    /**
     * testUpdatePersistentFilterIdsAndLabel
     * -> id should not be saved, label must be saved
     */
    public function testUpdatePersistentFilterIdsAndLabel()
    {
        $favoriteId = Tinebase_PersistentFilter::getInstance()->getPreferenceValues('HumanResources', NULL, 'All my projects');
        $favorite = Tinebase_PersistentFilter::getInstance()->get($favoriteId);
        $favorite->name = 'testfilter';
        unset($favorite->id);
        // add filter with id and label
        $favorite->filters->addFilter(new Tinebase_Model_Filter_Text(array(
            'field'     => 'title',
            'operator'  => 'equals',
            'value'     => 'lala',
            'id'        => 'somenonpersistentid',
            'label'     => 'somepersistentlabel',
        )));
        $updatedFilter = Tinebase_PersistentFilter::getInstance()->create($favorite);
        $filterArray = $updatedFilter->filters->toArray();
        $this->assertEquals('somepersistentlabel', $filterArray[2]['label']);
        $this->assertTrue(! isset($filterArray[2]['id']));
    }
        
    /**
     * testFilterIds
     */
    public function testFilterIdsAndLabel()
    {
        $filterJson = '[{"condition":"OR","filters":'
            . '[{"condition":"AND","filters":[{"field":"contact","operator":"AND","value":'
                . '[{"field":"relation_type","operator":"in","value":["COWORKER","RESPONSIBLE"],"id":"ext-record-62"},'
                . '{"field":":id","operator":"AND"}],"id":"ext-record-23"},'
                . '{"field":"status","operator":"notin","value":["COMPLETED","CANCELLED"],"id":"ext-record-78","label":"statusfilter"}],"id":"ext-comp-1088","label":"filter1"}'
            . ',{"condition":"AND","filters":[{"field":"query","operator":"contains","value":"","id":"ext-record-94"}],"id":"ext-comp-1209","label":"filter2"}]}]';
        $result = $this->_json->searchHumanResources(Zend_Json::decode($filterJson), array());
        
        $this->assertEquals("ext-comp-1088", $result['filter'][0]['filters'][0]['id']);
        $this->assertEquals("ext-comp-1209", $result['filter'][0]['filters'][1]['id']);
        $this->assertEquals("filter1", $result['filter'][0]['filters'][0]['label']);
        $this->assertEquals("filter2", $result['filter'][0]['filters'][1]['label']);
        $this->assertEquals("statusfilter", $result['filter'][0]['filters'][0]['filters'][1]['label']);
    }
    
    /************ protected helper funcs *************/
    
    /**
     * get Project
     *
     * @return array
     */
    protected function _getProjectData()
    {
        return array(
            'title'         => Tinebase_Record_Abstract::generateUID(),
            'description'   => 'blabla',
            'status'        => 'IN-PROCESS',
        );
    }
        
    /**
     * get paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'creation_time',
            'dir' => 'ASC',
        );
    }

    /**
     * get Project filter
     *
     * @return array
     */
    protected function _getProjectFilter($_projectName)
    {
        return array(
            array(
                'field' => 'title', 
                'operator' => 'contains', 
                'value' => $_projectName
            ),     
        );
    }
    
    /**
     * get contact data
     * 
     * @return array
     */
    protected function _getContactData()
    {
        return array(
            'n_family'          => 'PHPUNIT',
            'org_name'          => Tinebase_Record_Abstract::generateUID(),
            'tel_cell_private'  => '+49TELCELLPRIVATE',
        );
    }

    /**
     * createProjectWithContactRelation
     * 
     * @return array
     */
    protected function _createProjectWithContactRelation()
    {
        $project = $this->_getProjectData();
        $contact = $this->_getContactData();
        $project['relations'] = array(
            array(
                'own_model'              => 'HumanResources_Model_Project',
                'own_backend'            => 'Sql',
                'own_id'                 => 0,
                'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                'type'                   => 'COWORKER',
                'related_record'         => NULL,
                'related_backend'        => 'Sql',
                'related_id'             => Tinebase_Core::getUser()->contact_id,
                'related_model'          => 'Addressbook_Model_Contact',
                'remark'                 => NULL,
            )
        );
        $projectData = $this->_json->saveProject($project);
        $this->_projectsToDelete[] = $projectData['id'];
        
        return $projectData;
    }
}
