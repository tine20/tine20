<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2041 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Addressbook_Frontend_CardDAV
 */
class Addressbook_Frontend_CardDAVTest extends TestCase
{
    /**
     * test getChildren
     */
    public function testGetChildren()
    {
        $collection = new Addressbook_Frontend_WebDAV(\Sabre\CardDAV\Plugin::ADDRESSBOOK_ROOT, true);
        
        $children = $collection->getChildren();
        
        $this->assertTrue($children[0] instanceof Addressbook_Frontend_WebDAV);
    }
        
    /**
     * test getChild
     */
    public function testGetChild()
    {
        $collection = new Addressbook_Frontend_WebDAV(\Sabre\CardDAV\Plugin::ADDRESSBOOK_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $child = $collection->getChild($this->_getTestContainer('Addressbook')->getId());
        
        $this->assertTrue($child instanceof Addressbook_Frontend_WebDAV_Container);
    }
    
    /**
     * test to a create file. this should not be possible at this level
     */
    public function testCreateFile()
    {
        $collection = new Addressbook_Frontend_WebDAV(\Sabre\CardDAV\Plugin::ADDRESSBOOK_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $collection->createFile('foobar');
    }
    
    /**
     * test to create a new directory
     */
    public function testCreateDirectory()
    {
        $randomName = Tinebase_Record_Abstract::generateUID();
        
        $collection = new Addressbook_Frontend_WebDAV(\Sabre\CardDAV\Plugin::ADDRESSBOOK_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $collection->createDirectory($randomName);
        
        $container = Tinebase_Container::getInstance()->getContainerByName('Addressbook', $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        
        $this->assertTrue($container instanceof Tinebase_Model_Container);
    }
    
    public function testGetAllContactsMetaContainer()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mac OS X/10.9 (13A603) AddressBook/1365';
    
        $collection = new Addressbook_Frontend_WebDAV(\Sabre\CardDAV\Plugin::ADDRESSBOOK_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
    
        $children = $collection->getChildren();
        
        $this->assertCount(1, $children, 'there should be just one global container');
        $this->assertTrue($children[0] instanceof Addressbook_Frontend_CardDAV_AllContacts, 'wrong instance');
    }
    
    public function testGetAllContainers()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
    
        $collection = new Addressbook_Frontend_WebDAV(\Sabre\CardDAV\Plugin::ADDRESSBOOK_ROOT, true);
    
        $children = $collection->getChildren();
    
        $this->assertGreaterThanOrEqual(2, count($children), 'there should be more than one container');
    }
}
