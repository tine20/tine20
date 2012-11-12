<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Tests
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to test <...>
 *
 * @package     Syncroton
 * @subpackage  Tests
 */
class Syncroton_Command_SyncTests extends Syncroton_Command_ATestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton Sync command tests');
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
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections><Collection><Class>Contacts</Class><SyncKey>1356</SyncKey><CollectionId>48ru47fhf7ghf7fgh4</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections>
                <HeartbeatInterval>10</HeartbeatInterval>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
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
        $this->assertEquals(Syncroton_Command_Sync::STATUS_INVALID_SYNC_KEY, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    /**
     * test sync with non existing collection id and synckey == 0
     */
    public function testSyncWithInvalidCollectiondIdAndSyncKey0()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections><Collection><Class>Contacts</Class><SyncKey>0</SyncKey><CollectionId>48ru47fhf7ghf7fgh4</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections>
                <HeartbeatInterval>10</HeartbeatInterval>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
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
        $this->assertEquals(Syncroton_Command_Sync::STATUS_FOLDER_HIERARCHY_HAS_CHANGED, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
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
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
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
        $this->assertEquals(Syncroton_Command_Sync::STATUS_FOLDER_HIERARCHY_HAS_CHANGED, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
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
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $responseDoc = $folderSync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        
        // request initial synckey
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>0</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <WindowSize>2</WindowSize>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>0</SyncKey>
                        <CollectionId>anotherAddressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <WindowSize>2</WindowSize>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                </Collections>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(1, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        $this->assertEquals(1, $nodes->item(1)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        
        // now do the first sync windowsize of collection = 2
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>1</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <WindowSize>2</WindowSize>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>1</SyncKey>
                        <CollectionId>anotherAddressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <WindowSize>2</WindowSize>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                </Collections>
                <WindowSize>2</WindowSize>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(2, $nodes->length, 'class count mismatch: ' . $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, 'class mismatch' . $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        $this->assertEquals(2, $nodes->item(1)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:MoreAvailable');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        #$this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        // there should be no responses element
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Responses');
        $this->assertEquals(0, $nodes->length, $syncDoc->saveXML());
                
        $this->assertEquals("uri:Contacts", $syncDoc->lookupNamespaceURI('Contacts'), $syncDoc->saveXML());

        
        // now do the first sync windowsize of collection = 2
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>2</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <WindowSize>2</WindowSize>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>2</SyncKey>
                        <CollectionId>anotherAddressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <WindowSize>2</WindowSize>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                </Collections>
                <WindowSize>2</WindowSize>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(3, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        $this->assertEquals(2, $nodes->item(1)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:MoreAvailable');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        #$this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        
        // and now fetch the rest
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>3</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>2</SyncKey>
                        <CollectionId>anotherAddressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                </Collections>
                <HeartbeatInterval>10</HeartbeatInterval>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(4, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        $this->assertEquals(3, $nodes->item(1)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:MoreAvailable');
        $this->assertEquals(0, $nodes->length, $syncDoc->saveXML());
    }
    
    /**
     * test sync of existing contacts folder with partial element
     */
    public function testSyncOfContactsWithPartial()
    {
        // first do a foldersync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $responseDoc = $folderSync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        
        // request initial synckey
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>0</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <WindowSize>2</WindowSize>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>0</SyncKey>
                        <CollectionId>anotherAddressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <WindowSize>2</WindowSize>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                </Collections>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(1, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        $this->assertEquals(1, $nodes->item(1)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        
        // now do the first sync windowsize of collection = 2
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>1</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <WindowSize>2</WindowSize>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>1</SyncKey>
                        <CollectionId>anotherAddressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <WindowSize>2</WindowSize>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                </Collections>
                <WindowSize>2</WindowSize>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(2, $nodes->length, 'class count mismatch: ' . $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, 'class mismatch' . $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        $this->assertEquals(2, $nodes->item(1)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:MoreAvailable');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        #$this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        // there should be no responses element
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Responses');
        $this->assertEquals(0, $nodes->length, $syncDoc->saveXML());
                
        $this->assertEquals("uri:Contacts", $syncDoc->lookupNamespaceURI('Contacts'), $syncDoc->saveXML());

        
        // now do the first sync windowsize of collection = 2
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <SyncKey>2</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                    </Collection>
                    <Collection>
                        <SyncKey>2</SyncKey>
                        <CollectionId>anotherAddressbookFolderId</CollectionId>
                    </Collection>
                </Collections>
                <WindowSize>2</WindowSize>
                <Partial/>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(3, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        $this->assertEquals(2, $nodes->item(1)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:MoreAvailable');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        #$this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        
        // and now fetch the rest
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <SyncKey>3</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                        <WindowSize>100</WindowSize>
                    </Collection>
                    <Collection>
                        <SyncKey>2</SyncKey>
                        <CollectionId>anotherAddressbookFolderId</CollectionId>
                        <WindowSize>100</WindowSize>
                    </Collection>
                </Collections>
                <HeartbeatInterval>10</HeartbeatInterval>
                <Partial/>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(2, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(4, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        $this->assertEquals(3, $nodes->item(1)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:MoreAvailable');
        $this->assertEquals(0, $nodes->length, $syncDoc->saveXML());
    }
    
    /**
     * test sync of existing contacts folder
     */
    public function testSyncOfContactsWithHeartbeatInterval()
    {
        // first do a foldersync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $responseDoc = $folderSync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
    
    
        // request initial synckey
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>0</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                </Collections>
            </Sync>'
        );
    
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
    
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
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    
        //we have to sleep on second here
        sleep(1);
    
        // now do the first sync windowsize of collection = 2
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <Class>Contacts</Class>
                        <SyncKey>1</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                </Collections>
                <HeartbeatInterval>120</HeartbeatInterval>
            </Sync>'
        );
    
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
    
        $sync->handle();
    
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
    
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
    
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, 'class count mismatch: ' . $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, 'class mismatch' . $syncDoc->saveXML());
    
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        #$this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    
        $this->assertEquals("uri:Contacts", $syncDoc->lookupNamespaceURI('Contacts'), $syncDoc->saveXML());
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
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Contacts="uri:Contacts"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>4</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    <Commands><Add><ClientId>42</ClientId><ApplicationData><Contacts:FirstName>Lars</Contacts:FirstName></ApplicationData></Add></Commands>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
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
        $this->assertEquals(5, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
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
                    <Class>Contacts</Class><SyncKey>5</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    <Commands><Change><ServerId>' . $serverId . '</ServerId><ApplicationData><Contacts:FirstName>aaaadde</Contacts:FirstName><Contacts:LastName>aaaaade</Contacts:LastName></ApplicationData></Change></Commands>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
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
        $this->assertEquals(6, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Responses');
        $this->assertEquals(0, $nodes->length, $syncDoc->saveXML());
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
                    <Class>Contacts</Class><SyncKey>5</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    <Commands><Delete><ServerId>' . $serverId . '</ServerId></Delete></Commands>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
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
        $this->assertEquals(6, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
            
    public function testFetchingContactFromServer()
    {
        $serverId = $this->testAddingContactToServer();
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>5</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges>0</GetChanges><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    <Commands><Fetch><ServerId>' . $serverId . '</ServerId></Fetch></Commands>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
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
        $this->assertEquals(5, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Responses/AirSync:Fetch/AirSync:ServerId');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals($serverId, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    public function testUpdatingContactOnClient()
    {
        $serverId = $this->testAddingContactToServer();
        
        Syncroton_Data_Contacts::$changedEntries['Syncroton_Data_Contacts'][] = $serverId;
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>5</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        Syncroton_Data_Contacts::$changedEntries['Syncroton_Data_Contacts'] = array();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        $xpath->registerNamespace('Contacts', 'uri:Contacts');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(6, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands/AirSync:Change/AirSync:ServerId');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals($serverId, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands/AirSync:Change/AirSync:ApplicationData/Contacts:FirstName');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Lars', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    public function testUpdatingContactOnClientWithPartial()
    {
        $serverId = $this->testAddingContactToServer();
        
        Syncroton_Data_Contacts::$changedEntries['Syncroton_Data_Contacts'][] = $serverId;
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <SyncKey>5</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                    </Collection>
                </Collections>
                <Partial/>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        Syncroton_Data_Contacts::$changedEntries['Syncroton_Data_Contacts'] = array();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        $xpath->registerNamespace('Contacts', 'uri:Contacts');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(6, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands/AirSync:Change/AirSync:ServerId');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals($serverId, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands/AirSync:Change/AirSync:ApplicationData/Contacts:FirstName');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Lars', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    public function testDeletingContactOnClient()
    {
        $serverId = $this->testAddingContactToServer();
        
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $this->_device, new DateTime(null, new DateTimeZone('UTC')));
        
        $entries = $dataController->getServerEntries('addressbookFolderId', null);
        
        $dataController->deleteEntry('addressbookFolderId', $entries[0], array());
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>5</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
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
        $this->assertEquals(6, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands/AirSync:Delete/AirSync:ServerId');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals($entries[0], $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    /**
     * test sync with no changes
     * 
     * the SyncKey should not change
     */
    public function testSyncWithNoChanges()
    {
        $serverId = $this->testSyncOfContacts();
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <Class>Contacts</Class><SyncKey>4</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                        <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    </Collection>
                </Collections>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(4, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    /**
     * test sync with Partial element
     * 
     * the SyncKey should not change
     */
    public function testSyncWithPartialElement()
    {
        $serverId = $this->testSyncWithNoChanges();
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <SyncKey>4</SyncKey>
                        <CollectionId>addressbookFolderId</CollectionId>
                    </Collection>
                </Collections>
                <Partial/>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(4, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Partial/>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(4, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    /**
     * test sync with no collections
     */
    public function testSyncWithNoCollections()
    {
        $serverId = $this->testSyncWithNoChanges();
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_RESEND_FULL_XML, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    /**
     * @return string the id of the newly created contact
     */
    public function testConcurringSyncRequest()
    {
        $this->testSyncOfContacts();
        
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $this->_device, new DateTime(null, new DateTimeZone('UTC')));
        
        $entries = $dataController->getServerEntries('addressbookFolderId', null);
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>4</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    <Commands><Add><ClientId>42</ClientId><ApplicationData></ApplicationData></Add></Commands>
                </Collection>
            </Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        $count = count($dataController->getServerEntries('addressbookFolderId', null));
        $folder = Syncroton_Registry::getFolderBackend()->getFolder($this->_device, 'addressbookFolderId');
        $syncState = Syncroton_Registry::getSyncStateBackend()->getSyncState($this->_device, $folder);
        $syncState->counter++;
        $syncState = Syncroton_Registry::getSyncStateBackend()->create($syncState);
        
        try {
            $syncDoc = $sync->getResponse();
            $catchedException = false;
        } catch (Zend_Db_Statement_Exception $zdse) {
            $catchedException = true;
        }
        
        $this->assertTrue($catchedException);
        $this->assertGreaterThan(count($dataController->getServerEntries('addressbookFolderId', null)), $count);
    }
}
