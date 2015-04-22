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
 * Test class for Tinebase_Server_Plugin_Http
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_HttpTests extends TestCase
{
    /**
     * test general functionality of Tinebase_Server_Plugin_Http
     */
    public function testServer()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
POST /index.php?frontend=webdav HTTP/1.1\r
Host: localhost\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r
EOS
        );
        
        $server = Tinebase_Server_Plugin_Http::getServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_Http', $server);
    }
}
