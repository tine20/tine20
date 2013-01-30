<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 *
 * @todo        add testSetImage (NOTE: we can't test the upload yet, so we needd to simulate the upload)
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Addressbook_Frontend_Json
 */
class Addressbook_JsonTest extends PHPUnit_Framework_TestCase
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
    protected $_instance;

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
        $this->_geodata = Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(false);
        
        // always resolve customfields
        Addressbook_Controller_Contact::getInstance()->resolveCustomfields(TRUE);
        
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
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts($this->_geodata);
        
        $this->_instance->deleteContacts($this->_contactIdsToDelete);

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
            Tinebase_Tags::getInstance()->deleteTags($this->objects['createdTagIds']);
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
                ]';
        $contacts = $this->_instance->searchContacts(Zend_Json::decode($filter), NULL);
        $this->assertGreaterThan(0, $contacts['totalcount']);
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
    
        $contacts = $this->_instance->searchContacts($filter, $paging);
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
        $newContact = $this->_instance->saveContact($newContactData, $_forceCreation);
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
            array('name' => 'customfield_' . $createdCustomField->name, 'value' => 'PHPUNIT_multipleUpdate' )
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
        $searchResult = $this->_instance->searchContacts($sFilter,$this->objects['paging']);

        // look if all 3 contacts are found again by default field, and check if default field got properly updated
        $this->assertEquals(3, $searchResult['totalcount'],'Could not find the correct number of records by adr_one_region');

        $record = array_pop($searchResult['results']);

        // check if customfieldvalue was updated properly
        $this->assertTrue(isset($record['customfields']), 'No customfields in record');
        $this->assertEquals($record['customfields'][$createdCustomField->name],'PHPUNIT_multipleUpdate','Customfield was not updated as expected');

        // check if other default field value was updated properly
        $this->assertEquals($record['url'],'http://www.phpunit.de','DefaultField "url" was not updated as expected');
        
        // check 'changed' systemnote
        $this->_checkChangedNote($record['id'], 'adr_one_region ( -> PHPUNIT_multipleUpdate) url ( -> http://www.phpunit.de) customfields ( -> {');
        
        // check invalid data
        
        $changes = array(
            array('name' => 'n_family', 'value' => ''),
            array('name' => 'n_given',  'value' => ''),
            array('name' => 'org_name', 'value' => '')
        );
        $result = $json->updateMultipleRecords('Addressbook', 'Contact', $changes, $filter);
        
        $this->assertEquals($result['failcount'], 3, 'failcount does not show the correct number');
        $this->assertEquals($result['totalcount'], 0, 'totalcount does not show the correct number');
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
        $result = $this->_instance->saveContact($contact);
        
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
            'field' => 'record_id', 'operator' => 'equals', 'value' => $_recordId
        )), array('sort' => array('note_type_id', 'creation_time')));
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
        
        $result = $this->_instance->saveContact($contact);
        
        $this->assertEquals($tagName, $result['tags'][0]['name']);
        $this->_checkChangedNote($result['id'], array(
            '[] -> {"model":"Tinebase_Model_Tag","added":[{"id":"',
            '"type":"personal"',
            ',"name":"' . $tagName . '","description":"testModlog","color":"#009B31"'
        ));
        
        return $result;
    }

    /**
    * test attach multiple tags modlog
    */
    public function testAttachMultipleTagsModlog()
    {
        $contact = $this->_addContact();
        $filter = new Addressbook_Model_ContactFilter(array(array(
            'field'    => 'id',
            'operator' => 'equals',
            'value'    =>  $contact['id']
        )));
        $sharedTagName = $this->_createAndAttachTag($filter);
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
        $result = $this->_instance->saveContact($contact);
        $this->_checkChangedNote($result['id'], array('-> {"model":"Tinebase_Model_Tag","added":[],"removed":[{"id":"'), 4);
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
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(true);
        
        $contact = $this->_addContact();

        $contact['n_family'] = 'PHPUNIT UPDATE';
        $contact['adr_one_locality'] = 'Hamburg';
        $contact['adr_one_street'] = 'Pickhuben 2';
        $updatedContact = $this->_instance->saveContact($contact);

        $this->assertEquals($contact['id'], $updatedContact['id'], 'updated produced a new contact');
        $this->assertEquals('PHPUNIT UPDATE', $updatedContact['n_family'], 'updating data failed');

        if (Tinebase_Config::getInstance()->get(Tinebase_Config::MAPPANEL, TRUE)) {
            // check geo data
            $this->assertEquals('9.99489300545466', $updatedContact['adr_one_lon'], 'wrong geodata (lon)');
            $this->assertEquals('53.5444258235736', $updatedContact['adr_one_lat'], 'wrong geodata (lat)');

            // try another address
            $updatedContact['adr_one_locality']    = 'Wien';
            $updatedContact['adr_one_street']      = 'Blindengasse 52';
            $updatedContact['adr_one_postalcode']  = '1095';
            $updatedContact['adr_one_countryname'] = '';
            $updatedContact = $this->_instance->saveContact($updatedContact);

            // check geo data
            $this->assertEquals('16.3419589',   $updatedContact['adr_one_lon'], 'wrong geodata (lon)');
            $this->assertEquals('48.2147964',   $updatedContact['adr_one_lat'], 'wrong geodata (lat)');
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

        $this->_instance->deleteContacts($contact['id']);

        $this->setExpectedException('Tinebase_Exception_NotFound');
        $contact = $this->_instance->getContact($contact['id']);
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
     * get tag
     * 
     * @param string $tagType
     * @param string $tagName
     * @return Tinebase_Model_Tag
     */
    protected function _getTag($tagType = Tinebase_Model_Tag::TYPE_SHARED, $tagName = NULL)
    {
        if ($tagName) {
            try {
                $tag = Tinebase_Tags::getInstance()->getTagByName($tagName);
                return $tag;
            } catch (Tinebase_Exception_NotFound $tenf) {
            }
        } else {
            $tagName = Tinebase_Record_Abstract::generateUID();
        }
        
        return new Tinebase_Model_Tag(array(
            'type'          => $tagType,
            'name'          => $tagName,
            'description'   => 'testTagDescription',
            'color'         => '#009B31',
        ));
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
     * test import
     * 
     * @see 0006226: Data truncated for column 'adr_two_lon'
     */
    public function testImport()
    {
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
        $fritz['last_modified_time'] = $lastModified->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE))->toString();
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
        $result = $this->_instance->importContacts($tempFile->getId(), $definition->getId(), $options, $clientRecords);
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
        $fritz = $this->_instance->saveContact($fritz);
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
        $klaus = $this->_tagImportHelper('discard');
        $this->assertEquals(2, count($klaus['tags']), 'klaus should have both tags: ' . print_r($klaus['tags'], TRUE));
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
        
        $klaus = $this->_instance->getContact($klausId);
        
        return $klaus;
    }

    /**
     * testImportKeepBothWithTag
     * 
     * @see 0006628: tag handling on duplicate resolve actions in import fails
     */
    public function testImportKeepBothWithTag()
    {
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
        
        $contact = $this->_instance->saveContact($this->_getContactData());
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
                'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
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
                'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
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
        $result = $this->_instance->searchContacts($filter, array());

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
        $result = $this->_instance->searchContacts($filter, array());
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
        $result = $this->_instance->searchContacts($filter, array());

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
        $result = $this->_instance->searchContacts($filter, array());

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
            $newContact = $this->_instance->saveContact($contact, TRUE);
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
        $contact = $this->_instance->saveContact($contact);
        $this->_contactIdsToDelete[] = $contact['id'];
        try {
            $contact2 = $this->_getContactData();
            $contact2['email'] = 'test@example.org';
            $contact2 = $this->_instance->saveContact($contact2);
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
        $registryData = $this->_instance->getRegistryData();

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
        $allContacts = $this->_instance->searchContacts(array(), array());

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
        $allContactsWithoutTheTag = $this->_instance->searchContacts($filter, array());

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
        $allContactsWithTheTag = $this->_instance->searchContacts($filter, array());
        $this->assertEquals(1, $allContactsWithTheTag['totalcount']);

        $filter = array(array(
            'field'    => 'tag',
            'operator' => 'in',
            'value'    => array()
        ));
        $emptyResult = $this->_instance->searchContacts($filter, array());
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
        
        $result = $this->_instance->parseAddressData($addressString);
        
        $this->assertTrue(array_key_exists('contact', $result));
        $this->assertTrue(is_array($result['contact']));
        $this->assertTrue(array_key_exists('unrecognizedTokens', $result));
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
        
        $result = $this->_instance->parseAddressData($addressString);
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
        $result = $this->_instance->searchContacts($filter, array());
        $this->assertEquals(0, $result['totalcount']);
        
        $filter[] = array('field' => 'showDisabled', 'operator' => 'equals',   'value' => TRUE);
        $result = $this->_instance->searchContacts($filter, array());
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
        $hiddenGroup = Admin_Controller_Group::getInstance()->create($hiddenGroup);
        $this->_groupIdsToDelete = array($hiddenGroup->getId());
        
        $filter = array(array(
            'field'    => 'name',
            'operator' => 'equals',
            'value'    => 'hiddengroup'
        ));
        $result = $this->_instance->searchLists($filter, array());
        $this->assertEquals(0, $result['totalcount'], 'should not find hidden list: ' . print_r($result, TRUE));
    }
}
