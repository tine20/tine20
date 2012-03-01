<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Syncope_Command_Sync
 * 
 * @package     Tests
 */
class Syncope_Command_SyncTests extends Syncope_Command_ATestCase
{
    #protected $_logPriority = Zend_Log::DEBUG;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Sync Command Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test sync with non existing collection id and synckey > 0
     */
    public function testSyncWithInvalidCollectiondId()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1356</SyncKey><CollectionId>48ru47fhf7ghf7fgh4</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(0, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncope_Command_Sync::STATUS_INVALID_SYNC_KEY, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    /**
     * test sync with non existing collection id and synckey == 0
     */
    public function testSyncWithInvalidCollectiondIdAndSyncKey0()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>0</SyncKey><CollectionId>48ru47fhf7ghf7fgh4</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(0, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncope_Command_Sync::STATUS_FOLDER_HIERARCHY_HAS_CHANGED, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    /**
     * test sync of existing folder, before a folderSync got excecuted before
     */
    public function testSyncBeforeFolderSync()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>0</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(0, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncope_Command_Sync::STATUS_FOLDER_HIERARCHY_HAS_CHANGED, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    /**
     * test sync of existing contacts folder
     */
    public function testSyncOfContacts()
    {        
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
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>0</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>2</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(1, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncope_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        
        // now do the first sync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>2</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncope_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:MoreAvailable');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        #$this->assertEquals(Syncope_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        // there should be no responses element
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Responses');
        $this->assertEquals(0, $nodes->length, $syncDoc->saveXML());
                
        $this->assertEquals("uri:Contacts", $syncDoc->lookupNamespaceURI('Contacts'), $syncDoc->saveXML());
        
        // try with previous synckey again
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>2</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        
        // and now fetch the rest
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>2</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>20</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:MoreAvailable');
        $this->assertEquals(0, $nodes->length, $syncDoc->saveXML());
    }
        
    /**
     * @return string the id of the newly created contact
     */
    public function testAddingContactToServer()
    {
        $this->testSyncOfContacts();
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>2</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    <Commands><Add><ClientId>42</ClientId><ApplicationData></ApplicationData></Add></Commands>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(3, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncope_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Responses/AirSync:Add/AirSync:ServerId');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertFalse(empty($nodes->item(0)->nodeValue), $syncDoc->saveXML());
        
        return $nodes->item(0)->nodeValue;
    }
            
    public function testUpdatingContactOnServer()
    {
        $serverId = $this->testAddingContactToServer();
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:Contacts="uri:Contacts" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>3</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    <Commands><Change><ServerId>' . $serverId . '</ServerId><ApplicationData><Contacts:FirstName>aaaadde</Contacts:FirstName><Contacts:LastName>aaaaade</Contacts:LastName></ApplicationData></Change></Commands>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(4, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncope_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Responses');
        $this->assertEquals(0, $nodes->length, $syncDoc->saveXML());
        
        $this->assertEquals('aaaadde', Syncope_Data_AData::$entries['Syncope_Data_Contacts']['addressbookFolderId'][$serverId]['FirstName']);
        $this->assertEquals('aaaaade', Syncope_Data_AData::$entries['Syncope_Data_Contacts']['addressbookFolderId'][$serverId]['LastName']);
    }
            
    public function testDeletingContactOnServer()
    {
        $serverId = $this->testAddingContactToServer();
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>3</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    <Commands><Delete><ServerId>' . $serverId . '</ServerId></Delete></Commands>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(4, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncope_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
            
    public function testUpdatingContactOnClient()
    {
        $serverId = $this->testAddingContactToServer();
        
        Syncope_Data_Contacts::$changedEntries['Syncope_Data_Contacts'][] = $serverId;
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>3</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        Syncope_Data_Contacts::$changedEntries['Syncope_Data_Contacts'] = array();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(4, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncope_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands/AirSync:Change/AirSync:ServerId');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals($serverId, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    public function testDeletingContactOnClient()
    {
        $serverId = $this->testAddingContactToServer();
        
        unset(Syncope_Data_Contacts::$entries['Syncope_Data_Contacts']['addressbookFolderId'][$serverId]);
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>3</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(4, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncope_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands/AirSync:Delete/AirSync:ServerId');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals($serverId, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    public function testSyncWithNoChanges()
    {
        $serverId = $this->testSyncOfContacts();
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>3</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(3, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncope_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    /**
     * @return string the id of the newly created contact
     */
    public function testConcurringSyncRequest()
    {
        $this->testSyncOfContacts();
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>2</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    <Commands><Add><ClientId>42</ClientId><ApplicationData></ApplicationData></Add></Commands>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncope_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        $count = count(Syncope_Data_Contacts::$entries['Syncope_Data_Contacts']["addressbookFolderId"]);
        $folder = $this->_folderBackend->getFolder($this->_device, 'addressbookFolderId');
        $syncState = $this->_syncStateBackend->getSyncState($this->_device, $folder);
        $syncState->counter++;
        $syncState = $this->_syncStateBackend->create($syncState);
        
        try {
            $syncDoc = $sync->getResponse();
            $catchedException = false;
        } catch (Zend_Db_Statement_Exception $zdse) {
            $catchedException = true;
        }
        
        $this->assertTrue($catchedException);
        $this->assertGreaterThan(count(Syncope_Data_Contacts::$entries['Syncope_Data_Contacts']["addressbookFolderId"]), $count);
    }
            
}
