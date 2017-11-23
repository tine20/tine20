<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * server plugin to dispatch Routing requests
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_Routing implements Tinebase_Server_Plugin_Interface
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Plugin_Interface::getServer()
     * @param Zend\Http\Request $request
     * @return Tinebase_Server_Interface|null
     */
    public static function getServer(\Zend\Http\Request $request)
    {
        /**************************** JSON API *****************************/
        if (null !== $request->getQuery(Tinebase_Server_Routing::QUERY_PARAM_DO_ROUTING)) {
            return new Tinebase_Server_Routing();
        }
        return null;
    }
}