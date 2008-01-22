<?php
/**
 * this is the general file any request should be routed trough
 *
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

// check php environment
$requiredIniSettings = array(
    'magic_quotes_sybase'  => 0,
    'magic_quotes_gpc'     => 0,
    'magic_quotes_runtime' => 0,
);

foreach ($requiredIniSettings as $variable => $newValue) {
    $oldValue = ini_get($variable);
    if ($oldValue != $newValue) {
        if (ini_set($variable, $newValue) === false) {
            die("Sorry, your environment is not supported. You need set $variable from $oldValue to $newValue.");
        }
    }
}

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

$egwBase = Egwbase_Controller::getInstance();

$egwBase->handle();
