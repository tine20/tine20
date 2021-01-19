<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @var Tinebase_Model_AreaLockConfig
     */
    protected $_config;

    public function __construct(Tinebase_Model_AreaLockConfig $config)
    {
        $this->_config = $config;
    }

    public function saveValidAuth(): Tinebase_DateTime
    {
        switch (strtolower($this->_config->validity)) {
            case Tinebase_Model_AreaLockConfig::VALIDITY_SESSION:
                $sessionValidity = new Tinebase_DateTime('2150-01-01');
                break;
            case Tinebase_Model_AreaLockConfig::VALIDITY_LIFETIME:
                $lifetimeMinutes = $this->_config->lifetime;
                $lifetimeSeconds = $lifetimeMinutes ? $lifetimeMinutes * 60  : 15 * 60;
                $sessionValidity = Tinebase_DateTime::now()->addSecond($lifetimeSeconds);
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('validity ' . $this->_config->validity . ' not supported');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' saveValidAreaLock until ' . $sessionValidity->toString() . ' (' . $this->_config->validity . ')');
        Tinebase_Session::getSessionNamespace()->{self::AREALOCK_VALIDITY_SESSION_NAMESPACE}[$this->_config->getKey()] =
            $sessionValidity->toString();

        return $sessionValidity;
    }

    public function hasValidAuth(): bool
    {
        if (!Tinebase_Session::isStarted()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' No session started to check auth in session');
            }
            return false;
        }
        if (!Tinebase_Session::getSessionEnabled()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Session not enabled to check auth in session');
            }
            return false;
        }

        if ($validUntil = $this->getAuthValidity()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " valid until: " . $validUntil . ' now: ' . Tinebase_DateTime::now());

            return Tinebase_DateTime::now()->isEarlier($validUntil);
        } else {
            return false;
        }
    }

    public function getAuthValidity(): ?Tinebase_DateTime
    {
        $areaLocksInSession = Tinebase_Session::getSessionNamespace()->{self::AREALOCK_VALIDITY_SESSION_NAMESPACE};
        $key = $this->_config->getKey();
        if (!isset($areaLocksInSession[$key])) {
            return null;
        }

        $currentValidUntil = $areaLocksInSession[$key];
        if (is_string($currentValidUntil)) {
            return new Tinebase_DateTime($currentValidUntil);
        }

        return null;
    }

    public function resetValidAuth(): void
    {
        $areaLocksInSession = Tinebase_Session::getSessionNamespace()->{self::AREALOCK_VALIDITY_SESSION_NAMESPACE};
        $key = $this->_config->getKey();
        if (isset($areaLocksInSession[$key])) {
            unset($areaLocksInSession[$key]);
            Tinebase_Session::getSessionNamespace()->{self::AREALOCK_VALIDITY_SESSION_NAMESPACE} = $areaLocksInSession;
        }
    }
}
