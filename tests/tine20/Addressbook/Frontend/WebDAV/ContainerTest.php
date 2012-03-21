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

/**
 * Test class for Addressbook_Frontend_WebDAV_Container
 */
class Addressbook_Frontend_WebDAV_ContainerTest extends PHPUnit_Framework_TestCase
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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
        )));
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
    
    /**
     * test getProperties
     */
    public function testGetProperties()
    {
        $this->testCreateFile();
        
        $requestedProperties = array(
            '{http://calendarserver.org/ns/}getctag',
            '{DAV:}resource-id'
        );
        
        $container = new Addressbook_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getProperties($requestedProperties);
       
        $this->assertTrue(! empty($result['{http://calendarserver.org/ns/}getctag']));
        $this->assertEquals($result['{DAV:}resource-id'], 'urn:uuid:' . $this->objects['initialContainer']->getId());
    }
    
    /**
     * test createFile
     * 
     * @return Addressbook_Frontend_WebDAV_Contact
     */
    public function testCreateFile()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/sogo_connector.vcf', 'r');
        
        $container = new Addressbook_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $id = Tinebase_Record_Abstract::generateUID();
        
        $contact = $container->createFile("$id.vcf", $vcardStream);
        $record = $contact->getRecord();
        
        $this->assertTrue($contact instanceof Addressbook_Frontend_WebDAV_Contact);
        
        $this->assertEquals($id, $record->getId(), 'ID mismatch');
        
        return $contact;
    }    
    
    /**
     * test getChildren
     * 
     * @depends testCreateFile
     */
    public function testGetChildren()
    {
        $contact = $this->testCreateFile();
        
        $container = new Addressbook_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $children = $container->getChildren();
        
        $this->assertEquals(1, count($children));
        $this->assertTrue($children[0] instanceof Addressbook_Frontend_WebDAV_Contact);
    }    
}
