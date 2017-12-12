<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for calendar poll routing
 *
 * @package     Tinebase
 */
class Calendar_Frontend_PollRoutingTest extends TestCase
{
    /**
     * @group ServerTests
     * @throws Tinebase_Exception_NotImplemented
     */
    public function testExampleApplicationPublicTestRoute()
    {
        if (TINE20_BUILDTYPE !== 'DEVELOPMENT') {
            static::markTestSkipped('test only works in development mode, fix it when PSR container is used properly');
        }

        $agbStr = 'testAGB';
        Calendar_Config::getInstance()->set(Calendar_Config::POLL_AGB, $agbStr);

        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter, false);

        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            'GET /Calendar/view/pollagb HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . "\r\n"
        );

        $server->handle($request, '');

        $emitter->response->getBody()->rewind();
        static::assertEquals($agbStr, $emitter->response->getBody()->getContents());
    }
}