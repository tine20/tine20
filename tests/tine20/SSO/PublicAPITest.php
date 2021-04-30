<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     SSO
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * SSO public API tests
 *
 * @package     SSO
 */
class SSO_PublicAPITest extends TestCase
{
    /**
     * Tinebase_Core::getContainer()->set(RequestInterface::class,
    (new \Zend\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
    ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => ['url' => 'http://unittest/shalala']],
    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256')));

     */
    public function testGetLoginMask()
    {
        $relyingParty = SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'unittest',
            SSO_Model_RelyingParty::FLD_REDIRECT_URLS => ['https://unittest.test/uri'],
            SSO_Model_RelyingParty::FLD_SECRET => 'unittest',
            SSO_Model_RelyingParty::FLD_IS_CONFIDENTIAL => true,
        ]));

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'https://unittest/shalala?response_type=code' .
                '&scope=openid%20profile%20email' .
                '&client_id=' . urlencode($relyingParty->getId()) .
                '&state=af0ifjsldkj' .
                '&nonce=nonce' .
                '&redirect_uri=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_REDIRECT_URLS}[0]), 'GET'))
            ->withQueryParams([
                'response_type' => 'code',
                'scope' => 'openid profile email',
                'client_id' => $relyingParty->getId(),
                'state' => 'af0ifjsldkj',
                'nonce' => 'nonce',
                'redirect_uri' => $relyingParty->{SSO_Model_RelyingParty::FLD_REDIRECT_URLS}[0]
            ])
        );

        Tinebase_Core::unsetUser();
        $coreSession = Tinebase_Session::getSessionNamespace();
        if (isset($coreSession->currentAccount)) {
            unset($coreSession->currentAccount);
        }

        $response = SSO_Controller::publicAuthorize();
        $response->getBody()->rewind();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<input type="hidden" name="nonce" value="nonce"/>', $response->getBody()->getContents());
    }

    public function testPostLoginMask()
    {
        $relyingParty = SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'unittest',
            SSO_Model_RelyingParty::FLD_REDIRECT_URLS => ['https://unittest.test/uri'],
            SSO_Model_RelyingParty::FLD_SECRET => 'unittest',
            SSO_Model_RelyingParty::FLD_IS_CONFIDENTIAL => true,
        ]));

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'https://unittest/shalala?response_type=code' .
                '&scope=openid%20profile%20email' .
                '&client_id=' . urlencode($relyingParty->getId()) .
                '&state=af0ifjsldkj' .
                '&nonce=nonce' .
                '&redirect_uri=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_REDIRECT_URLS}[0]), 'POST'))
                ->withQueryParams([
                    'response_type' => 'code',
                    'scope' => 'openid profile email',
                    'client_id' => $relyingParty->getId(),
                    'state' => 'af0ifjsldkj',
                    'nonce' => 'nonce',
                    'redirect_uri' => $relyingParty->{SSO_Model_RelyingParty::FLD_REDIRECT_URLS}[0],
                ])->withParsedBody([
                    'username' => 'tine20admin',
                    'password' => 'tine20admin'
                ])
        );

        Tinebase_Core::unsetUser();
        $coreSession = Tinebase_Session::getSessionNamespace();
        if (isset($coreSession->currentAccount)) {
            unset($coreSession->currentAccount);
        }

        $response = SSO_Controller::publicAuthorize();
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAutoAuth()
    {
        $relyingParty = SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'unittest',
            SSO_Model_RelyingParty::FLD_REDIRECT_URLS => ['https://unittest.test/uri'],
            SSO_Model_RelyingParty::FLD_SECRET => 'unittest',
            SSO_Model_RelyingParty::FLD_IS_CONFIDENTIAL => true,
        ]));

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'https://unittest/shalala?response_type=code' .
                '&scope=openid%20profile%20email' .
                '&client_id=' . urlencode($relyingParty->getId()) .
                '&state=af0ifjsldkj' .
                '&nonce=nonce' .
                '&redirect_uri=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_REDIRECT_URLS}[0]), 'GET'))
                ->withQueryParams([
                    'response_type' => 'code',
                    'scope' => 'openid profile email',
                    'client_id' => $relyingParty->getId(),
                    'state' => 'af0ifjsldkj',
                    'nonce' => 'nonce',
                    'redirect_uri' => $relyingParty->{SSO_Model_RelyingParty::FLD_REDIRECT_URLS}[0]
                ])
        );

        $response = SSO_Controller::publicAuthorize();
        $this->assertSame(302, $response->getStatusCode());
        $header = $response->getHeader('Location');
        $this->assertIsArray($header);
        $this->assertCount(1, $header);
        $this->assertStringContainsString('&state=af0ifjsldkj', $header[0]);
        $this->assertStringStartsWith($relyingParty->{SSO_Model_RelyingParty::FLD_REDIRECT_URLS}[0] . '?', $header[0]);
        $this->assertTrue((bool)preg_match('/code=([^&]+)/', $header[0], $m), 'can not pregmatch code in redirect url');
        // TODO test $m[1] against token api
    }
}
