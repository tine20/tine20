<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * SecondFactor Auth Facade
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
final class Tinebase_Auth_Webauthn
{
    public static function webAuthnRegister(?string $data = null, ?Tinebase_Model_FullUser $user = null): void
    {
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        $credentialSource = self::_getServer()->loadAndCheckAttestationResponse(
            $data ?: $request->getBody()->getContents(),
            self::getWebAuthnCreationOptions(false, $user),
            $request
        );

        (new Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository())->saveCredentialSource($credentialSource);
    }

    public static function webAuthnAuthenticate(?string $data = null): Tinebase_Model_FullUser
    {
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);

        /** @var \Webauthn\PublicKeyCredentialSource $result */
        $result = self::_getServer()->loadAndCheckAssertionResponse(
            $data ?: $request->getBody()->getContents(),
            self::getWebAuthnRequestOptions(),
            null,
            $request
        );

        return Tinebase_User::getInstance()->getFullUserById($result->getUserHandle());
    }

    public static function getWebAuthnCreationOptions(bool $createChallenge = false, ?Tinebase_Model_FullUser $user = null): \Webauthn\PublicKeyCredentialCreationOptions
    {
        if ($createChallenge) {
            if (null === $user) {
                $user = Tinebase_Core::getUser();
            }
            $credentialCreationOptions = self::_getServer()->generatePublicKeyCredentialCreationOptions(
                new \Webauthn\PublicKeyCredentialUserEntity(
                    $user->accountLoginName, $user->getId(), $user->accountDisplayName
                )
            );
            Tinebase_Session::getSessionNamespace(__CLASS__)->regchallenge = json_encode($credentialCreationOptions->jsonSerialize());
        } else {
            if (!($challenge = Tinebase_Session::getSessionNamespace(__CLASS__)->regchallenge)) {
                throw new Tinebase_Exception_Backend('no registration challenge found');
            }
            $credentialCreationOptions = \Webauthn\PublicKeyCredentialCreationOptions::createFromString($challenge);
        }

        return $credentialCreationOptions;
    }

    public static function getWebAuthnRequestOptions(?string $accountId = null): \Webauthn\PublicKeyCredentialRequestOptions
    {
        if (null === $accountId) {
            if (!($challenge = Tinebase_Session::getSessionNamespace(__CLASS__)->authchallenge)) {
                throw new Tinebase_Exception_Backend('no authentication challenge found');
            }
            $credentialRequestOptions = \Webauthn\PublicKeyCredentialRequestOptions::createFromString($challenge);
        } else {
            $user = Tinebase_User::getInstance()->getFullUserById($accountId);
            $credDescriptors = [];
            foreach ((new Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository())->findAllForUserEntity(
                new \Webauthn\PublicKeyCredentialUserEntity(
                    $user->accountLoginName, $user->getId(), $user->accountDisplayName
                )) as $val) {
                $credDescriptors[] = $val->getPublicKeyCredentialDescriptor();
            }
            $credentialRequestOptions = self::_getServer()->generatePublicKeyCredentialRequestOptions(
                // TODO .... to be discussed, needs to be configurable etc.
                \Webauthn\PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                $credDescriptors
            );

            Tinebase_Session::getSessionNamespace(__CLASS__)->authchallenge = json_encode($credentialRequestOptions->jsonSerialize());
        }

        return $credentialRequestOptions;
    }

    protected static function _getServer(): \Webauthn\Server
    {
        static $server;
        if (null === $server) {
            $server = new \Webauthn\Server(
            // TODO FIXME tine20 is probably not very good here, should be hostname or something ... url+path maybe even
                new \Webauthn\PublicKeyCredentialRpEntity('tine20'),
                new Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository());

            // TODO FIXME remove this! just for testing during implemenation!
            $server->setSecuredRelyingPartyId(['localhost']);
        }
        return $server;
    }
}
