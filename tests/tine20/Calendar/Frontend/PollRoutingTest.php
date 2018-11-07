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
        $enabledFeatures = Calendar_Config::getInstance()->get(Calendar_Config::ENABLED_FEATURES);
        $enabledFeatures[Calendar_Config::FEATURE_POLLS] = true;

        Calendar_Config::getInstance()->set(Calendar_Config::ENABLED_FEATURES, $enabledFeatures);

        $agbStr = 'testAGB';
        Calendar_Config::getInstance()->set(Calendar_Config::POLL_GTC, $agbStr);

        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter);

        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'GET /Calendar/view/pollagb HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . "\r\n"
        ));

        /** @var \Symfony\Component\DependencyInjection\Container $container */
        $container = Tinebase_Core::getPreCompiledContainer();
        $container->set(\Psr\Http\Message\RequestInterface::class, $request);
        Tinebase_Core::setContainer($container);

        $server->handle();

        $emitter->response->getBody()->rewind();
        static::assertEquals($agbStr, $emitter->response->getBody()->getContents());
    }
}