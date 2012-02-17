<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Syncope_Command_Ping
 * 
 * @package     ActiveSync
 */
class ActiveSync_Command_PingTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var ActiveSync_Model_Device
     */
    protected $_device;
    
    /**
     * @var ActiveSync_Backend_Device
     */
    protected $_deviceBackend;
    
    /**
     * @var Syncope_Backend_Folder
     */
    protected $_folderBackend;

    /**
     * @var Syncope_Backend_SyncState
     */
    protected $_syncStateBackend;
    
    /**
     * @var Syncope_Backend_IContent
     */
    protected $_contentStateBackend;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Ping Command Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::setUp()
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        Syncope_Registry::setDatabase(Tinebase_Core::getDb());
        Syncope_Registry::setTransactionManager(Tinebase_TransactionManager::getInstance());
        
        $this->_deviceBackend       = new Syncope_Backend_Device(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_');
        $this->_folderBackend       = new Syncope_Backend_Folder(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_');
        $this->_syncStateBackend    = new Syncope_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_');
        $this->_contentStateBackend = new Syncope_Backend_Content(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_');
        
        $this->_device = $this->_deviceBackend->create(
            ActiveSync_Backend_DeviceTests::getTestDevice()
        );
        
        Syncope_Registry::set('deviceBackend',       $this->_deviceBackend);
        Syncope_Registry::set('folderStateBackend',  $this->_folderBackend);
        Syncope_Registry::set('syncStateBackend',    $this->_syncStateBackend);
        Syncope_Registry::set('contentStateBackend', $this->_contentStateBackend);
        Syncope_Registry::set('loggerBackend',       Tinebase_Core::getLogger());  

        Syncope_Registry::setContactsDataClass('ActiveSync_Controller_Contacts');
        Syncope_Registry::setCalendarDataClass('ActiveSync_Controller_Calendar');
        Syncope_Registry::setEmailDataClass('ActiveSync_Controller_Email');
        Syncope_Registry::setTasksDataClass('ActiveSync_Controller_Tasks');
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
     * test sync of existing contacts folder
     */
    public function testPingForContacts()
    {        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Tinebase_Core::getUser(),
            'Addressbook', 
            Tinebase_Core::getUser(),
            Tinebase_Model_Grants::GRANT_EDIT
        )->getFirstRecord();
        
        // first do a foldersync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $folderSync->getResponse();
        
        
        // request initial synckey
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>0</SyncKey><CollectionId>' . $personalContainer->getId() . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        $sync->handle();
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        
        // now do the first sync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1</SyncKey><CollectionId>' . $personalContainer->getId() . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        $sync->handle();
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        sleep(1);
        
        // add a new contact
        $contact = new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '20457',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'bday'                  => '1975-01-02 03:00:00', // new Tinebase_DateTime???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'container_id'          => $personalContainer->getId(),
            'role'                  => 'Role',
            'n_given'               => 'Lars',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
        ));
        
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact, FALSE);
        
        
        // and now we can start the ping request
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
                    <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
                    <Ping xmlns="uri:Ping"><HeartBeatInterval>10</HeartBeatInterval><Folders><Folder><Id>' . $personalContainer->getId() . '</Id><Class>Contacts</Class></Folder></Folders></Ping>'
        );
        
        $ping = new Syncope_Command_Ping($doc, $this->_device, null);
        $ping->handle();
        $responseDoc = $ping->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();

        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Ping', 'uri:Ping');
        
        $nodes = $xpath->query('//Ping:Ping/Ping:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_Ping::STATUS_CHANGES_FOUND, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Ping:Ping/Ping:Folders/Ping:Folder');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals($personalContainer->getId(), $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * test sync of existing events folder
     */
    public function testPingForEvents()
    {        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Tinebase_Core::getUser(),
            'Calendar', 
            Tinebase_Core::getUser(),
            Tinebase_Model_Grants::GRANT_EDIT
        )->getFirstRecord();
                
        // first do a foldersync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $folderSync->getResponse();
        
        
        // request initial synckey
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Calendar</Class><SyncKey>0</SyncKey><CollectionId>' . $personalContainer->getId() . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><FilterType>4</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        $sync->handle();
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
                
        
        // now do the first sync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Calendar</Class><SyncKey>1</SyncKey><CollectionId>' . $personalContainer->getId() . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><FilterType>4</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        $sync->handle();
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        sleep(1);
        
        // add a test event
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'SyncTest',
            'dtstart'       => Tinebase_DateTime::now()->addMonth(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:00:00',
            'dtend'         => Tinebase_DateTime::now()->addMonth(1)->addHour(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'container_id'  => $personalContainer->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            'attendee'      => new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
                array(
                    'user_id' => Tinebase_Core::getUser()->contact_id,
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'status' => Calendar_Model_Attender::STATUS_ACCEPTED
                )
            ))
        ));
        
        $event = Calendar_Controller_Event::getInstance()->create($event);
        
        // and now we can start the ping request
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Ping xmlns="uri:Ping"><HeartBeatInterval>10</HeartBeatInterval><Folders><Folder><Id>' . $personalContainer->getId() . '</Id><Class>Calendar</Class></Folder></Folders></Ping>'
        );
        
        $ping = new Syncope_Command_Ping($doc, $this->_device, null);
        $ping->handle();
        $responseDoc = $ping->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Ping', 'uri:Ping');
        
        $nodes = $xpath->query('//Ping:Ping/Ping:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_Ping::STATUS_CHANGES_FOUND, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Ping:Ping/Ping:Folders/Ping:Folder');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals($personalContainer->getId(), $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
    }
    
    /**
     * test sync of existing imap folder
     */
    public function testPingForEmails()
    {        
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
        if (! $imapConfig || ! isset($imapConfig->useSystemAccount) || $imapConfig->useSystemAccount != TRUE) {
            $this->markTestSkipped('IMAP backend not configured');
        }
        
        $emailController = new ActiveSync_Controller_Email($this->_device, new Tinebase_DateTime(null, null, 'de_DE'));

        $folders = $emailController->getAllFolders();
        $this->assertGreaterThan(0, count($folders));
        
        foreach ($folders as $folder) {
            if (strtoupper($folder['displayName']) == 'INBOX') {
                break;
            }
        }
        
        $emailController->updateCache($folder['folderId']);
        
        // first do a foldersync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $syncDoc = $folderSync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        
        // request initial synckey
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Email</Class><SyncKey>0</SyncKey><CollectionId>' . $folder['folderId'] . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><FilterType>4</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        $sync->handle();
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
                
        
        // now do the first sync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Email</Class><SyncKey>1</SyncKey><CollectionId>' . $folder['folderId'] . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><FilterType>4</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        $sync->handle();
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        // and now we can start the ping request
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Ping xmlns="uri:Ping"><HeartBeatInterval>10</HeartBeatInterval><Folders><Folder><Id>' . $folder['folderId'] . '</Id><Class>Email</Class></Folder></Folders></Ping>'
        );
        
        // add test email message to folder
        $emailTest = new Felamimail_Controller_MessageTest();
        $emailTest->setUp();
        $inbox = $emailTest->getFolder('INBOX');
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_plain.eml');
        Felamimail_Controller_Message::getInstance()->appendMessage($inbox, $email);
                
        $ping = new Syncope_Command_Ping($doc, $this->_device, null);
        $ping->handle();
        $responseDoc = $ping->getResponse();
        $responseDoc->formatOutput = true;
        //echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Ping', 'uri:Ping');
        
        $nodes = $xpath->query('//Ping:Ping/Ping:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_Ping::STATUS_CHANGES_FOUND, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Ping:Ping/Ping:Folders/Ping:Folder');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals($folder['folderId'], $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $message = $emailTest->searchAndCacheMessage('text/plain', $inbox);
        Felamimail_Controller_Message_Flags::getInstance()->addFlags(array($message), array(Zend_Mail_Storage::FLAG_DELETED));
    }
}
