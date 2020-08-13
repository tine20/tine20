<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Addressbook_Frontend_ActiveSync
 * 
 * @package     Addressbook
 */
class Addressbook_Frontend_ActiveSyncTest extends ActiveSync_Controller_ControllerTest
{
    /**
     * name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Addressbook';
    
    protected $_controllerName = 'Addressbook_Frontend_ActiveSync';
    
    protected $_class = Syncroton_Data_Factory::CLASS_CONTACTS;
    
    protected $_testXMLInput = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<Sync xmlns="uri:AirSync" xmlns:Contacts="uri:Contacts">
    <Collections>
        <Collection>
            <Class>Contacts</Class>
            <SyncKey>1</SyncKey>
            <CollectionId>addressbook-root</CollectionId>
            <DeletesAsMoves/>
            <GetChanges/>
            <WindowSize>50</WindowSize>
            <Options>
                <FilterType>0</FilterType>
                <Truncation>2</Truncation>
                <Conflict>0</Conflict>
            </Options>
            <Commands>
                <Add>
                    <ClientId>1</ClientId>
                    <ApplicationData>
                        <Contacts:FileAs>ads2f, asdfadsf</Contacts:FileAs>
                        <Contacts:FirstName>asdf</Contacts:FirstName>
                        <Contacts:LastName>asdfasdfaasd</Contacts:LastName>
                        <Contacts:Birthday>2000-12-25T23:00:00.000Z</Contacts:Birthday>
                        <Contacts:WebPage>fb://some.dumb.fb.url</Contacts:WebPage>
                        <Contacts:MobilePhoneNumber>+4312341234124</Contacts:MobilePhoneNumber>
                        <Contacts:homePhoneNumber>+43123412341241212234432223452352352345234554</Contacts:homePhoneNumber>
                        <Contacts:BusinessAddressStreet>Pickhuben 2</Contacts:BusinessAddressStreet>
                        <Body xmlns="uri:AirSyncBase"><Type>1</Type><Data>Hello</Data></Body>
                        <Contacts:Email1Address>l.kneschke@example.com</Contacts:Email1Address>
                        <Contacts:Email2Address>Cornelius Weiß &lt;c.weiss@example.de&gt;</Contacts:Email2Address>
                        <Contacts:Categories>
                            <Contacts:Category>1234</Contacts:Category>
                            <Contacts:Category>5678</Contacts:Category>
                        </Contacts:Categories>
                    </ApplicationData>
                </Add>
            </Commands>
        </Collection>
    </Collections>
</Sync>';
    
    protected $_exampleXMLExisting = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<Sync xmlns="uri:AirSync" xmlns:Contacts="uri:Contacts"><Collections><Collection><Class>Contacts</Class><SyncKey>1</SyncKey><CollectionId>addressbook-root</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>50</WindowSize><Options><FilterType>0</FilterType><Truncation>2</Truncation><Conflict>0</Conflict></Options><Commands><Add><ClientId>1</ClientId><ApplicationData><Contacts:FileAs>Kneschke, Lars</Contacts:FileAs><Contacts:FirstName>Lars</Contacts:FirstName><Contacts:LastName>Kneschke</Contacts:LastName></ApplicationData></Add></Commands></Collection></Collections></Sync>';
    
    protected $_xmlContactBirthdayWithoutTimeAndroid = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Contacts="uri:Contacts"><Collections><Collection><Class>Contacts</Class><SyncKey>3</SyncKey><CollectionId>addressbook-root</CollectionId><DeletesAsMoves/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options><Commands><Add><ClientId>4600</ClientId><ApplicationData><Contacts:FileAs>Fritzchen</Contacts:FileAs><Contacts:FirstName>Fritzchen</Contacts:FirstName><Contacts:LastName>Meinen</Contacts:LastName><Contacts:Birthday>1969-12-31</Contacts:Birthday><AirSyncBase:Body><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:EstimatedDataSize>0</AirSyncBase:EstimatedDataSize><AirSyncBase:Data></AirSyncBase:Data></AirSyncBase:Body><Contacts:Categories/><Contacts:Picture/></ApplicationData></Add></Commands></Collection></Collections></Sync>';
    
    protected $_setGeoData = TRUE;
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->_setGeoData = Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(FALSE);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts($this->_setGeoData);
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_TestCase::testCreateEntry()
     */
    public function testCreateEntry($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        $xml = new SimpleXMLElement($this->_testXMLInput);
        $syncrotonContact = new Syncroton_Model_Contact($xml->Collections->Collection->Commands->Add[0]->ApplicationData);
        
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonContact);
        
