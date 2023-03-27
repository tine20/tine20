<?php declare(strict_types=1);
/**
 * facade for AccessTokenEntity
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class SSO_Facade_OAuth2_AccessTokenEntity implements AccessTokenEntityInterface
{
    use AccessTokenTrait {
        AccessTokenTrait::initJwtConfiguration as parentInitJwtConfiguration;
        AccessTokenTrait::convertToJWT as parentConvertToJWT;
    }
    use TokenEntityTrait;
    use EntityTrait;

    protected $claims = [];

    /**
     * Initialise the JWT Configuration.
     */
    public function initJwtConfiguration()
    {
        $this->parentInitJwtConfiguration();
        $this->jwtConfiguration->setBuilderFactory(function() {
            return new Token\Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates());
        });
    }

    /**
     * Generate a JWT from the access token
     *
     * @return Token
     */
    private function convertToJWT()
    {
        return $this->jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new DateTimeImmutable())
            ->canOnlyBeUsedAfter(new DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo((string) $this->getUserIdentifier())
            ->withClaim('scopes', $this->getScopes())
            ->withClaim('claims', $this->getClaims())
            ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
    }

    public function setClaims(array $claims) {
        $this->claims = $claims;
    }

    public function getClaims(): array {
        return $this->claims;
    }
}
