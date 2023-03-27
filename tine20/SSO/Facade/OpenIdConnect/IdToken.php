<?php declare(strict_types=1);
/**
 * facade for IdToken
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Idaas\OpenID\Entities\IdToken;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use League\OAuth2\Server\CryptKey;

class SSO_Facade_OpenIdConnect_IdToken extends IdToken
{
    public function convertToJWT(CryptKey $privateKey)
    {
        $configuration = Configuration::forAsymmetricSigner(
        // You may use RSA or ECDSA and all their variations (256, 384, and 512)
            new Lcobucci\JWT\Signer\Rsa\Sha256(),
            LocalFileReference::file($privateKey->getKeyPath()),
            LocalFileReference::file($privateKey->getKeyPath())
        // You may also override the JOSE encoder/decoder if needed by providing extra arguments here
        );

        $token = $configuration->builder(ChainedFormatter::withUnixTimestampDates())
            ->withHeader('kid', method_exists($privateKey, 'getKid') ? $privateKey->getKid() : null)
            ->issuedBy($this->getIssuer())
            ->identifiedBy($this->getSubject())
            ->permittedFor($this->getAudience())
            ->relatedTo($this->getSubject())
            ->expiresAt(DateTimeImmutable::createFromMutable($this->getExpiration()))
            ->issuedAt($this->getIat())
            ->withClaim('auth_time', $this->getAuthTime()->getTimestamp())
            ->withClaim('nonce', $this->getNonce());

        foreach ($this->extra as $key => $value) {
            $token->withClaim($key, $value);
        }

        return $token->getToken($configuration->signer(), $configuration->signingKey())->toString();
    }
}