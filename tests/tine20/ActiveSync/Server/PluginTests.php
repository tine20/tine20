<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for ActiveSync_Server_Plugin
 * 
 * @package     ActiveSync
 * @subpackage  Server
 */
class ActiveSync_Server_PluginTests extends TestCase
{
    /**
     * test general functionality of ActiveSync_Server_Plugin
     */
    public function testServerGetParameter()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            "POST /index.php?frontend=activesync HTTP/1.1\r\n".
            "Host: localhost\r\n".
            "Depth: 0\r\n".
            "User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7"
        );
        
        $request->setQuery(new Zend\Stdlib\Parameters(array('frontend' => 'activesync')));
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('ActiveSync_Server_Http', $server);
    }
}
