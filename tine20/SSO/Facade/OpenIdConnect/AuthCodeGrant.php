<?php declare(strict_types=1);
/**
 * facade for AuthCodeGrant
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Idaas\OpenID\Grant\AuthCodeGrant;
use Idaas\OpenID\ResponseTypes\BearerTokenResponse;
use Idaas\OpenID\SessionInformation;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

class SSO_Facade_OpenIdConnect_AuthCodeGrant extends AuthCodeGrant
{
    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        \DateInterval $accessTokenTTL
    ) {
        /**
         * @var BearerTokenResponse $result
         */
        $result = parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);

        $encryptedAuthCode = $this->getRequestParameter('code', $request, null);
        $authCodePayload = json_decode($this->decrypt($encryptedAuthCode));

        if ($authCodePayload->claims) {
            $authCodePayload->claims = (array) $authCodePayload->claims;
        }

        $idToken = new SSO_Facade_OpenIdConnect_IdToken();
        $idToken->setIssuer($this->issuer);
        $idToken->setSubject($authCodePayload->user_id);
        $idToken->setAudience($authCodePayload->client_id);
        $idToken->setExpiration((new \DateTime())->add($this->idTokenTTL));
        $idToken->setIat(new \DateTimeImmutable());

        $idToken->setAuthTime(new \DateTime('@' . $authCodePayload->auth_time));
        $idToken->setNonce($authCodePayload->nonce);

        if ($authCodePayload->claims) {
            $accessToken = $result->getAccessToken();

            $this->accessTokenRepository->storeClaims($accessToken, $authCodePayload->claims);
        }

        // TODO: populate idToken with claims ...
        /**
         * @var \Idaas\OpenID\SessionInformation
         */
        $sessionInformation = SessionInformation::fromJSON($authCodePayload->sessionInformation);

        $idToken->setAcr($sessionInformation->getAcr());
        $idToken->setAmr($sessionInformation->getAmr());
        $idToken->setAzp($sessionInformation->getAzp());

        $result->setIdToken($idToken);

        return $result;
    }
}