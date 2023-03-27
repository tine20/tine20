<?php declare(strict_types=1);

/**
 * facade for RefreshTokenRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OAuth2_RefreshTokenRepository implements \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface
{
    public function getNewRefreshToken()
    {
        return new SSO_Facade_OAuth2_RefreshTokenEntity();
    }

    public function persistNewRefreshToken(\League\OAuth2\Server\Entities\RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $token = new SSO_Model_Token([
            SSO_Model_Token::FLD_TOKEN  => $refreshTokenEntity->getIdentifier(),
            SSO_Model_Token::FLD_TYPE   => SSO_Model_Token::TYPE_REFRESH,
        ]);

        SSO_Controller_Token::getInstance()->create($token);
    }

    protected function getFilterForToken($token): Tinebase_Model_Filter_FilterGroup
    {
        return Tinebase_Model_Filter_FilterGroup::getFilterForModel(SSO_Model_Token::class, [
            ['field' => SSO_Model_Token::FLD_TOKEN, 'operator' => 'equals', 'value' => $token],
            ['field' => SSO_Model_Token::FLD_TYPE,  'operator' => 'equals', 'value' => SSO_Model_Token::TYPE_REFRESH]
        ]);
    }

    public function revokeRefreshToken($tokenId)
    {
        SSO_Controller_Token::getInstance()->deleteByFilter($this->getFilterForToken($tokenId));
    }

    public function isRefreshTokenRevoked($tokenId)
    {
        try {
            if (null !== SSO_Controller_Token::getInstance()->search($this->getFilterForToken($tokenId))
                    ->getFirstRecord()) {
                return false;
            }
        } catch (Tinebase_Exception_NotFound $tenf) {}
        return true;
    }
}
