<?php declare(strict_types=1);

/**
 * facade for IdentityRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 *  * FIXME Reflection error: lass OpenIDConnectServer\Repositories\IdentityProviderInterface not found.
 */

class SSO_Facade_OpenIdConnect_IdentityRepository // implements \OpenIDConnectServer\Repositories\IdentityProviderInterface
{

    public function getUserEntityByIdentifier($identifier)
    {
        return new SSO_Facade_OAuth2_UserEntity(Tinebase_User::getInstance()->getUserById($identifier));
    }
}
