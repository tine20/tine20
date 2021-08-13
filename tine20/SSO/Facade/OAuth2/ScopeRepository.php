<?php declare(strict_types=1);

/**
 * facade for ScopeRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OAuth2_ScopeRepository implements League\OAuth2\Server\Repositories\ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier($identifier)
    {
        $scope = new SSO_Facade_OAuth2_ScopeEntity();
        $scope->setIdentifier($identifier);
        return $scope;
    }

    public function finalizeScopes(array $scopes, $grantType, \League\OAuth2\Server\Entities\ClientEntityInterface $clientEntity, $userIdentifier = null)
    {
        return $scopes;
    }
}
