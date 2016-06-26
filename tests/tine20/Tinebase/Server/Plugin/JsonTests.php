<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Tinebase_Server_Plugin_Json
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_JsonTests extends TestCase
{
    /**
     * test with ContentType header set to application/json
     */
    public function testServerContentType()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            "POST /index.php HTTP/1.1\r\n".
            "Host: localhost\r\n".
            "Content-Type: application/json\r\n".
            "User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7"
        );
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_Json', $server);
    }
    
    /**
     * test with ACCESS-CONTROL-REQUEST-METHOD header set
     */
    public function testServerCORSHeader()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            "POST /index.php HTTP/1.1\r\n".
            "Host: localhost\r\n".
            "ACCESS-CONTROL-REQUEST-METHOD: application/json\r\n".
            "User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7"
        );
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_Json', $server);
    }
    
    /**
     * test with post parameter requestType set to JSON
     */
    public function testServerPostParameter()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            "POST /index.php?requestType=JSON HTTP/1.1\r\n".
            "Host: localhost\r\n".
            "User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7"
        );
        
        $request->setPost(new Zend\Stdlib\Parameters(array('requestType' => 'JSON')));
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_Json', $server);
    }
}
