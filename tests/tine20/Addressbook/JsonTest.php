<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 *
 * @todo        add testSetImage (NOTE: we can't test the upload yet, so we needd to simulate the upload)
 */

/**
 * Test class for Addressbook_Frontend_Json
 */
class Addressbook_JsonTest extends TestCase
{
    /**
     * set geodata for contacts
     * 
     * @var boolean
     */
    protected $_geodata = FALSE;
    
    /**
     * instance of test class
     *
     * @var Addressbook_Frontend_Json
     */
    protected $_uit;

    /**
     * contacts that should be deleted later
     *
     * @var array
     */
    protected $_contactIdsToDelete = array();

    /**
     * customfields that should be deleted later
     *
     * @var array
     */
    protected $_customfieldIdsToDelete = array();

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
     * sclever was made invisible! show her again in tearDown if this is TRUE!
     * 
     * @var boolean
     */
    protected $_makeSCleverVisibleAgain = FALSE;
    
    /**
     * group ids to delete in tearDown
     * 
     * @var array
     */
    protected $_groupIdsToDelete = NULL;
    
    protected $_originalRoleRights = null;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_geodata = Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(false);
        
        // always resolve customfields
        Addressbook_Controller_Contact::getInstance()->resolveCustomfields(TRUE);
        
        $this->_uit = new Addressbook_Frontend_Json();
        
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

        parent::setUp();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts($this->_geodata);
        
        $this->_uit->deleteContacts($this->_contactIdsToDelete);

        foreach ($this->_customfieldIdsToDelete as $cfd) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfd);
        }
        
        if ($this->_makeSCleverVisibleAgain) {
            $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
            $sclever->visibility = Tinebase_Model_User::VISIBILITY_DISPLAYED;
            Tinebase_User::getInstance()->updateUser($sclever);
        }
        
        if ($this->_groupIdsToDelete) {
            Admin_Controller_Group::getInstance()->delete($this->_groupIdsToDelete);
        }
        
        if (! empty($this->objects['createdTagIds'])) {
            try {
                Tinebase_Tags::getInstance()->deleteTags($this->objects['createdTagIds'], TRUE);
                $this->objects['createdTagIds'] = array();
            } catch (Tinebase_Exception_AccessDenied $e) {
                $this->objects['createdTagIds'] = array();
            }
        }
        
        $this->_resetOriginalRoleRights();

        parent::tearDown();
    }
    
    protected function _resetOriginalRoleRights()
    {
        if (! empty($this->_originalRoleRights)) {
            foreach ($this->_originalRoleRights as $roleId => $rights) {
                Tinebase_Acl_Roles::getInstance()->setRoleRights($roleId, $rights);
            }
            
            $this->_originalRoleRights = null;
        }
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
        $contacts = $this->_uit->searchContacts($filter, $paging);

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
        $contacts = $this->_uit->searchContacts($filter, $paging);

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
     * testSearchContactsByListValueNull
     */
    public function testSearchContactsByListValueNull()
    {
        $filter = '[
            {
                "condition": "OR",
                "filters": [
                    {
                        "condition": "AND",
                        "filters": [
                            {
                                "field": "list",
                                "operator": "equals",
                                "value": null,
                                "id": "ext-record-102"
                            }
                        ],
                        "id": "ext-comp-1211",
                        "label": "Kontakte"
                    }
                ]
            }]';
        $contacts = $this->_uit->searchContacts(Zend_Json::decode($filter), NULL);
        $this->assertGreaterThanOrEqual(0, $contacts['totalcount']);
    }

    /**
    * testSearchContactsWithBadPaging
    * 
    * @see 0006214: LIMIT argument offset=-50 is not valid
    */
    public function testSearchContactsWithBadPaging()
    {
        $paging = $this->objects['paging'];
        $paging['start'] = -50;
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'all'),
        );
    
        $contacts = $this->_uit->searchContacts($filter, $paging);
        $this->assertGreaterThan(0, $contacts['totalcount']);
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
        $contacts = $this->_uit->searchContacts($filter, $paging);

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
        $contacts = $this->_uit->searchContacts($filter, $paging);

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
        $contacts = $this->_uit->searchContacts($filter, $paging);
        $this->assertEquals(1, $contacts['totalcount']);
    }

    /**
     * add a contact
     *
     * @param string $_orgName
     * @param boolean $_forceCreation
     * @param array $_tags
     * @return array contact data
     */
    protected function _addContact($_orgName = NULL, $_forceCreation = FALSE, $_tags = NULL)
    {
        $newContactData = $this->_getContactData($_orgName);
        if ($_tags !== NULL) {
            $newContactData['tags'] = $_tags;
        }
        $newContact = $this->_uit->saveContact($newContactData, $_forceCreation);
        $this->assertEquals($newContactData['n_family'], $newContact['n_family'], 'Adding contact failed');

        $this->_contactIdsToDelete[] = $newContact['id'];

        return $newContact;
    }

    /**
     * get contact data
     *
     * @param string $_orgName
     * @return array
     */
    protected function _getContactData($_orgName = NULL)
    {
        $note = array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',
        );

        return array(
            'n_given'           => 'ali',
            'n_family'          => 'PHPUNIT',
            'org_name'          => ($_orgName === NULL) ? Tinebase_Record_Abstract::generateUID() : $_orgName,
            'container_id'      => $this->container->id,
            'notes'             => array($note),
            'tel_cell_private'  => '+49TELCELLPRIVATE',
        );
    }

    /**
     * this test is for Tinebase_Frontend_Json updateMultipleRecords with contact data in the addressbook app
     */
    public function testUpdateMultipleRecords()
    {
        $companies = array('Janes', 'Johns', 'Bobs');
        $contacts = array();

        $createdCustomField = $this->_createCustomfield();
        $changes = array(
            array('name' => 'url',                    'value' => "http://www.phpunit.de"),
            array('name' => 'adr_one_region',         'value' => 'PHPUNIT_multipleUpdate'),
            array('name' => 'customfield_' . $createdCustomField->name, 'value' => 'PHPUNIT_multipleUpdate' ),
            array('name' => '%add', 'value' => json_encode(array(
                'own_model'         => 'Addressbook_Model_Contact',
                'own_backend'       => 'Sql',
                'related_degree'    => 'parent',
                'related_model'     => 'Addressbook_Model_Contact',
                'related_backend'   => 'Sql',
                'related_id'        => Tinebase_Core::getUser()->contact_id,
                'remark'            => 'some remark'
            ))),
        );
        foreach($companies as $company) {
            $contact = $this->_addContact($company);
            $contactIds[] = $contact['id'];
        }

        $filter = array(array('field' => 'id','operator' => 'in', 'value' => $contactIds));
        $json = new Tinebase_Frontend_Json();

        $result = $json->updateMultipleRecords('Addressbook', 'Contact', $changes, $filter);

        // look if all 3 contacts are updated
        $this->assertEquals(3, $result['totalcount'],'Could not update the correct number of records');

        // check if default field adr_one_region value was found
        $sFilter = array(array('field' => 'adr_one_region','operator' => 'equals', 'value' => 'PHPUNIT_multipleUpdate'));
        $searchResult = $this->_uit->searchContacts($sFilter,$this->objects['paging']);

        // look if all 3 contacts are found again by default field, and check if default field got properly updated
        $this->assertEquals(3, $searchResult['totalcount'],'Could not find the correct number of records by adr_one_region');

        $record = array_pop($searchResult['results']);

        // check if customfieldvalue was updated properly
        $this->assertTrue(isset($record['customfields']), 'No customfields in record');
        $this->assertEquals($record['customfields'][$createdCustomField->name],'PHPUNIT_multipleUpdate','Customfield was not updated as expected');

        // check if other default field value was updated properly
        $this->assertEquals($record['url'],'http://www.phpunit.de','DefaultField "url" was not updated as expected');
        
        // check 'changed' systemnote
        $this->_checkChangedNote($record['id'], 'adr_one_region ( -> PHPUNIT_multipleUpdate) url ( -> http://www.phpunit.de) relations (1 hinzugefügt) customfields ( -> {');

        // check relation
        $fullRecord = $this->_uit->getContact($record['id']);
        $this->assertEquals(1, count($fullRecord['relations']), 'relation got not added');
        $this->assertEquals('some remark', $fullRecord['relations'][0]['remark']);
        $this->assertEquals('parent', $fullRecord['relations'][0]['related_degree']);

        // check invalid data
        $changes = array(
            array('name' => 'type', 'value' => 'Z'),
        );
        $result = $json->updateMultipleRecords('Addressbook', 'Contact', $changes, $filter);

        $this->assertEquals(3, $result['failcount'], 'failcount does not show the correct number');
        $this->assertEquals(0, $result['totalcount'], 'totalcount does not show the correct number');
    }
    
    /**
     * created customfield config
     * 
     * @return Tinebase_Model_CustomField_Config
     */
    protected function _createCustomfield($cfName = NULL)
    {
        $cfName = ($cfName !== NULL) ? $cfName : Tinebase_Record_Abstract::generateUID();
        $cfc = Tinebase_CustomFieldTest::getCustomField(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'          => 'Addressbook_Model_Contact',
            'name'           => $cfName,
        ));
        
        $createdCustomField = Tinebase_CustomField::getInstance()->addCustomField($cfc);
        $this->_customfieldIdsToDelete[] = $createdCustomField->getId();
        
        return $createdCustomField;
    }
    
    /**
     * test customfield modlog
     */
    public function testCustomfieldModlog()
    {
        $cf = $this->_createCustomfield();
        $contact = $this->_addContact();
        $contact['customfields'][$cf->name] = 'changed value';
        $result = $this->_uit->saveContact($contact);
        
        $this->assertEquals('changed value', $result['customfields'][$cf->name]);
        $this->_checkChangedNote($result['id'], ' -> {"' . $cf->name . '":"changed value"})');
    }
    
    /**
     * check 'changed' system note and modlog after tag/customfield update
     * 
     * @param string $_recordId
     * @param string|array $_expectedText
     * @param integer $_changedNoteNumber
     */
    protected function _checkChangedNote($_recordId, $_expectedText = array(), $_changedNoteNumber = 3)
    {
        $tinebaseJson = new Tinebase_Frontend_Json();
        $history = $tinebaseJson->searchNotes(array(array(
            'field' => 'record_id',
            'operator' => 'equals',
            'value' => $_recordId
        ), array(
            'field' => "record_model",
            'operator' => "equals",
            'value' => 'Addressbook_Model_Contact'
        )), array(
            'sort' => array('note_type_id', 'creation_time')
        ));
        $this->assertEquals($_changedNoteNumber, $history['totalcount'], print_r($history, TRUE));
        $changedNote = $history['results'][$_changedNoteNumber - 1];
        foreach ((array) $_expectedText as $text) {
            $this->assertContains($text, $changedNote['note'], print_r($changedNote, TRUE));
        }
    }

    /**
     * test tags modlog
     * 
     * @return array contact with tag
     * 
     * @see 0008546: When edit event, history show "code" ...
     */
    public function testTagsModlog()
    {
        $contact = $this->_addContact();
        $tagName = Tinebase_Record_Abstract::generateUID();
        $tag = array(
            'type'          => Tinebase_Model_Tag::TYPE_PERSONAL,
            'name'          => $tagName,
            'description'    => 'testModlog',
            'color'         => '#009B31',
        );
        $contact['tags'] = array($tag);
        
        $result = $this->_uit->saveContact($contact);
        
        $this->assertEquals($tagName, $result['tags'][0]['name']);
        $this->_checkChangedNote($result['id'], array(
            'tags',
            '1 ' . Tinebase_Translation::getTranslation('Tinebase')->_('added'),
        ));
        
        return $result;
    }

    /**
    * test attach multiple tags modlog
    * 
    * @param string $type tag type
    */
    public function testAttachMultipleTagsModlog($type = Tinebase_Model_Tag::TYPE_SHARED)
    {
        $contact = $this->_addContact();
        $filter = new Addressbook_Model_ContactFilter(array(array(
            'field'    => 'id',
            'operator' => 'equals',
            'value'    =>  $contact['id']
        )));
        $sharedTagName = $this->_createAndAttachTag($filter, $type);
        $this->_checkChangedNote($contact['id'], array(',"name":"' . $sharedTagName . '","description":"testTagDescription"', 'tags ([] -> [{'));
    }
    
    /**
    * test detach multiple tags modlog
    */
    public function testDetachMultipleTagsModlog()
    {
        $contact = $this->testTagsModlog();
        $contact['tags'] = array();
        sleep(1); // make sure that the second change always gets last when fetching notes
        $result = $this->_uit->saveContact($contact);
        $this->_checkChangedNote($result['id'], array(
            'tags',
            '1 ' . Tinebase_Translation::getTranslation('Tinebase')->_('removed'),
        ), 4);
    }
    
    /**
     * testCreatePersonalTagWithoutRight
     * 
     * @see 0010732: add "use personal tags" right to all applications
     */
    public function testCreatePersonalTagWithoutRight()
    {
        $this->_originalRoleRights = $this->_removeRoleRight('Addressbook', Tinebase_Acl_Rights::USE_PERSONAL_TAGS);
        
        try {
            $this->testAttachMultipleTagsModlog(Tinebase_Model_Tag::TYPE_PERSONAL);
            $this->fail('personal tags right is disabled');
        } catch (Tinebase_Exception $e) {
            $this->assertTrue($e instanceof Tinebase_Exception_AccessDenied, 'did not get expected exception: ' . $e);
        }
    }
    
    /**
     * testFetchPersonalTagWithoutRight
     * 
     * @see 0010732: add "use personal tags" right to all applications
     */
    public function testFetchPersonalTagWithoutRight()
    {
        $contact = $this->testAttachMultipleTagsModlog(Tinebase_Model_Tag::TYPE_PERSONAL);
        $this->_originalRoleRights = $this->_removeRoleRight('Addressbook', Tinebase_Acl_Rights::USE_PERSONAL_TAGS);
        
        $contact = $this->_uit->getContact($contact['id']);
        
        $this->assertTrue(! isset($contact['tags']) || count($contact['tags'] === 0), 'record should not have any tags');
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
        $contacts = $this->_uit->searchContacts($filter, $paging);

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
        $contacts = $this->_uit->searchContacts($filter, $paging);

        $this->assertGreaterThan(0, $contacts['totalcount']);
    }

    /**
     * test getting contact
     *
     */
    public function testGetContact()
    {
        $contact = $this->_addContact();

        $contact = $this->_uit->getContact($contact['id']);

        $this->assertEquals('PHPUNIT', $contact['n_family'], 'getting contact failed');
    }

    /**
     * test updating of a contact (including geodata)
     */
    public function testUpdateContactWithGeodata()
    {
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(true);
        
        $contact = $this->_addContact();

        $contact['n_family'] = 'PHPUNIT UPDATE';
        $contact['adr_one_locality'] = 'Hamburg';
        $contact['adr_one_street'] = 'Pickhuben 2';
        $updatedContact = $this->_uit->saveContact($contact);

        $this->assertEquals($contact['id'], $updatedContact['id'], 'updated produced a new contact');
        $this->assertEquals('PHPUNIT UPDATE', $updatedContact['n_family'], 'updating data failed');

        if (Tinebase_Config::getInstance()->get(Tinebase_Config::MAPPANEL, TRUE)) {
            // check geo data
            $this->assertEquals(10, round($updatedContact['adr_one_lon']), 'wrong geodata (lon): ' . $updatedContact['adr_one_lon']);
            $this->assertEquals(54, round($updatedContact['adr_one_lat']), 'wrong geodata (lat): ' . $updatedContact['adr_one_lat']);

            // try another address
            $updatedContact['adr_one_locality']    = 'Wien';
            $updatedContact['adr_one_street']      = 'Blindengasse 52';
            $updatedContact['adr_one_postalcode']  = '1095';
            $updatedContact['adr_one_countryname'] = '';
            $updatedContact = $this->_uit->saveContact($updatedContact);

            // check geo data
            $this->assertEquals(16,   round($updatedContact['adr_one_lon']), 'wrong geodata (lon): ' . $updatedContact['adr_one_lon']);
            $this->assertEquals(48,   round($updatedContact['adr_one_lat']), 'wrong geodata (lat): ' . $updatedContact['adr_one_lat']);
            $this->assertEquals('AT',           $updatedContact['adr_one_countryname'], 'wrong country');
        }
    }

    /**
     * test deleting contact
     *
     */
    public function testDeleteContact()
    {
        $contact = $this->_addContact();

        $this->_uit->deleteContacts($contact['id']);

        $this->setExpectedException('Tinebase_Exception_NotFound');
        $contact = $this->_uit->getContact($contact['id']);
    }

    /**
     * get all salutations
     */
    public function testGetSalutations()
    {
        $salutations = Addressbook_Config::getInstance()->contactSalutation;
        $this->assertGreaterThan(2, count($salutations->records));
    }

    /**
     * test export data
     */
    public function testExport()
    {
        $filter = new Addressbook_Model_ContactFilter(array(array(
            'field'    => 'n_fileas',
            'operator' => 'equals',
            'value'    =>  Tinebase_Core::getUser()->accountDisplayName
        )));
        $sharedTagName = $this->_createAndAttachTag($filter);
        $personalTagName = $this->_createAndAttachTag($filter, Tinebase_Model_Tag::TYPE_PERSONAL);

        // export first and create files array
        $exporter = new Addressbook_Export_Csv($filter, Addressbook_Controller_Contact::getInstance());
        $filename = $exporter->generate();
        $export = file_get_contents($filename);
        $this->assertContains($sharedTagName, $export, 'shared tag was not found in export:' . $export);
        $this->assertContains($personalTagName, $export, 'personal tag was not found in export:' . $export);

        // cleanup
        unset($filename);
        $sharedTagToDelete = Tinebase_Tags::getInstance()->getTagByName($sharedTagName);
        $personalTagToDelete = Tinebase_Tags::getInstance()->getTagByName($personalTagName);
        Tinebase_Tags::getInstance()->deleteTags(array($sharedTagToDelete->getId(), $personalTagToDelete->getId()));
    }

    /**
     * tag attachment helper
     * 
     * @param Addressbook_Model_ContactFilter $_filter
     * @param string $_tagType
     * @return string created tag name
     */
    protected function _createAndAttachTag($_filter, $_tagType = Tinebase_Model_Tag::TYPE_SHARED)
    {
        $tag = $this->_getTag($_tagType);
        Tinebase_Tags::getInstance()->attachTagToMultipleRecords($_filter, $tag);
        
        return $tag->name;
    }
    
    /**
     * testExportXlsWithCustomfield
     * 
     * @see 0006634: custom fields missing in XLS export
     */
    public function testExportXlsWithCustomfield()
    {
        $exportCf = $this->_createCustomfield('exportcf');
        $filter = new Addressbook_Model_ContactFilter(array(array(
            'field'    => 'n_fileas',
            'operator' => 'equals',
            'value'    =>  Tinebase_Core::getUser()->accountDisplayName
        )));
        
        $ownContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        $ownContact->customfields = array('exportcf' => 'testcustomfieldvalue');
        Addressbook_Controller_Contact::getInstance()->update($ownContact);
        
        $definition = dirname(__FILE__) . '/Export/definitions/adb_cf_xls_test.xml';
        $exporter = new Addressbook_Export_Xls($filter, Addressbook_Controller_Contact::getInstance(), array('definitionFilename' => $definition));
        $doc = $exporter->generate();
        
        $xlswriter = PHPExcel_IOFactory::createWriter($doc, 'CSV');
        ob_start();
        $xlswriter->save('php://output');
        $out = ob_get_clean();
        
        $this->assertContains(Tinebase_Core::getUser()->accountDisplayName, $out, 'display name not found.');
        $this->assertContains('exportcf', $out, 'customfield not found in headline.');
        $this->assertContains('testcustomfieldvalue', $out, 'customfield value not found.');
    }
    
    /**
     * testExportXlsWithTranslation
     *
     */
    public function testExportXlsWithTranslation()
    {
        $instance = new Tinebase_Frontend_Json();
        $instance->setLocale('de', FALSE, FALSE);
        
        $filter = new Addressbook_Model_ContactFilter(array(array(
                'field'    => 'n_fileas',
                'operator' => 'equals',
                'value'    =>  Tinebase_Core::getUser()->accountDisplayName
        )));
        
        $ownContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        $ownContact->salutation = 'MR';
        Addressbook_Controller_Contact::getInstance()->update($ownContact);
        
        $definition = dirname(__FILE__) . '/Export/definitions/adb_translation_xls_test.xml';
        $exporter = new Addressbook_Export_Xls($filter, Addressbook_Controller_Contact::getInstance(), array('definitionFilename' => $definition));
        $doc = $exporter->generate();
        
        $xlswriter = PHPExcel_IOFactory::createWriter($doc, 'CSV');
        ob_start();
        $xlswriter->save('php://output');
        $out = ob_get_clean();
        
        $this->assertContains(Tinebase_Core::getUser()->accountDisplayName, $out, 'display name not found.');
        $this->assertContains('Herr', $out, 'no translated salutation found.');
    }
    
    /**
     * each tag should have an own column
     */
    public function testExportOdsWithTagMatrix()
    {
        $filter = new Tinebase_Model_TagFilter(array());
        try {
            $allTags = Tinebase_Tags::getInstance()->searchTags($filter);
            if ($allTags->count()) {
                Tinebase_Tags::getInstance()->deleteTags($allTags->getId(), TRUE);
            }
        } catch (Tinebase_Exception_AccessDenied $e) {
            $this->markTestSkipped('This fails each 2nd time.');
        }
        
        $controller = Addressbook_Controller_Contact::getInstance();
        
        $t1 = $this->_getTag(Tinebase_Model_Tag::TYPE_SHARED, 'tag1');
        $this->objects['createdTagIds'][] = $t1->getId();
        
        $c1 = new Addressbook_Model_Contact(array(
            'n_given'           => 'ali',
            'n_family'          => 'PHPUNIT',
            'org_name'          => 'test',
            'container_id'      => $this->container->id,
            'tags' => array($t1)
        ));
        
        $c1 = $controller->create($c1);
        $this->_contactIdsToDelete[] = $c1->getId();
        
        $t2 = $this->_getTag(Tinebase_Model_Tag::TYPE_SHARED, 'tag2');
        $this->objects['createdTagIds'][] = $t2->getId();
        
        // this tag should not occur, this is the addressbook application
        $crmAppId = Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId();
        $t3 = $this->_getTag(Tinebase_Model_Tag::TYPE_SHARED, 'tag3', array($crmAppId));
        $this->objects['createdTagIds'][] = $t3->getId();
        
        $c2 = new Addressbook_Model_Contact(array(
            'n_given'           => 'alisabeth',
            'n_family'          => 'PHPUNIT',
            'org_name'          => 'test',
            'container_id'      => $this->container->id,
            'tags' => array($t2)
        ));
        
        $c2 = $controller->create($c2);
        $this->_contactIdsToDelete[] = $c2->getId();
        
        $this->assertNotEmpty($c1->tags);
        $this->assertNotEmpty($c2->tags);
        
        $filter = new Addressbook_Model_ContactFilter(array(array(
            'field'    => 'n_family',
            'operator' => 'equals',
            'value'    =>  'PHPUNIT'
        )));
        
        $definition = dirname(__FILE__) . '/Export/definitions/adb_tagmatrix_ods.xml';
        $exporter = new Addressbook_Export_Ods($filter, Addressbook_Controller_Contact::getInstance(), array('definitionFilename' => $definition));
        $doc = $exporter->generate();
        
        $xml = $this->_getContentXML($doc);
        
        $ns = $xml->getNamespaces(true);
        $spreadsheetXml = $xml->children($ns['office'])->{'body'}->{'spreadsheet'};
        
        $headerRowXml = $spreadsheetXml->children($ns['table'])->{'table'}->xpath('table:table-row');
        $headerRowXml = $headerRowXml[1];
        $cells = $headerRowXml->xpath('table:table-cell');
        $tag1 = $cells[1]->xpath('text:p');
        $tag1 = $tag1[0];
        $tag2 = $cells[2]->xpath('text:p');
        $tag2 = $tag2[0];
        
        // the tags should exist in the header row
        $this->assertEquals('tag1', (string) $tag1);
        $this->assertEquals('tag2', (string) $tag2);
        
        // if there is no more header column, tag3 is not shown
        $this->assertEquals(3, count($cells));
    }
    
    /**
     * test import
     * 
     * @see 0006226: Data truncated for column 'adr_two_lon'
     *
     * TODO move import test to separate test class
     */
    public function testImport()
    {
        $this->_testNeedsTransaction();

        $result = $this->_importHelper();
        $this->assertEquals(2, $result['totalcount'], 'dryrun should detect 2 for import.' . print_r($result, TRUE));
        $this->assertEquals(0, $result['failcount'], 'Import failed for one or more records.');
        $this->assertEquals('Müller, Klaus', $result['results'][0]['n_fileas'], 'file as not found');

        // import again without dryrun
        $result = $this->_importHelper(array('dryrun' => 0));
        $this->assertEquals(2, $result['totalcount'], 'Didn\'t import anything.');
        $klaus = $result['results'][0];
        $this->assertEquals('Import list (' . Tinebase_Translation::dateToStringInTzAndLocaleFormat(Tinebase_DateTime::now(), NULL, NULL, 'date') . ')', $klaus['tags'][0]['name']);

        // import with duplicates
        $result = $this->_importHelper(array('dryrun' => 0));
        $this->assertEquals(0, $result['totalcount'], 'Do not import anything.');
        $this->assertEquals(2, $result['duplicatecount'], 'Should find 2 dups.');
        $this->assertEquals(1, count($result['exceptions'][0]['exception']['clientRecord']['tags']), '1 autotag expected');

        // import again with clientRecords
        $klaus['adr_one_locality'] = 'Hamburg';
        // check that empty filter works correctly, db only accepts NULL for empty value here
        $klaus['adr_two_lon'] = '';
        $clientRecords = array(array(
            'recordData'        => $klaus,
            'resolveStrategy'   => 'mergeMine',
            'index'             => 0,
        ));
        $result = $this->_importHelper(array('dryrun' => 0), $clientRecords);
        $this->assertEquals(1, $result['totalcount'], 'Should merge Klaus: ' . print_r($result, TRUE));
        $this->assertEquals(1, $result['duplicatecount'], 'Fritz is no duplicate.');
        $this->assertEquals('Hamburg', $result['results'][0]['adr_one_locality'], 'locality should change');
    }

    /**
    * test import with discard resolve strategy
    */
    public function testImportWithResolveStrategyDiscard()
    {
        $this->_testNeedsTransaction();

        $result = $this->_importHelper(array('dryrun' => 0));
        $fritz = $result['results'][1];

        $clientRecords = array(array(
            'recordData'        => $fritz,
            'resolveStrategy'   => 'discard',
            'index'             => 1,
        ));
        $result = $this->_importHelper(array('dryrun' => 0), $clientRecords);
        $this->assertEquals(0, $result['totalcount'], 'Should discard fritz');
        $this->assertEquals(0, $result['failcount'], 'no failures expected');
        $this->assertEquals(1, $result['duplicatecount'], 'klaus should still be a duplicate');
    }

    /**
    * test import with mergeTheirs resolve strategy
    * 
    * @see 0007226: tag handling and other import problems
    */
    public function testImportWithResolveStrategyMergeTheirs()
    {
        $this->_testNeedsTransaction();

        $result = $this->_importHelper(array('dryrun' => 0));
        $this->assertEquals(2, count($result['results']), 'no import results');
        $fritz = $result['results'][1];
        $fritz['tags'][] = array(
            'name'        => 'new import tag'
        );
        $fritz['tel_work'] = '04040';

        $clientRecords = array(array(
            'recordData'        => $fritz,
            'resolveStrategy'   => 'mergeTheirs',
            'index'             => 1,
        ));
        $result = $this->_importHelper(array('dryrun' => 0), $clientRecords);
        $this->assertEquals(1, $result['totalcount'], 'Should merge fritz');
        $this->assertEquals(2, count($result['results'][0]['tags']), 'Should merge tags');
        $this->assertEquals(0, $result['failcount'], 'no failures expected');
        $this->assertEquals(1, $result['duplicatecount'], 'klaus should still be a duplicate');
        
        $fritz = $result['results'][0];
        $fritz['tel_work'] = '04040';
        // need to adjust user TZ
        $lastModified = new Tinebase_DateTime($fritz['last_modified_time']);
        $fritz['last_modified_time'] = $lastModified->setTimezone(Tinebase_Core::getUserTimezone())->toString();
        $clientRecords = array(array(
            'recordData'        => $fritz,
            'resolveStrategy'   => 'mergeTheirs',
            'index'             => 1,
        ));
        $result = $this->_importHelper(array('dryrun' => 0), $clientRecords);
        $this->assertEquals(1, $result['totalcount'], 'Should merge fritz: ' . print_r($result, TRUE));
    }

    /**
     * import helper
     *
     * @param array $additionalOptions
     * @param array $clientRecords
     * @param string $filename
     * @return array
     */
    protected function _importHelper($additionalOptions = array('dryrun' => 1), $clientRecords = array(), $filename = NULL)
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_tine_import_csv');
        $definitionOptions = Tinebase_ImportExportDefinition::getOptionsAsZendConfigXml($definition);
        
        $tempFileBackend = new Tinebase_TempFile();
        $importFile = ($filename) ? $filename : dirname(dirname(dirname(dirname(__FILE__)))) . '/tine20/' . $definitionOptions->example;
        $tempFile = $tempFileBackend->createTempFile($importFile);
        $options = array_merge($additionalOptions, array(
            'container_id'  => $this->container->getId(),
        ));
        $result = $this->_uit->importContacts($tempFile->getId(), $definition->getId(), $options, $clientRecords);
        if (isset($additionalOptions['dryrun']) && $additionalOptions['dryrun'] === 0) {
            foreach ($result['results'] as $contact) {
                $this->_contactIdsToDelete[] = $contact['id'];
            }
        }

        return $result;
    }

    /**
     * testImportWithTags
     */
    public function testImportWithTags()
    {
        $this->_testNeedsTransaction();

        $options = array(
            'dryrun'     => 0,
            'autotags'   => array(array(
                'name'            => 'Importliste (19.10.2011)',
                'description'    => 'Kontakte der Importliste vom 19.10.2011 um 20.00 Uhr. Bearbeiter: UNITTEST',
                'contexts'        => array('Addressbook' => ''),
                'type'            => Tinebase_Model_Tag::TYPE_SHARED,
            )),
        );
        $result = $this->_importHelper($options);
        $fritz = $result['results'][1];
        
        //print_r($result);
        $this->assertEquals(2, count($result['results']), 'should import 2');
        $this->assertEquals(1, count($result['results'][0]['tags']), 'no tag added');
        $this->assertEquals('Importliste (19.10.2011)', $result['results'][0]['tags'][0]['name']);
        
        $fritz['tags'] = array(array(
            'name'    => 'supi',
            'type'    => Tinebase_Model_Tag::TYPE_PERSONAL,
        ));
        $fritz = $this->_uit->saveContact($fritz);
        //print_r($fritz);
        
        // once again for duplicates (check if client record has tag)
        $result = $this->_importHelper($options);
        //print_r($result);
        $this->assertEquals(2, count($result['exceptions']), 'should have 2 duplicates');
        $this->assertEquals(1, count($result['exceptions'][0]['exception']['clientRecord']['tags']), 'no tag added');
        $this->assertEquals('Importliste (19.10.2011)', $result['exceptions'][0]['exception']['clientRecord']['tags'][0]['name']);
        $fritzClient = $result['exceptions'][1]['exception']['duplicates'][0];
        
        // emulate client merge behaviour
        $fritzClient['tags'][] = $result['exceptions'][1]['exception']['clientRecord']['tags'][0];
        $fritzClient['adr_one_locality'] = '';
        $clientRecords = array(array(
            'recordData'        => $fritzClient,
            'resolveStrategy'   => 'mergeMine',
            'index'             => 1,
        ));
        
        $result = $this->_importHelper(array('dryrun' => 0), $clientRecords);
        $this->assertEquals(1, $result['totalcount'], 'Should merge fritz: ' . print_r($result['exceptions'], TRUE));
        $this->assertEquals(2, count($result['results'][0]['tags']), 'Should merge tags');
        $this->assertEquals(NULL, $result['results'][0]['adr_one_locality'], 'Should remove locality');
    }

    /**
    * testImportWithExistingTag
    */
    public function testImportWithExistingTag()
    {
        $this->_testNeedsTransaction();

        $tag = $this->_getTag(Tinebase_Model_Tag::TYPE_PERSONAL);
        $tag = Tinebase_Tags::getInstance()->create($tag);
        
        $options = array(
            'dryrun'     => 0,
            'autotags'   => array($tag->getId()),
        );
        $result = $this->_importHelper($options);
        
        $this->assertEquals(0, count($result['exceptions']));
        $this->assertEquals($tag->name, $result['results'][0]['tags'][0]['name']);
    }
    
    /**
    * testImportWithNewTag
    */
    public function testImportWithNewTag()
    {
        $this->_testNeedsTransaction();

        $tag = $this->_getTag(Tinebase_Model_Tag::TYPE_PERSONAL);
        
        $options = array(
            'dryrun'     => 0,
            'autotags'   => array($tag->toArray()),
        );
        $result = $this->_importHelper($options);
        
        $this->assertEquals(0, count($result['exceptions']));
        $this->assertEquals($tag->name, $result['results'][0]['tags'][0]['name']);
    }
    
    /**
     * testImportKeepExistingWithTag
     * 
     * @see 0006628: tag handling on duplicate resolve actions in import fails
     */
    public function testImportKeepExistingWithTag()
    {
        $this->_testNeedsTransaction();

        $klaus = $this->_tagImportHelper('discard');
        $this->assertEquals(2, count($klaus['tags']), 'klaus should have both tags: ' . print_r($klaus['tags'], TRUE));
    }
    
    /**
     * testImportMergeTheirsWithTag
     *
     */
    public function testImportMergeTheirsWithTag()
    {
        $this->_testNeedsTransaction();

        $result = $this->_importHelper(array('dryrun' => 0));
        $this->assertTrue(count($result['results']) > 0, 'no record were imported');
        $klaus = $result['results'][0];
        
        $klaus['tags'][] = $this->_getTag()->toArray();
        $klaus['adr_one_postalcode'] = '12345';
        
        $clientRecords = array(array(
            'recordData'        => $klaus,
            'resolveStrategy'   => 'mergeTheirs',
            'index'             => 0,
        ));
        
        $options = array(
            'dryrun'     => 0,
            'duplicateResolveStrategy' => 'mergeTheirs',
        );
        
        $result = $this->_importHelper($options, $clientRecords);
        $this->assertEquals(2, count($result['results'][0]['tags']), 'klaus should have both tags: ' . print_r($result['results'][0], TRUE));
        
        $klaus = $this->_uit->getContact($klaus['id']);
        $this->assertEquals(2, count($klaus['tags']), 'klaus should have both tags: ' . print_r($klaus, TRUE));
        $this->assertEquals('12345', $klaus['adr_one_postalcode']);
    }
    
    /**
     * helper for import with tags and keep/discard strategy
     * 
     * @param string $resolveStrategy
     * @return array
     */
    protected function _tagImportHelper($resolveStrategy)
    {
        $result = $this->_importHelper(array('dryrun' => 0));
        $klaus =  $result['results'][0];
        $currentTag = $klaus['tags'][0];
        $klausId = $klaus['id'];
        
        if ($resolveStrategy === 'keep') {
            unset($klaus['id']);
        }
        
        // keep existing record and discard mine + add new tag
        $clientRecords = array(array(
            'recordData'        => $klaus,
            'resolveStrategy'   => $resolveStrategy,
            'index'             => 0,
        ));
        $tag = $this->_getTag(Tinebase_Model_Tag::TYPE_PERSONAL);
        $options = array(
            'dryrun'     => 0,
            'autotags'   => array($tag->toArray()),
        );
        
        $result = $this->_importHelper($options, $clientRecords);
        
        $expectedTotalcount = ($resolveStrategy === 'keep') ? 1 : 0;
        $this->assertEquals($expectedTotalcount, $result['totalcount'], 'Should discard fritz');
        $this->assertEquals(1, $result['duplicatecount'], 'fritz should still be a duplicate');
        
        $klaus = $this->_uit->getContact($klausId);
        
        return $klaus;
    }

    /**
     * testImportKeepBothWithTag
     * 
     * @see 0006628: tag handling on duplicate resolve actions in import fails
     */
    public function testImportKeepBothWithTag()
    {
        $this->_testNeedsTransaction();

        $klaus = $this->_tagImportHelper('keep');
        $this->assertEquals(1, count($klaus['tags']), 'klaus should have only one tag: ' . print_r($klaus['tags'], TRUE));
    }
    
    /**
     * testImportTagWithLongName
     * 
     * @see 0007276: import re-creates tags that have names with more than 40 chars
     * @see 0007356: increase tag name size to 256 chars
     */
    public function testImportTagWithLongName()
    {
        $this->_testNeedsTransaction();

        // import records with long tag name
        $result = $this->_importHelper(array('dryrun' => 0), array(), dirname(__FILE__) . '/Import/files/adb_tine_import_with_tag.csv');
        
        $this->assertEquals(2, $result['totalcount'], 'should import 2 records: ' . print_r($result, TRUE));
        $this->assertEquals(2, count($result['results'][0]['tags']), 'record should have 2 tags: ' . print_r($result['results'][0], TRUE));
        
        // check that tag is only created and added once + remove
        $tagName = '2202_Netzwerk_national_potentielle_Partner';
        $tags = Tinebase_Tags::getInstance()->searchTags(new Tinebase_Model_TagFilter(array(
            'name'  => $tagName,
            'grant' => Tinebase_Model_TagRight::VIEW_RIGHT,
            'type'  => Tinebase_Model_Tag::TYPE_SHARED
        )));
        $this->objects['createdTagIds'] = $tags->getArrayOfIds();
        $this->assertEquals(1, count($tags), 'tag not found');
        $this->assertEquals(2, $tags->getFirstRecord()->occurrence);
    }
    
    /**
     * test project relation filter
     */
    public function testProjectRelationFilter()
    {
        if (! Setup_Controller::getInstance()->isInstalled('Projects')) {
            $this->markTestSkipped('Projects not installed.');
        }
        
        $contact = $this->_uit->saveContact($this->_getContactData());
        $project = $this->_getProjectData($contact);

        $projectJson = new Projects_Frontend_Json();
        $newProject = $projectJson->saveProject($project);

        $this->_testProjectRelationFilter($contact, 'definedBy', $newProject);
        $this->_testProjectRelationFilter($contact, 'in', $newProject);
        $this->_testProjectRelationFilter($contact, 'equals', $newProject);
    }

    /**
     * get Project (create and link project + contacts)
     *
     * @return array
     */
    protected function _getProjectData($_contact)
    {
        $project = array(
            'title'         => Tinebase_Record_Abstract::generateUID(),
            'description'   => 'blabla',
            'status'        => 'IN-PROCESS',
        );

        $project['relations'] = array(
            array(
                'own_model'              => 'Projects_Model_Project',
                'own_backend'            => 'Sql',
                'own_id'                 => 0,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'type'                   => 'COWORKER',
                'related_backend'        => 'Sql',
                'related_id'             => $_contact['id'],
                'related_model'          => 'Addressbook_Model_Contact',
                'remark'                 => NULL,
            ),
            array(
                'own_model'              => 'Projects_Model_Project',
                'own_backend'            => 'Sql',
                'own_id'                 => 0,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'type'                   => 'RESPONSIBLE',
                'related_backend'        => 'Sql',
                'related_id'             => Tinebase_Core::getUser()->contact_id,
                'related_model'          => 'Addressbook_Model_Contact',
                'remark'                 => NULL,
            )

        );

        return $project;
    }

    /**
     * helper for project relation filter test
     *
     * @param array $_contact
     * @param string
     * @param array $_project
     */
    protected function _testProjectRelationFilter($_contact, $_operator, $_project)
    {
        switch ($_operator) {
            case 'definedBy':
                $closedStatus = Projects_Config::getInstance()->get(Projects_Config::PROJECT_STATUS)->records->filter('is_open', 0);
                $filters = array(
                    array('field' => ":relation_type", "operator" => "equals", "value" => "COWORKER"),
                    array('field' => "status",         "operator" => "notin",  "value" => $closedStatus->getId()),
                    array('field' => 'id',             'operator' =>'in',      'value' => array($_project['id']))
                );
                break;
            case 'in':
                $filters = array(array('field' => 'id', 'operator' => $_operator, 'value' => array($_project['id'])));
                break;
            case 'equals':
                $filters = array(array('field' => 'id', 'operator' => $_operator, 'value' => $_project['id']));
                break;
        }

        $filterId = Tinebase_Record_Abstract::generateUID();
        $filter = array(
            array(
                'field'     => 'foreignRecord',
                'operator'  => 'AND',
                'id'        => $filterId,
                'value' => array(
                    'linkType'      => 'relation',
                    'appName'       => 'Projects',
                    'modelName'     => 'Project',
                    'filters'       => $filters
                )
            ),
            array('field' => 'id', 'operator' => 'in', 'value' => array($_contact['id'], Tinebase_Core::getUser()->contact_id)),
        );
        $result = $this->_uit->searchContacts($filter, array());

        $this->assertEquals('relation', $result['filter'][0]['value']['linkType']);
        $this->assertTrue(isset($result['filter'][0]['id']), 'id expected');
        $this->assertEquals($filterId, $result['filter'][0]['id']);

        if ($_operator === 'definedBy') {
            $this->assertEquals(':relation_type',        $result['filter'][0]['value']['filters'][0]['field']);
            $this->assertEquals(1, $result['totalcount'], 'Should find only the COWORKER!');
            $this->assertEquals($_contact['org_name'], $result['results'][0]['org_name']);
        } else {
            $this->assertEquals(2, $result['totalcount'], 'Should find both contacts!');
        }
    }

    /**
     * testAttenderForeignIdFilter
     */
    public function testAttenderForeignIdFilter()
    {
        $contact = $this->_addContact();
        $event = $this->_getEvent($contact);
        Calendar_Controller_Event::getInstance()->create($event);

        $filter = array(
            array(
                'field' => 'foreignRecord',
                'operator' => 'AND',
                'value' => array(
                    'linkType'      => 'foreignId',
                    'appName'       => 'Calendar',
                    'filterName'    => 'ContactAttendeeFilter',
                    'modelName'     => 'Event',
                    'filters'       => array(
                        array('field' => "period",            "operator" => "within", "value" => array(
                            'from'  => '2009-01-01 00:00:00',
                            'until' => '2010-12-31 23:59:59',
                        )),
                        array('field' => 'attender_status',   "operator" => "in",  "value" => array('NEEDS-ACTION', 'ACCEPTED')),
                        array('field' => 'attender_role',     "operator" => "in",  "value" => array('REQ')),
                    )
                )
            ),
            array('field' => 'id', 'operator' => 'in', 'value' => array(Tinebase_Core::getUser()->contact_id, $contact['id']))
        );
        $result = $this->_uit->searchContacts($filter, array());
        $this->assertEquals('foreignRecord', $result['filter'][0]['field']);
        $this->assertEquals('foreignId', $result['filter'][0]['value']['linkType']);
        $this->assertEquals('ContactAttendeeFilter', $result['filter'][0]['value']['filterName']);
        $this->assertEquals('Event', $result['filter'][0]['value']['modelName']);

        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, $result['results'][0]['id']);
    }

    /**
     * testOrganizerForeignIdFilter
     */
    public function testOrganizerForeignIdFilter()
    {
        $contact = $this->_addContact();
        $event = $this->_getEvent($contact);
        Calendar_Controller_Event::getInstance()->create($event);

        $filter = array(
            $this->_getOrganizerForeignIdFilter(),
            array('field' => 'id', 'operator' => 'in', 'value' => array(Tinebase_Core::getUser()->contact_id, $contact['id']))
        );
        $result = $this->_uit->searchContacts($filter, array());

        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, $result['results'][0]['id']);
    }

    /**
     * return event organizuer filter
     *
     * @return array
     */
    protected function _getOrganizerForeignIdFilter()
    {
        return array(
            'field' => 'foreignRecord',
            'operator' => 'AND',
            'value' => array(
                'linkType'      => 'foreignId',
                'appName'       => 'Calendar',
                'filterName'    => 'ContactOrganizerFilter',
                'filters'       => array(
                    array('field' => "period",            "operator" => "within", "value" => array(
                        'from'  => '2009-01-01 00:00:00',
                        'until' => '2010-12-31 23:59:59',
                    )),
                    array('field' => 'organizer',   "operator" => "equals",  "value" => Tinebase_Core::getUser()->contact_id),
                )
            )
        );
    }

    /**
     * testOrganizerForeignIdFilterWithOrCondition
     */
    public function testOrganizerForeignIdFilterWithOrCondition()
    {
        $contact = $this->_addContact();
        $event = $this->_getEvent($contact);
        Calendar_Controller_Event::getInstance()->create($event);

        $filter = array(array(
            'condition' => 'OR',
            'filters'   => array(
                $this->_getOrganizerForeignIdFilter(),
                array('field' => 'id', 'operator' => 'in', 'value' => array($contact['id']))
            )
        ));
        $result = $this->_uit->searchContacts($filter, array());

        $this->assertEquals(2, $result['totalcount'], 'expected 2 contacts');
    }

    /**
     * returns a simple event
     *
     * @param array $_contact
     * @return Calendar_Model_Event
     */
    protected function _getEvent($_contact)
    {
        $testCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'PHPUnit test calendar',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'        => 'Sql',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()
        ), true));

        return new Calendar_Model_Event(array(
            'summary'     => 'Wakeup',
            'dtstart'     => '2009-03-25 06:00:00',
            'dtend'       => '2009-03-25 06:15:00',
            'description' => 'Early to bed and early to rise, makes a men healthy, wealthy and wise',
            'attendee'    => new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
                array (
                    'user_id'        => Tinebase_Core::getUser()->contact_id,
                    'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                    'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                    'status_authkey' => Tinebase_Record_Abstract::generateUID(),
                ),
                array (
                    'user_id'        => $_contact['id'],
                    'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                    'role'           => Calendar_Model_Attender::ROLE_OPTIONAL,
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

    /**
     * testDuplicateCheck
     */
    public function testDuplicateCheck($_duplicateCheck = TRUE)
    {
        $contact = $this->_addContact();
        try {
            $this->_addContact($contact['org_name'], $_duplicateCheck);
            $this->assertFalse($_duplicateCheck, 'duplicate detection failed');
        } catch (Tinebase_Exception_Duplicate $ted) {
            $this->assertTrue($_duplicateCheck, 'force creation failed');
            $exceptionData = $ted->toArray();
            $this->assertEquals(1, count($exceptionData['duplicates']), print_r($exceptionData['duplicates'], TRUE));
            $this->assertEquals($contact['n_given'], $exceptionData['duplicates'][0]['n_given']);
            $this->assertEquals($contact['org_name'], $exceptionData['duplicates'][0]['org_name']);
        }
    }
    
    /**
    * testDuplicateCheckWithTag
    */
    public function testDuplicateCheckWithTag()
    {
        $this->_testNeedsTransaction();

        $tagName = Tinebase_Record_Abstract::generateUID();
        $tag = array(
            'type'          => Tinebase_Model_Tag::TYPE_PERSONAL,
            'name'          => $tagName,
            'description'    => 'testModlog',
            'color'         => '#009B31',
        );
        $contact = $this->_addContact(NULL, FALSE, array($tag));
        
        unset($contact['id']);
        // replace tag array with single tag id (like the client does)
        $contact['tags'] = array($contact['tags'][0]['id']);
        try {
            $newContact = $this->_uit->saveContact($contact, TRUE);
            $this->assertTrue(FALSE, 'duplicate detection failed');
        } catch (Tinebase_Exception_Duplicate $ted) {
            $exceptionData = $ted->toArray();
            $this->assertEquals(1, count($exceptionData['clientRecord']['tags']), print_r($exceptionData['duplicates'], TRUE));
            $this->assertTrue(is_array($exceptionData['clientRecord']['tags'][0]), 'array of tag data expected: ' . print_r($exceptionData['clientRecord']['tags'], TRUE));
            $this->assertEquals(1, count($exceptionData['duplicates'][0]['tags']), print_r($exceptionData['duplicates'], TRUE));
            $this->assertTrue(is_array($exceptionData['duplicates'][0]['tags'][0]));
        }
    }
    
    /**
     * testDuplicateCheckWithEmail
     */
    public function testDuplicateCheckWithEmail()
    {
        $contact = $this->_getContactData();
        $contact['email'] = 'test@example.org';
        $contact = $this->_uit->saveContact($contact);
        $this->_contactIdsToDelete[] = $contact['id'];
        try {
            $contact2 = $this->_getContactData();
            $contact2['email'] = 'test@example.org';
            $contact2 = $this->_uit->saveContact($contact2);
            $this->_contactIdsToDelete[] = $contact2['id'];
            $this->assertTrue(FALSE, 'no duplicate exception');
        } catch (Tinebase_Exception_Duplicate $ted) {
            $exceptionData = $ted->toArray();
            $this->assertEquals(1, count($exceptionData['duplicates']));
            $this->assertEquals($contact['email'], $exceptionData['duplicates'][0]['email']);
        }
    }

    /**
     * testForceCreation
     */
    public function testForceCreation()
    {
        $this->testDuplicateCheck(FALSE);
    }

    /**
     * testImportDefinitionsInRegistry
     */
    public function testImportDefinitionsInRegistry()
    {
        $registryData = $this->_uit->getRegistryData();

        $this->assertEquals('adb_tine_import_csv', $registryData['defaultImportDefinition']['name']);
        $this->assertTrue(is_array($registryData['importDefinitions']['results']));

        $options = $registryData['defaultImportDefinition']['plugin_options'];
        $this->assertTrue(is_array($options));
        $this->assertEquals('Addressbook_Model_Contact', $options['model']);
        $this->assertTrue(is_array($options['autotags']));
        $this->assertEquals('Import list (###CURRENTDATE###)', $options['autotags'][0]['name']);
    }

    /**
     * testSearchContactsWithTagIsNotFilter
     */
    public function testSearchContactsWithTagIsNotFilter()
    {
        $allContacts = $this->_uit->searchContacts(array(), array());

        $filter = new Addressbook_Model_ContactFilter(array(array(
            'field'    => 'n_fileas',
            'operator' => 'equals',
            'value'    =>  Tinebase_Core::getUser()->accountDisplayName
        )));
        $sharedTagName = Tinebase_Record_Abstract::generateUID();
        $tag = new Tinebase_Model_Tag(array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => $sharedTagName,
            'description' => 'testImport',
            'color' => '#009B31',
        ));
        $tag = Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $tag);

        $filter = array(array(
            'field'    => 'tag',
            'operator' => 'not',
            'value'    => $tag->getId()
        ));
        $allContactsWithoutTheTag = $this->_uit->searchContacts($filter, array());

        $this->assertTrue(count($allContactsWithoutTheTag['totalcount']) > 0);
        $this->assertEquals($allContacts['totalcount']-1, $allContactsWithoutTheTag['totalcount']);

        $sharedTagToDelete = Tinebase_Tags::getInstance()->getTagByName($sharedTagName);
    }
    
    /**
     * test search with tag filter with 'in' operator
     */
    public function testSearchContactsWithTagInFilter()
    {
        $filter = new Addressbook_Model_ContactFilter(array(array(
            'field'    => 'n_fileas',
            'operator' => 'equals',
            'value'    =>  Tinebase_Core::getUser()->accountDisplayName
        )));
        $sharedTagName = Tinebase_Record_Abstract::generateUID();
        $tag = new Tinebase_Model_Tag(array(
                    'type'  => Tinebase_Model_Tag::TYPE_SHARED,
                    'name'  => $sharedTagName,
                    'description' => 'testImport',
                    'color' => '#009B31',
        ));
        $tag = Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $tag);
        $filter = array(array(
            'field'    => 'tag',
            'operator' => 'in',
            'value'    => array($tag->getId())
        ));
        $allContactsWithTheTag = $this->_uit->searchContacts($filter, array());
        $this->assertEquals(1, $allContactsWithTheTag['totalcount']);

        $filter = array(array(
            'field'    => 'tag',
            'operator' => 'in',
            'value'    => array()
        ));
        $emptyResult = $this->_uit->searchContacts($filter, array());
        $this->assertEquals(0, $emptyResult['totalcount']);
    }
    
    /**
    * testParseAddressData
    */
    public function testParseAddressData()
    {
        $addressString = "Dipl.-Inf. (FH) Philipp Schüle
Core Developer
Metaways Infosystems GmbH
Pickhuben 2, D-20457 Hamburg

E-Mail: p.schuele@metaways.de
Web: http://www.metaways.de
Tel: +49 (0)40 343244-232
Fax: +49 (0)40 343244-222";
        
        $result = $this->_uit->parseAddressData($addressString);
        
        $this->assertTrue((isset($result['contact']) || array_key_exists('contact', $result)));
        $this->assertTrue(is_array($result['contact']));
        $this->assertTrue((isset($result['unrecognizedTokens']) || array_key_exists('unrecognizedTokens', $result)));
        $this->assertTrue(count($result['unrecognizedTokens']) > 10 && count($result['unrecognizedTokens']) < 13,
            'unrecognizedTokens number mismatch: ' . count('unrecognizedTokens'));
        $this->assertEquals('p.schuele@metaways.de', $result['contact']['email']);
        $this->assertEquals('Pickhuben 2', $result['contact']['adr_one_street']);
        $this->assertEquals('Hamburg', $result['contact']['adr_one_locality']);
        $this->assertEquals('Metaways Infosystems GmbH', $result['contact']['org_name']);
        $this->assertEquals('+49 (0)40 343244-232', $result['contact']['tel_work']);
        $this->assertEquals('http://www.metaways.de', $result['contact']['url']);
    }

    /**
    * testParseAnotherAddressData
    * 
    * @see http://forge.tine20.org/mantisbt/view.php?id=5800
    */
    public function testParseAnotherAddressData()
    {
        // NOTE: on some systems the /u modifier fails
        if (! preg_match('/\w/u', 'ä')) {
            $this->markTestSkipped('preg_match has no unicode support');
        }
        
        $addressString = "Straßenname 25 · 23569 Lübeck
Steuernummer 33/111/32212";
        
        $result = $this->_uit->parseAddressData($addressString);
        $this->assertEquals('Straßenname 25', $result['contact']['adr_one_street'], 'wrong street: ' . print_r($result, TRUE));
        $this->assertEquals('Lübeck', $result['contact']['adr_one_locality'], 'wrong street: ' . print_r($result, TRUE));
    }
    
    /**
     * testContactDisabledFilter
     */
    public function testContactDisabledFilter()
    {
        $this->_makeSCleverVisibleAgain = TRUE;
        
        // hide sclever from adb
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        $sclever->visibility = Tinebase_Model_User::VISIBILITY_HIDDEN;
        Tinebase_User::getInstance()->updateUser($sclever);
        
        // search for her with ContactDisabledFilter
        $filter = array(array('field' => 'n_given',      'operator' => 'equals',   'value' => 'Susan'));
        $result = $this->_uit->searchContacts($filter, array());
        $this->assertEquals(0, $result['totalcount'], 'found contacts: ' . print_r($result, true));
        
        $filter[] = array('field' => 'showDisabled', 'operator' => 'equals',   'value' => TRUE);
        $result = $this->_uit->searchContacts($filter, array());
        $this->assertEquals(1, $result['totalcount']);
    }
    
    /**
     * test search hidden list -> should not appear
     * 
     * @see 0006934: setting a group that is hidden from adb as attendee filter throws exception
     */
    public function testSearchHiddenList()
    {
        $hiddenGroup = new Tinebase_Model_Group(array(
            'name'          => 'hiddengroup',
            'description'   => 'hidden group',
            'visibility'    => Tinebase_Model_Group::VISIBILITY_HIDDEN
        ));
        
        try {
            $hiddenGroup = Admin_Controller_Group::getInstance()->create($hiddenGroup);
        } catch (Exception $e) {
            // group already exists
            $hiddenGroup = Tinebase_Group::getInstance()->getGroupByName($hiddenGroup->name);
        }
        
        $this->_groupIdsToDelete = array($hiddenGroup->getId());
        
        $filter = array(array(
            'field'    => 'name',
            'operator' => 'equals',
            'value'    => 'hiddengroup'
        ));
        
        $result = $this->_uit->searchLists($filter, array());
        $this->assertEquals(0, $result['totalcount'], 'should not find hidden list: ' . print_r($result, TRUE));
    }

    public function testAttachMultipleTagsToMultipleRecords()
    {
        $contact1 = $this->_addContact('contact1');
        $contact2 = $this->_addContact('contact2');
        $tag1 = Tinebase_Tags::getInstance()->create($this->_getTag(Tinebase_Model_Tag::TYPE_PERSONAL, 'tag1'));
        $tag2 = Tinebase_Tags::getInstance()->create($this->_getTag(Tinebase_Model_Tag::TYPE_PERSONAL, 'tag2'));

        $filter = array(array('field' => 'id','operator' => 'in', 'value' => array($contact1['id'], $contact2['id'])));
        $tinebaseJson = new Tinebase_Frontend_Json();

        $tinebaseJson->attachMultipleTagsToMultipleRecords($filter,'Addressbook_Model_ContactFilter',array(
            $tag1->toArray(),
            $tag2->toArray(),
        ));

        $result = $this->_uit->searchContacts($filter, array());
        $this->assertCount(2, $result['results'], 'search count failed');

        foreach($result['results'] as $contactData) {
            $this->assertCount(2, $contactData['tags'], $contactData['n_fn'] . ' tags failed');
        }
    }

    /**
     * @see 0011584: allow to set group member roles
     */
    public function testCeateListWithMemberAndRole($listRoleName = 'my test role')
    {
        $contact = $this->_addContact();
        $listRole = $this->_uit->saveListRole(array(
            'name'          => $listRoleName,
            'description'   => 'my test description'
        ));
        $memberroles = array(array(
            'contact_id'   => $contact['id'],
            'list_role_id' => $listRole['id'],
        ));
        $list = $this->_uit->saveList(array(
            'name'                  => 'my test list',
            'description'           => '',
            'members'               => array($contact['id']),
            'memberroles'           => $memberroles,
            'type'                  => Addressbook_Model_List::LISTTYPE_LIST,
        ));

        $this->assertEquals(array($contact['id']), $list['members'], 'members are not saved/returned in list: ' . print_r($list, true));
        $this->assertTrue(isset($list['memberroles']), 'memberroles missing from list');
        $this->assertEquals(1, count($list['memberroles']), 'member roles are not saved/returned in list: ' . print_r($list, true));
        $this->assertTrue(isset($list['memberroles'][0]['list_role_id']['id']), 'list roles should be resolved');
        $this->assertEquals($listRole['id'], $list['memberroles'][0]['list_role_id']['id'], 'member roles are not saved/returned in list: ' . print_r($list, true));

        return $list;
    }

    /**
     * @see 0011584: allow to set group member roles
     */
    public function testRemoveListMemberRoles()
    {
        $list = $this->testCeateListWithMemberAndRole();

        $list['memberroles'] = array();
        $updatedList = $this->_uit->saveList($list);
        $this->assertTrue(empty($updatedList['memberroles']), 'memberroles should be removed: ' . print_r($updatedList, true));
    }

    /**
     * @see 0011578: add list roles to CoreData + Addressbook
     */
    public function testListRolesApi()
    {
        $this->_testSimpleRecordApi('ListRole');
    }

    /**
     * @see 0011584: allow to set group member roles
     */
    public function testSearchContactByListRole()
    {
        $list = $this->testCeateListWithMemberAndRole();

        $filter = array(
            array('field' => 'list_role_id','operator' => 'in', 'value' => array($list['memberroles'][0]['list_role_id']['id']))
        );

        $result = $this->_uit->searchContacts($filter, array());

        $this->assertEquals(1, $result['totalcount']);
    }

    /**
     * @see 0011704: PHP 7 can't decode empty JSON-strings
     */
    public function testEmptyPagingParamJsonDecode()
    {
        $filter = array(array(
            'field'    => 'n_family',
            'operator' => 'equals',
            'value'    => 'somename'
        ));
        $result = $this->_uit->searchContacts($filter, '');
        $this->assertEquals(0, $result['totalcount']);
    }
}
