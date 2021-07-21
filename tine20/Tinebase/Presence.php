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
 * Presence Facility - is updated by \Tinebase_Frontend_Json::reportPresence
 * - subscribers can register presence
 * - save presence in SESSION
 *
 * @package     Tinebase
 * @subpackage  Adapter
 */
class Tinebase_Presence implements Tinebase_Controller_Interface
{
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Presence
     */
    private static $_instance = NULL;

    /**
     * session namespace
     */
    const PRESENCE_SESSION_NAMESPACE = 'presence';

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
     * @return Tinebase_Presence
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Presence();
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
     * @param $key
     * @param integer $increment in seconds
     * @param bool $setLastPresence
     */
    public function setPresence($key, $increment, $setLastPresence = true)
    {
        $presenceKeys = Tinebase_Session::getSessionNamespace()->{self::PRESENCE_SESSION_NAMESPACE};
        if (! isset($presenceKeys) || ! is_array($presenceKeys)) {
            $presenceKeys = [];
        }

        $presenceKeys[$key] = [
            'increment' => $increment,
            'lastPresence' => ($setLastPresence) ? Tinebase_DateTime::now()->toString() : null
        ];

        Tinebase_Session::getSessionNamespace()->{self::PRESENCE_SESSION_NAMESPACE} = $presenceKeys;
    }

    /**
     * @param $key
     */
    public function resetPresence($key)
    {
        $presenceKeys = Tinebase_Session::getSessionNamespace()->{self::PRESENCE_SESSION_NAMESPACE};
        if (isset($presenceKeys)) {
            unset($presenceKeys[$key]);
            Tinebase_Session::getSessionNamespace()->{self::PRESENCE_SESSION_NAMESPACE} = $presenceKeys;
        }
    }

    /**
     * @param $key
     * @return null|Tinebase_DateTime
     */
    public function getLastPresence($key)
    {
        if (! Tinebase_Session::isStarted()) {
            return null;
        }

        $presenceKeys = Tinebase_Session::getSessionNamespace()->{self::PRESENCE_SESSION_NAMESPACE};
        if (isset($presenceKeys[$key]['lastPresence'])) {
            return new Tinebase_DateTime($presenceKeys[$key]['lastPresence']);
        } else {
            return null;
        }
    }

    /**
     * updates presence in all keys
     *
     * @return bool
     */
    public function reportPresence()
    {
        if (!Tinebase_Session::isStarted()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' No session started');
            }
            return false;
        }
        if (!Tinebase_Session::getSessionEnabled()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Session not enabled');
            }
            return false;
        }

        $presenceKeys = Tinebase_Session::getSessionNamespace()->{self::PRESENCE_SESSION_NAMESPACE};
        if (isset($presenceKeys) && is_array($presenceKeys)) {
            $now = Tinebase_DateTime::now()->toString();
            array_walk($presenceKeys, function (&$item, $key, $now) {
                $item['lastPresence'] = $now;
            }, $now);

            try {
                Tinebase_Session::getSessionNamespace()->{self::PRESENCE_SESSION_NAMESPACE} = $presenceKeys;
            } catch (Zend_Session_Exception $zse) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' ' . $zse->getMessage());
                }
                return false;
            }
        }

        return true;
    }
}
