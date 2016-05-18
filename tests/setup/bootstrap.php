<?php
require_once __DIR__ . '/../../tine20/bootstrap.php';

// add path the local directory and main tine20 test dir
$autoloader->add('', array(__DIR__ . '/../tine20', __DIR__));

// TODO is this required?
define('PATH_TO_REAL_DIR', dirname(__FILE__). '/../../tine20');
define('PATH_TO_TINE_LIBRARY', dirname(__FILE__). '/../../tine20/library');
define('PATH_TO_TEST_DIR', dirname(__FILE__));

// phpunit wants to handle the errors / report all errors + restore the default error handler
error_reporting(E_ALL | E_STRICT);
restore_error_handler();

// disable sending cookies
Zend_Session::setOptions(array(
    'use_cookies'      => 0,
    'use_only_cookies' => 0
));
