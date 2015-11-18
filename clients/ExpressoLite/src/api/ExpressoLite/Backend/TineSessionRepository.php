<?php
/**
 * Expresso Lite
 * This class is responsible for storing and receiving the TineSession
 * object associated to the current user session.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend;

use ExpressoLite\TineTunnel\TineSession;

class TineSessionRepository
{

    /**
     * This constant is the default name for the $_SESSION entry that
     * will store the current TineSession
     */
    const TINE_SESSION = 'tine_session';

    /**
     * @var string $backendUrl Contains the address where tine is located.
     * May be overriden for test purposes (see overrideBackendUrlForTests)
     */
    private static $backendUrl = BACKEND_URL;

    /**
     * This method allows us to override Tine's URL defined in
     * conf.php. This is useful for testing. However, this method
     * should NOT be used in production code.
     *
     * @param $backendUrl The new URL.
     *
     */
    public static function overrideBackendUrlForTests($backendUrl)
    {
        self::$backendUrl = $backendUrl;
    }

    /**
     * Dumps the current tineSession and creates a new one
     *
     * @return TineSession The new TineSession
     *
     */
    public static function resetTineSession() {
        $tineSession = self::createNewTineSession();
        self::storeTineSession($tineSession);
        return $tineSession;
    }

    /**
     * Creates and inits a new TineSession.
     *
     * @return a new TineSession
     *
     */
    private static function createNewTineSession()
    {
        $tineSession = new TineSession(self::$backendUrl);

        if (defined('ACTIVATE_TINE_XDEBUG') && ACTIVATE_TINE_XDEBUG === true) {
            //'=== true' only for the sake of clarity
            $tineSession->setActivateTineXDebug(true);
        }

        $tineSession->setLocale('pt_BR');
        return $tineSession;
    }

    /**
     * Returns the current TineSession stored in the user session.
     * If no TineSession exists, it creates a new one.
     *
     * @return the current TineSession
     *
     */
    public static function getTineSession()
    {
        if (! isset($_SESSION[self::TINE_SESSION])) {
            self::resetTineSession();
        }
        return $_SESSION[self::TINE_SESSION];
    }

    /**
     * Stores a TineSession in the user session ($_SESSION)
     *
     * @param a new TineSession
     *
     */
    public static function storeTineSession(TineSession $tineSession)
    {
        $_SESSION[self::TINE_SESSION] = $tineSession;
    }
}

