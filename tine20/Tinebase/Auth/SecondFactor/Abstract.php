<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
abstract class Tinebase_Auth_SecondFactor_Abstract
{
    protected $_options;
    
    public function __construct($options)
    {
        $this->_options = $options;
    }

    /**
     * validate second factor
     *
     * @param $username
     * @param $password
     * @return mixed
     */
    abstract public function validate($username, $password);

    /**
     * @param int $lifetimeMinutes
     * @throws Exception
     * @throws Zend_Session_Exception
     */
    public static function saveValidSecondFactor($lifetimeMinutes = 15)
    {
        Tinebase_Session::getSessionNamespace()->secondFactorValidUntil =
            Tinebase_DateTime::now()->addMinute($lifetimeMinutes)->toString();
    }

    /**
     * @return bool
     * @throws Exception
     * @throws Zend_Session_Exception
     */
    public static function hasValidSecondFactor()
    {

        if (! Tinebase_Session::isStarted()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' No session started to check second factor in session');
            }
            return true;
        }
        if (! Tinebase_Session::getSessionEnabled()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Session not enabled to check second factor in session');
            }
            return true;
        }
        $currentValidUntil = Tinebase_Session::getSessionNamespace()->secondFactorValidUntil;
        if ($currentValidUntil) {
            $validUntil = new Tinebase_DateTime($currentValidUntil);
            return Tinebase_DateTime::now()->isEarlier($validUntil);
        } else {
            return false;
        }
    }
}
