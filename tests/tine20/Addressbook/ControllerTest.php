<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected $objects = array();

    /**
     * @var Addressbook_Controller_Contact
     */
    protected $_instance = NULL;
    
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

        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        $container = $personalContainer[0];
        
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
        $contact->notes = new Tinebase_Record_RecordSet('Tinebase_Model_Note', array($this->objects['note']));
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
            'Addressbook', 
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
        $contact = $this->_instance->update($this->objects['updatedContact']);

        $this->assertEquals($this->objects['updatedContact']->adr_one_locality, $contact->adr_one_locality);
        $this->assertEquals($this->objects['updatedContact']->n_given." ".$this->objects['updatedContact']->n_family, $contact->n_fn);
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'last_modified_by', 'operator' => 'equals', 'value' => Zend_Registry::get('currentAccount')->getId())
        ));
        $count = $this->_instance->searchCount($filter);
        $this->assertTrue($count > 0);
        
        $date = Tinebase_DateTime::now();
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'last_modified_time', 'operator' => 'equals', 'value' => $date->toString('Y-m-d'))
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
        
        // Nominatim no longer returns multiple postcodes
        // TODO verify if there are still places with multiple postcodes
        $this->assertEquals(null, $updatedContact->adr_two_postalcode);
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
     * test equals operator of creation time filter
     */
    public function testCreationTimeEqualsOperator()
    {
        $contact = $this->_addContact();
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'container_id',  'operator' => 'equals',   'value' => $contact->container_id),
            array('field' => 'owner',         'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        ));
        $count1 = $this->_instance->searchCount($filter);
        
        $date = Tinebase_DateTime::now();
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'creation_time', 'operator' => 'equals',   'value' => $date->toString('Y-m-d')),
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
            'Crm',
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
}
