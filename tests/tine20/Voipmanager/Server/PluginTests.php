<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Voipmanager
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Voipmanager_Server_Plugin
 * 
 * @package     Voipmanager
 * @subpackage  Server
 */
class Voipmanager_Server_PluginTests extends TestCase
{
    /**
     * test general functionality of Voipmanager_Server_Plugin
     */
    public function testServerUserAgentSnom()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
POST /index.php?frontend=activesync HTTP/1.1\r
Host: localhost\r
User-Agent: Mozilla/4.0 (compatible; snom320-SIP 8.4.35 1.1.3-n)\r
EOS
        );
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Voipmanager_Server_Snom', $server);
    }
    
    /**
     * test general functionality of Voipmanager_Server_Plugin
     */
    public function testServerUserAgentAsterisk()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
POST /index.php?frontend=activesync HTTP/1.1\r
Host: localhost\r
User-Agent: asterisk-libcurl-agent/1.0\r
EOS
        );
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Voipmanager_Server_Asterisk', $server);
    }
}
