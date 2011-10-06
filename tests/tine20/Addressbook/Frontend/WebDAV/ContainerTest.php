<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_Controller_Frontend_WebDAV_ContainerTest::main');
}

/**
 * Test class for Addressbook_Controller_Frontend_WebDAV_Container
 */
class Addressbook_Controller_Frontend_WebDAV_ContainerTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook WebDAV Container Tests');
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
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
        )));
        
        $this->objects['containerToDelete'][] = $this->objects['initialContainer'];
        
        return;
        
        $GLOBALS['Addressbook_Controller_ListTest'] = array_key_exists('Addressbook_Controller_ListTest', $GLOBALS) ? $GLOBALS['Addressbook_ListControllerTest'] : array();
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        $container = $personalContainer[0];

        $this->objects['contact1'] = new Addressbook_Model_Contact(array(
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
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $container->getId(),
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Contact1',
            'n_fileas'              => 'Contact1, List',
            'n_given'               => 'List',
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
        $this->objects['contact1'] = Addressbook_Controller_Contact::getInstance()->create($this->objects['contact1']);
        
        $this->objects['contact2'] = new Addressbook_Model_Contact(array(
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
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $container->getId(),
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Contact2',
            'n_fileas'              => 'Contact2, List',
            'n_given'               => 'List',
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
        $this->objects['contact2'] = Addressbook_Controller_Contact::getInstance()->create($this->objects['contact2']);
        
        $this->objects['initialList'] = new Addressbook_Model_List(array(
            'name'	=> 'initial list',
            'container_id' => $container->getId(),
            'members' => array($this->objects['contact1'], $this->objects['contact2'])
        )); 
        
        #$this->objects['updatedList'] = new Addressbook_Model_List(array(
        #    'name'	=> 'updated list',
        #    'container_id' => $container->getId(),
        #    'members' => array($this->objects['contact2'], $this->objects['contact2'])
        #)); 
            	
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->objects['containerToDelete'] as $containerId) {
            $containerId = $containerId instanceof Tinebase_Model_Container ? $containerId->getId() : $containerId;
            
            try {
                Tinebase_Container::getInstance()->deleteContainer($containerId);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }
        
        return;
        
        Addressbook_Controller_Contact::getInstance()->delete(array(
            $this->objects['contact1']->getId(),
            $this->objects['contact2']->getId()
        ));
    }
    
    /**
     * assert that name of folder is container name
     */
    public function testGetName()
    {
        $container = new Addressbook_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getName();
        
        $this->assertEquals($this->objects['initialContainer']->name, $result);
    }
    
    /**
     * assert that name of folder is container name
     */
    public function testGetIdAsName()
    {
        $container = new Addressbook_Frontend_WebDAV_Container($this->objects['initialContainer'], true);
        
        $result = $container->getName();
        
        $this->assertEquals($this->objects['initialContainer']->getId(), $result);
    }
    
    public function testGetChildren()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/contacts.vcf', 'r');
        
        $contact = Addressbook_Frontend_WebDAV_Contact::create($this->objects['initialContainer'], $vcardStream);
    }
    /**
     * test getProperties
     */
    public function testGetProperties()
    {
        $requestedProperties = array(
        	'{http://calendarserver.org/ns/}getctag',
            '{DAV:}resource-id'
        );
        
        $container = new Addressbook_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getProperties($requestedProperties);
       
        $this->assertNotEmpty($result['{http://calendarserver.org/ns/}getctag']);
        $this->assertEquals($result['{DAV:}resource-id'], 'urn:uuid:' . $this->objects['initialContainer']->getId());
    }
}
