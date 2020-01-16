<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Addressbook_Controller
 */
class Addressbook_ControllerTest extends TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = [];

    /**
     * @var Addressbook_Controller_Contact
     */
    protected $_instance = null;

    protected $_oldFileSystemConfig = null;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_instance = Addressbook_Controller_Contact::getInstance();

        $this->_oldFileSystemConfig = clone Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM};

        $container = $this->_getTestContainer('Addressbook', 'Addressbook_Model_Contact');
        
        $this->objects['initialContact'] = new Addressbook_Model_Contact(array(
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
            'jpegphoto'             => file_get_contents(dirname(__FILE__) . '/../Tinebase/ImageHelper/phpunit-logo.gif'),
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $container->id,
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.mundundzähne.de',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Laars',
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
        
        $this->objects['updatedContact'] = new Addressbook_Model_Contact(array(
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
            'jpegphoto'             => '',
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $container->id,
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
                
        $this->objects['note'] = new Tinebase_Model_Note(array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',    
        ));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $this->_instance->useNotes(true);
        if ((isset($this->objects['contact']) || array_key_exists('contact', $this->objects))) {
            $this->_instance->delete($this->objects['contact']);
        }

        Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM} = $this->_oldFileSystemConfig;

        parent::tearDown();
    }
    
    /**
     * adds a contact
     *
     * @return Addressbook_Model_Contact
     */
    protected function _addContact()
    {
        $contact = $this->objects['initialContact'];
        $contact->notes = array($this->objects['note']);
        $contact = $this->_instance->create($contact);
        $this->objects['contact'] = $contact;
        
        $this->assertEquals($this->objects['initialContact']->adr_one_locality, $contact->adr_one_locality);
        
        return $contact;
    }
    
    /**
     * try to get a contact
     */
    public function testGetContact()
    {
        $contact = $this->_addContact();
        
        $this->assertEquals($this->objects['initialContact']->adr_one_locality, $contact->adr_one_locality);
    }
    
    /**
     * test getImage function
     *
     */
    public function testGetImage()
    {
        $contact = $this->_addContact();
        
        $image = Addressbook_Controller::getInstance()->getImage($contact->getId());
        $this->assertEquals('Tinebase_Model_Image', get_class($image));
        $this->assertEquals($image->width, 94);
    }
    
    /**
     * try to get count of contacts
     *
     */
    public function testGetCountByOwner()
    {
        $contact = $this->_addContact();
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'query',         'operator' => 'contains', 'value' => $contact->n_family),
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'personal'),
            array('field' => 'owner',         'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        ));
        $count = $this->_instance->searchCount($filter);
        
        $this->assertEquals(1, $count);
    }
    
    /**
     * try to get count of contacts
     *
     */
    public function testGetCountByAddressbookId()
    {
        $contact = $this->_addContact();
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            Addressbook_Model_Contact::class,
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        $container = $personalContainer[0];
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'all'),
        ));
        $filter->container = array($container->getId());
        $count = $this->_instance->searchCount($filter);
        
        $this->assertGreaterThan(0, $count);
    }
    
    /**
     * try to get count of contacts
     *
     */
    public function testGetCountOfAllContacts()
    {
        $contact = $this->_addContact();
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'query',         'operator' => 'contains', 'value' => $contact->n_family),
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'all'),
        ));
        $count = $this->_instance->searchCount($filter);
        
        $this->assertEquals(1, $count);
    }
    
    /**
     * try to update a contact
     */
    public function testUpdateContact()
    {
        $contact = $this->_addContact();
        
        $this->objects['updatedContact']->setId($contact->getId());
        $date = Tinebase_DateTime::now()->subSecond(1);
        $contact = $this->_instance->update($this->objects['updatedContact']);

        $this->assertEquals($this->objects['updatedContact']->adr_one_locality, $contact->adr_one_locality);
        $this->assertEquals($this->objects['updatedContact']->n_given." ".$this->objects['updatedContact']->n_family, $contact->n_fn);
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'last_modified_by', 'operator' => 'equals', 'value' => Zend_Registry::get('currentAccount')->getId())
        ));
        $count = $this->_instance->searchCount($filter);
        $this->assertTrue($count > 0);
        
        $date = Tinebase_DateTime::now()->subSecond(5);
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'last_modified_time', 'operator' => 'after', 'value' => $date)
        ));
        $count = $this->_instance->searchCount($filter);
        $this->assertTrue($count > 0);
    }

    /**
     * try to update a contact with missing postalcode
     * 
     * @see 0009424: missing postalcode prevents saving of contact
     */
    public function testUpdateContactWithMissingPostalcode()
    {
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::MAPPANEL, TRUE)) {
            $this->markTestSkipped('Nominatim disabled');
        }
        
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(true);
        $contact = $this->_addContact();
        $contact->adr_two_street = null;
        $contact->adr_two_postalcode = null;
        $contact->adr_two_locality = 'Münster';
        $contact->adr_two_region = 'Nordrhein-Westfalen';
        
        $updatedContact = $this->_instance->update($contact);
        $this->assertTrue(48143 == $updatedContact->adr_two_postalcode || is_null($updatedContact->adr_two_postalcode));
    }
    
    /**
     * test remove image
     */
    public function testRemoveContactImage()
    {
        $contact = $this->_addContact();
        
        $contact->jpegphoto = '';
        $contact = $this->_instance->update($contact);
        
        $this->setExpectedException('Addressbook_Exception_NotFound');
        $image = Addressbook_Controller::getInstance()->getImage($contact->id);
    }
    
    /**
     * try to delete a contact
     *
     */
    public function testDeleteContact()
    {
        $contact = $this->_addContact();
        
        $this->_instance->delete($contact->getId());
        unset($this->objects['contact']);

        $this->setExpectedException('Tinebase_Exception_NotFound');
        $contact = $this->_instance->get($contact->getId());
    }

    /**
     * try to delete a contact
     *
     */
    public function testDeleteUserAccountContact()
    {
        $this->setExpectedException('Addressbook_Exception_AccessDenied');
        $userContact = $this->_instance->getContactByUserId(Tinebase_Core::getUser()->getId());
        $this->_instance->delete($userContact->getId());
    }
    
    /**
     * try to create a personal folder 
     *
     */
    public function testCreatePersonalFolder()
    {
        $account = Zend_Registry::get('currentAccount');
        $folder = Addressbook_Controller::getInstance()->createPersonalFolder($account);
        $this->assertEquals(1, count($folder));
        $folder = Addressbook_Controller::getInstance()->createPersonalFolder($account->getId());
        $this->assertEquals(1, count($folder));
    }
    
    /**
     * test in week operator of creation time filter
     *
     * TODO this fails around Sunday -> Monday midnight as inweek filter uses user tz, but creation_time contains utc
     */
    public function testCreationTimeWeekOperator()
    {
        $contact = $this->_addContact();
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'container_id',  'operator' => 'equals',   'value' => $contact->container_id),
            array('field' => 'owner',         'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        ));
        $count1 = $this->_instance->searchCount($filter);
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'creation_time', 'operator' => 'inweek',   'value' => 0),
            array('field' => 'container_id',  'operator' => 'equals',   'value' => $contact->container_id),
            array('field' => 'owner',         'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        ));
        $count2 = $this->_instance->searchCount($filter);
        $this->assertEquals($count1, $count2);
    }
    
    /**
     * test useNotes
     */
    public function testUseNotes()
    {
        $contact = $this->objects['initialContact'];
        $contact1 = clone $contact;
    
        $contact1->notes = array(new Tinebase_Record_RecordSet('Tinebase_Model_Note', array($this->objects['note'])));
        $contact->notes = array(new Tinebase_Record_RecordSet('Tinebase_Model_Note', array($this->objects['note'])));
    
        $newcontact1 = $this->_instance->create($contact1);
        $this->_instance->delete($newcontact1);
    
        $this->_instance->useNotes(false);
        $this->objects['contact'] = $this->_instance->create($contact);
    
        $compStr = 'Array
(
    [0] => Array
        (
            [note_type_id] => 1
            [note] => phpunit test note
            [record_backend] => Sql
            [id] => 
        )

)';
        
        $this->assertTrue($newcontact1->has('notes'));
        $this->assertEquals($compStr, $newcontact1->notes[0]->note);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->objects['contact']->notes[0]->note = 'note';
    }

    /**
     * @see 0012744: allow to configure when user contacts are hidden
     */
    public function testContactHiddenFilter()
    {
        $user = Tinebase_Core::getUser();

        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_fileas',      'operator' => 'equals', 'value' => $user->accountDisplayName),
            array('field' => 'showDisabled',  'operator' => 'equals', 'value' => 0),
        ));

        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(1, $count, 'contact should be found');

        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_DISABLED);

        // test case: disabled
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(0, $count, 'disabled contact should not be found');

        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_ENABLED);
        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED);
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(1, $count, 'expired contact should be found');

        // test case: expired
        Addressbook_Config::getInstance()->set(Addressbook_Config::CONTACT_HIDDEN_CRITERIA, 'expired');
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(0, $count, 'expired contact should not be found');

        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_ENABLED);
        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_DISABLED);
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(1, $count, 'disabled contact be found');

        // test case: never
        Addressbook_Config::getInstance()->set(Addressbook_Config::CONTACT_HIDDEN_CRITERIA, 'never');
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(1, $count);

        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED);
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(1, $count);
    }

    public function testCustomFieldRelationLoop()
    {
        $contact = $this->_addContact();

        $cField1 = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => 'Addressbook_Model_Contact',
            'definition'        => [
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'record',
                'recordConfig' => ['value' => ['records' => 'Tine.Crm.Model.Lead']],
                'uiconfig' => [
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                ]
            ]
        ]));

        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'),
            Crm_Model_Lead::class,
            Zend_Registry::get('currentAccount'),
            Tinebase_Model_Grants::GRANT_EDIT
        );
        if($personalContainer->count() === 0) {
            $personalContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $personalContainer = $personalContainer[0];
        }

        $lead = Crm_Controller_Lead::getInstance()->create(new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'  => $personalContainer->id,
            'start'         => Tinebase_DateTime::now(),
            'description'   => 'Description',
            'end'           => Tinebase_DateTime::now(),
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => Tinebase_DateTime::now(),
            'relations'     => [[
                'related_model' => Addressbook_Model_Contact::class,
                'related_backend' => 'sql',
                'related_id' => $contact->getId(),
                'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                'type' => 'y'
            ]]
        )));

        // this lead to an infinite loop before it was fixed
        $contact->customfields = [
            $cField1->name => $lead->getId()
        ];
        $contact->relations = null;
        $contact = $this->_instance->update($contact);
        static::assertEquals(1, count($contact->customfields));
    }

    /**
     * 0013014: Allow to manage resources in addressbook module
     */
    public function testEnableResourcesFeature()
    {
        $enabledFeatures = Addressbook_Config::getInstance()->get(Addressbook_Config::ENABLED_FEATURES);
        $enabledFeatures[Addressbook_Config::FEATURE_LIST_VIEW] = true;

        Addressbook_Config::getInstance()->set(Addressbook_Config::ENABLED_FEATURES, $enabledFeatures);

        $this->assertTrue(Addressbook_Config::getInstance()->featureEnabled(Addressbook_Config::FEATURE_LIST_VIEW));
    }

    public function testModLogUndo()
    {
        // activate ModLog in FileSystem!
        Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
            ->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE} = true;
        $filesystem = Tinebase_FileSystem::getInstance();
        $filesystem->resetBackends();
        Tinebase_Core::clearAppInstanceCache();

        $cField1 = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => 'Addressbook_Model_Contact',
            'definition'        => array(
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'string',
                'uiconfig' => array(
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                )
            )
        )));
        $cField2 = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => 'Addressbook_Model_Contact',
            'definition'        => array(
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'string',
                'uiconfig' => array(
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                )
            )
        )));
        $user = Tinebase_Core::getUser();
        /** @var Addressbook_Model_Contact $contact */
        $contact = $this->objects['initialContact'];

        // create contact with notes, relations, tags, attachments, customfield
        $contact->notes = array($this->objects['note']);
        $contact->relations = array(array(
            'related_id'        => $user->contact_id,
            'related_model'     => 'Addressbook_Model_Contact',
            'related_degree'    => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'type'              => 'foo'
        ));
        $contact->tags = array(array('name' => 'testtag1'));
        $this->_addRecordAttachment($contact);
        $contact->customfields = array(
            $cField1->name => 'test field1'
        );

        $createdContact = $this->_instance->create($contact);

        // update contact, add more notes, relations, tags, attachments, customfields
        /** @var Addressbook_Model_Contact $updateContact */
        $updateContact = $this->objects['updatedContact'];
        $updateContact->setId($createdContact->getId());
        $notes = $createdContact->notes->toArray();
        $notes[] = array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note 2',
        );
        $updateContact->notes = $notes;
        $relations = $createdContact->relations->toArray();
        $relations[] = array(
            'related_id'        => $user->contact_id,
            'related_model'     => 'Addressbook_Model_Contact',
            'related_degree'    => Tinebase_Model_Relation::DEGREE_CHILD,
            'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'type'              => 'bar'
        );
        $updateContact->relations = $relations;
        $updateContact->tags = clone $createdContact->tags;
        $updateContact->tags->addRecord(new Tinebase_Model_Tag(array('name' => 'testtag2'), true));
        $updateContact->attachments = clone $createdContact->attachments;
        $path = Tinebase_TempFile::getTempPath();
        file_put_contents($path, 'moreTestAttachementData');
        $updateContact->attachments->addRecord(new Tinebase_Model_Tree_Node(array(
                'name'      => 'moreTestAttachementData.txt',
                'tempFile'  => Tinebase_TempFile::getInstance()->createTempFile($path)
        ), true));
        $updateContact->xprops('customfields')[$cField2->name] = 'test field2';

        $contact = $this->_instance->update($updateContact);

        // delete it
        $this->_instance->delete($contact->getId());

        $oldSequence = $contact->seq;
        $contact->seq = 0;
        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getModificationsBySeq(
            Tinebase_Application::getInstance()->getApplicationById('Addressbook')->getId(), $contact, 10000);

        // undelete it
        $oldContentSequence = Tinebase_Container::getInstance()->getContentSequence($contact->container_id);
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undeletedContact = $this->_instance->get($contact->getId());
        static::assertEquals(2, $undeletedContact->notes->count());
        static::assertEquals(2, $undeletedContact->relations->count());
        static::assertEquals(2, $undeletedContact->tags->count());
        static::assertEquals(2, $undeletedContact->attachments->count());
        static::assertEquals(2, count($undeletedContact->customfields));
        static::assertGreaterThan($oldSequence, $undeletedContact->seq);
        $undeletedContentSequence = Tinebase_Container::getInstance()->getContentSequence($contact->container_id);
        static::assertGreaterThan($oldContentSequence, $undeletedContentSequence);

        // undo update
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undidContact = $this->_instance->get($contact->getId());
        static::assertEquals(1, $undidContact->notes->count());
        static::assertEquals(1, $undidContact->relations->count());
        static::assertEquals(1, $undidContact->tags->count());
        static::assertEquals(1, $undidContact->attachments->count());
        static::assertEquals(1, count($undidContact->customfields));
        static::assertGreaterThan($undeletedContact->seq, $undidContact->seq);
        $undidContentSequence = Tinebase_Container::getInstance()->getContentSequence($contact->container_id);
        static::assertGreaterThan($undeletedContentSequence, $undidContentSequence);

        // undo create
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        try {
            $this->_instance->get($contact->getId());
            static::fail('undo create did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}
        $uncreateContentSequence = Tinebase_Container::getInstance()->getContentSequence($contact->container_id);
        static::assertGreaterThan($undidContentSequence, $uncreateContentSequence);
    }

    public function testUpdateInternalContactHiddenListMembership()
    {
        $defaultGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $defaultGroup->visibility = Tinebase_Model_Group::VISIBILITY_HIDDEN;
        Tinebase_Group::getInstance()->updateGroup($defaultGroup);

        $adminContact = $this->_instance->get(Tinebase_Core::getUser()->contact_id);

        $adminContact->tel_car = '0132451566';

        $result = $this->_instance->update($adminContact);

        static::assertEquals($adminContact->tel_car, $result->tel_car);
    }

    public function testContactModelPerformance()
    {
        self::markTestSkipped('this test has no assertions - just for performance measurement');

        $container = $this->_getTestContainer('Addressbook', 'Addressbook_Model_Contact');

        $memory = memory_get_usage();
        $timeStarted = microtime(true);
        $recordData = array(
            'adr_one_countryname' => 'DE',
            'adr_one_locality' => 'Hamburg',
            'adr_one_postalcode' => '24xxx',
            'adr_one_region' => 'Hamburg',
            'adr_one_street' => 'Pickhuben 4',
            'adr_one_street2' => 'no second street',
            'adr_two_countryname' => 'DE',
            'adr_two_locality' => 'Hamburg',
            'adr_two_postalcode' => '24xxx',
            'adr_two_region' => 'Hamburg',
            'adr_two_street' => 'Pickhuben 4',
            'adr_two_street2' => 'no second street2',
            'assistent' => 'Cornelius Weiß',
            'bday' => '1975-01-02 03:04:05', // new Tinebase_DateTime???
            'email' => 'unittests@tine20.org',
            'email_home' => 'unittests@tine20.org',
            'note' => 'Bla Bla Bla',
            'container_id' => $container->id,
            'role' => 'Role',
            'title' => 'Title',
            'url' => 'http://www.tine20.org',
            'url_home' => 'http://www.mundundzähne.de',
            'n_family' => 'Kneschke',
            'n_fileas' => 'Kneschke, Lars',
            'n_given' => 'Laars',
            'n_middle' => 'no middle name',
            'n_prefix' => 'no prefix',
            'n_suffix' => 'no suffix',
            'org_name' => 'Metaways Infosystems GmbH',
            'org_unit' => 'Tine 2.0',
            'tel_assistent' => '+49TELASSISTENT',
            'tel_car' => '+49TELCAR',
            'tel_cell' => '+49TELCELL',
            'tel_cell_private' => '+49TELCELLPRIVATE',
            'tel_fax' => '+49TELFAX',
            'tel_fax_home' => '+49TELFAXHOME',
            'tel_home' => '+49TELHOME',
            'tel_pager' => '+49TELPAGER',
            'tel_work' => '+49TELWORK',
        );

        for($i =0; $i < 100; ++$i) {
            $contact = new Addressbook_Model_Contact(null, true);
            $data = $recordData;
            unset($data['tel_work']);
            $contact->hydrateFromBackend($data);
        }

        $timeEnd = microtime(true);
        $memoryEnd = memory_get_usage();

        echo PHP_EOL . 'time: ' . (($timeEnd - $timeStarted) * 1000) . 'ms, memory: ' . ($memoryEnd - $memory) .
            PHP_EOL;
    }

    public function testAccountEmailUpdate2ContactUpdate2EmailListSieveUpdate()
    {
        if (empty(Tinebase_Config::getInstance()->{Tinebase_Config::IMAP})) {
            self::markTestSkipped('no imap config found');
        }

        Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS} = true;

        $pwd = Tinebase_Record_Abstract::generateUID();

        $newUser = Admin_Controller_User::getInstance()->create(new Tinebase_Model_FullUser([
            'accountLoginName'      => Tinebase_Record_Abstract::generateUID(),
            'accountDisplayName'    => Tinebase_Record_Abstract::generateUID(),
            'accountLastName'       => Tinebase_Record_Abstract::generateUID(),
            'accountFullName'       => Tinebase_Record_Abstract::generateUID(),
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
        ]), $pwd, $pwd, true);

        $newContact = $this->_instance->get($newUser->contact_id);

        $list = $this->_createMailinglist();
        Addressbook_Controller_List::getInstance()->addListMember($list->getId(), [$newContact->getId()]);

        $newUser->accountEmailAddress = $newUser->accountLoginName . '@' . TestServer::getPrimaryMailDomain();
        $newUser = Admin_Controller_User::getInstance()->update($newUser);

        $updatedContact = $this->_instance->get($newUser->contact_id);
        static::assertSame($newUser->accountLoginName . '@' . TestServer::getPrimaryMailDomain(),
            $newUser->accountEmailAddress);
        static::assertSame($newUser->accountEmailAddress, $updatedContact->email);

        /** TODO add sieve asserts! */
    }
}
