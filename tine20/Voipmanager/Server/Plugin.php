<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 */

/**
 * server plugin to dispatch requests from SNOM phones and Asterisk server
 *
 * @package     Voipmanager
 * @subpackage  Server
 */
class Voipmanager_Server_Plugin implements Tinebase_Server_Plugin_Interface
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Plugin_Interface::getServer()
     */
    public static function getServer(\Zend\Http\Request $request)
    {
        /**************************** SNOM API *****************************/
        if (
            $request->getHeaders('USER-AGENT') &&
            preg_match('/^Mozilla\/4\.0 \(compatible; (snom...)\-SIP (\d+\.\d+\.\d+)/i', $request->getHeaders('USER-AGENT')->getFieldValue())
        ) {
            return new Voipmanager_Server_Snom();
        /**************************** ASTERISK API *****************************/
        } else if (
            $request->getHeaders('USER-AGENT') &&
            $request->getHeaders('USER-AGENT')->getFieldValue() === 'asterisk-libcurl-agent/1.0'
        ) {
            return new Voipmanager_Server_Asterisk();
        }
    }
}
