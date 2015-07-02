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

// iconv_set_encoding throws a deprecated exception since 5.6.*
// Zend 1 still uses that, but at least we can effort to fix that.
if (PHP_VERSION_ID > 50600) {
    ini_set('default_charset', 'UTF-8');
} else {
    // set default internal encoding
    if (extension_loaded('iconv')) {
        iconv_set_encoding('internal_encoding', "UTF-8");
    }
}

// intialize composers autoloader
require 'vendor/autoload.php';

// initialize plugins
require 'init_plugins.php';

// activate our own error handler after autoloader initialization
set_error_handler('Tinebase_Core::errorHandler', E_ALL | E_STRICT);
