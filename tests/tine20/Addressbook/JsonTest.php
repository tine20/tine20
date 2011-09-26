<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 * @todo        add testSetImage (NOTE: we can't test the upload yet, so we needd to simulate the upload)
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Addressbook_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * instance of test class
     *
     * @var Addressbook_Frontend_Json
     */
    protected $_instance;
    
    /**
     * contacts that should be deleted later
     * 
     * @var array
     */
    protected $_contactIdsToDelete = array();
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * container to use for the tests
     *
     * @var Tinebase_Model_Container
     */
    protected $container;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook Json Tests');
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
        $this->_instance = new Addressbook_Frontend_Json();
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        if ($personalContainer->count() === 0) {
            $this->container = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Addressbook', 'PHPUNIT');
        } else {
            $this->container = $personalContainer[0];
        }
            	
        // define filter
        $this->objects['paging'] = array(
            'start' => 0,
            'limit' => 10,
            'sort' => 'n_fileas',
            'dir' => 'ASC',
        );
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
	   $this->_instance->deleteContacts($this->_contactIdsToDelete);
    }
    
    /**
     * try to get all contacts
     */
    public function testGetAllContacts()
    {
        $paging = $this->objects['paging'];
                
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'all'),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        
        $this->assertGreaterThan(0, $contacts['totalcount']);
    }    

    /**
     * test search contacts by list
     */
    public function testSearchContactsByList()
    {
        $paging = $this->objects['paging'];
        
        $adminListId = Tinebase_Group::getInstance()->getDefaultAdminGroup()->list_id;
        $filter = array(
            array('field' => 'list', 'operator' => 'equals',   'value' => $adminListId),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        
        $this->assertGreaterThan(0, $contacts['totalcount']);
        // check if user in admin list
        $found = FALSE;
        foreach ($contacts['results'] as $contact) {
            if ($contact['account_id'] == Tinebase_Core::getUser()->getId()) {
                $found = TRUE;
                break;
            }
        }
        $this->assertTrue($found);
    }    
    
    /**
     * try to get contacts by missing container
     *
     */
    public function testGetMissingContainerContacts()
    {
        $paging = $this->objects['paging'];
                
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals',   'value' => ''),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        
        $this->assertGreaterThan(0, $contacts['totalcount']);
    }    
    
    /**
     * try to get other people contacts
     *
     */
    public function testGetOtherPeopleContacts()
    {
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'otherUsers'),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        
        $this->assertGreaterThanOrEqual(0, $contacts['totalcount'], 'getting other peoples contacts failed');
    }
        
    /**
     * try to get contacts by owner
     *
     */
    public function testGetContactsByTelephone()
    {
        $this->_addContact();
        
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'telephone', 'operator' => 'contains', 'value' => '+49TELCELLPRIVATE')
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        $this->assertEquals(1, $contacts['totalcount']);
    }

    /**
     * add a contact
     *
     * @return array contact data
     */
    protected function _addContact()
    {
        $newContactData = $this->_getContactData();
        $newContact = $this->_instance->saveContact($newContactData);
        $this->assertEquals($newContactData['n_family'], $newContact['n_family'], 'Adding contact failed');
        
        $this->_contactIdsToDelete[] = $newContact['id'];
        
        return $newContact;
    }
    
    /**
     * get contact data
     * 
     * @return array
     */
    protected function _getContactData()
    {
        $note = array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',            
        );
        
        return array(
            'n_family'          => 'PHPUNIT',
            'org_name'          => Tinebase_Record_Abstract::generateUID(),
            'container_id'      => $this->container->id,
            'notes'             => array($note),
            'tel_cell_private'  => '+49TELCELLPRIVATE',
        );
    }
    
    /**
     * try to get contacts by owner
     *
     */
    public function testGetContactsByAddressbookId()
    {
        $this->_addContact();
        
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'singleContainer'),
            array('field' => 'container', 'operator' => 'equals',   'value' => $this->container->id),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        
        $this->assertGreaterThan(0, $contacts['totalcount']);
    }
    
    /**
     * try to get contacts by owner / container_id
     *
     */
    public function testGetContactsByOwner()
    {
        $this->_addContact();
        
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'personal'),
            array('field' => 'owner',  'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        
        $this->assertGreaterThan(0, $contacts['totalcount']);
    }

    /**
     * test getting contact
     *
     */
    public function testGetContact()
    {
        $contact = $this->_addContact();
        
        $contact = $this->_instance->getContact($contact['id']);
        
        $this->assertEquals('PHPUNIT', $contact['n_family'], 'getting contact failed');
    }

    /**
     * test updating of a contact (including geodata)
     */
    public function testUpdateContactWithGeodata()
    {
        $contact = $this->_addContact();
        
        $contact['n_family'] = 'PHPUNIT UPDATE';
        $contact['adr_one_locality'] = 'Hamburg';
        $contact['adr_one_street'] = 'Pickhuben 2';
        $updatedContact = $this->_instance->saveContact($contact);
        
        $this->assertEquals($contact['id'], $updatedContact['id'], 'updated produced a new contact');
        $this->assertEquals('PHPUNIT UPDATE', $updatedContact['n_family'], 'updating data failed');
        
        if (Tinebase_Config::getInstance()->getConfig(Tinebase_Config::MAPPANEL, NULL, TRUE)->value) {
            // check geo data 
            $this->assertEquals('9.99489818142748', $updatedContact['lon'], 'wrong geodata (lon)');
            $this->assertEquals('53.5444309689663', $updatedContact['lat'], 'wrong geodata (lat)');
            
            // try another address
            $updatedContact['adr_one_locality']    = 'Wien';
            $updatedContact['adr_one_street']      = 'Blindengasse 52';
            $updatedContact['adr_one_postalcode']  = '1095';
            $updatedContact['adr_one_countryname'] = '';
            $updatedContact = $this->_instance->saveContact($updatedContact);
            
            // check geo data 
            $this->assertEquals('16.3419589',   $updatedContact['lon'], 'wrong geodata (lon)');
            $this->assertEquals('48.2147964',   $updatedContact['lat'], 'wrong geodata (lat)');
            $this->assertEquals('AT',           $updatedContact['adr_one_countryname'], 'wrong country');
            $this->assertEquals('1080',         $updatedContact['adr_one_postalcode'], 'wrong postalcode');
        }
    }
    
    /**
     * test deleting contact
     *
     */
    public function testDeleteContact()
    {
        $contact = $this->_addContact();
        
        $this->_instance->deleteContacts($contact['id']);
        
        $this->setExpectedException('Exception');
        $contact = $this->_instance->getContact($contact['id']);
    }
    
    /**
     * get all salutations
     *
     */
    public function testGetSalutations()
    {
        $salutations = $this->_instance->getSalutations();
        
        $this->assertGreaterThan(2, $salutations['totalcount']);
    }
    
    /**
     * test import data
     */
    public function testExportImport()
    {
        $filter = new Addressbook_Model_ContactFilter(array(
            array(
                'field'    => 'n_fileas',
                'operator' => 'equals',
                'value'    =>  Tinebase_Core::getUser()->accountDisplayName
            )
        ));
        $sharedTagName = Tinebase_Record_Abstract::generateUID();
        $tag = new Tinebase_Model_Tag(array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => $sharedTagName,
            'description' => 'testImport',
            'color' => '#009B31',
        ));
        Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $tag);
        
        $personalTagName = Tinebase_Record_Abstract::generateUID();
        $tag = new Tinebase_Model_Tag(array(
            'type'  => Tinebase_Model_Tag::TYPE_PERSONAL,
            'name'  => $personalTagName,
            'description' => 'testImport',
            'color' => '#009B31',
        ));
        Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $tag);
        
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_tine_import_csv');
        
        // export first and create files array
        $exporter = new Addressbook_Export_Csv($filter, Addressbook_Controller_Contact::getInstance());
        $filename = $exporter->generate();
        $export = file_get_contents($filename);
        $this->assertContains($sharedTagName, $export, 'shared tag was not found in export:' . $export);
        $this->assertContains($personalTagName, $export, 'personal tag was not found in export:' . $export);
        
        // then import
        $files = array(
            array('name' => $filename, 'path' => $filename)
        );
        $options = array(
            'container_id'  => $this->container->getId(),
            'dryrun'        => 1,
        );
        $result = $this->_instance->importContacts($files, $options, $definition->getId());
        
        // check
        $this->assertGreaterThan(0, $result['totalcount'], 'Didn\'t import anything.');
        $this->assertEquals(0, $result['failcount'], 'Import failed for one or more records.');
        $this->assertEquals(Tinebase_Core::getUser()->accountDisplayName, $result['results'][0]['n_fileas'], 'file as not found');
        $this->assertTrue(in_array($sharedTagName, $result['results'][0]['tags']),
            'Did not get shared tag: ' . $sharedTagName . ' / ' . print_r($result['results'][0]['tags'], TRUE));
        $this->assertTrue(in_array($personalTagName, $result['results'][0]['tags']), 
            'Did not get personal tag: ' . $personalTagName . ' / ' . print_r($result['results'][0]['tags'], TRUE));
        
        // cleanup
        unset($filename);
        $sharedTagToDelete = Tinebase_Tags::getInstance()->getTagByName($sharedTagName);
        $personalTagToDelete = Tinebase_Tags::getInstance()->getTagByName($personalTagName);
        Tinebase_Tags::getInstance()->deleteTags(array($sharedTagToDelete->getId(), $personalTagToDelete->getId()));
    }    

    /**
     * test project relation filter
     */
    public function testProjectRelationFilter()
    {
        // create and link project + contacts
        $project = $this->_getProjectData();
        $contact = $this->_getContactData();
        $project['relations'] = array(
            array(
                'own_model'              => 'Projects_Model_Project',
                'own_backend'            => 'Sql',
                'own_id'                 => 0,
                'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                'type'                   => 'COWORKER',
                'related_record'         => $contact,
                'related_id'             => NULL,
                'related_model'          => 'Addressbook_Model_Contact',
                'remark'                 => NULL,
            )
        );
        $projectJson = new Projects_Frontend_Json();
        $newProject = $projectJson->saveProject($project);
        
        $this->_testProjectRelationFilter($contact, 'definedBy');
        $this->_testProjectRelationFilter($contact, 'in', $newProject);
        $this->_testProjectRelationFilter($contact, 'equals', $newProject);
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
     * helper for project relation filter test
     * 
     * @param array $_contact
     * @param string
     * @param array $_project
     */
    protected function _testProjectRelationFilter($_contact, $_operator, $_project = NULL)
    {
        switch ($_operator) {
            case 'definedBy':
                $closedStatus = Projects_Config::getInstance()->get(Projects_Config::PROJECT_STATUS)->records->filter('is_open', 0);
                $value = array(
                    array('field' => "relation_type", "operator" => "equals", "value" => "COWORKER"),
                    array('field' => "status",        "operator" => "notin",  "value" => $closedStatus->getId()),
                );
                break;
            case 'in':
                $value = array($_project['id']);
                break;
            case 'equals':
                $value = $_project['id'];
                break;
        }
        
        $filter = array(
            array(
                'field' => array(
                    'linkType'      => 'relation',
                    'appName'       => 'Projects',
                    'modelName'     => 'Project',
                ), 
                'operator' => $_operator, 
                'value' => $value
            ),
            array('field' => 'org_name', 'operator' => 'equals', 'value' => $_contact['org_name'])
        );
        $result = $this->_instance->searchContacts($filter, array());
        
        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals($_contact['org_name'], $result['results'][0]['org_name']);
    }
    
    /**
     * testAttenderForeignIdFilter
     * 
     * @todo finish and activate 
     */
    public function _testAttenderForeignIdFilter()
    {
        $event = $this->_getEvent();
        Calendar_Controller_Event::getInstance()->create($event);
        
        $filter = array(
            array(
                'field' => 'foreignRecord', 
                'operator' => array(
                    'linkType'      => 'foreignId',
                    'appName'       => 'Calendar',
                    'filterName'    => 'ContactFilter',
                ), 
                'value' => array(
                    array('condition' => 'OR', 'filters' =>
                        array('field' => "dtstart",       "operator" => "within", "value" => "yearThis"),
                        array('field' => "dtstart",       "operator" => "within", "value" => "yearLast")
                    ),
                    array('field' => "attender_status",   "operator" => "in",  "value" => array('NEEDS-ACTION', 'ACCEPTED')),
                )
            ),
            array('field' => 'id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->contact_id)
        );
        $result = $this->_instance->searchContacts($filter, array());
        
        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, $result['results'][0]['id']);
    }
    
    /**
     * returns a simple event
     *
     * @return Calendar_Model_Event
     */
    protected function _getEvent()
    {
        $testCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'PHPUnit test calendar',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'        => $this->_backend->getType(),
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()
        ), true));
        
        return new Calendar_Model_Event(array(
            'summary'     => 'Wakeup',
            'dtstart'     => '2009-03-25 06:00:00',
            'dtend'       => '2009-03-25 06:15:00',
            'description' => 'Early to bed and early to rise, makes a men healthy, wealthy and wise',
            'attendee'    => new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
                array(
                    'user_id'        => Tinebase_Core::getUser()->contact_id,
                    'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                    'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                    'status_authkey' => Tinebase_Record_Abstract::generateUID(),
                ),
            )),
        
            'container_id' => $testCalendar->getId(),
            'organizer'    => Tinebase_Core::getUser()->contact_id,
            'uid'          => Calendar_Model_Event::generateUID(),
        
            Tinebase_Model_Grants::GRANT_READ    => true,
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            Tinebase_Model_Grants::GRANT_DELETE  => true,
        ));
    }
}
