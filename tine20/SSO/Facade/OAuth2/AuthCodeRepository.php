<?php declare(strict_types=1);

/**
 * facade for AuthCodeRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OAuth2_AuthCodeRepository implements \League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface
{
    public function getNewAuthCode()
    {
        return new SSO_Facade_OAuth2_AuthCodeEntity();
    }

    public function persistNewAuthCode(\League\OAuth2\Server\Entities\AuthCodeEntityInterface $authCodeEntity)
    {
        $token = new SSO_Model_Token([
            'token' => $authCodeEntity->getIdentifier(),
            SSO_Model_Token::FLD_TYPE => SSO_Model_Token::TYPE_AUTH,
        ]);

        SSO_Controller_Token::getInstance()->create($token);
    }

    protected function getFilterForToken($token): Tinebase_Model_Filter_FilterGroup
    {
        return Tinebase_Model_Filter_FilterGroup::getFilterForModel(SSO_Model_Token::class, [
            ['field' => SSO_Model_Token::FLD_TOKEN, 'operator' => 'equals', 'value' => $token],
            ['field' => SSO_Model_Token::FLD_TYPE,  'operator' => 'equals', 'value' => SSO_Model_Token::TYPE_AUTH]
        ]);
    }

    public function revokeAuthCode($codeId)
    {
        SSO_Controller_Token::getInstance()->deleteByFilter($this->getFilterForToken($codeId));
    }

    public function isAuthCodeRevoked($codeId)
    {
        try {
            if (null !== SSO_Controller_Token::getInstance()->search($this->getFilterForToken($codeId))
                    ->getFirstRecord()) {
                return false;
            }
        } catch (Tinebase_Exception_NotFound $tenf) {}
        return true;
    }
}
