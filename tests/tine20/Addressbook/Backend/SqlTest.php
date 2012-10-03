<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */


/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_Backend_SqlTest::main');
}

/**
 * Test class for Tinebase_User
 */
class Addressbook_Backend_SqlTest extends PHPUnit_Framework_TestCase
{
    /**
     * Backend
     *
     * @var Addressbook_Backend_Sql
     */
    protected $_backend;
    
    /**
     * 
     * @var Tinebase_Model_Container
     */
    protected $_container;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook SQL Backend Tests');
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
        
        $this->_backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        $this->_container = $personalContainer[0];

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
     * try to add a contact
     * 
     * @return Addressbook_Model_Contact
     */
    public function testCreateContact()
    {
        $contact = $this->_backend->create(self::getTestContact($this->_container));
        
        $this->assertTrue(!empty($contact->id));
        
        return $contact;
    }

    /**
     * try to get a contact
     * 
     * @return Addressbook_Model_Contact
     */
    public function testGetContact()
    {
        $contact = $this->testCreateContact();
       
        $updateContact = $this->_backend->get($contact->getId());
        
        $this->assertTrue($updateContact instanceof Addressbook_Model_Contact);
        $this->assertEquals($contact->getId(), $updateContact->getId());
        $this->assertEquals($contact->adr_one_locality, $updateContact->adr_one_locality);
        
        return $contact;
    }
    
    
    /**
     * try to check the id of the contact
     */
    public function testgetByUserId()
    {
        Tinebase_Core::getUser()->accountId;
         
        $contact = $this->_backend->getByUserId(Tinebase_Core::getUser()->accountId);
    
        $this->assertTrue($contact instanceof Addressbook_Model_Contact);
    
        $this->setExpectedException('Addressbook_Exception_NotFound');
        
        $this->_backend->getByUserId('invalid_id');
        
    }
     
    
    /**
     * test search results
     * 
     * @return Addressbook_Model_Contact
     */
    public function testSearchContact()
    {
        $contact = $this->testCreateContact();
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array(
                'field' => 'container_id', 
                'operator' => 'equals', 
                'value' => $contact->container_id
                    
        )));
        
        $contacts = $this->_backend->search($filter);
        
        
        $this->assertTrue(count($contacts) >= 1, 'empty search');
        $this->assertTrue( in_array($contact->getId(), $contacts->getId()) );
        
        $this->assertTrue((bool) $contact->jpegphoto, 'contact image is not detected');
        
        return $contact;
    }
    
    /**
     * test if image is in contact
     * 
     * 
     */
    public function testImage()
    {
        $contact = $this->testCreateContact();
        
        $image = $this->_backend->getImage($contact->getId());
        $tmpPath = tempnam(Tinebase_Core::getTempDir(), 'tine20_tmp_gd');
        file_put_contents($tmpPath, $image);
        
        $this->assertFileEquals(dirname(__FILE__) . '/../../Tinebase/ImageHelper/phpunit-logo.gif', $tmpPath);
        
        unset($tmpPath);
    }
    
    /**
     * try to update a contact
     * 
     *@ return Addressbook_Model_Contact
     */
    public function testUpdateContact()
    {
        $contact = $this->testCreateContact();
           
        $contact->n_family = 'Toptas';
        
        $contact = $this->_backend->update($contact);
        
        $this->assertEquals('Toptas', $contact->n_family, 'family name mismatch');
  
        
        return $contact;
    }
    
    /**
     * try to remove image
     *
     * API change 2009-04-26, image must now be queried separatly
     */
    public function testRemoveImage()
    {
        $contact = $this->testCreateContact();
        
        $contact->jpegphoto = '';
        $updatedContact = $this->_backend->update($contact);
        
        $this->assertEquals('', $updatedContact->jpegphoto);
    }
    
    /**
     * try to delete a contact and its id
     *
     */
    public function testDeleteContact()
    {
        $contact = $this->testCreateContact();
        
        $this->_backend->delete($contact->getId());
        
        $this->setExpectedException('Tinebase_Exception_NotFound');   
        $contact = $this->_backend->get($contact->getId());
    }
    
    /**
     * create test contact
     * 
     * @return Addressbook_Model_Contact
     */
    public static function getTestContact(Tinebase_Model_Container $container)
    {
        $contact = new Addressbook_Model_Contact(array(
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
            'jpegphoto'             => file_get_contents(dirname(__FILE__) . '/../../Tinebase/ImageHelper/phpunit-logo.gif'),
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

        return $contact;
    }
}        


if (PHPUnit_MAIN_METHOD == 'Addressbook_Backend_SqlTest::main') {
    Addressbook_Backend_SqlTest::main();
}
