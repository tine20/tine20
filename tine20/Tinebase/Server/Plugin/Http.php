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
 * server plugin to dispatch HTTP requests
 * 
 * should be the last plugins, as it handles all requests
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_Http implements Tinebase_Server_Plugin_Interface
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Plugin_Interface::getServer()
     */
    public static function getServer(\Laminas\Http\Request $request)
    {
        /**************************** OpenID ****************************
         * RewriteRule ^/users/(.*)                      /index.php?frontend=openid&username=$1 [L,QSA]
         */
        if (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/xrds+xml') !== FALSE) {
            $_REQUEST['method'] = 'Tinebase.getXRDS';
        } else if ((isset($_SERVER['REDIRECT_USERINFOPAGE']) && $_SERVER['REDIRECT_USERINFOPAGE'] == 'true') ||
                   (isset($_REQUEST['frontend']) && $_REQUEST['frontend'] == 'openid')) {
            $_REQUEST['method'] = 'Tinebase.userInfoPage';
        }

        if (!isset($_REQUEST['method']) && (isset($_REQUEST['openid_action']) || isset($_REQUEST['openid_assoc_handle'])) ) {
            $_REQUEST['method'] = 'Tinebase.openId';
        }

        return new Tinebase_Server_Http();
    }
}