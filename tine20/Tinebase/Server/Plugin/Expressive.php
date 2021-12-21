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
 * server plugin to dispatch Expressive requests
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_Expressive implements Tinebase_Server_Plugin_Interface
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Plugin_Interface::getServer()
     * @param Laminas\Http\Request $request
     * @return Tinebase_Server_Interface|null
     */
    public static function getServer(\Laminas\Http\Request $request)
    {
        /**************************** JSON API *****************************/
        if (null !== $request->getQuery(Tinebase_Server_Expressive::QUERY_PARAM_DO_EXPRESSIVE)) {
            return new Tinebase_Server_Expressive();
        }
        return null;
    }
}
