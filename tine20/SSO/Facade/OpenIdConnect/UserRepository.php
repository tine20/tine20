<?php declare(strict_types=1);
/**
 * facade for ClaimRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OpenIdConnect_UserRepository implements \Idaas\OpenID\Repositories\UserRepositoryInterface
{
    use \Idaas\OpenID\Repositories\UserRepositoryTrait;

    public function getUserEntityByUserCredentials($username, $password, $grantType, \League\OAuth2\Server\Entities\ClientEntityInterface $clientEntity)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__);
    }

    public function getAttributes(\League\OAuth2\Server\Entities\UserEntityInterface $userEntity, $claims, $scopes)
    {
        /** @var SSO_Facade_OAuth2_UserEntity $userEntity */
        $result = [];
        foreach ($claims as $claim) {
            switch ($claim) {
                case 'sub':
                    $result['sub'] = $userEntity->getTineUser()->accountEmailAddress;
                    break;
                case 'name':
                    $result['name'] = $userEntity->getTineUser()->accountFullName;
                    break;
                case 'given_name':
                    $result['given_name'] = $userEntity->getTineUser()->accountFirstName;
                    break;
                case 'family_name':
                    $result['family_name'] = $userEntity->getTineUser()->accountLastName;
                    break;
                case 'email':
                    $result['email'] = $userEntity->getTineUser()->accountEmailAddress;
                    break;
                case 'groups':
                    $result['groups'] = array_values(Tinebase_Group::getInstance()->getMultiple(
                        Tinebase_Group::getInstance()->getGroupMemberships($userEntity->getTineUser()->getId()))->name);
                    break;
            }
        }
        return $result;
    }

    public function getUserByIdentifier($identifier): ?\League\OAuth2\Server\Entities\UserEntityInterface
    {
        try {
            return new SSO_Facade_OAuth2_UserEntity(Tinebase_User::getInstance()->getUserById($identifier));
        } catch (Tinebase_Exception_NotFound $tenf) {
            return null;
        }
    }
}