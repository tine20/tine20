<?php declare(strict_types=1);

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

/**
 * facade for ClientRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OAuth2_ClientRepository implements ClientRepositoryInterface
{

    public function getClientEntity($clientIdentifier): SSO_Facade_OAuth2_ClientEntity
    {
        return new SSO_Facade_OAuth2_ClientEntity(SSO_Controller_RelyingParty::getInstance()->get($clientIdentifier));
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        /** @var SSO_Model_RelyingParty $relyingParty */
        $relyingParty = SSO_Controller_RelyingParty::getInstance()->get($clientIdentifier);

        // TODO fixme needs to use hashing
        if (!$relyingParty->validateSecret($clientSecret)) {
            return false;
        }

        // if ! $relyingParty->grants->has($grantType) throw new Exception(grant not allowed for relying party)

        return true;
    }
}
