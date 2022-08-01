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
 * SecondFactor Auth Facade
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
final class Tinebase_Auth_MFA
{
    /**
     * the singleton pattern
     *
     * @return self
     */
    public static function getInstance(string $mfaId): self
    {
        if (!isset(self::$_instances[$mfaId])) {
            $mfas = Tinebase_Config::getInstance()->{Tinebase_Config::MFA};
            if (!$mfas->records || ! ($config = $mfas->records->getById($mfaId))) {
                throw new Tinebase_Exception_Backend(self::class . ' with id ' . $mfaId . ' not found');
            }
            if (is_array($config->{Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG})) {
                $mfas->records->runConvertToRecord();
            }
            self::$_instances[$mfaId] = new self($config);
        }

        return self::$_instances[$mfaId];
    }

    public static function destroyInstances(): void
    {
        self::$_instances = [];
    }

    public function sendOut(Tinebase_Model_MFA_UserConfig $_userCfg): bool
    {
        return $this->_adapter->sendOut($_userCfg);
    }

    public function validate($_data, Tinebase_Model_MFA_UserConfig $_userCfg): bool
    {
        return $this->_adapter->validate($_data, $_userCfg);
    }

    public function getAdapter(): Tinebase_Auth_MFA_AdapterInterface
    {
        return $this->_adapter;
    }

    public static function getAccountsMFAUserConfig(string $_userMfaId, Tinebase_Model_FullUser $_account): ?Tinebase_Model_MFA_UserConfig
    {
        if (!$_account->mfa_configs) {
            return null;
        }
        return $_account->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_ID, $_userMfaId);
    }

    public function persistUserConfig(?string $_accountId, Closure $cb): bool
    {
        if ($this->_persistUserConfigDelegator) {
            return ($this->_persistUserConfigDelegator)($cb);
        } else {
            $user = Tinebase_User::getInstance()->getUserById($_accountId, Tinebase_Model_FullUser::class);
            if (!$cb($user)) {
                return false;
            }
            Tinebase_User::getInstance()->updateUserInSqlBackend($user);
        }

        return true;
    }

    public function setPersistUserConfigDelegator(?Closure $fun)
    {
        $this->_persistUserConfigDelegator = $fun;
    }

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct(Tinebase_Model_MFA_Config $config)
    {
        $this->_adapter = new $config->{Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS}(
            $config->{Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG},
            $config->getId()
        );
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() {}

    /**
     * @var Tinebase_Auth_MFA_AdapterInterface
     */
    private $_adapter;

    /**
     * holds the instances of the singleton
     *
     * @var array<self>
     */
    private static $_instances = [];

    protected $_persistUserConfigDelegator = null;
}
