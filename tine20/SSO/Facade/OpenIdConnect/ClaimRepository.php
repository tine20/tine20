<?php declare(strict_types=1);

use Idaas\OpenID\Repositories\ClaimRepositoryInterface;

/**
 * facade for ClaimRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OpenIdConnect_ClaimRepository implements ClaimRepositoryInterface
{

    public function getClaimEntityByIdentifier($identifier, $type, $essential)
    {
        // TODO: Implement getClaimEntityByIdentifier() method.
    }

    public function getClaimsByScope(\League\OAuth2\Server\Entities\ScopeEntityInterface $scope): iterable
    {
        return [];
    }

    public function claimsRequestToEntities(array $json = null)
    {
        // TODO: Implement claimsRequestToEntities() method.
    }
}
