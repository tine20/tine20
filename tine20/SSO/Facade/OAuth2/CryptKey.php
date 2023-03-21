<?php declare(strict_types=1);
/**
 * facade for ClientRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OAuth2_CryptKey extends \League\OAuth2\Server\CryptKey
{
    protected $kid;

    public function __construct($keyPath, string $kid, $passPhrase = null, $keyPermissionsCheck = true)
    {
        $this->kid = $kid;
        parent::__construct($keyPath, $passPhrase, $keyPermissionsCheck);
    }

    public function getKid(): string
    {
        return $this->kid;
    }
}