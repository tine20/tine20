<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Server Abstract with handle function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
abstract class Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    /**
     * fetch basic auth data / credentials from $_SERVER
     * 
     * @return string
     */
    protected function _getBasicAuthData()
    {
        $basicAuthData = NULL;
        
        // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
        if (isset($_SERVER["Authorization"])) {
            $basicAuthData = base64_decode(substr($_SERVER["Authorization"], 6));
        } elseif (isset($_SERVER["HTTP_AUTHORIZATION"])) {
            $basicAuthData = base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"], 6));
        } else {
            // check if (REDIRECT_)*REMOTE_USER is found in SERVER vars
            $remoteUserValues = Tinebase_Helper::searchArrayByRegexpKey('/REMOTE_USER$/', $_SERVER);
            if (! empty($remoteUserValues)) {
                $firstServerValue = array_shift($remoteUserValues);
                $basicAuthData = base64_decode(substr($firstServerValue, 6));
            }
        }
        
        return $basicAuthData;
    }
}
