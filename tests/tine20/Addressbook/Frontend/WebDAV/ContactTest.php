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
    define('PHPUnit_MAIN_METHOD', 'Addressbook_Frontend_WebDAV_ContactTest::main');
}

/**
 * Test class for Addressbook_Frontend_WebDAV_Contact
 */
class Addressbook_Frontend_WebDAV_ContactTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook WebDAV Contact Tests');
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
        
        $this->objects['contactsToDelete'] = array();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->objects['contactsToDelete'] as $contact) {
            $contact->delete();
        }
        
        foreach ($this->objects['containerToDelete'] as $containerId) {
            $containerId = $containerId instanceof Tinebase_Model_Container ? $containerId->getId() : $containerId;
            
            try {
                Tinebase_Container::getInstance()->deleteContainer($containerId);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }
    }
    
    /**
     * test create contact
     */
    public function testCreate()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/sogo_connector.vcf', 'r');
        
        $contact = Addressbook_Frontend_WebDAV_Contact::create($this->objects['initialContainer'], $vcardStream);
        
        $this->objects['contactsToDelete'][] = $contact;
        
        $record = $contact->getContact();

        $this->assertEquals('l.kneschke@metaways.de', $record->email);
        $this->assertEquals('Kneschke', $record->n_family);
        $this->assertEquals('+49 BUSINESS', $record->tel_work);
    }    
    
    /**
     * test get vcard
     */
    public function testGet()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/sogo_connector.vcf', 'r');
    
        $contact = Addressbook_Frontend_WebDAV_Contact::create($this->objects['initialContainer'], $vcardStream);
    
        $this->objects['contactsToDelete'][] = $contact;
    
        $vcard = stream_get_contents($contact->get());
        
        $this->assertContains('TEL;TYPE=WORK:+49 BUSINESS', $vcard);
    }

    /**
     * test updating existing contact
     */
    public function testPut()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/sogo_connector.vcf', 'r');
        
        $contact = Addressbook_Frontend_WebDAV_Contact::create($this->objects['initialContainer'], $vcardStream);
        
        $this->objects['contactsToDelete'][] = $contact;
        
        rewind($vcardStream);
        $contact->put($vcardStream);
        
        $record = $contact->getContact();
        
        $this->assertEquals('l.kneschke@metaways.de', $record->email);
        $this->assertEquals('Kneschke', $record->n_family);
        $this->assertEquals('+49 BUSINESS', $record->tel_work);
    }
    
    /**
     * test get name of vcard
     */
    public function testGetName()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/sogo_connector.vcf', 'r');
        
        $contact = Addressbook_Frontend_WebDAV_Contact::create($this->objects['initialContainer'], $vcardStream);
        
        $this->objects['contactsToDelete'][] = $contact;
        
        $record = $contact->getContact();
        
        $this->assertEquals($contact->getName(), $record->getId() . '.vcf');
    }
}
