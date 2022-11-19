<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * SecondFactor Auth Adapter Interface
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
interface Tinebase_Auth_MFA_AdapterInterface
{
    public function __construct(Tinebase_Record_Interface $_config, string $id);
    public function sendOut(Tinebase_Model_MFA_UserConfig $_userCfg): bool;
    public function validate($_data, Tinebase_Model_MFA_UserConfig $_userCfg): bool;
    public function getClientPasswordLength(): ?int;
}
