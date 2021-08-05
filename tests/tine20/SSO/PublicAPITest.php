<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     SSO
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use SAML2\Utils;

/**
 * SSO public API tests
 *
 * @package     SSO
 */
class SSO_PublicAPITest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $config = SSO_Config::getInstance();
        $config->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED} = true;
        $config->{SSO_Config::SAML2}->{SSO_Config::ENABLED} = true;

        $keys = $config->{SSO_Config::SAML2}->{SSO_Config::SAML2_KEYS}[0];
        $dir = '';
        if (isset($keys['privatekey']) && is_file($keys['privatekey'])) {
            $dir = dirname($keys['privatekey']);
            chmod($keys['privatekey'], 0600);
        }
        if (isset($keys['certificate']) && is_file($keys['certificate'])) {
            $dir = dirname($keys['certificate']);
            chmod($keys['certificate'], 0600);
        }
        if (is_file($dir . '/private.key')) {
            chmod($dir . '/private.key', 0600);
        }
    }

    protected function _createSAML2Config()
    {
        SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'https://localhost:8443/auth/saml2/sp/metadata.php',
            SSO_Model_RelyingParty::FLD_CONFIG_CLASS => SSO_Model_Saml2RPConfig::class,
            SSO_Model_RelyingParty::FLD_CONFIG => new SSO_Model_Saml2RPConfig([
                SSO_Model_Saml2RPConfig::FLD_NAME => 'moodle',
                SSO_Model_Saml2RPConfig::FLD_ENTITYID => 'https://localhost:8443/auth/saml2/sp/metadata.php',
                SSO_Model_Saml2RPConfig::FLD_ASSERTION_CONSUMER_SERVICE_BINDING => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                SSO_Model_Saml2RPConfig::FLD_ASSERTION_CONSUMER_SERVICE_LOCATION => 'https://localhost:8443/auth/saml2/sp/saml2-acs.php/localhost',
                SSO_Model_Saml2RPConfig::FLD_SINGLE_LOGOUT_SERVICE_LOCATION => 'https://localhost:8443/auth/saml2/sp/saml2-logout.php/localhost',
            ]),
        ]));

    }

    public function testSaml2RedirectRequestAlreadyLoggedIn()
    {
        $this->markTestSkipped('fails on gitlab, locally it works');
        
        $this->_createSAML2Config();

        $authNRequest = new \SAML2\AuthnRequest();
        ($issuer = new \SAML2\XML\saml\Issuer())->setValue('https://localhost:8443/auth/saml2/sp/metadata.php');
        $authNRequest->setIssuer($issuer);
        $msgStr = $authNRequest->toUnsignedXML();
        $msgStr = $msgStr->ownerDocument->saveXML($msgStr);
        $msgStr = gzdeflate($msgStr);
        $msgStr = base64_encode($msgStr);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = 'SAMLRequest='.urlencode($msgStr);
        $_GET['SAMLRequest'] = $msgStr;

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'https://unittest/shalala?SAMLRequest=' .
                urlencode($msgStr), 'GET'))
                ->withQueryParams([
                    'SAMLRequest' => $msgStr,
                ])
        );

        $response = SSO_Controller::publicSaml2RedirectRequest();
        $response->getBody()->rewind();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<input type="hidden" name="SAMLResponse"', $response->getBody()->getContents());

    }

    public function testOAuthGetLoginMask()
    {
        $relyingParty = SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'unittest',
            SSO_Model_RelyingParty::FLD_CONFIG_CLASS => SSO_Model_OAuthOIdRPConfig::class,
            SSO_Model_RelyingParty::FLD_CONFIG => new SSO_Model_OAuthOIdRPConfig([
                SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS   => ['https://unittest.test/uri'],
                SSO_Model_OAuthOIdRPConfig::FLD_SECRET          => 'unittest',
                SSO_Model_OAuthOIdRPConfig::FLD_IS_CONFIDENTIAL => true,
            ]),
        ]));

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'https://unittest/shalala?response_type=code' .
                '&scope=openid%20profile%20email' .
                '&client_id=' . urlencode($relyingParty->getId()) .
                '&state=af0ifjsldkj' .
                '&nonce=nonce' .
                '&redirect_uri=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0]), 'GET'))
            ->withQueryParams([
                'response_type' => 'code',
                'scope' => 'openid profile email',
                'client_id' => $relyingParty->getId(),
                'state' => 'af0ifjsldkj',
                'nonce' => 'nonce',
                'redirect_uri' => $relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0]
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

    public function testOAuthPostLoginMask()
    {
        $relyingParty = SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'unittest',
            SSO_Model_RelyingParty::FLD_CONFIG_CLASS => SSO_Model_OAuthOIdRPConfig::class,
            SSO_Model_RelyingParty::FLD_CONFIG => new SSO_Model_OAuthOIdRPConfig([
                SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS   => ['https://unittest.test/uri'],
                SSO_Model_OAuthOIdRPConfig::FLD_SECRET          => 'unittest',
                SSO_Model_OAuthOIdRPConfig::FLD_IS_CONFIDENTIAL => true,
            ]),
        ]));

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'https://unittest/shalala?response_type=code' .
                '&scope=openid%20profile%20email' .
                '&client_id=' . urlencode($relyingParty->getId()) .
                '&state=af0ifjsldkj' .
                '&nonce=nonce' .
                '&redirect_uri=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0]), 'POST'))
                ->withQueryParams([
                    'response_type' => 'code',
                    'scope' => 'openid profile email',
                    'client_id' => $relyingParty->getId(),
                    'state' => 'af0ifjsldkj',
                    'nonce' => 'nonce',
                    'redirect_uri' => $relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0],
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

    public function testOAuthAutoAuth()
    {
        $relyingParty = SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'unittest',
            SSO_Model_RelyingParty::FLD_CONFIG_CLASS => SSO_Model_OAuthOIdRPConfig::class,
            SSO_Model_RelyingParty::FLD_CONFIG => new SSO_Model_OAuthOIdRPConfig([
                SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS   => ['https://unittest.test/uri'],
                SSO_Model_OAuthOIdRPConfig::FLD_SECRET          => 'unittest',
                SSO_Model_OAuthOIdRPConfig::FLD_IS_CONFIDENTIAL => true,
            ]),
        ]));

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'https://unittest/shalala?response_type=code' .
                '&scope=openid%20profile%20email' .
                '&client_id=' . urlencode($relyingParty->getId()) .
                '&state=af0ifjsldkj' .
                '&nonce=nonce' .
                '&redirect_uri=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0]), 'GET'))
                ->withQueryParams([
                    'response_type' => 'code',
                    'scope' => 'openid profile email',
                    'client_id' => $relyingParty->getId(),
                    'state' => 'af0ifjsldkj',
                    'nonce' => 'nonce',
                    'redirect_uri' => $relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0]
                ])
        );

        $response = SSO_Controller::publicAuthorize();
        $this->assertSame(302, $response->getStatusCode());
        $header = $response->getHeader('Location');
        $this->assertIsArray($header);
        $this->assertCount(1, $header);
        $this->assertStringContainsString('&state=af0ifjsldkj', $header[0]);
        $this->assertStringStartsWith($relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0] . '?', $header[0]);
        $this->assertTrue((bool)preg_match('/code=([^&]+)/', $header[0], $m), 'can not pregmatch code in redirect url');
        // TODO test $m[1] against token api
    }
}
