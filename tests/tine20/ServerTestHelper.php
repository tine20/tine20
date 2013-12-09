<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  test root
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */

/*
 * Set error reporting 
 * 
 * @todo put that in config.inc as well?
 */
error_reporting( E_ALL | E_STRICT );

/*
 * Set include path
 */
define('PATH_TO_REAL_DIR', dirname(__FILE__). '/../../tine20');
define('PATH_TO_TINE_LIBRARY', dirname(__FILE__). '/../../tine20/library');
define('PATH_TO_TEST_DIR', dirname(__FILE__));

$path = array(
    PATH_TO_REAL_DIR,
    PATH_TO_TEST_DIR,
    PATH_TO_TEST_DIR . PATH_SEPARATOR . 'library',
    PATH_TO_TINE_LIBRARY,
    get_include_path(),
);

set_include_path(implode(PATH_SEPARATOR, $path));

require_once 'bootstrap.php';

// phpunit wants to handle the errors / report all errors + restore the default error handler
error_reporting(E_ALL | E_STRICT);
restore_error_handler();