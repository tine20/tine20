<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Tinebase_Server_Routing
 *
 * @package     Tinebase
 */
class Tinebase_Server_RoutingTests extends TestCase
{
    /**
     * @group ServerTests
     */
    public function testExampleApplicationPublicTestRoute()
    {
        $server = new Tinebase_Server_Expressive();

        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            'GET /ExampleApplication/public/testRoute HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryZQRf6nhpOLbSRcoe' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . "\r\n"
        );

        ob_start();
        $server->handle($request, '');
        $out = ob_get_clean();

        static::assertEquals(ExampleApplication_Controller::publicTestRouteOutput, $out);
    }

    /**
     * @group ServerTests
     */
    public function testExampleApplicationAuthTestRoute()
    {
        $server = new Tinebase_Server_Expressive();

        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            'GET /ExampleApplication/testRoute HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryZQRf6nhpOLbSRcoe' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . "\r\n"
        );

        ob_start();
        $server->handle($request, '');
        $out = ob_get_clean();

        static::assertEquals(ExampleApplication_Controller::authTestRouteOutput, $out);
    }
}