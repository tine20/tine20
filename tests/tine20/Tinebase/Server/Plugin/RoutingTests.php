<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Tinebase_Server_Plugin_Routing
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_RoutingTests extends TestCase
{
    /**
     * test general functionality of Tinebase_Server_Plugin_Routing
     */
    public function testServer()
    {
        $request = Tinebase_Http_Request::fromString(
            "POST /index.php?" . Tinebase_Server_Expressive::QUERY_PARAM_DO_EXPRESSIVE . "=1 HTTP/1.1\r\n".
            "Host: localhost\r\n".
            "User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7"
        );

        $server = Tinebase_Server_Plugin_Expressive::getServer($request);

        $this->assertInstanceOf(Tinebase_Server_Expressive::class, $server);
    }
}
