<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 *
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
            Addressbook_Model_Contact::class,
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

        // disable "short name" feature as this messes with some tests
        $enabledFeatures = Addressbook_Config::getInstance()->get(Addressbook_Config::ENABLED_FEATURES);
        $enabledFeatures[Addressbook_Config::FEATURE_SHORT_NAME] = false;
        Addressbook_Config::getInstance()->set(Addressbook_Config::ENABLED_FEATURES, $enabledFeatures);

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

        if ($this->_uit) {
            $this->_uit->deleteContacts($this->_contactIdsToDelete);
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

        /*
        $contactsById = [];
        foreach ($contacts['results'] as $data) {
            $contactsById[$data['id']] = $data;
        }
        $records = Addressbook_Controller_Contact::getInstance()->getMultiple(array_keys($contactsById));

        Addressbook_Frontend_Json::resolveImages($records);
        if (true === Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH)) {
            $pathController = Tinebase_Record_Path::getInstance();
            foreach ($records as $record) {
                $record->paths = $pathController->getPathsForRecord($record);
                $pathController->cutTailAfterRecord($record, $record->paths);
            }
        }
        $converter = new Tinebase_Convert_Json();
        $data = [];
        foreach ($converter->fromTine20RecordSet($records) as $a) {

            // fix legacy bugs
            if (array_key_exists('cat_id', $a)) {
                unset($a['cat_id']);
            }
            if (array_key_exists('label', $a)) {
                unset($a['label']);
            }
            if (array_key_exists('private', $a)) {
                unset($a['private']);
            }
            $data[$a['id']] = $a;
        }

        $id = $contacts['results'][0]['id'];
        static::assertEquals($data[$id], $contactsById[$id]);
        static::assertEquals($data, $contactsById);
        */
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

    public function testGetListWithAccountOnlyField()
    {
        $adminListId = Tinebase_Group::getInstance()->getDefaultAdminGroup()->list_id;
        $list = $this->_uit->getList($adminListId);
        self::assertTrue(isset($list['account_only']), 'account_only field missing from list ' . print_r($list, true));

        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP ||
            Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY
        ) {
            self::assertEquals('0', $list['account_only']);
        } else {
            self::assertEquals('1', $list['account_only']);
        }
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
     */
    public function testGetOtherPeopleContacts()
    {
        $paging = $this->objects['paging'];

        $filter = array(
            array('field' => 'container_id', 'operator' => 'in',   'value' => array(
                'id'    => 'otherUsers',
                'name'  => 'Adressbücher anderer Benutzer',
                'path'  => '/personal'
            )),
        );
        $contacts = $this->_uit->searchContacts($filter, $paging);

        $this->assertGreaterThanOrEqual(0, $contacts['totalcount'], 'getting other peoples contacts failed');

        // all the way down \Tinebase_Model_Filter_FilterGroup::setFromArray
        // \Tinebase_Model_Filter_FilterGroup::_createStandardFilterFromArray
        // \Tinebase_Model_Filter_FilterGroup::createFilter
        // options will be overwritten: $data['options'] = array_merge($this->_options, isset($definition['options']) ? (array)$definition['options'] : array(), array('parentFilter' => $self));
        $filter = array(
            array('field' => 'container_id', 'operator' => 'in',   'value' => array(
                'id'    => 'otherUsers',
                'name'  => 'Adressbücher anderer Benutzer',
                'path'  => '/personal'
            ), 'options' => array('ignoreAcl' => true)),
        );
        $contacts1 = $this->_uit->searchContacts($filter, $paging);

        $this->assertEquals(count($contacts['results']), count($contacts1['results']));
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
     * Test getting a Contact with and without the PrivateDataGrant
     * 
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testGetPrivateContactData() {
        $originalUser = Tinebase_Core::getUser();
        $contact = $this->_addContact();

        $this->assertTrue(Tinebase_Core::getUser()->hasGrant($contact['container_id'], Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA));
        $this->assertArrayHasKey('tel_cell_private', $contact);
        
        $this->_setPersonaGrantsForTestContainer($contact['container_id'], 'sclever');
        Tinebase_Core::setUser($this->_personas['sclever']);
        $this->assertFalse(Tinebase_Core::getUser()->hasGrant($contact['container_id'], Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA));

        $contactWithoutPrivate = $this->_uit->getContact($contact['id']);
        $this->assertArrayNotHasKey('tel_cell_private', $contactWithoutPrivate);

        Tinebase_Core::setUser($originalUser);
    }

    /**
     * sclever has no PrivateData Grant or Admin Grant for InternalContacts
     * But still sees her own Contacts private data
     * 
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function testGetPrivateContactDataOfOwnContact() {
        $originalUser = Tinebase_Core::getUser();
        $internalContainer = Tinebase_Container::getInstance()->getContainerByName(Addressbook_Model_Contact::class, 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);
        
        Tinebase_Core::setUser($this->_personas['sclever']);
        $this->assertFalse(Tinebase_Core::getUser()->hasGrant($internalContainer->getId(), Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA));
        $this->assertFalse(Tinebase_Core::getUser()->hasGrant($internalContainer->getId(), Tinebase_Model_Grants::GRANT_ADMIN));


        $contactWithoutPrivate = $this->_uit->getContact(Tinebase_Core::getUser()->contact_id);
        $this->assertArrayHasKey('tel_cell_private', $contactWithoutPrivate);

        Tinebase_Core::setUser($originalUser);
    }

    /**
     * Test seraching for a contact with and without the privateDataGrant
     * 
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testSearchPrivateContactData() {
        $originalUser = Tinebase_Core::getUser();
        $contact = $this->_addContact();

        $paging = $this->objects['paging'];

        $filter = array(
            array('field' => 'n_family', 'operator' => 'contains', 'value' => 'PHPUNIT')
        );
        $contacts = $this->_uit->searchContacts($filter, $paging);

        $this->assertTrue(Tinebase_Core::getUser()->hasGrant($contact['container_id'], Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA));
        $this->assertArrayHasKey('tel_cell_private', $contacts['results'][0]);

        $this->_setPersonaGrantsForTestContainer($contact['container_id'], 'sclever');
        Tinebase_Core::setUser($this->_personas['sclever']);
        $this->assertFalse(Tinebase_Core::getUser()->hasGrant($contact['container_id'], Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA));

        $contactWithoutPrivate = $this->_uit->searchContacts($filter, $paging);
        $this->assertArrayNotHasKey('tel_cell_private', $contactWithoutPrivate['results'][0]);
        
        Tinebase_Core::setUser($originalUser);
    }
    
    /**
     * this test is for Tinebase_Frontend_Json updateMultipleRecords with contact data in the addressbook app
     */
    public function testUpdateMultipleRecords()
    {
        $companies = array('Janes', 'Johns', 'Bobs');

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

        $translate = Tinebase_Translation::getTranslation('Tinebase');
        // check 'changed' systemnote
        $this->_checkChangedNote($record['id'], 'adr_one_region ( -> PHPUNIT_multipleUpdate) url ( -> http://www.phpunit.de) customfields ( ->  YomiName: PHPUNIT_multipleUpdate) relations (1 '. $translate->_('added') .': ');

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
     * test customfield modlog
     */
    public function testCustomfieldModlogAndSort()
    {
        $cf = $this->_createCustomfield(Tinebase_Record_Abstract::generateUID());
        $this->_addContact();
        $contact = $this->_addContact();
        $contact['customfields'][$cf->name] = 'changed value';
        $result = $this->_uit->saveContact($contact);
        
        $this->assertEquals('changed value', $result['customfields'][$cf->name]);
        $this->_checkChangedNote($result['id'], ' ->  ' . $cf->name . ': changed value)');

        // sort by customfield
        $paging = $this->objects['paging'];
        $paging['sort'] = $cf->name;
        $paging['dir'] = 'DESC';

        $searchResult = $this->_uit->searchContacts(
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->container->id] , $paging);
        static::assertEquals('changed value', $searchResult['results'][0]['customfields'][$cf->name]);
        static::assertTrue(!isset($searchResult['results'][1]['customfields']) ||
            !isset($searchResult['results'][1]['customfields'][$cf->name]));
    }

    /**
     * test record customfield resolving
     */
    public function testCustomfieldResolving()
    {
        $cf = $this->_createCustomfield($name = 'ContactCF', $model = 'Addressbook_Model_Contact', $type = 'record');
        $contact = $this->_addContact();
        $contact2 = $this->_addContact('another one');
        $contact['customfields'][$cf->name] = $contact2['id'];
        $result = $this->_uit->saveContact($contact);

        $this->assertTrue(is_array($result['customfields'][$cf->name]), 'customfield value is not resolved');
        $this->assertEquals($contact2['id'], $result['customfields'][$cf->name]['id']);
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
        $changedNote = preg_replace('/\s*GDPR_DataProvenance \([^)]+\)/', '',
            $history['results'][$_changedNoteNumber - 1]);
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
        list(,$sharedTagId) = $this->_createAndAttachTag($filter, $type);
        $this->_checkChangedNote($contact['id'], array('tags ( ->  0: ' . $sharedTagId));

        return $contact;
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
        
        $this->assertTrue(! isset($contact['tags']) || count($contact['tags']) === 0, 'record should not have any tags');
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
     */
    public function testGetContact()
    {
        $contact = $this->_addContact();

        $contact = $this->_uit->getContact($contact['id']);

        $this->assertEquals('PHPUNIT', $contact['n_family'], 'getting contact failed');
    }

    /**
     * @see 0012280: Add Industries to Contact
     */
    public function testUpdateContactWithIndustry()
    {
        if (! Addressbook_Config::getInstance()->featureEnabled(Addressbook_Config::FEATURE_INDUSTRY)) {
            $this->markTestSkipped('feature disabled');
        }

        // create industry
        $industry = $this->_testSimpleRecordApi('Industry', /* $nameField */ 'name', /* $descriptionField */ 'description', /* $delete */ false);

        // use industry in contact
        $newContactData = $this->_getContactData();
        $newContactData['industry'] = $industry['id'];
        $contact = $this->_uit->saveContact($newContactData);

        // check if industry is resolved in contact
        $this->assertTrue(is_array($contact['industry']), 'Industry not resolved: ' . print_r($contact, true));
    }

    /**
     * Test automatic Short Name Creation
     */
    public function testContactShortName()
    {
        $this->markTestSkipped('FIXME: does not work with de locale ...');

        $enabledFeatures = Addressbook_Config::getInstance()->get(Addressbook_Config::ENABLED_FEATURES);
        $enabledFeatures[Addressbook_Config::FEATURE_SHORT_NAME] = true;

        Addressbook_Config::getInstance()->set(Addressbook_Config::ENABLED_FEATURES, $enabledFeatures);
        $this->assertTrue(Addressbook_Config::getInstance()->featureEnabled(Addressbook_Config::FEATURE_SHORT_NAME));

        $newContactData = $this->_getContactData();
        $newContactData['n_given'] = 'Li';
        $newContactData['n_middle'] = '1';
        $newContactData['n_family'] = 'Wun';
        $contact = $this->_uit->saveContact($newContactData);

        $this->assertEquals('LWU', $contact['n_short'], 'Short Name should be LWU ');

        try {
            $newContactData['n_given'] = 'Lee';
            $contact = $this->_uit->saveContact($newContactData);
            $this->assertEquals('LEWU', $contact['n_short'], 'Short Name should be LEWU ');
        } catch (Tinebase_Exception_SystemGeneric $e) {
            $this->assertEquals('This Short Name already exists. How about LEWU?', $e->getMessage(), 'Short Name should be LEWU');
        }

        try {
            $newContactData['n_given'] = 'Len';
            $contact = $this->_uit->saveContact($newContactData);
            $this->assertEquals('LEWUN', $contact['n_short'], 'Short Name should be LEWUN ');
        } catch (Tinebase_Exception_SystemGeneric $e) {
            $this->assertEquals('This Short Name already exists. How about LEWUN?', $e->getMessage(), 'Short Name should be LEWUN');
        }

        try {
            $newContactData['n_given'] = 'Lena';
            $contact = $this->_uit->saveContact($newContactData);
            $this->assertEquals('LENWUN', $contact['n_short'], 'Short Name should be LENWUN ');
        } catch (Tinebase_Exception_SystemGeneric $e) {
            $this->assertEquals('This Short Name already exists. How about LENWUN?', $e->getMessage(), 'Short Name should be LENWUN');
        }

        // set manually
        $newContactData['n_given'] = 'Leander';
        $newContactData['n_short'] = 'XXX';
        $contact = $this->_uit->saveContact($newContactData);
        $this->assertEquals('XXX', $contact['n_short'], 'Short Name should be XXX ');
        $newContactData['n_short'] = NULL;

        // Leonard wants to be XXX (set manually) but Leander already is XXX
        try {
            $newContactData['n_given'] = 'Leonard';
            $newContactData['n_short'] = 'XXX';
            $this->_uit->saveContact($newContactData);
        } catch (Tinebase_Exception_SystemGeneric $e) {
            $this->assertEquals('This Short Name already exists. How about LEOWUN?', $e->getMessage(), 'Sry but Leander should be XXX already!');
        }

        $enabledFeatures = Addressbook_Config::getInstance()->get(Addressbook_Config::FEATURE_SHORT_NAME);
        $enabledFeatures[Addressbook_Config::FEATURE_SHORT_NAME] = false;

        Addressbook_Config::getInstance()->set(Addressbook_Config::ENABLED_FEATURES, $enabledFeatures);
    }
    
    /**
     * test updating of a contact (including geodata)
     *
     * @group longrunning
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
        list($sharedTagName) = $this->_createAndAttachTag($filter);
        list($personalTagName) = $this->_createAndAttachTag($filter, Tinebase_Model_Tag::TYPE_PERSONAL);

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
        
        return array($tag->name, $tag->id);
    }
    
    /**
     * testExportXlsWithCustomfield
     * 
     * @see 0006634: custom fields missing in XLS export
     */
    public function testExportXlsWithCustomfield()
    {
        static::markTestSkipped('FIX ME');

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
        static::markTestSkipped('FIX ME');

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
     *
     * @return array
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

        return $contact;
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
     * testTextFilterCaseSensitivity
     */
    public function testTextFilterCaseSensitivity()
    {
        $contact = $this->_addContact();
        $filter = array(
            array('field' => 'n_family', 'operator' => 'contains', 'value' => strtolower('PHPUNIT'))
        );
        $result = $this->_uit->searchContacts($filter, array());

        $this->assertGreaterThan(0, $result['totalcount'], 'contact not found: ' . print_r($result, true));
    }

    /**
     * testTextFilterWildcards
     */
    public function testTextFilterWildcards()
    {
        $contact = $this->_addContact('my * Corp');
        $filter = array(
            array('field' => 'n_family', 'operator' => 'equals', 'value' => strtolower('PHP*NIT')),
            array('field' => 'id', 'operator' => 'equals', 'value' => $contact['id']),
        );
        $result = $this->_uit->searchContacts($filter, array());

        $this->assertEquals(1, $result['totalcount'], 'contact not found: ' . print_r($result, true));

        $filter = array(
            array('field' => 'n_family', 'operator' => 'equals', 'value' => strtolower('PHP*NIT')),
            array('field' => 'org_name', 'operator' => 'equals', 'value' => strtolower('* Corp')),
            array('field' => 'id', 'operator' => 'equals', 'value' => $contact['id']),
        );
        $result = $this->_uit->searchContacts($filter, array());

        $this->assertEquals(1, $result['totalcount'], 'contact not found: ' . print_r($result, true));

        $filter = array(
            array('field' => 'n_family', 'operator' => 'equals', 'value' => strtolower('PHP*NIT')),
            array('field' => 'org_name', 'operator' => 'contains', 'value' => strtolower('\* Corp')),
            array('field' => 'id', 'operator' => 'equals', 'value' => $contact['id']),
        );
        $result = $this->_uit->searchContacts($filter, array());

        $this->assertEquals(1, $result['totalcount'], 'contact not found: ' . print_r($result, true));

        $filter = array(
            array('field' => 'n_family', 'operator' => 'equals', 'value' => strtolower('PHP*NIT')),
            array('field' => 'org_name', 'operator' => 'contains', 'value' => strtolower('not \* Corp')),
            array('field' => 'id', 'operator' => 'equals', 'value' => $contact['id']),
        );
        $result = $this->_uit->searchContacts($filter, array());

        $this->assertEquals(0, $result['totalcount'], 'contact not found: ' . print_r($result, true));
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
     * Tests if path info in query breaks backend
     */
    public function testSearchContactsWithPathFilterOrCondition()
    {
        $filter = [
            [
                'condition' => 'OR',
                'filters' => [
                    [
                        'condition' => 'AND',
                        'filters' => [
                            [
                                'field' => 'query',
                                'operator' => 'contains',
                                'value' => '',
                            ],
                        ],
                    ],
                    [
                        'field' => 'path',
                        'operator' => 'contains',
                        'value' => '',
                    ],
                ],
            ]
        ];

        $paging = [
            'sort' => 'n_fn',
            'dir' => 'ASC',
            'start' => 0,
            'limit' => 10
        ];

        $result = $this->_uit->searchContacts($filter, $paging);

        $this->assertGreaterThanOrEqual(6, $result['totalcount']);
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
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'          => Calendar_Model_Event::class,
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
            self::assertFalse($_duplicateCheck, 'duplicate detection failed');
        } catch (Tinebase_Exception_Duplicate $ted) {
            self::assertTrue($_duplicateCheck, 'force creation failed');
            $exceptionData = $ted->toArray();
            self::assertEquals(1, count($exceptionData['duplicates']), print_r($exceptionData['duplicates'], TRUE));
            $duplicateContact = $exceptionData['duplicates'][0];
            self::assertEquals($contact['n_given'], $duplicateContact['n_given']);
            self::assertEquals($contact['org_name'], $duplicateContact['org_name']);
            self::assertTrue(is_array($duplicateContact['container_id']), print_r($duplicateContact, true));
            self::assertTrue(isset($duplicateContact['container_id']['account_grants']), print_r($duplicateContact, true));
            self::assertTrue(is_array($duplicateContact['container_id']['account_grants']), print_r($duplicateContact, true));
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
        $tfj = new Tinebase_Frontend_Json();
        $allRegistryData = $tfj->getAllRegistryData();
        $registryData = $allRegistryData['Addressbook'];

        $this->assertEquals('adb_tine_import_csv', $registryData['defaultImportDefinition']['name']);
        $this->assertTrue(is_array($registryData['importDefinitions']['results']));

        $options = $registryData['defaultImportDefinition']['plugin_options_json'];
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

        $this->assertTrue($allContactsWithoutTheTag['totalcount'] > 0);
        $this->assertEquals($allContacts['totalcount']-1, $allContactsWithoutTheTag['totalcount']);
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
            'unrecognizedTokens number mismatch: ' . count($result['unrecognizedTokens']));
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
     * test search hidden user (for example as role member)
     *
     * @see 0013160: user search should find disabled/hidden users
     */
    public function testSearchHiddenUser()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP ||
            Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            $this->markTestSkipped('FIXME: Does not work with LDAP/AD backend');
        }

        $filter = array(
            0 =>
                array(
                    'condition' => 'OR',
                    'filters' =>
                        array(
                            0 =>
                                array(
                                    'condition' => 'AND',
                                    'filters' =>
                                        array(
                                            0 =>
                                                array(
                                                    'field' => 'query',
                                                    'operator' => 'contains',
                                                    'value' => 'replication',
                                                ),
                                            1 =>
                                                array(
                                                    'field' => 'showDisabled',
                                                    'operator' => 'equals',
                                                    'value' => true,
                                                ),
                                        ),
                                ),
                            1 =>
                                array(
                                    'field' => 'path',
                                    'operator' => 'contains',
                                    'value' => 'reploic',
                                ),
                        ),
                ),
            1 =>
                array(
                    'field' => 'type',
                    'operator' => 'equals',
                    'value' => 'user',
                ),
        );

        $result = $this->_uit->searchContacts($filter, array());
        self::assertEquals(1, $result['totalcount'], 'should find replication user');
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
     * @return array
     */
    public function testCreateListWithMemberAndRole($listRoleName = 'my test role')
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

        static::assertCount(1, $list['members'], 'expect one member');
        $this->assertEquals($contact['id'], $list['members'][0]['id'], 'members are not saved/returned in list: ' . print_r($list, true));
        $this->assertTrue(isset($list['memberroles']), 'memberroles missing from list');
        $this->assertEquals(1, count($list['memberroles']), 'member roles are not saved/returned in list: ' . print_r($list, true));
        $this->assertTrue(isset($list['memberroles'][0]['list_role_id']['id']), 'list roles should be resolved');
        $this->assertEquals($listRole['id'], $list['memberroles'][0]['list_role_id']['id'], 'member roles are not saved/returned in list: ' . print_r($list, true));

        return $list;
    }

    public function testAddMemberAndRole()
    {
        // create list without members / roles
        $list = $this->_uit->saveList(array(
            'name'                  => 'my test list',
            'description'           => '',
            'type'                  => Addressbook_Model_List::LISTTYPE_LIST,
        ));

        // add one member / role
        $contact = $this->_addContact();
        $listRole = $this->_uit->saveListRole(array(
            'name'          => 'my test name',
            'description'   => 'my test description'
        ));
        $list['members'][] = $contact['id'];
        $list['memberroles'][] = array(
            'contact_id'   => $contact['id'],
            'list_role_id' => $listRole['id'],
        );

        $list = $this->_uit->saveList($list);

        // add second member / role
        $contact = $this->_addContact();
        $listRole = $this->_uit->saveListRole(array(
            'name'          => 'my test name 2',
            'description'   => 'my test description 2'
        ));
        $list['members'][] = $contact['id'];
        $list['memberroles'][] = array(
            'contact_id'   => $contact['id'],
            'list_role_id' => $listRole['id'],
        );

        $list = $this->_uit->saveList($list);

        // empty member / role
        $list['members'] = [];
        $list['memberroles'] = [];
        $list = $this->_uit->saveList($list);

        // get history of list
        $historyFE = new Tinebase_Frontend_Json();
        $notes = $historyFE->searchNotes(array(
            array('field' => 'record_model',    'operator' => 'equals', 'value' => Addressbook_Model_List::class),
            array('field' => 'record_id',       'operator' => 'equals', 'value' => $list['id']),
        ), '');

        static::assertEquals(4, $notes['totalcount']);
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        foreach (array(
                array('members ( 0: ali PHPUNIT 1: ali PHPUNIT -> )', 'memberroles (2 ' . $translate->_('removed') . ': my test name: ali PHPUNIT, my test name 2: ali PHPUNIT)'),
                array('members ( 0: ali PHPUNIT ->  0: ali PHPUNIT 1: ali PHPUNIT)', 'memberroles (1 ' . $translate->_('added') . ': my test name 2: ali PHPUNIT)'),
                array('members ( ->  0: ali PHPUNIT)', 'memberroles (1 ' . $translate->_('added') . ': my test name: ali PHPUNIT)'),
            ) as $expectedStrings) {
            $found = false;
            foreach ($notes['results'] as $note) {
                $hits = 0;
                foreach ($expectedStrings as $searchStr) {
                    if (strpos($note['note'], $searchStr) !== false) {
                        ++$hits;
                    }
                }
                if ($hits === count($expectedStrings)) {
                    $found = true;
                    break;
                }
            }
            static::assertTrue($found, 'did not find strings: ' . join(', ', $expectedStrings) . ' in notes: ' . print_r($notes, true));
        }
    }

    public function testAddNonAccountContactToList()
    {
        $this->_skipIfLDAPBackend();

        $contact = $this->_getContactData();
        $newContact = $this->_uit->saveContact($contact);

        // get admin list, try to add non-account-contact, expect exception
        $adminListId = Tinebase_Group::getInstance()->getDefaultAdminGroup()->list_id;
        $list = $this->_uit->getList($adminListId);
        $list['members'][] = $newContact['id'];
        try {
            $updatedlist = $this->_uit->saveList($list);
            self::fail('should throw exception - it is not allowed to add non-account contact to admin list! '
                . print_r($updatedlist, true));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            $translate = Tinebase_Translation::getTranslation('Addressbook');
            self::assertEquals($translate->_('It is not allowed to add non-account contacts to this list'),
               $tesg->getMessage());
        }
    }

    public function testUpdateListWithRelation()
    {
        $list = $this->testCreateListWithMemberAndRole();
        $relatedList = $this->testCreateListWithMemberAndRole();

        $list['relations'] =  array(
            array(
                'type'  => 'LIST',
                'own_model' => 'Addressbook_Model_List',
                'own_backend' => 'Sql',
                'related_degree' => 'sibling',
                'related_model' => 'Addressbook_Model_List',
                'related_backend' => 'Sql',
                'related_id' => $relatedList['id'],
                'related_record' => $relatedList
            )
        );
        $list = $this->_uit->saveList($list);
        self::assertEquals(1, count($list['relations']), 'relation missing from list');
        //Save the list again...
        $list = $this->_uit->saveList($list);
        self::assertEquals(1, count($list['relations']), 'relation missing from list');
    }

    public function testUpdateListEmail()
    {
        $list = $this->testCreateListWithMemberAndRole();
        $list['email'] = 'somelistemail@' . TestServer::getPrimaryMailDomain();
        // client sends empty memberroles like that ...
        $list['memberroles'] = '';
        $updatedList = $this->_uit->saveList($list);
        self::assertEquals($list['email'], $updatedList['email']);
        $updatedList['email'] = 'somelistemailupdated@' . TestServer::getPrimaryMailDomain();
        $updatedListAgain = $this->_uit->saveList($updatedList);
        self::assertEquals($updatedList['email'], $updatedListAgain['email']);
    }

    public function testUpdateListEmailOfSystemGroup()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP ||
            Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            $this->markTestSkipped('FIXME: Does not work with LDAP/AD backend');
        }

        $lists = $this->_uit->searchLists([[
            'field'    => 'type',
            'operator' => 'equals',
            'value'    => Addressbook_Model_List::LISTTYPE_GROUP,
        ]], []);
        self::assertGreaterThan(0, $lists['totalcount'], 'no system groups found');
        $list = $lists['results'][0];

        $systemGroupEmail = $list['email'];
        // try to overwrite it with jsmith
        $jsmith = Tinebase_User::getInstance()->getFullUserByLoginName('jsmith');
        Tinebase_Core::setUser($jsmith);
        $list['email'] = Tinebase_Record_Abstract::generateUID(10) . '@' . TestServer::getPrimaryMailDomain();
        try {
            $this->_uit->saveList($list);
            self::fail('jsmith should not be able to update the record');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            self::assertContains('permission', $tead->getMessage());
        }

        // give jsmith edit grant
        Tinebase_Core::setUser($this->_originalTestUser);
        $container = Tinebase_Container::getInstance()->getContainerById($list['container_id']['id']);
        $this->_setPersonaGrantsForTestContainer(
            $container,
            'jsmith',
            false,
            true,
            [],
            true
        );
        Tinebase_Core::setUser($jsmith);
        try {
            $this->_uit->saveList($list);
            self::fail('jsmith should not be able to update the record');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            self::assertContains('ACCOUNTS', $tead->getMessage());
        }
    }

    public function testSearchListsByMember()
    {
        $list = $this->testCreateListWithMemberAndRole();
        $filter = array(array(
            'field'    => 'contact',
            'operator' => 'equals',
            'value'    => $list['members'][0],
        ));

        $result = $this->_uit->searchLists($filter, array());
        self::assertEquals(1, $result['totalcount']);
        self::assertEquals('my test list', $result['results'][0]['name']);
        self::assertTrue(isset($result['results'][0]['created_by']['accountDisplayName']), 'created_by is not resolved to user array');
    }

    /**
     * @see 0011584: allow to set group member roles
     */
    public function testRemoveListMemberRoles()
    {
        $list = $this->testCreateListWithMemberAndRole();

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
        $list = $this->testCreateListWithMemberAndRole();

        $filter = array(
            array('field' => 'list_role_id','operator' => 'in', 'value' => array($list['memberroles'][0]['list_role_id']['id']))
        );

        $result = $this->_uit->searchContacts($filter, array());

        $this->assertEquals(1, $result['totalcount']);
    }

    /**
     * @see 0012834: Tinbase_Model_Filter_Query - reimplement using FilterGroup
     */
    public function testSearchContactByQueryFilterWithAnd()
    {
        /* $contact1 = */ $this->_addContact();
        /* $contact2 = */ $this->_addContact('aaabbb');

        $filter = array(
            array('field' => 'query','operator' => 'contains', 'value' => 'aaabbb PHPUNIT')
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

    /**
     * @see 0013780: fix backslash in text filter
     */
    public function testBackslashInFilter()
    {
        $contact = $this->_getContactData();
        $contact['org_name'] = 'my org with \\backslash';
        $this->_uit->saveContact($contact);
        $filter = array(array(
            'field'    => 'org_name',
            'operator' => 'equals',
            'value'    => 'my org with \\backslash'
        ));
        $result = $this->_uit->searchContacts($filter, '');
        $this->assertEquals(1, $result['totalcount'], 'contact not found ' . print_r($result, true));
    }

    public function testSearchListWithPathFilter()
    {
        if (true !== Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH)) {
            $this->markTestSkipped('Path feature not actiavted');
        }

        Tinebase_Core::getCache()->clean();
        Tinebase_Group::getInstance()->resetClassCache();

        $filter = array(
            array(
                'field'    => 'query',
                'operator' => 'contains',
                'value'    => 'User'
            ), array(
                'field'    => 'path',
                'operator' => 'contains',
                'value'    => 'User'
            )
        );

        $result = $this->_uit->searchLists($filter, '');

        $this->assertTrue($result['totalcount'] > 0, 'Did not find list User with path filter');
    }

    /**
     * test Addressbook.searchEmailAddresss and check for "emails" property
     *
     * {"jsonrpc":"2.0","method":"Addressbook.searchEmailAddresss","params":{"filter":[{"condition":"OR","filters":[{"condition":"AND","filters":[{"field":"query","operator":"contains","value":""},{"field":"email_query","operator":"contains","value":"@"}]},{"field":"path","operator":"contains","value":""}]}],"paging":{"sort":"name","dir":"ASC","start":0,"limit":50}},"id":4}
     */
    public function testSearchEmailAddresss()
    {
        Addressbook_Controller_List::destroyInstance();
        $result = $this->_uit->searchEmailAddresss([
            ["condition" => "OR", "filters" => [["condition" => "AND", "filters" => [
                ["field" => "query", "operator" => "contains", "value" => ""],
                ["field" => "email_query", "operator" => "contains", "value" => "@"]
            ]], ["field" => "path", "operator" => "contains", "value" => ""]]]
        ], ["sort" => "name", "dir" => "ASC", "start" => 0, "limit" => 50]);

        static::assertGreaterThan(0, $result['totalcount'], 'no results found');
        static::assertTrue(isset($result['results'][count($result['results'])-1]['emails']),
            'last entry should be a list that has emails: ' . print_r($result['results'],
                true));
        foreach ($result['results'] as $entry) {
            // only lists have 'emails' key
            if (isset($entry['emails']) && empty($entry['emails'])) {
                self::fail('empty lists should not be returned - list: ' . print_r($entry, true));
            }
        }
    }

    /**
     * test with maillinglist
     */
    public function testSearchEmailAddresssWithMailinglist()
    {
        $this->_skipWithoutEmailSystemAccountConfig();

        $list = $this->_createMailinglist();
        $result = $this->_uit->searchEmailAddresss([
            ["condition" => "AND", "filters" => [["condition" => "AND", "filters" => [
                ["field" => "email", "operator" => "equals", "value" => $list['email']]
            ]]]
            ]], []);

        static::assertEquals(1, $result['totalcount'], 'no results found');
        static::assertEquals($list['email'], $result['results'][0]['emails'][0]);

        // Felamimail searchAccounts should not return mailinglist
        $ffj = new Felamimail_Frontend_Json();
        $result = $ffj->searchAccounts([], []);
        $listaccounts = array_filter($result['results'], function($account) {
            return $account['type'] === Felamimail_Model_Account::TYPE_ADB_LIST;
        });
        self::assertEquals(0, count($listaccounts), 'found adb list account(s): '
            . print_r($listaccounts, true));
    }

    /**
     * testSaveContactWithAreaLockedRelation
     */
    public function testSaveContactWithAreaLockedRelation()
    {
        // create contact with project relation
        $contact = $this->testProjectRelationFilter();

        // lock projects
        $this->_createAreaLockConfig([
            'area' => 'Projects'
        ]);
        Projects_Controller_Project::getInstance()->resetValidatedAreaLock();

        // fetch & save contact again
        $contactWithLockedProject = $this->_uit->getContact($contact['id']);
        self::assertEquals(1, count($contactWithLockedProject['relations']));
        self::assertFalse(isset($contactWithLockedProject['relations'][0]['related_record']));
        self::assertEquals(Tinebase_Model_Relation::REMOVED_BY_AREA_LOCK,
            $contactWithLockedProject['relations'][0]['record_removed_reason']);
        $contactWithLockedProjectSaved = $this->_uit->saveContact($contactWithLockedProject);
        self::assertEquals(1, count($contactWithLockedProjectSaved['relations']));

        // unlock projects
        $user = Tinebase_Core::getUser();
        Tinebase_User::getInstance()->setPin($user, '1234');
        Tinebase_AreaLock::getInstance()->unlock('Projects', '1234');

        // save contact again
        $contactWithUnlockedProjectSaved = $this->_uit->saveContact($contactWithLockedProjectSaved);
        self::assertEquals(1, count($contactWithUnlockedProjectSaved['relations']),'project relation should not be removed!');
        self::assertTrue(isset($contactWithUnlockedProjectSaved['relations'][0]['related_record']));
        self::assertEquals('blabla', $contactWithUnlockedProjectSaved['relations'][0]['related_record']['description']);
    }

    public function testSetImage()
    {
        $contact = $this->_getContactWithImage();
        $savedContactWithImage = $this->_uit->saveContact($contact);

        // save contact again
        $savedContactWithImageAgain = $this->_uit->saveContact($savedContactWithImage);;

        // check if image is still there
        self::assertTrue(isset($savedContactWithImageAgain['jpegphoto']));
        self::assertEquals($savedContactWithImage['jpegphoto'], $savedContactWithImageAgain['jpegphoto'],
            'image should not change!');
        return $savedContactWithImageAgain;
    }

    /**
     * @return array
     */
    protected function _getContactWithImage()
    {
        // create tempfile
        $tempFileBackend = new Tinebase_TempFile();
        $image = dirname(dirname(dirname(dirname(__FILE__)))) . '/tine20/images/favicon.png';
        $tempFile = $tempFileBackend->createTempFile($image);

        // save contact with tempfile
        $contact = $this->_getContactData();
        $contact['jpegphoto'] = 'index.php?method=Tinebase.getImage&application=Tinebase&location=tempFile&id='
            . $tempFile->getId() . '&width=88&height=118&ratiomode=0&mtime=1546880445806';
        return $contact;
    }

    public function testSetImageCreateDuplicateContact()
    {
        $contact = $this->_getContactWithImage();
        // let's throw a duplicate exception
        $contact['email'] = Tinebase_Core::getUser()->accountEmailAddress;
        try {
            $result = $this->_uit->saveContact($contact);
            self::fail('duplicate exception expected');
        } catch (Tinebase_Exception_Duplicate $ted) {
            try {
                $jsonResponse = json_encode($ted->toArray());
            } catch (Throwable $e) {
                // pre php 7.2
                $jsonResponse = false;
            }
            self::assertNotFalse($jsonResponse);
        }

    }

    public function testRemoveImage()
    {
        $contact = $this->testSetImage();
        $contact['jpegphoto'] = '';
        $savedContactToRemoveImage = $this->_uit->saveContact($contact);
        self::assertEquals('images/icon-set/icon_undefined_contact.svg', $savedContactToRemoveImage['jpegphoto'], 'image not removed');
    }
}
