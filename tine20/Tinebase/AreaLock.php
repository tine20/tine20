<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Adapter
 *
 * - handles locking/unlocking of certain "areas" (could be login, apps, data safe, ...)
 * - areas can be locked with Tinebase_Auth_AreaLock_*
 * - @todo add more doc
 *
 * @package     Tinebase
 * @subpackage  Adapter
 */
class Tinebase_AreaLock implements Tinebase_Controller_Interface
{
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_AreaLock
     */
    private static $_instance = NULL;

    const AREALOCK_VALIDITY_SESSION_NAMESPACE = 'areaLockValidity';

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return Tinebase_AreaLock
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_AreaLock();
        }

        return self::$_instance;
    }

    /**
     * destroy instance of this class
     */
    public static function destroyInstance()
    {
        self::$_instance = NULL;
    }

    /**
     * constructor
     */
    private function __construct()
    {
    }

    /**
     * returns area lock status
     *
     * @todo implement or remove
     */
    public static function getStatus()
    {
        $status = [
            'active' => false,
            'problems' => [],
        ];
        //$config = Tinebase_Config::getInstance()->get(Tinebase_Config::AREA_LOCKS);
        // @todo check configs + backends

        return $status;
    }

    /**
     * @param $area
     * @return Tinebase_Model_AreaLockState
     */
    public function lock($area)
    {
        if ($this->_hasValidAuth($area)) {
            $this->_resetValidAuth($area);
        }

        return new Tinebase_Model_AreaLockState([
            'area' => $area,
            'expires' => new Tinebase_DateTime('1970-01-01')
        ]);
    }

    /**
     * @param $area
     * @param $password
     * @return Tinebase_Model_AreaLockState
     *
     * @todo allow "non-authentication" providers?
     */
    public function unlock($area, $password)
    {
        $user = Tinebase_Core::getUser();
        $areaConfig = $this->_getAreaConfig($area);
        $authProvider = Tinebase_Auth_Factory::factory($areaConfig->provider, $areaConfig->provider_config);
        $authProvider->setIdentity($user->accountLoginName)->setCredential($password);
        $authResult = $authProvider->authenticate();

        $expires = $authResult->isValid()
            ? $this->_saveValidAuth($area, $areaConfig)
            : new Tinebase_DateTime('1970-01-01');

        return new Tinebase_Model_AreaLockState([
            'area' => $area,
            'expires' => $expires
        ]);
    }

    /**
     * @param string $area
     * @return Tinebase_Model_AreaLockConfig
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getAreaConfig($area)
    {
        $areaConfigs = Tinebase_Config::getInstance()->get(Tinebase_Config::AREA_LOCKS);
        $areaConfig = $areaConfigs && $areaConfigs->records
            ? $areaConfigs->records->filter('area', $area)->getFirstRecord()
            : null;
        if (!$areaConfig) {
            throw new Tinebase_Exception_NotFound('config for area ' . $area . ' not found');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " AreaLockConfig: " . print_r($areaConfig->toArray(), true));

        return $areaConfig;
    }

    /**
     * @param string $area
     * @return bool
     */
    public function hasLock($area)
    {
        try {
            $this->_getAreaConfig($area);
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }

        return true;
    }

    /**
     * @param string $area
     * @return bool
     */
    public function isLocked($area)
    {
        return !$this->_hasValidAuth($area);
    }

    /**
     * @todo implement
     *
     * @param $area
     * @return bool
     */
    public function extendLock($area)
    {
//        if (Tinebase_Auth_SecondFactor_Abstract::hasValidSecondFactor()) {
//            Tinebase_Auth_SecondFactor_Abstract::saveValidSecondFactor();
//            $result = true;
//        } else {
//            $result = false;
//        }

        throw new Tinebase_Exception_NotImplemented('TODO implement');

        return false;
    }

    /**
     * @param $area
     * @return Tinebase_Model_AreaLockState
     */
    public function getState($area)
    {
        $expires = $this->_getAuthValidityFromSession($area);

        return new Tinebase_Model_AreaLockState([
            'area' => $area,
            'expires' => $expires ? $expires : new Tinebase_DateTime('1970-01-01')
        ]);
    }

    /**
     * @param string $area
     * @param Tinebase_Model_AreaLockConfig $config
     * @return Tinebase_DateTime
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _saveValidAuth($area, Tinebase_Model_AreaLockConfig $config)
    {
        switch ($config->validity) {
            case Tinebase_Model_AreaLockConfig::VALIDITY_SESSION:
                $valid = new Tinebase_DateTime('2150-01-01');
                break;
            case Tinebase_Model_AreaLockConfig::VALIDITY_LIFETIME:
                $lifetimeSeconds = $config->lifetime;
                $valid = Tinebase_DateTime::now()->addSecond($lifetimeSeconds ? $lifetimeSeconds : 15 * 60);
                break;
            case Tinebase_Model_AreaLockConfig::VALIDITY_PRESENCE:
            case Tinebase_Model_AreaLockConfig::VALIDITY_ONCE:
            case Tinebase_Model_AreaLockConfig::VALIDITY_DEFINEDBYPROVIDER:
                // @todo add support
                throw new Tinebase_Exception_InvalidArgument('validity ' . $config->validity . ' not supported yet');
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('validity ' . $config->validity . ' not supported');
        }
#
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' saveValidAreaLock until ' . $valid->toString() . ' (' . $config->validity . ')');

        Tinebase_Session::getSessionNamespace()->{self::AREALOCK_VALIDITY_SESSION_NAMESPACE}[$area] = $valid->toString();

        return $valid;
    }

    /**
     * @param string $area
     * @return bool
     * @throws Exception
     * @throws Zend_Session_Exception
     */
    protected function _hasValidAuth($area)
    {
        if (!Tinebase_Session::isStarted()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' No session started to check auth in session');
            }
            return true;
        }
        if (!Tinebase_Session::getSessionEnabled()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Session not enabled to check auth in session');
            }
            return true;
        }

        if ($validUntil = $this->_getAuthValidityFromSession($area)) {
            return Tinebase_DateTime::now()->isEarlier($validUntil);
        } else {
            return false;
        }
    }

    /**
     * @param $area
     * @return bool|Tinebase_DateTime
     */
    protected function _getAuthValidityFromSession($area)
    {
        $areaLocksInSession = Tinebase_Session::getSessionNamespace()->{self::AREALOCK_VALIDITY_SESSION_NAMESPACE};
        if (!isset($areaLocksInSession[$area])) {
            return false;
        }

        $currentValidUntil = $areaLocksInSession[$area];
        if (is_string($currentValidUntil)) {
            return new Tinebase_DateTime($currentValidUntil);
        }

        return false;
    }

    /**
     * @param string $area
     */
    protected function _resetValidAuth($area)
    {
        $areaLocksInSession = Tinebase_Session::getSessionNamespace()->{self::AREALOCK_VALIDITY_SESSION_NAMESPACE};
        if (isset($areaLocksInSession[$area])) {
            unset($areaLocksInSession[$area]);
            Tinebase_Session::getSessionNamespace()->{self::AREALOCK_VALIDITY_SESSION_NAMESPACE} = $areaLocksInSession;
        }
    }
}
