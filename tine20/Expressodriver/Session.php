<?php
/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Session
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 *
 */

/**
 * Session class for Expressodriver
 *
 * @package     Expressodriver
 * @subpackage  Session
 */
class Expressodriver_Session extends Tinebase_Session_Abstract
{
    /**
     * Addressbook Session Namespace
     */
    const EXPRESSODRIVER_SESSION_NAMESPACE = 'Expressodriver_Session_Namespace';

    /**
     * Gets Expressodriver session namespace
     *
     * @throws Exception
     * @return Ambigous <Zend_Session_Namespace, NULL, mixed>
     */
    public static function getSessionNamespace()
    {
        try {
            return self::_getSessionNamespace(self::EXPRESSODRIVER_SESSION_NAMESPACE);
        } catch(Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Session error: ' . $e->getMessage());
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            throw $e;
        }
    }
}