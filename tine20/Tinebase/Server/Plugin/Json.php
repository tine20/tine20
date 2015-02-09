<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 */

/**
 * server plugin to dispatch JSON requests
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_Json implements Tinebase_Server_Plugin_Interface
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Plugin_Interface::getServer()
     */
    public static function getServer(\Zend\Http\Request $request)
    {
        /**************************** JSON API *****************************/
        if (($request->getHeaders('X-TINE20-REQUEST-TYPE') && $request->getHeaders('X-TINE20-REQUEST-TYPE')->getFieldValue() === 'JSON')  ||
            ($request->getHeaders('CONTENT-TYPE') && substr($request->getHeaders('CONTENT-TYPE')->getFieldValue(),0,16) === 'application/json') ||
            ($request->getPost('requestType') === 'JSON') ||
            ($request->getHeaders('ACCESS-CONTROL-REQUEST-METHOD'))
        ) {
            return new Tinebase_Server_Json();
        }
    }
}