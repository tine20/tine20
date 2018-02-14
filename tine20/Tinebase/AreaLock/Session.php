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
 * area locks session backend
 *
 * @package     Tinebase
 * @subpackage  AreaLock
 */
class Tinebase_AreaLock_Session implements Tinebase_AreaLock_Interface
{
    /**
     * session namespace
     */
    const AREALOCK_VALIDITY_SESSION_NAMESPACE = 'areaLockValidity';

    /**
     * @var null|Tinebase_Model_AreaLockConfig
     */
    protected $_config = null;

    /**
     * Tinebase_AreaLock_Session constructor.
     * @param Tinebase_Model_AreaLockConfig $config
     */
    public function __construct(Tinebase_Model_AreaLockConfig $config)
    {
        $this->_config = $config;
    }

    /**
     * @param string $area
     * @return Tinebase_DateTime
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function saveValidAuth($area)
    {
        switch (strtolower($this->_config->validity)) {
            case Tinebase_Model_AreaLockConfig::VALIDITY_SESSION:
                $sessionValidity = new Tinebase_DateTime('2150-01-01');
                break;
            case Tinebase_Model_AreaLockConfig::VALIDITY_LIFETIME:
                $lifetimeSeconds = $this->_config->lifetime;
                $sessionValidity = Tinebase_DateTime::now()->addSecond($lifetimeSeconds ? $lifetimeSeconds : 15 * 60);
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('validity ' . $this->_config->validity . ' not supported');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' saveValidAreaLock until ' . $sessionValidity->toString() . ' (' . $this->_config->validity . ')');
        Tinebase_Session::getSessionNamespace()->{self::AREALOCK_VALIDITY_SESSION_NAMESPACE}[$area] = $sessionValidity->toString();

        return $sessionValidity;
    }

    /**
     * @param $area
     * @return bool
     */
    public function hasValidAuth($area)
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

        if ($validUntil = $this->getAuthValidity($area)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " valid until: " . $validUntil . ' now: ' . Tinebase_DateTime::now());

            return Tinebase_DateTime::now()->isEarlier($validUntil);
        } else {
            return false;
        }
    }

    /**
     * @param $area
     * @return bool|Tinebase_DateTime
     */
    public function getAuthValidity($area)
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
    public function resetValidAuth($area)
    {
        $areaLocksInSession = Tinebase_Session::getSessionNamespace()->{self::AREALOCK_VALIDITY_SESSION_NAMESPACE};
        if (isset($areaLocksInSession[$area])) {
            unset($areaLocksInSession[$area]);
            Tinebase_Session::getSessionNamespace()->{self::AREALOCK_VALIDITY_SESSION_NAMESPACE} = $areaLocksInSession;
        }
    }
}
