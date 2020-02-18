<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Crm_Json
 */
class Crm_JsonTest extends Crm_AbstractTest
{
    /**
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * Backend
     *
     * @var Crm_Frontend_Json
     */
    protected $_instance = null;
    
    /**
     * fs controller
     *
     * @var Tinebase_FileSystem
     */
    protected $_fsController;

    /**
     * customfield name
     *
     * @var string
     */
    protected $_cfcName = null;

   /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->_fsController = Tinebase_FileSystem::getInstance();
        Crm_Controller_Lead::getInstance()->duplicateCheckFields(array());
    }

    /**
     * @return Crm_Frontend_Json
     */
    protected function _getUit()
    {
        if ($this->_instance === null) {
            $this->_instance = new Crm_Frontend_Json();
        }

        return new $this->_instance;
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        if (isset($this->_objects['paths'])) {
            foreach ($this->_objects['paths'] as $path) {
                try {
                    $this->_fsController->rmdir($path, TRUE);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    // already deleted
                }
            }
        }
        
        parent::tearDown();
        Crm_Controller_Lead::getInstance()->duplicateCheckFields(array('lead_name'));
    }

    /**
     * try to add/search/delete a lead with linked contact, task and product
     * 
     * @see 0007214: if lead with linked task is saved, alarm is discarded
     */
    public function testAddGetSearchDeleteLead()
    {
        $savedLead = $this->saveLead();
        $getLead = $this->_getUit()->getLead($savedLead['id']);
        $searchLeads = $this->_getUit()->searchLeads($this->_getLeadFilter(), '');
        
        // test manual resolving of organizer in related_record and set it back for following tests
        for ($i = 0; $i < count($getLead['relations']); $i++) {
            if (isset($getLead['relations'][$i]['related_record']['organizer'])) {
                $this->assertTrue(is_array($getLead['relations'][$i]['related_record']['organizer']));
                $getLead['relations'][$i]['related_record']['organizer'] = $getLead['relations'][$i]['related_record']['organizer']['accountId'];
            }
        }
        
        // assertions
        $this->assertEquals($getLead, $savedLead);
        $this->assertEquals($getLead['notes'][0]['note'], 'phpunit test note');
        $this->assertTrue($searchLeads['totalcount'] > 0, print_r($searchLeads, true));
        $this->assertTrue(isset($searchLeads['totalleadstates']) && count($searchLeads['totalleadstates']) > 0);
        $this->assertEquals($getLead['description'], $searchLeads['results'][0]['description']);
        $this->assertEquals(200, $searchLeads['results'][0]['turnover'], 'turnover has not been calculated using product prices');
        $this->assertEquals($searchLeads['results'][0]['turnover']*$getLead['probability']/100, $searchLeads['results'][0]['probableTurnover']);
        // now we need 2 relations here (frontend search shall return relations with related_model Addressbook_Model_Contact or Sales_Model_Product
        $this->assertEquals(2, count($searchLeads['results'][0]['relations']), 'did not get all relations');

        $relatedTask = null;
        foreach($getLead['relations'] as $rel) {
            if ($rel['type'] == 'TASK') {
                $relatedTask = $rel['related_record'];
            }
        }

        $this->assertTrue($relatedTask !== null);
        $this->assertEquals($this->_getTask()->summary, $relatedTask['summary'], 'task summary does not match');
        $defaultTaskContainerId = Tinebase_Core::getPreference('Tasks')->getValue(Tasks_Preference::DEFAULTTASKLIST);
        $this->assertEquals($defaultTaskContainerId, $relatedTask['container_id']);
        $this->assertTrue(isset($relatedTask['alarms']) && count($relatedTask['alarms']) === 1, 'alarm missing in related task: ' . print_r($relatedTask, TRUE));
        
        $relatedTaskId = $relatedTask['id'];
        $relatedTask = NULL;
        
        // get related records and check relations
        foreach ($searchLeads['results'][0]['relations'] as $relation) {
            switch ($relation['type']) {
                case 'PRODUCT':
                    //print_r($relation);
                    $this->assertEquals(200, $relation['remark']['price'], 'product price (remark) does not match');
                    $relatedProduct = $relation['related_record'];
                    break;
                case 'TASK':
                    $relatedTask = $relation['related_record'];
                    break;
                case 'PARTNER':
                    $relatedContact = $relation['related_record'];
                    break;
            }
        }
        $this->assertTrue(isset($relatedContact), 'contact not found');
        $this->assertEquals($this->_getContact()->n_fn, $relatedContact['n_fn'], 'contact name does not match');
        
        $this->assertFalse(is_array($relatedTask), 'task must not be found');
        
        $this->assertTrue(isset($relatedProduct), 'product not found');
        $this->assertEquals($this->_getProduct()->name, $relatedProduct['name'], 'product name does not match');
        
        // delete all
        $this->_getUit()->deleteLeads($savedLead['id']);
        Addressbook_Controller_Contact::getInstance()->delete($relatedContact['id']);
        Sales_Controller_Product::getInstance()->delete($relatedProduct['id']);
        
        // check if delete worked
        $result = $this->_getUit()->searchLeads($this->_getLeadFilter(), '');
        $this->assertEquals(0, $result['totalcount']);
        
        // check if linked task got removed as well
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Tasks_Controller_Task::getInstance()->get($relatedTaskId);
    }
    
    /**
     * save lead with relations
     * 
     * @return array
     */
    public function saveLead()
    {
        $contact    = $this->_getContact();
        $task       = $this->_getTask();
        $lead       = $this->_getLead();
        $product    = $this->_getProduct();
        $price      = 200;
        
        $leadData = $lead->toArray();
        $leadData['relations'] = array(
            array('type'  => 'TASK',    'related_record' => $task->toArray()),
            array('type'  => 'PARTNER', 'related_record' => $contact->toArray()),
            array('type'  => 'PRODUCT', 'related_record' => $product->toArray(), 'remark' => array('price' => $price)),
        );
        // add note
        $note = array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',
        );
        $leadData['notes'] = array($note);
        
        $savedLead = $this->_getUit()->saveLead($leadData);
        return $savedLead;
    }
    
    /**
     * test tag filter (adds a contact with the same id + tag)
     * 
     * see bug #4834 (http://forge.tine20.org/mantisbt/view.php?id=4834)
     */
    public function testTagFilter()
    {
        $lead       = $this->_getLead();
        $savedLead = $this->_getUit()->saveLead($lead->toArray());
        
        $sharedTagName = Tinebase_Record_Abstract::generateUID();
        $tag = new Tinebase_Model_Tag(array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => $sharedTagName,
            'description' => 'testTagFilter',
            'color' => '#009B31',
        ));
        $contact    = $this->_getContact();
        $contact->setId($savedLead['id']);
        
        $contact->tags = array($tag);
        $savedContact = Addressbook_Controller_Contact::getInstance()->create($contact, FALSE);
        $tag = $savedContact->tags->getFirstRecord();
        
        $filter = array(
            array('field' => 'tag',           'operator' => 'equals',       'value' => $tag->getId()),
        );
        
        $result = $this->_getUit()->searchLeads($filter, array());
        $this->assertEquals(0, $result['totalcount'], 'Should not find the lead!');
    }    
    
    /**
     * testSearchByBrokenFilter
     * 
     * @see 0005990: cardinality violation when searching for leads / http://forge.tine20.org/mantisbt/view.php?id=5990
     */
    public function testSearchByBrokenFilter()
    {
        $filter = Zend_Json::decode('[{"field":"query","operator":"contains","value":"test"},{"field":"container_id","operator":"equals","value":{"path":"/"}},{"field":"contact","operator":"AND","value":[{"field":":id","operator":"equals","value":{"n_fn":"","n_fileas":"","org_name":"","container_id":"2576"}}]}]');
        $result = $this->_getUit()->searchLeads($filter, array());
        $this->assertEquals(0, $result['totalcount']);
    }
    
    /**
     * add relation, remove relation and add relation again
     * 
     * see bug #4840 (http://forge.tine20.org/mantisbt/view.php?id=4840)
     */
    public function testAddRelationAgain()
    {
        $contact    = $this->_getContact();
        $savedContact = Addressbook_Controller_Contact::getInstance()->create($contact, FALSE);
        $lead       = $this->_getLead();
        
        $leadData = $lead->toArray();
        $leadData['relations'] = array(
            array('type'  => 'PARTNER', 'related_record' => $savedContact->toArray()),
        );
        $savedLead = $this->_getUit()->saveLead($leadData);
        
        $savedLead['relations'] = array();
        $savedLead = $this->_getUit()->saveLead($savedLead);
        $this->assertEquals(0, count($savedLead['relations']), 'relations should be removed: '
            . print_r($savedLead['relations'], true));
        
        $savedLead['relations'] = array(
            array('type'  => 'PARTNER', 'related_record' => $savedContact->toArray()),
        );
        $savedLead = $this->_getUit()->saveLead($savedLead);
        
        $this->assertEquals(1, count($savedLead['relations']), 'Relation has not been added');
        $this->assertEquals($contact->n_fn, $savedLead['relations'][0]['related_record']['n_fn'], 'Contact name does not match');
    }
    
    /**
     * testRelationWithoutType
     * 
     * @see 0006206: relation type field can be empty
     */
    public function testRelationWithoutType()
    {
        $contact      = $this->_getContact();
        $savedContact = Addressbook_Controller_Contact::getInstance()->create($contact, FALSE);
        $lead         = $this->_getLead();
        
        $leadData = $lead->toArray();
        $leadData['relations'] = array(
            array('type'  => '', 'related_record' => $savedContact->toArray()),
        );
        $savedLead = $this->_getUit()->saveLead($leadData);
        
        $this->assertEquals(1, count($savedLead['relations']), 'Relation has not been added');
        $this->assertEquals('CUSTOMER', $savedLead['relations'][0]['type'], 'default type should be CUSTOMER');
    }

    public function testUpdateContactRelationOnCreate()
    {
        $contact      = $this->_getContact();
        $savedContact = Addressbook_Controller_Contact::getInstance()->create($contact, FALSE);
        $lead         = $this->_getLead();

        $leadData = $lead->toArray();
        $street = 'Heinrichstrasse 193';
        $contactData = $savedContact->toArray();
        $contactData['adr_one_street'] = $street;
        $leadData['relations'] = array(
            array('type'  => 'CUSTOMER', 'related_record' => $contactData),
        );
        $savedLead = $this->_getUit()->saveLead($leadData);
        $this->assertTrue(isset($savedLead['relations'][0]));
        $contactRelation = $savedLead['relations'][0];
        $this->assertEquals($street, $contactRelation['related_record']['adr_one_street'],
            'street not set in contact: ' . print_r($contactRelation, true));
    }
    
    /**
     * testConcurrentRelationSetting
     * 
     * @see 0007108: inspect and solve concurrency conflicts when setting lead relations
     * @see 0000554: modlog: records can't be updated in less than 1 second intervals
     */
    public function testConcurrentRelationSetting()
    {
        $leadData = $this->_getUit()->saveLead($this->_getLead()->toArray());
        $task = $this->_getTask();
        
        $taskJson = new Tasks_Frontend_Json();
        $taskData = $task->toArray();
        $taskData['relations'] = array(
            array(
                'type'  => 'TASK',
                'own_model' => 'Tasks_Model_Task',
                'own_backend' => 'Sql',
                'related_degree' => 'sibling',
                'related_model' => 'Crm_Model_Lead',
                'related_backend' => 'Sql',
                'related_id' => $leadData['id'],
                'related_record' => $leadData
            ),
        );
        
        $taskData = $taskJson->saveTask($taskData);
        $taskData['description'] = 1;
        $taskJson->saveTask($taskData);
        
        $savedLead = $this->_getUit()->getLead($leadData['id']);
        $savedLead['relations'][0]['related_record']['description'] = '2';
        $savedLead['relations'][0]['related_record']['due'] = '2012-10-18 12:54:33';
        
        // client may send wrong seq -> this should cause a concurrency conflict
        $savedLead['relations'][0]['related_record']['seq'] = 0;
        try {
            $this->_getUit()->saveLead($savedLead);
            $this->fail('expected concurrency exception');
        } catch (Tinebase_Exception_ConcurrencyConflict $ttecc) {
            $this->assertEquals('concurrency conflict!', $ttecc->getMessage());
        }
    }
    
    /**
     * @see #8840: relations config - constraints from the other side
     *      - validate in backend
     *      
     *      https://forge.tine20.org/mantisbt/view.php?id=8840
     */
    public function testConstraintsOtherSide()
    {
        $leadData1 = $this->_getUit()->saveLead($this->_getLead(FALSE, FALSE)->toArray());
        $task = $this->_getTask();
        
        $taskJson = new Tasks_Frontend_Json();
        $taskData = $task->toArray();
        $taskData['relations'] = array(
            array(
                'type'  => 'TASK',
                'own_model' => 'Tasks_Model_Task',
                'own_backend' => 'Sql',
                'related_degree' => 'sibling',
                'related_model' => 'Crm_Model_Lead',
                'related_backend' => 'Sql',
                'related_id' => $leadData1['id'],
                'related_record' => $leadData1
            ),
        );
        
        $taskData = $taskJson->saveTask($taskData);
        
        $leadData2 = $this->_getUit()->saveLead($this->_getLead(FALSE, FALSE)->toArray());
        $taskData['relations'][] = array(
            'type'  => 'TASK',
            'own_model' => 'Tasks_Model_Task',
            'own_backend' => 'Sql',
            'related_degree' => 'sibling',
            'related_model' => 'Crm_Model_Lead',
            'related_backend' => 'Sql',
            'related_id' => $leadData2['id'],
            'related_record' => $leadData2
        );
        
        $this->setExpectedException('Tinebase_Exception_InvalidRelationConstraints');
        $taskJson->saveTask($taskData);
    }
    
    /**
     * testOtherRecordConstraintsConfig
     */
    public function testOtherRecordConstraintsConfig()
    {
        $leadData1 = $this->_getUit()->saveLead($this->_getLead(FALSE, FALSE)->toArray());
        $task = $this->_getTask();
        
        $taskJson = new Tasks_Frontend_Json();
        $leadJson = new Crm_Frontend_Json();
        
        $taskData = $task->toArray();
        $taskData['relations'] = array(
            array(
                'type'  => 'TASK',
                'own_model' => 'Tasks_Model_Task',
                'own_backend' => 'Sql',
                'related_degree' => 'sibling',
                'related_model' => 'Crm_Model_Lead',
                'related_backend' => 'Sql',
                'related_id' => $leadData1['id'],
                'related_record' => $leadData1
            ),
        );
        
        $taskData = $taskJson->saveTask($taskData);
        
        $leadData2 = $this->_getUit()->saveLead($this->_getLead(FALSE, FALSE)->toArray());
        
        $leadData2['relations'] = array(
            array(
                'type'  => 'TASK',
                'own_model' => 'Crm_Model_Lead',
                'own_backend' => 'Sql',
                'related_degree' => 'sibling',
                'related_model' => 'Tasks_Model_Task',
                'related_backend' => 'Sql',
                'related_id' => $taskData['id'],
                'related_record' => $taskData
            )
        );
        
        $this->setExpectedException('Tinebase_Exception_InvalidRelationConstraints');
        
        $leadJson->saveLead($leadData2);
    }
    
    /**
     * try to add multiple related tasks with one save
     * @return array
     */
    public function testLeadWithMultipleTasks()
    {
        $lead = $this->_getLead();
        $task1 = $this->_getTask();
        $task2 = $this->_getTask();
        
        
        $leadData = $lead->toArray();
        $leadData['relations'] = array(
                array('type'  => 'TASK', 'related_record' => $task1->toArray()),
                array('type'  => 'TASK', 'related_record' => $task2->toArray())
        );
        
        $savedLead = $this->_getUit()->saveLead($leadData);
        $this->assertEquals(2, count($savedLead['relations']), 'Relations missing');

        return $savedLead;
    }

    /**
     * tasks should not be deleted
     */
    public function testUpdateLeadWithoutPermissionToRelatedTasks()
    {
        $lead = $this->testLeadWithMultipleTasks();

        // set permissions to lead container for sclever
        $leadContainer = Tinebase_Container::getInstance()->getDefaultContainer(Crm_Model_Lead::class);
        $this->_setPersonaGrantsForTestContainer($leadContainer, 'sclever');

        // switch to sclever
        Tinebase_Core::setUser($this->_personas['sclever']);

        // update lead
        $lead['description'] = 'updated';
        $updateLead = $this->_getUit()->saveLead($lead);
        self::assertEquals(2, count($updateLead['relations']));

        // switch back
        Tinebase_Core::setUser($this->_originalTestUser);

        // tasks should not get deleted
        $myLead = $this->_getUit()->getLead($lead['id']);
        $this->assertEquals(2, count($myLead['relations']), 'Relations missing');
    }
    
    /**
     * get contact
     * 
     * @return Addressbook_Model_Contact
     */
    protected function _getContact()
    {
        return new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'assistent'             => 'Cornelius Weiß',
            'bday'                  => '1975-01-02 03:04:05', // new Tinebase_DateTime???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'note'                  => 'Bla Bla Bla',
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Lars',
            'n_middle'              => 'no middle name',
            'n_prefix'              => 'no prefix',
            'n_suffix'              => 'no suffix',
            'org_name'              => 'Metaways Infosystems GmbH',
            'org_unit'              => 'Tine 2.0',
            'tel_assistent'         => '+49TELASSISTENT',
            'tel_car'               => '+49TELCAR',
            'tel_cell'              => '+49TELCELL',
            'tel_cell_private'      => '+49TELCELLPRIVATE',
            'tel_fax'               => '+49TELFAX',
            'tel_fax_home'          => '+49TELFAXHOME',
            'tel_home'              => '+49TELHOME',
            'tel_pager'             => '+49TELPAGER',
            'tel_work'              => '+49TELWORK',
        ));
    }

    /**
     * get task
     * 
     * @return Tasks_Model_Task
     */
    protected function _getTask()
    {
        return new Tasks_Model_Task(array(
            'created_by'           => Zend_Registry::get('currentAccount')->getId(),
            'creation_time'        => Tinebase_DateTime::now(),
            'percent'              => 70,
            'due'                  => Tinebase_DateTime::now()->addMonth(1),
            'summary'              => 'phpunit: crm test task',
            'alarms'               => new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(array(
                'minutes_before'    => 0
            )), TRUE),
        ));
    }
    
    /**
     * get lead
     * 
     * @param boolean $addCf
     * @param boolean $addTags
     * @return Crm_Model_Lead
     */
    protected function _getLead($addCf = TRUE, $addTags = TRUE)
    {
        if ($addCf) {
            $cfc = Tinebase_CustomFieldTest::getCustomField(array(
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId(),
                'model'          => 'Crm_Model_Lead',
                'name'           => Tinebase_Record_Abstract::generateUID(),
            ));
            $this->_cfcName = $cfc->name;
            
            $cfs = array(
                $this->_cfcName => '1234'
            );
            
            Tinebase_CustomField::getInstance()->addCustomField($cfc);
        } else {
            $cfs = array();
        }
        
        if ($addTags) {
            $tags = array(
                array('name' => 'lead tag', 'type' => Tinebase_Model_Tag::TYPE_SHARED)
            );
        } else {
            $tags = array();
        }
        
        return new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit LEAD',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'  => Tinebase_Container::getInstance()->getDefaultContainer(Crm_Model_Lead::class)->getId(),
            'start'         => Tinebase_DateTime::now(),
            'description'   => 'Description',
            'end'           => NULL,
            'turnover'      => 0,
            'probability'   => 70,
            'end_scheduled' => NULL,
            'tags'          => $tags,
            'customfields'  => $cfs
        ));
    }
    
    /**
     * get product
     * 
     * @return Sales_Model_Product
     */
    protected function _getProduct()
    {
        return new Sales_Model_Product(array(
            'name'  => 'PHPUnit test product',
            'price' => 10000,
        ));
    }
    
    /**
     * get lead filter
     * 
     * @return array
     */
    protected function _getLeadFilter()
    {
        return array(
            array('field' => 'query',           'operator' => 'contains',       'value' => 'PHPUnit'),
        );
    }

    /**
     * testRelatedModlog
     * 
     * @see 0000996: add changes in relations/linked objects to modlog/history
     */
    public function testRelatedModlog()
    {
        // create lead with tag, customfield and related contacts
        $savedLead = $this->saveLead();
        
        // change relations, customfields + tags
        $savedLead['tags'][] = array('name' => 'another tag', 'type' => Tinebase_Model_Tag::TYPE_PERSONAL);
        foreach ($savedLead['relations'] as $key => $value) {
            if ($value['type'] == 'PARTNER') {
                $savedLead['relations'][$key]['type'] = 'CUSTOMER';
            }
            if ($value['type'] == 'TASK') {
                unset($savedLead['relations'][$key]);
            }
        }
        Crm_Controller_Lead::getInstance()->resolveCustomfields(true);
        $savedLead['customfields'][$this->_cfcName] = '5678';
        $updatedLead = $this->_getUit()->saveLead($savedLead);
        
        // check modlog + history
        $modifications = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog');
        $modifications->addRecord(Tinebase_Timemachine_ModificationLog::getInstance()->getModifications('Crm', $updatedLead['id'])->getLastRecord());
        $diff = new Tinebase_Record_Diff(json_decode($modifications->getFirstRecord()->new_value, true));
        $changedAttributes = Tinebase_Timemachine_ModificationLog::getModifiedAttributes($modifications);
        
        //print_r($updatedLead);
        $this->assertEquals(3, count($changedAttributes), 'expected 3 modifications: ' . print_r($modifications->toArray(), TRUE));
        foreach ($changedAttributes as $attribute) {
            switch ($attribute) {
                case 'customfields':
                    $this->assertTrue(isset($diff->diff['customfields']) && is_array($diff->diff['customfields']) && isset($diff->diff['customfields'][$this->_cfcName]));
                    $this->assertEquals('5678', $diff->diff['customfields'][$this->_cfcName]);
                    break;
                case 'relations':
                    $diffSet = new Tinebase_Record_RecordSetDiff($diff->diff['relations']);
                    $this->assertEquals(1, count($diffSet->added));
                    $this->assertEquals(2, count($diffSet->removed), print_r($diffSet->toArray(), true));
                    $this->assertEquals(0, count($diffSet->modified), 'relations modified mismatch: ' . print_r($diffSet->toArray(), TRUE));
                    $this->assertTrue(isset($diffSet->added[0]['type']));
                    $this->assertEquals('CUSTOMER', $diffSet->added[0]['type'], 'type diff is not correct: ' . print_r($diffSet->toArray(), TRUE));
                    break;
                case 'tags':
                    $diffSet = new Tinebase_Record_RecordSetDiff($diff->diff['tags']);
                    $this->assertEquals(1, count($diffSet->added));
                    $this->assertEquals(0, count($diffSet->removed));
                    $this->assertEquals(0, count($diffSet->modified), 'tags modified mismatch: ' . print_r($diffSet->toArray(), TRUE));
                    break;
                default:
                    $this->fail('Invalid modification: ' . print_r($diff->toArray(), TRUE));
            }
        }
    }
    
    /**
     * testCreateLeadWithAttachment
     * 
     * @see 0005024: allow to attach external files to records
     */
    public function testCreateLeadWithAttachment()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP) {
            $this->markTestSkipped('FIXME: Does not work with LDAP backend (full test suite run only)');
        }

        $tempFileBackend = new Tinebase_TempFile();
        $tempFile = $tempFileBackend->createTempFile(dirname(dirname(__FILE__)) . '/Filemanager/files/test.txt');
        
        $lead = $this->_getLead()->toArray();
        $lead['attachments'] = array(array('tempFile' => $tempFile->toArray()));
        
        $savedLead = $this->_getUit()->saveLead($lead);
        // add path to files to remove
        $this->_objects['paths'][] = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath(new Crm_Model_Lead($savedLead, TRUE)) . '/' . $tempFile->name;
        
        $this->assertTrue(isset($savedLead['attachments']), 'no attachments found');
        $this->assertEquals(1, count($savedLead['attachments']));
        $attachment = $savedLead['attachments'][0];
        $this->assertEquals('text/plain', $attachment['contenttype'], print_r($attachment, TRUE));
        $this->assertEquals(17, $attachment['size']);
        $this->assertTrue(is_array($attachment['created_by']), 'user not resolved: ' . print_r($attachment['created_by'], TRUE));
        $this->assertEquals(Tinebase_Core::getUser()->accountFullName, $attachment['created_by']['accountFullName'], 'user not resolved: ' . print_r($attachment['created_by'], TRUE));
        
        return $savedLead;
    }
    
    /**
     * testUpdateLeadWithAttachment
     * 
     * @see 0005024: allow to attach external files to records
     */
    public function testUpdateLeadWithAttachment()
    {
        $lead = $this->testCreateLeadWithAttachment();
        $savedLead = $this->_getUit()->saveLead($lead);
        $this->assertTrue(isset($savedLead['attachments']), 'no attachments found');
        $this->assertEquals(1, count($savedLead['attachments']));
    }
    
    /**
     * testRemoveAttachmentFromLead
     * 
     * @see 0005024: allow to attach external files to records
     */
    public function testRemoveAttachmentFromLead()
    {
        $lead = $this->testCreateLeadWithAttachment();
        $lead['attachments'] = array();
    
        $savedLead = $this->_getUit()->saveLead($lead);
        $this->assertEquals(0, count($savedLead['attachments']));
        $this->assertFalse($this->_fsController->fileExists($this->_objects['paths'][0]));
    }
    
    /**
     * testDeleteLeadWithAttachment
     * 
     * @see 0005024: allow to attach external files to records
     */
    public function testDeleteLeadWithAttachment()
    {
        $lead = $this->testCreateLeadWithAttachment();
        $this->_getUit()->deleteLeads(array($lead['id']));
        $this->assertFalse($this->_fsController->fileExists($this->_objects['paths'][0]));
    }

    /**
     * test saving lead with empty start date
     * 
     * @see 0009602: CRM should cope with empty start of leads
     */
    public function testEmptyStart()
    {
        $leadArray = $this->_getLead()->toArray();
        $leadArray['start'] = null;
        $newLead = $this->_getUit()->saveLead($leadArray);
        
        $this->assertContains(Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())->format('Y-m-d'),
            $newLead['start'], 'start should be set to now if missing');
    }
    
    /**
     * testSortByLeadState
     * 
     * @see 0010792: Sort leads by status and source
     */
    public function testSortByLeadState()
    {
        $this->saveLead();
        $lead2 = $this->_getLead()->toArray();  // open
        $lead2['leadstate_id'] = 2;             // contacted
        $this->_getUit()->saveLead($lead2);
        
        $sort = array(
            'sort' => 'leadstate_id',
            'dir' => 'ASC'
        );
        $searchLeads = $this->_getUit()->searchLeads($this->_getLeadFilter(), $sort);
        
        $this->assertEquals(2, $searchLeads['results'][0]['leadstate_id'], 'leadstate "contacted" should come first');
    }
    
    /**
     * testAdvancedSearch in related products
     * 
     * @see 0010814: quicksearch should search in related records
     */
    public function testAdvancedSearchInProduct()
    {
        Tinebase_Core::getPreference()->setValue(Tinebase_Preference::ADVANCED_SEARCH, true);
        
        $this->saveLead();
        $filter = array(
            array('field' => 'query',           'operator' => 'contains',       'value' => 'PHPUnit test product'),
        );
        $searchLeads = $this->_getUit()->searchLeads($filter, '');
        $this->assertEquals(1, $searchLeads['totalcount']);
    }

    /**
     * @see 0012680: CRM can't store leads
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testCreateLeadWithoutPermissionToInternalContacts()
    {
        // switch to jsmith
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['jsmith']);
        $scleverContact = Addressbook_Controller_Contact::getInstance()->get($this->_personas['sclever']->contact_id);
        $lead = $this->_getLead();
        $leadData = $lead->toArray();
        $leadData['relations'] = array(
            array('type'  => 'PARTNER', 'related_record' => $scleverContact->toArray()),
        );
        $newLead = $this->_getUit()->saveLead($leadData);

        self::assertEquals(1, count($newLead['relations']), 'two relations expected');
    }

    public function testAddEventRelationToLead()
    {
        $lead = $this->saveLead();
        $event = Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event([
            'dtstart'   => '2015-01-01 00:00:00',
            'dtend'     => '2015-01-01 01:00:00',
            'summary'   => 'test event',
        ]));
        $lead['relations'][] = [
            'related_record' => $event->toArray(),
            'related_model' => 'Calendar_Model_Event',
            'type' => '',
            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
        ];
        $updatedLead = $this->_getUit()->saveLead($lead);
        self::assertEquals(4, count($updatedLead['relations']), 'relation count mismatch: '
            . print_r($updatedLead, true));
    }
}
