<?php declare(strict_types=1);

/**
 * facade for IdentityRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OAuth2_UserEntity implements \League\OAuth2\Server\Entities\UserEntityInterface
{
    protected $user;

    public function __construct(Tinebase_Model_User $user)
    {
        $this->user = $user;
    }

    public function getIdentifier()
    {
        return $this->user->getId();
    }

    public function getTineUser(): Tinebase_Model_User
    {
        return $this->user;
    }
}
