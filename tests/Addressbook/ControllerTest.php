<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_ControllerTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Addressbook_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

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
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Container::GRANT_EDIT
        );
        
        if($personalContainer->count() === 0) {
            $container = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Addressbook', 'PHPUNIT');
        } else {
            $container = $personalContainer[0];
        }
        
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
            'bday'                  => '1975-01-02 03:04:05', // new Zend_Date???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'id'                    => 20,
            'note'                  => 'Bla Bla Bla',
            'owner'                 => $container->id,
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
            'bday'                  => '1975-01-02 03:04:05', // new Zend_Date???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'id'                    => 20,
            'note'                  => 'Bla Bla Bla',
            'owner'                 => $container->id,
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
            	
        return;
        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
	
    }
    
    /**
     * try to add a contact
     *
     */
    public function testAddContact()
    {
        $contact = Addressbook_Controller::getInstance()->addContact($this->objects['initialContact']);
        
        $this->assertEquals($this->objects['initialContact']->id, $contact->id);
        $this->assertEquals($this->objects['initialContact']->adr_one_locality, $contact->adr_one_locality);
    }
    
    /**
     * try to get a contact
     *
     */
    public function testGetContact()
    {
        $contact = Addressbook_Controller::getInstance()->getContact($this->objects['initialContact']);
        
        $this->assertEquals($this->objects['initialContact']->id, $contact->id);
        $this->assertEquals($this->objects['initialContact']->adr_one_locality, $contact->adr_one_locality);
    }
    
    /**
     * try to get count of contacts
     *
     */
    public function testGetCountByOwner()
    {
        $count = Addressbook_Controller::getInstance()->getCountByOwner(Zend_Registry::get('currentAccount'), $this->objects['initialContact']->n_family);
        
        $this->assertEquals(1, $count);
    }
    
    /**
     * try to get count of contacts
     *
     */
    public function testGetCountOfAllContacts()
    {
        $count = Addressbook_Controller::getInstance()->getCountOfAllContacts($this->objects['initialContact']->n_family);
        
        $this->assertEquals(1, $count);
    }
    
    /**
     * try to update a contact
     *
     */
    public function testUpdateContact()
    {
        $contact = Addressbook_Controller::getInstance()->updateContact($this->objects['updatedContact']);
        
        $this->assertEquals($this->objects['updatedContact']->adr_one_locality, $contact->adr_one_locality);
    }

    /**
     * try to delete a contact
     *
     */
    public function testDeleteContact()
    {
        Addressbook_Controller::getInstance()->deleteContact($this->objects['initialContact']);

        $this->setExpectedException('UnderflowException');
        
        $contact = Addressbook_Controller::getInstance()->getContact($this->objects['initialContact']);
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Addressbook_ControllerTest::main') {
    Addressbook_ControllerTest::main();
}
