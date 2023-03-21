<?php declare(strict_types=1);
/**
 * facade for AccessTokenRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use League\OAuth2\Server\Entities\ScopeEntityInterface;

class SSO_Facade_OAuth2_AccessTokenRepository implements \Idaas\OpenID\Repositories\AccessTokenRepositoryInterface
{
    public function getNewToken(\League\OAuth2\Server\Entities\ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): \League\OAuth2\Server\Entities\AccessTokenEntityInterface
    {
        $token = new SSO_Facade_OAuth2_AccessTokenEntity();
        $token->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $token->addScope($scope);
        }
        $token->setUserIdentifier($userIdentifier);

        return $token;
    }

    public function persistNewAccessToken(\League\OAuth2\Server\Entities\AccessTokenEntityInterface $accessTokenEntity): void
    {
        $scopes = [];
        /** @var ScopeEntityInterface $scope */
        foreach ($accessTokenEntity->getScopes() as $scope) {
            $scopes[] = $scope->getIdentifier();
        }
        $token = new SSO_Model_Token([
            SSO_Model_Token::FLD_TOKEN  => $accessTokenEntity->getIdentifier(),
            SSO_Model_Token::FLD_TYPE   => SSO_Model_Token::TYPE_ACCESS,
            SSO_Model_Token::FLD_DATA   => [
                'client' => $accessTokenEntity->getClient()->getIdentifier(),
                'scopes' => $scopes,
                'userIdentifier' => $accessTokenEntity->getUserIdentifier(),
            ],
        ]);

        SSO_Controller_Token::getInstance()->create($token);
    }

    protected function getFilterForToken($token): Tinebase_Model_Filter_FilterGroup
    {
        return Tinebase_Model_Filter_FilterGroup::getFilterForModel(SSO_Model_Token::class, [
            ['field' => SSO_Model_Token::FLD_TOKEN, 'operator' => 'equals', 'value' => $token],
            ['field' => SSO_Model_Token::FLD_TYPE,  'operator' => 'equals', 'value' => SSO_Model_Token::TYPE_ACCESS]
        ]);
    }

    public function revokeAccessToken($tokenId)
    {
        SSO_Controller_Token::getInstance()->deleteByFilter($this->getFilterForToken($tokenId));
    }

    public function isAccessTokenRevoked($tokenId)
    {
        try {
            if (null !== SSO_Controller_Token::getInstance()->search($this->getFilterForToken($tokenId))
                    ->getFirstRecord()) {
                return false;
            }
        } catch (Tinebase_Exception_NotFound $tenf) {}
        return true;
    }

    public function storeClaims(\League\OAuth2\Server\Entities\AccessTokenEntityInterface $token, array $claims)
    {
        $ctrl = SSO_Controller_Token::getInstance();

        /** @var SSO_Model_Token $ssoToken */
        $ssoToken = $ctrl->search($this->getFilterForToken($token->getIdentifier()))->getFirstRecord();
        $ssoToken->xprops(SSO_Model_Token::FLD_DATA)['claims'] = array_unique($claims);
        $ctrl->update($ssoToken);
    }

    public function getAccessToken($tokenId)
    {
        /** @var SSO_Model_Token $ssoToken */
        $ssoToken = SSO_Controller_Token::getInstance()->search($this->getFilterForToken($tokenId))->getFirstRecord();
        $token = new SSO_Facade_OAuth2_AccessTokenEntity();
        $token->setClient((new SSO_Facade_OAuth2_ClientRepository())->getClientEntity($ssoToken->xprops('data')['client']));
        foreach ($ssoToken->xprops('data')['scopes'] as $scope) {
            $token->addScope((new SSO_Facade_OAuth2_ScopeRepository)->getScopeEntityByIdentifier($scope));
        }
        $token->setUserIdentifier($ssoToken->xprops('data')['userIdentifier']);

        return $token;
    }
}
