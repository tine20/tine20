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
 *
 * TODO rename all this stuff once we decided on a name!
 *
 * TODO routing Routing Expressive expressive in case we search for it, remove this comment after renaming stuff
 */
class Tinebase_Server_RoutingTests extends TestCase
{
    /**
     * @group ServerTests
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_NotImplemented
     */
    public function testExampleApplicationPublicTestRoute()
    {
        Tinebase_Application::getInstance()->setApplicationStatus(Tinebase_Application::getInstance()
            ->getApplicationByName('ExampleApplication'), Tinebase_Application::ENABLED);

        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter);

        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'GET /ExampleApplication/public/testRoute HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryZQRf6nhpOLbSRcoe' . "\r\n"
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
        static::assertEquals(ExampleApplication_Controller::publicTestRouteOutput, $emitter->response->getBody()
            ->getContents());
    }

    /**
     * @group ServerTests
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_NotImplemented
     */
    public function testExampleApplicationAuthTestRoute()
    {
        Tinebase_Application::getInstance()->setApplicationStatus(Tinebase_Application::getInstance()
            ->getApplicationByName('ExampleApplication'), Tinebase_Application::ENABLED);

        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'GET /ExampleApplication/testRoute HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryZQRf6nhpOLbSRcoe' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . "\r\n"
        ));

        $content = $this->_emitRequest($request);
        static::assertEquals(ExampleApplication_Controller::authTestRouteOutput, $content);
    }

    protected function _emitRequest($request)
    {
        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter);
        /** @var \Symfony\Component\DependencyInjection\Container $container */
        $container = Tinebase_Core::getPreCompiledContainer();
        $container->set(\Psr\Http\Message\RequestInterface::class, $request);
        Tinebase_Core::setContainer($container);

        $server->handle();

        $emitter->response->getBody()->rewind();
        return $emitter->response->getBody()->getContents();
    }

    /**
     * @group ServerTests
     */
    public function testHealthCheck()
    {
        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'GET /health HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Tine 2.0 UNITTEST' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . "\r\n"
        ));

        $content = $this->_emitRequest($request);
        self::assertNotEmpty($content);
        self::assertEquals('{"status":"pass","problems":[]}', $content);
    }

    public function testOCStatus()
    {
        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'GET /status.php HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Tine 2.0 UNITTEST of Tine20Drive' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . "\r\n"
        ));

        $data = new Tinebase_Frontend_Json();
        $registryData = $data->getRegistryData();
        $version = $registryData['version'];

        $values = array(
            'installed'     => true,
            'version'       => $version['codeName'],
            'versionstring' => $version['packageString'],
            'maintenance'   => Tinebase_Core::inMaintenanceMode(),
            'edition'       => $version['buildType'],
            'productname'   => 'Tine 2.0' // lets try it ;-)
        );

        $content = $this->_emitRequest($request);

        self::assertNotEmpty($content);
        self::assertContains($values['productname'], $content);
        self::assertContains($values['edition'], $content);
        self::assertContains($values['version'], $content);
        self::assertContains($values['versionstring'], $content);
    }
}
