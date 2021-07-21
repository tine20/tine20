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
 * area locks presence backend
 *
 * @package     Tinebase
 * @subpackage  AreaLock
 */
class Tinebase_AreaLock_Presence implements Tinebase_AreaLock_Interface
{
    /**
     * @var Tinebase_Model_AreaLockConfig
     */
    protected $_config;

    public function __construct(Tinebase_Model_AreaLockConfig $config)
    {
        if (! $config->lifetime) {
            // set lifetime default
            $config->lifetime = 15;
        }
        $this->_config = $config;
    }

    public function saveValidAuth(): Tinebase_DateTime
    {
        $lifetimeSeconds = $this->_config->lifetime * 60;
        $validity = Tinebase_DateTime::now()->addSecond($lifetimeSeconds);
        Tinebase_Presence::getInstance()->setPresence($this->_getPresenceKey(), $lifetimeSeconds);
        return $validity;
    }

    public function hasValidAuth(): bool
    {
        if ($validUntil = $this->getAuthValidity()) {
            return Tinebase_DateTime::now()->isEarlier($validUntil);
        } else {
            return false;
        }
    }

    public function getAuthValidity(): ?Tinebase_DateTime
    {
        $lastPresence = Tinebase_Presence::getInstance()->getLastPresence($this->_getPresenceKey());
        return $lastPresence ? $lastPresence->addSecond($this->_config->lifetime * 60) : null;
    }

    public function resetValidAuth(): void
    {
        Tinebase_Presence::getInstance()->resetPresence($this->_getPresenceKey());
    }

    protected function _getPresenceKey(): string
    {
        return __CLASS__ . '#' . $this->_config->getKey();
    }
}
