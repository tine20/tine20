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
 * server plugin to dispatch WebDAV requests
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_WebDAV implements Tinebase_Server_Plugin_Interface
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Plugin_Interface::getServer()
     */
    public static function getServer(\Laminas\Http\Request $request)
    {
        /**************************** WebDAV / CardDAV / CalDAV API **********************************
         * RewriteCond %{REQUEST_METHOD} !^(GET|POST)$
         * RewriteRule ^/$            /index.php?frontend=webdav [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]
         *
         * RewriteRule ^/addressbooks /index.php?frontend=webdav [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]
         * RewriteRule ^/calendars    /index.php?frontend=webdav [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]
         * RewriteRule ^/principals   /index.php?frontend=webdav [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]
         * RewriteRule ^/webdav       /index.php?frontend=webdav [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]
         */
        if ($request->getQuery('frontend') === 'webdav' &&
            !($request->getMethod() == \Laminas\Http\Request::METHOD_OPTIONS && $request->getHeaders()->has('ACCESS-CONTROL-REQUEST-METHOD'))

        ) {
            return new Tinebase_Server_WebDAV();
        }
    }
}
