<?php

/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Projects
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Tinebase_Group
 */
class Projects_JsonTest extends TestCase
{
    /**
     * @var Projects_Frontend_Json
     */
    protected $_json;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->_json = new Projects_Frontend_Json();
    }

    /**
     * try to add a Project
     * @group nodockerci
     */
    public function testAddProject()
    {
        $project = $this->_getProjectData();
        $projectData = $this->_json->saveProject($project);
        
        // checks
        $this->assertEquals($project['description'], $projectData['description']);
        
        // created by should be resolved
        $this->assertTrue(is_array($projectData['created_by']), 'Created by has not been resolved.');
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $projectData['created_by']['accountId']);
        
        // cleanup
        $this->_json->deleteProjects($projectData['id']);

        // check if it got deleted
        $this->expectException('Tinebase_Exception_NotFound');
        Projects_Controller_Project::getInstance()->get($projectData['id']);
    }
    
    /**
     * try to get a Project
     * @group nodockerci
     */
    public function testGetProject()
    {
        $project = $this->_getProjectData();
        $projectData = $this->_json->saveProject($project);
        $projectData = $this->_json->getProject($projectData['id']);
        
        // checks
        $this->assertEquals($project['description'], $projectData['description']);
        
        // created by should be resolved
        $this->assertTrue(is_array($projectData['created_by']), 'Created by has not been resolved.');
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $projectData['created_by']['accountId']);
    }

    /**
     * try to update a Project
     * @group nodockerci
     */
    public function testUpdateProject()
    {
        $project = $this->_getProjectData();
        $projectData = $this->_json->saveProject($project);
        
        // update Project
        $projectData['description'] = "blubbblubb";
        $projectUpdated = $this->_json->saveProject($projectData);
        
        // check
        $this->assertEquals($projectData['id'], $projectUpdated['id']);
        $this->assertEquals($projectData['description'], $projectUpdated['description']);
        
        // last modified by should be resolved
        $this->assertTrue(is_array($projectUpdated['last_modified_by']), 'Last modified by has not been resolved.');
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $projectUpdated['last_modified_by']['accountId']);
    }
    
    /**
     * try to search a Project
     * @group nodockerci
     */
    public function testSearchProjects()
    {
        // create
        $project = $this->_getProjectData();
        $projectData = $this->_json->saveProject($project);
        
        // search & check
        $search = $this->_json->searchProjects($this->_getProjectFilter($projectData['title']), $this->_getPaging());
        $this->assertEquals($project['description'], $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
    }
    
    /**
     * testSearchProjectsWithMultipleFilters / taken from a bugreport
     */
    public function testSearchProjectsWithMultipleFilters()
    {
        $filter = '[
            {
                "condition": "OR",
                "filters": [
                    {
                        "condition": "AND",
                        "filters": [
                            {
                                "field": "status",
                                "operator": "notin",
                                "value": [
                                    "COMPLETED",
                                    "CANCELLED"
                                ],
                                "id": "ext-record-347"
                            },
                            {
                                "field": "contact",
                                "operator": "AND",
                                "value": [
                                    {
                                        "field": "list",
                                        "operator": "equals",
                                        "value": null,
                                        "id": "ext-record-1073"
                                    },
                                    {
                                        "field": ":relation_type",
                                        "operator": "in",
                                        "value": [
                                            "COWORKER",
                                            "RESPONSIBLE"
                                        ],
                                        "id": "ext-record-395"
                                    }
                                ],
                                "id": "ext-record-379"
                            }
                        ],
                        "id": "ext-comp-1330",
                        "label": "Projekte"
                    }
                ]}]';
        $search = $this->_json->searchProjects(Zend_Json::decode($filter), $this->_getPaging());
        $this->assertGreaterThanOrEqual(0, $search['totalcount']);
    }

    /**
     * try to search projects with contact relation
     * @group nodockerci
     */
    public function testSearchProjectsWithContactRelation()
    {
        $projectData = $this->_createProjectWithContactRelation();
        
        // search & check
        $filter = $this->_getProjectFilter($projectData['title']);
        $filter[] = array('field' => 'contact', 'operator' => 'AND', 'value' => array(
            array('field' => ':relation_type', 'operator' => 'in', 'value' => array('COWORKER')),
            array('field' => 'id', 'operator' => 'equals', 'value' => 'currentContact'),
        ));
        $filter[] = array('field' => 'container_id', 'operator' => 'equals', 'value' => array('path' => '/'));
        $search = $this->_json->searchProjects($filter, $this->_getPaging());
        $this->assertEquals($projectData['description'], $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        // NOTE: this assertion is strange because the results vary between 3 and 4
        // maybe dependent on Addressbook_Controller_Contact::getInstance()->doContainerACLChecks()
        $filterValues = $search['filter'][1]['value'];
        $this->assertCount(2, $filterValues, 'filter values: ' . print_r($filterValues, true));
        $this->assertEquals(':relation_type', $search['filter'][1]['value'][0]['field']);
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, 
            $search['filter'][1]['value'][1]['value']['id'], 'currentContact not resolved');
        $this->assertTrue(! isset($search['filter'][2]['implicit']), 'implicit flag should not be set in container filter');
    }

    /**
     * testPersistentRelationFilter
     */
    public function testPersistentRelationFilter()
    {
        $favoriteId = Tinebase_PersistentFilter::getInstance()->getPreferenceValues('Projects', NULL, 'All my projects');
        $favorite = Tinebase_PersistentFilter::getInstance()->getFilterById($favoriteId);
        
        $this->assertTrue($favorite instanceof Tinebase_Model_Filter_FilterGroup);
        $closedStatus = Projects_Config::getInstance()->get(Projects_Config::PROJECT_STATUS)->records->filter('is_open', 0);
        $this->assertEquals(array(
            array(
                'field'     => 'contact',
                'operator'  => 'AND',
                'value'     => array(array(
                    'field'     => ':relation_type',
                    'operator'  => 'in',
                    'value'     => Projects_Config::getInstance()->get(Projects_Config::PROJECT_ATTENDEE_ROLE)->records->id
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
        $favoriteId = Tinebase_PersistentFilter::getInstance()->getPreferenceValues('Projects', NULL, 'All my projects');
        $favorite = Tinebase_PersistentFilter::getInstance()->get($favoriteId);
        
        $this->assertTrue($favorite->filters instanceof Tinebase_Model_Filter_FilterGroup);
        
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
                . '[{"field":"relation_type","operator":"in","value":["COWORKER","RESPONSIBLE"],"id":"ext-record-62"}]'
                . ',"id":"ext-record-23"},'
                . '{"field":"status","operator":"notin","value":["COMPLETED","CANCELLED"],"id":"ext-record-78","label":"statusfilter"}],"id":"ext-comp-1088","label":"filter1"}'
            . ',{"condition":"AND","filters":[{"field":"query","operator":"contains","value":"","id":"ext-record-94"}],"id":"ext-comp-1209","label":"filter2"}]}]';
        $result = $this->_json->searchProjects(Zend_Json::decode($filterJson), array());
        
        $this->assertEquals("ext-comp-1088", $result['filter'][0]['filters'][0]['id']);
        $this->assertEquals("ext-comp-1209", $result['filter'][0]['filters'][1]['id']);
        $this->assertEquals("filter1", $result['filter'][0]['filters'][0]['label']);
        $this->assertEquals("filter2", $result['filter'][0]['filters'][1]['label']);
        $this->assertEquals("statusfilter", $result['filter'][0]['filters'][0]['filters'][1]['label']);
    }
    
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
     * createProjectWithContactRelation
     * 
     * @return array
     */
    protected function _createProjectWithContactRelation()
    {
        $project = $this->_getProjectData();
        $project['relations'] = array(
            array(
                'own_model'              => 'Projects_Model_Project',
                'own_backend'            => 'Sql',
                'own_id'                 => 0,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'type'                   => 'COWORKER',
                'related_backend'        => 'Sql',
                'related_id'             => Tinebase_Core::getUser()->contact_id,
                'related_model'          => 'Addressbook_Model_Contact',
                'remark'                 => NULL,
            )
        );
        $projectData = $this->_json->saveProject($project);
        
        return $projectData;
    }
    
    /**
     * testPersonalContainers
     * 
     * @see 0007098: personal containers of other users are shown below personal container node
     */
    public function testPersonalContainers()
    {
        $containerJson = new Tinebase_Frontend_Json_Container();
        $personalContainers = $containerJson->getContainer(Projects_Model_Project::class, 'personal', Tinebase_Core::getUser()->getId());
        $this->assertEquals(1, count($personalContainers), 'this should only return 1 personal container: ' . print_r($personalContainers, TRUE));
    }

    /**
     * testAddRecordAttachments
     * @group nodockerci
     */
    public function testAddRecordAttachments()
    {
        $project = new Projects_Model_Project($this->_getProjectData(), true);
        $this->_addRecordAttachment($project);
        $newproject1 = $this->_json->saveProject($project->toArray());
        self::assertEquals(1, count($newproject1['attachments']));

        $project = new Projects_Model_Project($this->_getProjectData(), true);
        $this->_addRecordAttachment($project);
        $newproject2 = $this->_json->saveProject($project->toArray());
        self::assertEquals(1, count($newproject2['attachments']));
    }

    public function testProjectTasks()
    {
        $task = Tasks_Controller_Task::getInstance()->create(new Tasks_Model_Task([
            'status' => 'NEEDS-ACTION',
            'summary' => 'sfvsdv',
        ]));
        $projectData = $this->_getProjectData();
        $projectData[Projects_Model_Project::FLD_TASKS] = [
            $task->toArray()
        ];
        $savedProjectData = $this->_json->saveProject($projectData);

        self::assertIsArray($savedProjectData[Projects_Model_Project::FLD_TASKS], print_r($savedProjectData, true));
        self::assertCount(1, $savedProjectData[Projects_Model_Project::FLD_TASKS], print_r($savedProjectData, true));

        $filter = [[
            'field' => 'tasks',
            'operator' => 'definedBy',
            'value' => [
                ['field' => 'query', 'operator' => 'contains', 'value' => 'sfvsdv']
            ],
        ]];

        $searchResult = $this->_json->searchProjects($filter, []);
        self::assertEquals(1, $searchResult['totalcount']);
    }
}
