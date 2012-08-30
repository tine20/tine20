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
class Tinebase_Server_WebDAVTests extends PHPUnit_Framework_TestCase
{
    #protected $_logPriority = Zend_Log::DEBUG;
    
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
    
    public function testServer()
    {
        $_SERVER['REQUEST_METHOD']            = 'PROPFIND';
        $_SERVER['HTTP_DEPTH']                = '0';
        $_SERVER['HTTP_USER_AGENT']           = 'Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7';
        $_SERVER['REQUEST_URI']               = '/calendars/64d7fdf9202f7b1faf7467f5066d461c2e75cf2b/4/';
        $_SERVER['PHP_AUTH_USER']             = $this->_config->username;
        $_SERVER['PHP_AUTH_PW']               = $this->_config->password;
        $_SERVER['REMOTE_ADDR']               = $this->_config->ip;
        
        $request = new Zend_Controller_Request_Http(
            Zend_Uri::factory('http://localhost/calendars/64d7fdf9202f7b1faf7467f5066d461c2e75cf2b/4/')
        );
        
        $body = fopen('php://temp', 'r+');
        fwrite($body, '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:CS="http://calendarserver.org/ns/"><D:prop><CS:getctag/></D:prop></D:propfind>');
        rewind($body);
        
        ob_start();
        
        $server = new Tinebase_Server_WebDAV();
        
        $server->handle($request, $body);
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $this->assertEquals('PD94bWwgdmVyc2lvbj0iMS4wIiBlbm', substr(base64_encode($result),0,30));
    }
}
