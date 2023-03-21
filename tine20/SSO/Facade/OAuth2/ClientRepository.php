<?php declare(strict_types=1);
/**
 * facade for ClientRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class SSO_Facade_OAuth2_ClientRepository implements ClientRepositoryInterface
{

    public function getClientEntity($clientIdentifier): ?SSO_Facade_OAuth2_ClientEntity
    {
        $rp = SSO_Controller_RelyingParty::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(SSO_Model_RelyingParty::class, [
                ['field' => SSO_Model_RelyingParty::FLD_NAME, 'operator' => 'equals', 'value' => $clientIdentifier],
                ['field' => SSO_Model_RelyingParty::FLD_CONFIG_CLASS, 'operator' => 'equals', 'value' =>
                    SSO_Model_OAuthOIdRPConfig::class],
            ]))->getFirstRecord();
        return $rp ? new SSO_Facade_OAuth2_ClientEntity($rp) : null;
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        /** @var SSO_Model_RelyingParty $relyingParty */
        $relyingParty = SSO_Controller_RelyingParty::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(SSO_Model_RelyingParty::class, [
                ['field' => SSO_Model_RelyingParty::FLD_NAME, 'operator' => 'equals', 'value' => $clientIdentifier],
                ['field' => SSO_Model_RelyingParty::FLD_CONFIG_CLASS, 'operator' => 'equals', 'value' =>
                    SSO_Model_OAuthOIdRPConfig::class],
            ]))->getFirstRecord();

        // TODO fixme needs to use hashing
        if (!$relyingParty || !$relyingParty->validateSecret($clientSecret)) {
            return false;
        }

        // if ! $relyingParty->grants->has($grantType) throw new Exception(grant not allowed for relying party)

        return true;
    }
}
