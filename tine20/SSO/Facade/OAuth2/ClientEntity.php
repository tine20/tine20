<?php declare(strict_types=1);

/**
 * facade for ClientEntity
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OAuth2_ClientEntity implements League\OAuth2\Server\Entities\ClientEntityInterface
{
    protected $relyingParty;

    public function __construct(SSO_Model_RelyingParty $relyingParty)
    {
        $this->relyingParty = $relyingParty;
    }

    public function getIdentifier(): string
    {
        return $this->relyingParty->{SSO_Model_RelyingParty::FLD_NAME};
    }

    public function getName(): string
    {
        return $this->relyingParty->{SSO_Model_RelyingParty::FLD_NAME};
    }

    public function getRedirectUri(): array
    {
        return $this->relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS};
    }

    public function isConfidential(): bool
    {
        return (bool)$this->relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_IS_CONFIDENTIAL};
    }
}
