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
            $this->resetValidAuth($area);
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
     * @throws Tinebase_Exception_AreaUnlockFailed
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
    protected function _getAreaConfig($area)
    {
        $areaConfigs = Tinebase_Config::getInstance()->get(Tinebase_Config::AREA_LOCKS);
        $areaConfig = $areaConfigs && $areaConfigs->records
            ? $areaConfigs->records->filter('area', $area)->getFirstRecord()
            : null;
        if (!$areaConfig) {
            throw new Tinebase_Exception_NotFound('config for area ' . $area . ' not found');
        }

//        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
//            . " AreaLockConfig: " . print_r($areaConfig->toArray(), true));

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
        $sessionValidity = $alBackend->saveValidAuth($area);

        return $sessionValidity;
    }

    /**
     * @param $config
     * @return Tinebase_AreaLock_Interface
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
            case Tinebase_Model_AreaLockConfig::VALIDITY_ONCE:
            case Tinebase_Model_AreaLockConfig::VALIDITY_DEFINEDBYPROVIDER:
                // @todo add support
                throw new Tinebase_Exception_InvalidArgument('validity ' . $config->validity . ' not supported yet');
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('validity ' . $config->validity . ' not supported');
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
        $config = $this->_getAreaConfig($area);
        $alBackend = $this->_getBackend($config);
        return $alBackend->hasValidAuth($area);
    }

    /**
     * @param $area
     * @return bool|Tinebase_DateTime
     */
    protected function _getAuthValidity($area)
    {
        $config = $this->_getAreaConfig($area);
        $alBackend = $this->_getBackend($config);
        return $alBackend->getAuthValidity($area);
    }

    /**
     * @param string $area
     */
    public function resetValidAuth($area)
    {
        $config = $this->_getAreaConfig($area);
        $alBackend = $this->_getBackend($config);
        $alBackend->resetValidAuth($area);
    }
}
