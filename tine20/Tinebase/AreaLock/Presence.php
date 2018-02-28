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
 * area locks presence backend
 *
 * @package     Tinebase
 * @subpackage  AreaLock
 */
class Tinebase_AreaLock_Presence implements Tinebase_AreaLock_Interface
{
    /**
     * @var null|Tinebase_Model_AreaLockConfig
     */
    protected $_config = null;

    /**
     * Tinebase_AreaLock_Presence constructor.
     * @param Tinebase_Model_AreaLockConfig $config
     */
    public function __construct(Tinebase_Model_AreaLockConfig $config)
    {
        if (! $config->lifetime) {
            // set lifetime default
            $config->lifetime = 15;
        }
        $this->_config = $config;
    }

    /**
     * @param string $area
     * @return Tinebase_DateTime
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function saveValidAuth($area)
    {
        $lifetimeSeconds = $this->_config->lifetime * 60;
        $validity = Tinebase_DateTime::now()->addSecond($lifetimeSeconds);
        Tinebase_Presence::getInstance()->setPresence(__CLASS__ . '#' . $area, $lifetimeSeconds);
        return $validity;
    }

    /**
     * @param $area
     * @return bool
     */
    public function hasValidAuth($area)
    {
        if ($validUntil = $this->getAuthValidity($area)) {
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
        $lastPresence = Tinebase_Presence::getInstance()->getLastPresence(__CLASS__ . '#' . $area);
        $lifetimeSeconds = $this->_config->lifetime * 60;
        return $lastPresence ? $lastPresence->addSecond($lifetimeSeconds) : false;
    }

    /**
     * @param string $area
     */
    public function resetValidAuth($area)
    {
        Tinebase_Presence::getInstance()->resetPresence(__CLASS__ . '#' . $area);
    }
}
