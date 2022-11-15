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
 * User Pin SecondFactor Auth Adapter
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_MFA_PinAdapter implements Tinebase_Auth_MFA_AdapterInterface
{
    protected $_mfaId;

    public function __construct(Tinebase_Record_Interface $_config, string $id)
    {
        $this->_mfaId = $id;
    }

    public function getClientPasswordLength(): ?int
    {
        return null;
    }

    public function sendOut(Tinebase_Model_MFA_UserConfig $_userCfg): bool
    {
        return true;
    }

    public function validate($_data, Tinebase_Model_MFA_UserConfig $_userCfg): bool
    {
        if ($_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG} instanceof Tinebase_Model_MFA_PinUserConfig &&
                $_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}->validate($_data)) {
            return true;
        }
        return false;
    }
}
