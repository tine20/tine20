<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

// All server operations are done in UTC
date_default_timezone_set('UTC');

// disable magic_quotes_runtime
ini_set('magic_quotes_runtime', 0);

// display errors we can't handle ourselves
error_reporting(E_COMPILE_ERROR | E_CORE_ERROR | E_ERROR | E_PARSE);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// set default internal encoding
iconv_set_encoding("internal_encoding", "UTF-8");

// set include path
$paths = array(
    realpath(dirname(__FILE__)),
    realpath(dirname(__FILE__) . '/library'),
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $paths));

// intialize autoloader
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);
Tinebase_Autoloader::initialize($autoloader);

// activate our own error handler after autoloader initialization
set_error_handler('Tinebase_Core::errorHandler', E_ALL | E_STRICT);
