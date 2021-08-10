<?php declare(strict_types=1);

/**
 * facade for AuthCodeEntity
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OAuth2_AuthCodeEntity implements \League\OAuth2\Server\Entities\AuthCodeEntityInterface
{
    use \League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
    use \League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
    use \League\OAuth2\Server\Entities\Traits\EntityTrait;
}
