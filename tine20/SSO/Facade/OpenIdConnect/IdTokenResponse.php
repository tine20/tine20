<?php declare(strict_types=1);

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;

/**
 * facade for IdTokenResponse
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OpenIdConnect_IdTokenResponse extends \OpenIDConnectServer\IdTokenResponse
{
    protected $nonce = null;

    public function setNonce(string $nonce)
    {
        $this->nonce = $nonce;
    }

    protected function getBuilder(AccessTokenEntityInterface $accessToken, UserEntityInterface $userEntity)
    {
        $builder = parent::getBuilder($accessToken, $userEntity)
            ->issuedBy(Tinebase_Core::getUrl(Tinebase_Core::GET_URL_NOPATH));
        if (null !== $this->nonce) {
            $builder->withClaim('nonce', $this->nonce);
        }

        return $builder;
    }
}