        $syncrotonContact = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        
        $this->assertEquals('asdf',                   $syncrotonContact->firstName);
        $this->assertEquals('asdfasdfaasd',           $syncrotonContact->lastName);
        $this->assertEquals('l.kneschke@example.com', $syncrotonContact->email1Address);
        $this->assertEquals('c.weiss@example.de',     $syncrotonContact->email2Address);
        $this->assertEquals('20001224T230000Z',       $syncrotonContact->birthday->format('Ymd\THis\Z'));
        $this->assertEquals(NULL,                     $syncrotonContact->webPage, 'facebook url should be removed');
        
        return array($serverId, $syncrotonContact);
    }

    /**
     * test birthday handling for BB10
     * 
     * @param string $syncrotonFolder
     * @return array
     */
    public function testCreateEntryBB10($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_BLACKBERRY), new Tinebase_DateTime(null, null, 'de_DE'));
        
        $xml = new SimpleXMLElement($this->_testXMLInput);
        $syncrotonContact = new Syncroton_Model_Contact($xml->Collections->Collection->Commands->Add[0]->ApplicationData);
        
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonContact);
        
        $tine20Contact = Addressbook_Controller_Contact::getInstance()->get($serverId);
        $this->assertEquals('2000-12-25T11:00:00+00:00', $tine20Contact->bday->getIso());
        
        $syncrotonContact = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        $this->assertEquals('2000-12-25T23:00:00+00:00', $syncrotonContact->birthday->getIso());
        
        return array($serverId, $syncrotonContact);
    }
    
    /**
     * testCreateEntryWithNoFamilyName
     * 
     * @param Syncroton_Model_Folder $syncrotonFolder
     * @return array
     */
    public function testCreateEntryWithNoFamilyName($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        $xml = new SimpleXMLElement($this->_testXMLInput);
        $syncrotonContact = new Syncroton_Model_Contact($xml->Collections->Collection->Commands->Add[0]->ApplicationData);
        unset($syncrotonContact->lastName);
        
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonContact);
        
        $syncrotonContact = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        
        $this->assertEquals('asdf',                   $syncrotonContact->firstName);
        $this->assertEquals('l.kneschke@example.com', $syncrotonContact->email1Address);
        $this->assertEquals('c.weiss@example.de',     $syncrotonContact->email2Address);
        $this->assertEquals('20001224T230000Z',       $syncrotonContact->birthday->format('Ymd\THis\Z'));
        $this->assertEquals(NULL,                     $syncrotonContact->webPage, 'facebook url should be removed');
        
        return array($serverId, $syncrotonContact);
    }

    /**
     * (non-PHPdoc)
     * @see ActiveSync_TestCase::testUpdateEntry()
     */
    public function testUpdateEntry($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        list($serverId, $syncrotonContact) = $this->testCreateEntry($syncrotonFolder);
        
        $syncrotonContact->lastName = 'MiniMe';
        
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonContact);
        
        $syncrotonContact = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        
        $this->assertEquals('asdf',   $syncrotonContact->firstName);
        $this->assertEquals('MiniMe', $syncrotonContact->lastName);
        $this->assertEquals(NULL,     $syncrotonContact->webPage, 'facebook url should be removed');
        
        return array($serverId, $syncrotonContact);
    }

    /**
     * testGetFolderForOutlook
     * 
     * @see 0009184: Only Admin Contact Data is synced (Outlook 2013)
     */
    public function testGetFolderForOutlook()
    {
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice('windowsoutlook15'), Tinebase_DateTime::now());
        $folders = $controller->getAllFolders();
        
        $this->assertEquals(1, count($folders));
        $this->assertTrue(array_key_exists('addressbook-root', $folders));
    }
    
    /**
     * test if only default addressbook is default AS folder 
     */
    public function testGetAllFoldersIPhone()
    {
        $controller = Syncroton_Data_Factory::factory(
            $this->_class,
            $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE),
            Tinebase_DateTime::now()
        );
        $allSyncrotonFolders = $controller->getAllFolders();
        
        $defaultFolderId = Tinebase_Core::getPreference('Addressbook')->{Addressbook_Preference::DEFAULTADDRESSBOOK};
        
        foreach ($allSyncrotonFolders as $syncrotonFolder) {
            $this->assertTrue($syncrotonFolder->serverId == $defaultFolderId 
                ? $syncrotonFolder->type === Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT
                : $syncrotonFolder->type === Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED
            );
        }
    }
}
