<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Tinebase autodiscover frontend
 *
 * @package     Tinebase
 */
class Tinebase_Frontend_AutodiscoverTests extends TestCase
{
    public function testOutlook2006Request()
    {
        $enabledFeatures = Tinebase_Config::getInstance()->get(Tinebase_Config::ENABLED_FEATURES);
        Tinebase_Config::getInstance()->clearCache();
        $enabledFeatures[Tinebase_Config::FEATURE_AUTODISCOVER] = true;
        $enabledFeatures[Tinebase_Config::FEATURE_AUTODISCOVER_MAILCONFIG] = true;

        Tinebase_Config::getInstance()->set(Tinebase_Config::ENABLED_FEATURES, $enabledFeatures);

        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter);

        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'POST /autodiscover/autodiscover.xml HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . "\r\n"
            . '<?xml version="1.0" encoding="utf-8" ?>
<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/outlook/requestschema/2006">
    <Request>
        <AcceptableResponseSchema>http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a</AcceptableResponseSchema>
        <EMailAddress>JohnDoe@sample.com</EMailAddress>
    </Request>
</Autodiscover>' . "\r\n\r\n"
        ));

        /** @var \Symfony\Component\DependencyInjection\Container $container */
        $container = Tinebase_Core::getPreCompiledContainer();
        $container->set(\Psr\Http\Message\RequestInterface::class, $request);
        Tinebase_Core::setContainer($container);

        $server->handle();

        static::assertTrue((bool)$responseXml = simplexml_load_string((string)$emitter->response->getBody()));
        static::assertTrue((bool)$responseXml->Response);
        $imap = Tinebase_Config::getInstance()->{Tinebase_Config::IMAP};
        $smtp = Tinebase_Config::getInstance()->{Tinebase_Config::SMTP};
        if (($imap && $imap->host) || ($smtp && $smtp->host)) {
            static::assertSame('email', (string)$responseXml->Response->Account->AccountType);
            static::assertSame('settings', (string)$responseXml->Response->Account->Action);
        }
        static::assertSame('MobileSync', (string)$responseXml->Response->Action->Settings->Server->Type);
    }

    public function testMobilesyncRequest()
    {
        $enabledFeatures = Tinebase_Config::getInstance()->get(Tinebase_Config::ENABLED_FEATURES);
        Tinebase_Config::getInstance()->clearCache();
        $enabledFeatures[Tinebase_Config::FEATURE_AUTODISCOVER] = true;

        Tinebase_Config::getInstance()->set(Tinebase_Config::ENABLED_FEATURES, $enabledFeatures);

        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter);

        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'POST /autodiscover/autodiscover.xml HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . "\r\n"
            . '<?xml version="1.0" encoding="utf-8" ?>
<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/outlook/requestschema/2006">
    <Request>
        <AcceptableResponseSchema>http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006a</AcceptableResponseSchema>
        <EMailAddress>JohnDoe@sample.com</EMailAddress>
    </Request>
</Autodiscover>' . "\r\n\r\n"
        ));

        /** @var \Symfony\Component\DependencyInjection\Container $container */
        $container = Tinebase_Core::getPreCompiledContainer();
        $container->set(\Psr\Http\Message\RequestInterface::class, $request);
        Tinebase_Core::setContainer($container);

        $server->handle();

        static::assertTrue((bool)$responseXml = simplexml_load_string((string)$emitter->response->getBody()));
        static::assertTrue((bool)$responseXml->Response);
        static::assertTrue((bool)$responseXml->Response->User);
        static::assertSame('JohnDoe@sample.com', (string)$responseXml->Response->User->DisplayName);
        static::assertSame('JohnDoe@sample.com', (string)$responseXml->Response->User->EMailAddress);
        static::assertSame('MobileSync', (string)$responseXml->Response->Action->Settings->Server->Type);
        static::assertSame(Tinebase_Core::getUrl() . '/Microsoft-Server-ActiveSync',
            (string)$responseXml->Response->Action->Settings->Server->Url);
        static::assertSame(Tinebase_Config::getInstance()->{Tinebase_Config::BRANDING_TITLE},
            (string)$responseXml->Response->Action->Settings->Server->Name);
    }
}

