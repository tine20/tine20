<?php
/**
 * Tine 2.0 - this file starts the setup process
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * magic_quotes_gpc Hack!!!
 * @author Florian Blasel
 * 
 * If you are on a shared host you may not able to change the php setting for magic_quotes_gpc
 * this hack will solve this BUT this takes performance (speed)!
 */
/*
if (ini_get('magic_quotes_gpc')) {
    function __magic_quotes_gpc($requests) {
        foreach($requests AS $k=>&$v) {
            if (is_array($v)) {
                $requests[stripslashes($k)] = __magic_quotes_gpc($v);
            } else {
                $requests[stripslashes($k)] = stripslashes($v);
            }
        }
        return $requests;
    } 
    
    // Change the incomming data if needed
    $_GET = __magic_quotes_gpc( $_GET );
    $_POST = __magic_quotes_gpc( $_POST );
    $_COOKIE = __magic_quotes_gpc( $_COOKIE );
    $_ENV = __magic_quotes_gpc( $_ENV );
    $_REQUEST = __magic_quotes_gpc( $_REQUEST );
} // end magic_quotes_gpc Hack
*/

$paths = array(
    realpath(dirname(__FILE__)),
    realpath(dirname(__FILE__) . '/library'),
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

Setup_Core::dispatchRequest();

exit;
