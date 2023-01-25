<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Abstract MFA UserConfig Model
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_MFA_AbstractUserConfig extends Tinebase_Record_NewAbstract
    implements Tinebase_Auth_MFA_UserConfigInterface
{
    public function toFEArray(): array
    {
        return [];
    }

    public function getClientPasswordLength(): ?int
    {
        return null;
    }
}
