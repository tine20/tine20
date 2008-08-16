<?php
/**
 * Tine 2.0 PHP HTTP Client
 * 
 * @package     tests
 * @subpackage  php_client
 * @license     New BSD License
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_ServiceTest::main');
}

class Addressbook_ServiceTest extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;
    
    /**
     * @var Tinebase_Connection
     */
    protected $_connection = NULL;
    
    /**
     * @var Addressbook_Service
     */
    protected $_service = NULL;
    
    /**
     * @var array
     */
    protected $_contactData = array(
        'n_family'              => 'Weiss',
        'n_fileas'              => 'Weiss Cornelius',
        'n_given'               => 'Cornelius',
        'org_name'              => 'Metaways Infosystems GmbH',
        'org_unit'              => 'Tine 2.0',
        'adr_one_countryname'   => 'DE',
        'adr_one_locality'      => 'Hamburg',
        'adr_one_postalcode'    => '24xxx',
        'adr_one_region'        => 'Hamburg',
        'adr_one_street'        => 'Pickhuben 4',
        'assistent'             => '',
        'bday'                  => '1979-06-05 03:04:05',
        'email'                 => 'c.weiss@metawyas.de',
        'role'                  => 'Core Developer',
        'title'                 => 'Dipl. Phys.',
    );
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Addressbook_ServiceTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public function setup()
    {
        $this->_connection = Tinebase_Connection::getInstance();
        $this->_service = new Addressbook_Service($this->_connection);
    }

    /**
     * tests remote adding of a contact
     *
     */
    public function testAddContact()
    {
        $newContact = $this->_service->addContact( new Addressbook_Model_Contact($this->_contactData, true));
        $this->assertEquals($this->_contactData['email'], $newContact->email);
        $GLOBALS['Addressbook_ServiceTest']['newContactId'] = $newContact->getId();
    }
    
    /**
     * test remote retrivial of a contact
     *
     */
    public function testGetContact()
    {
        $remoteContact = $this->_service->getContact($GLOBALS['Addressbook_ServiceTest']['newContactId']);
        $this->assertEquals($this->_contactData['email'], $remoteContact->email);
    }
    
    /**
     * test retrivial of all contacts
     *
     */
    public function testGetAllContacts()
    {
        $contacts = $this->_service->getAllContacts();
        $this->assertGreaterThan(0, count($contacts));
    }
    
    /**
     * test retrivial of remote image data
     * 
     * NOTE : useless till we have an upload test
     */
    public function testGetImage()
    {
        $image = $this->_service->getImage($GLOBALS['Addressbook_ServiceTest']['newContactId']);
    }
    
    public function testDeleteContact()
    {
        $this->_service->deleteContact($GLOBALS['Addressbook_ServiceTest']['newContactId']);
        $this->setExpectedException('Exception');
        $remoteContact = $this->_service->getContact($GLOBALS['Addressbook_ServiceTest']['newContactId']);
    }
}

if (PHPUnit_MAIN_METHOD == 'Addressbook_ServiceTest::main') {
    AllTests::main();
}