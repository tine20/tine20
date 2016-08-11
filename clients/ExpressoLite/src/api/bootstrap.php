<?php
/**
 * Expresso Lite
 * Bootstrap to be included in all PHP files.
 * It performs the following tasks:
 *  1 - Loads the version number file
 *  2 - Register class loader
 *  3 - Checks if configuration file exists. If it does, loads it,
 *      otherwise, it dies with an error
 *  4 - Starts/resume session
 *
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014-2016 Serpro (http://www.serpro.gov.br)
 */

require_once (dirname(__FILE__).'/../version.php');
require_once (dirname(__FILE__) .'/SplClassLoader.php');

$confPhp = dirname(__FILE__).'/../conf.php';
if (!file_exists($confPhp)) {
    header('Content-type:text/html; charset=UTF-8');
    header('HTTP/1.1 500 Internal Server Error');
    die('ConfPhpNotFound');
}

require_once ($confPhp);
define('LITE_BASE_DIR', dirname(__FILE__) . '/..');

$classLoader = new SplClassLoader('ExpressoLite', dirname(__FILE__));
$classLoader->register();

@session_start();

