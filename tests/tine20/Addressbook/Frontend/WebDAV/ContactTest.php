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
     * 
     * @return Addressbook_Frontend_WebDAV_Contact
     */
    public function testCreateContact()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
                
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/sogo_connector.vcf', 'r');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $contact = Addressbook_Frontend_WebDAV_Contact::create($this->objects['initialContainer'], "$id.vcf", $vcardStream);
        
        $this->objects['contactsToDelete'][] = $contact;
        
        $record = $contact->getRecord();

        $this->assertEquals('l.kneschke@metaways.de', $record->email);
        $this->assertEquals('Kneschke', $record->n_family);
        $this->assertEquals('+49 BUSINESS', $record->tel_work);
        
        return $contact;
    }    
    
    /**
     * test get vcard
     * @depends testCreateContact
     */
    public function testGetContact()
    {
        $contact = $this->testCreateContact();
    
        $vcard = stream_get_contents($contact->get());
        
        $this->assertContains('TEL;TYPE=WORK:+49 BUSINESS', $vcard);
    }

    /**
     * test updating existing contact from sogo connector
     * @depends testCreateContact
     */
    public function testPutContactFromThunderbird()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $contact = $this->testCreateContact();
        
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/sogo_connector.vcf', 'r');
        
        $contact->put($vcardStream);
        
        $record = $contact->getRecord();
        
        $this->assertEquals('l.kneschke@metaways.de', $record->email);
        $this->assertEquals('Kneschke', $record->n_family);
        $this->assertEquals('+49 BUSINESS', $record->tel_work);
    }
    
    /**
     * test updating existing contact from MacOS X
     * @depends testCreateContact
     */
    public function testPutContactFromMacOsX()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'AddressBook/6.0 (1043) CardDAVPlugin/182 CFNetwork/520.0.13 Mac_OS_X/10.7.1 (11B26)';
        
        $contact = $this->testCreateContact();
        
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/mac_os_x_addressbook.vcf', 'r');
    
        $contact->put($vcardStream);
    
        $record = $contact->getRecord();
    
        $this->assertEquals('l.kneschke@metaways.de', $record->email);
        $this->assertEquals('Kneschke', $record->n_family);
        $this->assertEquals('+49 BUSINESS', $record->tel_work);
    }
    
    /**
     * test updating existing contact from MacOS X
     * @depends testCreateContact
     */
    public function testPutContactFromGenericClient()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        
        $contact = $this->testCreateContact();
    
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/mac_os_x_addressbook.vcf', 'r');
    
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $contact->put($vcardStream);
    }
    
    /**
     * test get name of vcard
     * @depends testCreateContact
     */
    public function testGetNameOfContact()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        
        $contact = $this->testCreateContact();
        
        $record = $contact->getRecord();
        
        $this->assertEquals($contact->getName(), $record->getId() . '.vcf');
    }
}
