<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
class Addressbook_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * set geodata for contacts
     * 
     * @var boolean
     */
    protected $_geodata = FALSE;
    
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * @var Addressbook_Controller_Contact
     */
    protected $_instance = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook Controller Tests');
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
        
        $this->_instance = Addressbook_Controller_Contact::getInstance();
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
        
        if (array_key_exists('contact', $this->objects)) {
            $this->_instance->delete($this->objects['contact']);
        }
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
     *
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
}
