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
 * AreaLock facility
 *
 * - handles locking/unlocking of certain "areas" (could be login, apps, data safe, ...)
 * - areas can be locked with Tinebase_Auth_AreaLock_*
 * - @todo add more doc
 *
 * @package     Tinebase
 * @subpackage  AreaLock
 */
class Tinebase_AreaLock implements Tinebase_Controller_Interface
{
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_AreaLock
     */
    private static $_instance = NULL;

    protected $_hasLocks = [];

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
     */
    public static function getStatus()
    {
        $status = [
            'active' => false,
            'problems' => [],
        ];

        $areaConfigs = Tinebase_Config::getInstance()->get(Tinebase_Config::AREA_LOCKS);
        $status['active'] = $areaConfigs && $areaConfigs->records && count($areaConfigs->records) > 0;

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
            $this->resetValidAuth($area);
        }

        return new Tinebase_Model_AreaLockState([
            'area' => $area,
            'expires' => new Tinebase_DateTime('1970-01-01')
        ]);
    }

    /**
     * @param string $area
     * @param string $password
     * @param string $identity
     * @return Tinebase_Model_AreaLockState
     * @throws Tinebase_Exception_AreaUnlockFailed
     *
     * @todo allow "non-authentication" providers?
     */
    public function unlock($area, $password, $identity = null)
    {
        $areaConfig = $this->getAreaConfig($area);
        $authProvider = Tinebase_Auth_Factory::factory($areaConfig->provider, $areaConfig->provider_config);

        if (! $identity) {
            $user = Tinebase_Core::getUser();
            $identity = $user->accountLoginName;
        }
        $authProvider->setIdentity($identity)
            ->setCredential($password);
        $authResult = $authProvider->authenticate();

        if ($authResult->isValid()) {
            $expires =$this->_saveValidAuth($area, $areaConfig);
        } else {
            $teauf = new Tinebase_Exception_AreaUnlockFailed('Invalid authentication: ' . $authResult->getCode());
            $teauf->setArea($area);
            throw $teauf;
        }

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
    public function getAreaConfig($area)
    {
        $areaConfigs = Tinebase_Config::getInstance()->get(Tinebase_Config::AREA_LOCKS);
        $areaConfig = $areaConfigs && $areaConfigs->records
            ? $areaConfigs->records->filter('area', $area)->getFirstRecord()
            : null;
        if (!$areaConfig) {
            throw new Tinebase_Exception_NotFound('config for area ' . $area . ' not found');
        }

        return $areaConfig;
    }

    /**
     * @param string $area
     * @return bool
     */
    public function hasLock($area)
    {
        if (in_array($area, $this->_hasLocks)) {
            return true;
        }
        try {
            $this->getAreaConfig($area);
            $this->_hasLocks[] = $area;
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
     * @param $area
     * @return Tinebase_Model_AreaLockState
     */
    public function getState($area)
    {
        $expires = $this->_getAuthValidity($area);

        return new Tinebase_Model_AreaLockState([
            'area' => $area,
            'expires' => $expires ? $expires : new Tinebase_DateTime('1970-01-01')
        ]);
    }

    /**
     * @return Tinebase_Record_RecordSet of Tinebase_Model_AreaLockState
     */
    public function getAllStates()
    {
        $states = new Tinebase_Record_RecordSet(Tinebase_Model_AreaLockState::class);
        $areaConfigs = Tinebase_Config::getInstance()->get(Tinebase_Config::AREA_LOCKS);
        if ($areaConfigs->records) {
            foreach ($areaConfigs->records as $areaConfig) {
                $states->addRecord($this->getState($areaConfig->area));
            }
        }
        return $states;
    }

    /**
     * @param string $area
     * @param Tinebase_Model_AreaLockConfig $config
     * @return Tinebase_DateTime
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _saveValidAuth($area, Tinebase_Model_AreaLockConfig $config)
    {
        $alBackend = $this->_getBackend($config);
        $sessionValidity = $alBackend ? $alBackend->saveValidAuth($area) : Tinebase_DateTime::now();

        return $sessionValidity;
    }

    /**
     * @param $config
     * @return null|Tinebase_AreaLock_Interface
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getBackend($config)
    {
        switch (strtolower($config->validity)) {
            case Tinebase_Model_AreaLockConfig::VALIDITY_SESSION:
            case Tinebase_Model_AreaLockConfig::VALIDITY_LIFETIME:
                $backend = new Tinebase_AreaLock_Session($config);
                break;
            case Tinebase_Model_AreaLockConfig::VALIDITY_PRESENCE:
                $backend = new Tinebase_AreaLock_Presence($config);
                break;
            case Tinebase_Model_AreaLockConfig::VALIDITY_DEFINEDBYPROVIDER:
                // @todo add support
                throw new Tinebase_Exception_InvalidArgument('validity ' . $config->validity . ' not supported yet');
                break;
            default:
                // no persistent backend
                $backend = null;
        }

        return $backend;
    }

    /**
     * @param string $area
     * @return bool
     * @throws Exception
     * @throws Zend_Session_Exception
     */
    protected function _hasValidAuth($area)
    {
        $config = $this->getAreaConfig($area);
        $alBackend = $this->_getBackend($config);
        return $alBackend ? $alBackend->hasValidAuth($area) : false;
    }

    /**
     * @param $area
     * @return bool|Tinebase_DateTime
     */
    protected function _getAuthValidity($area)
    {
        $config = $this->getAreaConfig($area);
        $alBackend = $this->_getBackend($config);
        return $alBackend ? $alBackend->getAuthValidity($area) : false;
    }

    /**
     * @param string $area
     */
    public function resetValidAuth($area)
    {
        // invalidate class cache
        $this->_hasLocks = [];

        $config = $this->getAreaConfig($area);
        $alBackend = $this->_getBackend($config);
        if ($alBackend) {
            $alBackend->resetValidAuth($area);
        }
    }
}
