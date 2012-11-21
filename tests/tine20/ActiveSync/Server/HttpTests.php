<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'ServerTestHelper.php';

/**
 * Test class for ActiveSync_Server_Http
 * 
 * @package     ActiveSync
 */
class ActiveSync_Server_HttpTests extends PHPUnit_Framework_TestCase
{
    /**
     * 
     * @var Zend_Config
     */
    protected $_config;
    
    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        // get config
        $configData = @include('phpunitconfig.inc.php');
        if ($configData === false) {
            $configData = include('config.inc.php');
        }
        if ($configData === false) {
            die ('central configuration file config.inc.php not found in includepath: ' . get_include_path());
        }
        $this->_config = new Zend_Config($configData);
        
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
    }
    
    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testServer()
    {
        $_SERVER['REQUEST_METHOD']            = 'POST';
        $_SERVER['HTTP_MS_ASPROTOCOLVERSION'] = '14.1';
        $_SERVER['HTTP_USER_AGENT']           = 'Apple-iPhone/705.18';
        $_SERVER['PHP_AUTH_USER']             = $this->_config->username;
        $_SERVER['PHP_AUTH_PW']               = $this->_config->password;
        $_SERVER['REMOTE_ADDR']               = $this->_config->ip;
        
        $request = new Zend_Controller_Request_Http(
            Zend_Uri::factory('http://localhost/Microsoft-Server-ActiveSync?User=abc1234&DeviceId=Appl7R743U8YWH9&DeviceType=iPhone&Cmd=Sync')
        );
        
        $body = new DOMDocument();
        $body->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1356</SyncKey><CollectionId>48ru47fhf7ghf7fgh4</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        ob_start();
        
        $server = new ActiveSync_Server_Http();
        
        $server->handle($request, $body);
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $this->assertEquals('AwFqAEVcT0sDMAABUgM0OHJ1NDdmaG', substr(base64_encode($result),0,30));
    }
}
