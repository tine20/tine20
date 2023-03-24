<?php declare(strict_types=1);
/**
 * facade for ClaimRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Idaas\OpenID\Repositories\ClaimRepositoryInterface;

class SSO_Facade_OpenIdConnect_ClaimRepository implements ClaimRepositoryInterface
{

    public function getClaimEntityByIdentifier($identifier, $type, $essential)
    {
        // TODO: Implement getClaimEntityByIdentifier() method.
    }

    public function getClaimsByScope(\League\OAuth2\Server\Entities\ScopeEntityInterface $scope): iterable
    {
        return [
            'sub',
            'name',
            'given_name',
            'family_name',
            'email',
            'groups',
        ];
    }

    public function claimsRequestToEntities(array $json = null)
    {
        // TODO: Implement claimsRequestToEntities() method.
    }
}
