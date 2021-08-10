<?php declare(strict_types=1);

/**
 * facade for AccessTokenEntity
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OAuth2_AccessTokenEntity implements \League\OAuth2\Server\Entities\AccessTokenEntityInterface
{
    use \League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
    use \League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
    use \League\OAuth2\Server\Entities\Traits\EntityTrait;
}
