<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tasks_Controller_Task
 * 
 * @package     ActiveSync
 */
class ActiveSync_Controller_TasksTests extends ActiveSync_TestCase
{
    /**
     * name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Tasks';
    
    protected $_controllerName = 'ActiveSync_Controller_Tasks';
    
    protected $_class = Syncroton_Data_Factory::CLASS_TASKS;
    
    /**
     * xml input
     * 
     * @var string
     */
    protected $_testXMLInput = '<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
    <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Tasks="uri:Tasks">
        <Collections>
            <Collection>
                <Class>Tasks</Class>
                <SyncKey>17</SyncKey>
                <CollectionId>tasks-root</CollectionId>
                <DeletesAsMoves/>
                <GetChanges/>
                <WindowSize>50</WindowSize>
                <Options><FilterType>8</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>2048</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>0</Conflict></Options>
                <Commands>
                    <Change>
                        <ClientId>1</ClientId>
                        <ApplicationData><AirSyncBase:Body><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:Data>test beschreibung zeile 1&#13;
Zeile 2&#13;
Zeile 3</AirSyncBase:Data></AirSyncBase:Body><Tasks:Subject>Testaufgabe auf mfe</Tasks:Subject><Tasks:Importance>1</Tasks:Importance><Tasks:UtcDueDate>2010-11-28T22:59:00.000Z</Tasks:UtcDueDate><Tasks:DueDate>2010-11-28T23:59:00.000Z</Tasks:DueDate><Tasks:Complete>0</Tasks:Complete><Tasks:Sensitivity>0</Tasks:Sensitivity></ApplicationData>
                    </Change>
                </Commands>
            </Collection>
        </Collections>
    </Sync>';
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Controller Tasks Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public function testCreateEntry($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        $xml = new SimpleXMLElement($this->_testXMLInput);
        $syncrotonTask = new Syncroton_Model_Task($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonTask);
        
        $syncrotonTask = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        
        $this->assertEquals("Testaufgabe auf mfe", $syncrotonTask->subject);
        $this->assertEquals(1,                     $syncrotonTask->importance);
        
        //Body
        $this->assertTrue($syncrotonTask->body instanceof Syncroton_Model_EmailBody);
        #$this->assertEquals("test beschreibung zeile 1\r\nZeile 2\r\nZeile 3", $syncrotonTask->body->Data);
        
        
        return array($serverId, $syncrotonTask);
    }

    public function testUpdateEntry($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        list($serverId, $syncrotonTask) = $this->testCreateEntry($syncrotonFolder);
        
        $syncrotonTask->subject = $syncrotonTask->subject . 'Update';
        
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonTask);
        
        $syncrotonTask = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        
        $this->assertEquals("Testaufgabe auf mfeUpdate", $syncrotonTask->subject);
        
        return array($serverId, $syncrotonTask);
    }
}
